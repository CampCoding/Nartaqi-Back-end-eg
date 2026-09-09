<?php

namespace Modules\Courses\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Storage;
use Modules\Courses\Models\RoundResouceModel;
use Modules\Courses\Models\RoundResourceLinks;
use Modules\Courses\Http\Requests\AddRoundResources;
use Modules\Courses\Http\Requests\GetRoundResources;
use Modules\Courses\Http\Requests\EditRoundResources;
use Modules\Courses\Http\Requests\DeleteRoundResources;
use Modules\Courses\Transformers\RoundResourcesResource;

class RoundResourceController extends Controller
{
    /**
     * Display a listing of the resource.
     */

    public function getRoundResources(GetRoundResources $request)
    {
        $resources = RoundResouceModel::where('round_id', $request['round_id'])->get();

        $links = RoundResourceLinks::where('round_id', $request['round_id'])->first();

        $groupLinks = [];
        if ($links) {
            $groupLinks = array_filter([
                'telegram_link' => $links->telegram_link,
                'whatsapp_link' => $links->whatsapp_link,
            ], function ($value) {
                return !is_null($value);
            });
        }

        $formattedResources = $resources->map(function ($resource) {
            return [
                'id' => $resource->id,
                'round_id' => $resource->round_id,
                'title' => $resource->title,
                'description' => $resource->description,
                'url' => $resource->url,
                'show_date' => $resource->show_date,
                'created_at' => $resource->created_at,
                'updated_at' => $resource->updated_at,
            ];
        });

        return res_data([
            'group_links' => $groupLinks,
            'resource' => $formattedResources,
        ], 'success', 200);
    }
    public function editRoundResource(EditRoundResources $request)
    {
        $validated = $request->validated();
        $resource = RoundResouceModel::find($validated['id']);

        if (!$resource) {
            return res_data('الملف غير موجود', 'failed', 404);
        }

        $updateData = [
            'title' => $validated['title'],
            'description' => $validated['description'],
            'show_date' => $validated['show_date']
        ];

        $baseRequest = $request;
        $file = $baseRequest->file('file');

        if ($file) {
            $filename = time() . '_' . $file->getClientOriginalName();
            $path = $file->storeAs('round_resources/pdf', $filename, 'public');

            if ($path) {
                $fileUrl = asset('storage/' . $path);
                $updateData['url'] = $fileUrl;
            } else {
                return res_data('حدث خطأ أثناء رفع الملف', 'failed', 500);
            }
        }

        $resource->update($updateData);
        return res_data('تم تعديل الملف بنجاح', 'success', 200);
    }
    public function addRoundResource(AddRoundResources $request)
    {
        $validated = $request->validated();

        // Handle PDF file upload (file is already validated as required)
        /** @var \Illuminate\Http\Request $baseRequest */
        $baseRequest = $request;
        $file = $baseRequest->file('file');

        if (!$file) {
            return res_data('لم يتم رفع أي ملف', 'failed', 400);
        }

        $filename = time() . '_' . $file->getClientOriginalName();
        $path = $file->storeAs('round_resources/pdf', $filename, 'public');

        if ($path) {
            $fileUrl = asset('storage/' . $path);

            $resource = RoundResouceModel::create([
                'round_id' => $validated['round_id'],
                'title' => $validated['title'],
                'description' => $validated['description'],
                'url' => $fileUrl,
                'show_date' => $validated['show_date']
            ]);

            return res_data('تم إضافة الملف بنجاح', 'success', 200);
        } else {
            return res_data('حدث خطأ أثناء رفع الملف', 'failed', 500);
        }
    }
    public function deleteRoundResource(DeleteRoundResources $request)
    {
        $resource = RoundResouceModel::find($request['id']);

        $resource->delete();
        return res_data('تم حذف الملف بنجاح', 'success', 200);
    }
}
