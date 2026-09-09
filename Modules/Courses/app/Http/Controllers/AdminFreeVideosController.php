<?php

namespace Modules\Courses\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Modules\Courses\Http\Requests\EditFreeVideosRequest;
use Modules\Courses\Models\FreeVideosModel;
use Modules\Courses\Http\Requests\StoreFreeVideosRequest;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

class AdminFreeVideosController extends Controller
{

    public function get_all_free_videos(Request $request)
    {
        $data = $request->validate([
            'category_part_free_id' => 'required',
        ]);
        $perPage = (int) $request->get('per_page', 10);
        $freeVideos = FreeVideosModel::where('category_part_free_id', $data['category_part_free_id'])
            ->orderBy('sort_number', 'asc')
            ->paginate($perPage);
        return res_data($freeVideos, 'success', 200);
    }

    public function makeSortVideos(Request $request)
    {
        $data = $request->validate([
            'items' => 'required|array',
            'items.*.id' => 'required|exists:free_videos,id',
            'items.*.sort_number' => 'required|integer|min:1',
        ]);

        $items = $data['items'];

        foreach ($items as $item) {
            $video = FreeVideosModel::find($item['id']);
            if ($video) {
                $video->sort_number = $item['sort_number'];
                $video->save();
            }
        }

        return res_data('تم تحديث الترتيب بنجاح', 'success', 200);
    }

    public function delete_free_video(Request $request)
    {
        $data = $request->validate([
            'id' => 'required|exists:free_videos,id',
        ]);
        $freeVideo = FreeVideosModel::find($data['id']);
        $freeVideo->delete();
        return res_data('success', 'success', 200);
    }

    public function edit_free_video(EditFreeVideosRequest $request)
    {
        $freeVideo = FreeVideosModel::find($request->id);
        if (!$freeVideo) {
            return res_data('الفيديو غير موجود', 'error', 404);
        }

        $data = $request->validated();

        $uploadedImage = $request->hasFile('image') ? $request->file('image') : null;

        if ($uploadedImage) {
            // Delete old image if exists
            if ($freeVideo->image && file_exists(public_path('storage/' . $freeVideo->image))) {
                unlink(public_path('storage/' . $freeVideo->image));
            }

            $destinationPath = public_path('storage/free_videos');
            if (!file_exists($destinationPath)) {
                mkdir($destinationPath, 0777, true);
            }
            $imageName = time() . '_' . uniqid() . '.' . $uploadedImage->getClientOriginalExtension();
            $uploadedImage->move($destinationPath, $imageName);
            $data['image'] = 'free_videos/' . $imageName;
        } else {
            // No new uploaded image → try to fetch from new/updated Vimeo URL
            $vimeoUrl = $data['vimeo_link'] ?? null;

            if ($vimeoUrl && filter_var($vimeoUrl, FILTER_VALIDATE_URL)) {
                $videoData = $this->getVimeoThumbnailData($vimeoUrl);

                if ($videoData && !empty($videoData['started_image'])) {
                    // Delete old image if exists (to replace with new thumbnail)
                    if ($freeVideo->image && file_exists(public_path('storage/' . $freeVideo->image))) {
                        unlink(public_path('storage/' . $freeVideo->image));
                    }

                    $thumbnailUrl = $videoData['started_image'];

                    // Attempt higher resolution (common working hack)
                    $thumbnailUrl = preg_replace('/[_\-](\d+x\d+)(?=\.jpg$|$)/i', '_1280x720', $thumbnailUrl);
                    // If you want even larger (sometimes works): '_1920x1080'

                    $destinationPath = public_path('storage/free_videos');
                    if (!file_exists($destinationPath)) {
                        mkdir($destinationPath, 0777, true);
                    }

                    $imageName = 'vimeo_edit_' . time() . '_' . uniqid() . '.jpg';
                    $fullPath = $destinationPath . '/' . $imageName;

                    $context = stream_context_create([
                        'http' => [
                            'timeout'       => 10,
                            'ignore_errors' => true,
                        ]
                    ]);

                    $imageContent = @file_get_contents($thumbnailUrl, false, $context);

                    if ($imageContent !== false && strlen($imageContent) > 2000) {
                        file_put_contents($fullPath, $imageContent);
                        $data['image'] = 'free_videos/' . $imageName;
                    }
                }
            }
            // If no new thumbnail → keep the old $freeVideo->image value
        }

        $freeVideo->update($data);
        return res_data($freeVideo, 'success', 200);
    }

    public function add_free_video(StoreFreeVideosRequest $request)
    {
        $data = $request->validated();

        if (!isset($data['sort_number']) || $data['sort_number'] === null) {
            $maxSortNumber = FreeVideosModel::where('category_part_free_id', $data['category_part_free_id'])
                ->max('sort_number');
            $data['sort_number'] = $maxSortNumber ? $maxSortNumber + 1 : 1;
        }

        $uploadedImage = $request->hasFile('image') ? $request->file('image') : null;

        if ($uploadedImage) {
            $destinationPath = public_path('storage/free_videos');
            if (!file_exists($destinationPath)) {
                mkdir($destinationPath, 0777, true);
            }

            $imageName = time() . '_' . uniqid() . '.' . $uploadedImage->getClientOriginalExtension();
            $uploadedImage->move($destinationPath, $imageName);

            $data['image'] = 'free_videos/' . $imageName;
        } else {
            $vimeoUrl = $data['vimeo_link'] ?? $request->input('vimeo_link') ?? null;

            if ($vimeoUrl && filter_var($vimeoUrl, FILTER_VALIDATE_URL)) {
                $videoData = $this->getVimeoThumbnailData($vimeoUrl);

                if ($videoData && !empty($videoData['started_image'])) {
                    $thumbnailUrl = $videoData['started_image'];

                    $thumbnailUrl = preg_replace('/[_\-](\d+x\d+)(?=\.jpg$|$)/i', '_1280x720', $thumbnailUrl);

                    $destinationPath = public_path('storage/free_videos');
                    if (!file_exists($destinationPath)) {
                        mkdir($destinationPath, 0777, true);
                    }

                    $imageName = 'vimeo_thumb_' . time() . '_' . uniqid() . '.jpg';
                    $fullPath = $destinationPath . '/' . $imageName;

                    $context = stream_context_create([
                        'http' => [
                            'timeout'       => 10,
                            'ignore_errors' => true,
                        ]
                    ]);

                    $imageContent = @file_get_contents($thumbnailUrl, false, $context);

                    if ($imageContent !== false && strlen($imageContent) > 2000) {
                        file_put_contents($fullPath, $imageContent);
                        $data['image'] = 'free_videos/' . $imageName;
                    }
                }
            }
        }

        $freeVideo = FreeVideosModel::create($data);

        return res_data($freeVideo, 'success', 200);
    }


    protected function getVimeoThumbnailData(string $url): ?array
    {
        $oembedUrl = 'https://vimeo.com/api/oembed.json';

        $response = Http::timeout(10)->get($oembedUrl, [
            'url'    => $url,
            'width'  => 1280,
            'height' => 720,
        ]);

        if ($response->failed() || empty($response->json('thumbnail_url'))) {
            return null;
        }

        return [
            'started_image' => $response->json('thumbnail_url'),
        ];
    }
}
