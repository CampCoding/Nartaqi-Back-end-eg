<?php

namespace Modules\Store\Http\Controllers;

use Illuminate\Http\Request;
use Modules\Store\Models\Store;
use Modules\Store\Models\StoreBook;
use Modules\Store\Models\StoreImage;
use Modules\Store\Models\StudentBook;
use Modules\Courses\Models\Rounds;
use App\Http\Controllers\Controller;
use Modules\Store\Services\UploadService;
use Modules\Store\Http\Requests\StoreRequest;
use Modules\Store\Http\Requests\UpdateStoreRequest;
use Illuminate\Support\Facades\DB;

class StoreController extends Controller
{
    /**
     * Get all store items (Public API)
     */
    public function getStoreItems(Request $request)
    {
        $priceFilter = $request->input('priceFilter');
        $categoryFilter = $request->input('categoryFilter');
        $filter = $request->input('filter');

        // جلب العناصر غير المخفية
        $query = Store::with(['images', 'books'])->where('hidden', '!=', '1');

        if ($priceFilter) {
            $query->where('price', '<=', $priceFilter);
        }

        if ($categoryFilter) {
            $query->where('category', $categoryFilter);
        }

        // خيارات الترتيب أو البحث بالاسم
        $filterOptions = [
            'newest'        => fn($q) => $q->orderByDesc('created_at'),
            'oldest'        => fn($q) => $q->orderBy('created_at'),
            'highest_price' => fn($q) => $q->orderByDesc('price'),
            'lowest_price'  => fn($q) => $q->orderBy('price'),
        ];

        if ($filter && isset($filterOptions[$filter])) {
            $filterOptions[$filter]($query);
        } elseif ($filter) {
            $query->where('title', 'like', '%' . $filter . '%');
        }

        $stores = $query->paginate($request->get('per_page') ?? $request->get('perPage') ?? 10);

        // تحديد هوية الطالب (إما من التوكن أو من student_id في الرابط)
        $studentId = $request->query('student_id') ?? (auth()->user() ? auth()->user()->id : null);

        // جلب معرفات العناصر المشتراة (نستخدم pluck('store_id') لأننا نخزن الـ store_id مع كل كتاب)
        $purchasedBookIds = $studentId ? StudentBook::where('student_id', $studentId)->pluck('store_id')->unique()->toArray() : [];

        $stores->getCollection()->transform(function ($item) use ($purchasedBookIds) {
            $isPurchased = in_array($item->id, $purchasedBookIds);
            return [
                'id'          => $item->id,
                'name'        => $item->title,
                'description' => $item->description,
                'price'       => $item->price,
                'category'    => $item->category,
                'image'       => $item->image,
                'gallery'     => $item->images->pluck('image'),
                'is_purchased' => $isPurchased,
                'files'       => $isPurchased ? $item->books->pluck('book_url') : [],
                'created_at'  => $item->created_at
            ];
        });

        $hasCoupon = \Modules\Courses\Models\Coupon::where('is_active', '1')
            ->where('target', 'store')
            ->where(function ($q) {
                $q->whereNull('expiry_date')->orWhere('expiry_date', '>=', now());
            })
            ->where(function ($q) {
                $q->where('usage_limit', 0)->orWhereColumn('used_count', '<', 'usage_limit');
            })
            ->exists() ? 1 : 0;

        return res_data([
            'items'           => $stores,
            'highest_price'   => Store::max('price'),
            'has_coupon'      => $hasCoupon,
        ], 'success', 200);
    }

    /**
     * Check if there are any active store coupons
     */
    public function hasStoreCoupon()
    {
        $hasCoupon = \Modules\Courses\Models\Coupon::where('is_active', '1')
            ->where('target', 'store')
            ->where(function ($q) {
                $q->whereNull('expiry_date')->orWhere('expiry_date', '>=', now());
            })
            ->where(function ($q) {
                $q->where('usage_limit', 0)->orWhereColumn('used_count', '<', 'usage_limit');
            })
            ->exists() ? 1 : 0;

        return res_data(['has_coupon' => $hasCoupon], 'success', 200);
    }

    /**
     * Get all store items (Admin API)
     */
    public function getStoreItemsAdmin(Request $request)
    {
        $items = Store::with(['images', 'books'])->paginate($request->get('per_page') ?? $request->get('perPage') ?? 1000000000000);
        return res_data($items, 'success', 200);
    }

    public function addStoreItem(StoreRequest $request)
    {
        $data = $request->validated();
        return DB::transaction(function () use ($data, $request) {
            $item = Store::create([
                'title'       => $data['title'],
                'description' => $data['description'],
                'price'       => $data['price'],
                'category'    => $data['category'],
                'image'       => $data['image'],
                'hidden'      => $request->input('hidden', 0),
            ]);

            if ($request->has('images')) {
                foreach ($request->input('images') as $url) {
                    $item->images()->create(['image' => $url]);
                }
            }

            if ($request->has('book_urls')) {
                foreach ($request->input('book_urls') as $url) {
                    $item->books()->create(['book_url' => $url]);
                }
            }

            return res_data($item->load(['images', 'books']), 'Item added successfully', 200);
        });
    }

    public function updateStoreItem(UpdateStoreRequest $request)
    {
        $data = $request->validated();
        $item = Store::findOrFail($data['id']);

        return DB::transaction(function () use ($item, $data, $request) {
            $item->update($data);

            if ($request->has('images')) {
                $item->images()->delete();
                foreach ($request->input('images') as $url) {
                    $item->images()->create(['image' => $url]);
                }
            }

            if ($request->has('book_urls')) {
                $item->books()->delete();
                foreach ($request->input('book_urls') as $url) {
                    $item->books()->create(['book_url' => $url]);
                }
            }

            return res_data($item->load(['images', 'books']), 'Item updated successfully', 200);
        });
    }

    public function deleteStoreItem(Request $request)
    {
        $id = $request->input('id');
        $item = Store::findOrFail($id);
        $item->delete();
        return res_data(null, 'Item deleted successfully', 200);
    }

    public function uploadFile(Request $request)
    {
        $request->validate([
            'file' => 'required|file|max:20480', // 20MB
            'type' => 'required|in:image,book'
        ]);

        $path = $request->type === 'image' ? 'store/images' : 'store/books';
        $url = \Modules\Store\Services\UploadService::upload($request->file('file'), $path);

        return res_data(['url' => $url], 'success', 200);
    }
}
