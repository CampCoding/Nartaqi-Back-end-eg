<?php

namespace Modules\Home\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use Modules\Home\Models\HomeBanner;
use Modules\Home\Models\HomeVideo;
use Modules\Home\Http\Requests\AddBannerRequest;
use Modules\Home\Http\Requests\DeleteBannerRequest;
use Modules\Home\Http\Requests\UpdateVideoRequest;

class HomeController extends Controller
{
    /**
     * Get all active banners for users.
     */
    public function getBanners()
    {
        $banners = HomeBanner::all();
        return res_data($banners, 'تم استرجاع البانرات بنجاح', 200);
    }

    /**
     * Get the single home video.
     */
    public function getVideo()
    {
        $video = HomeVideo::first();
        return res_data($video, 'تم استرجاع الفيديو بنجاح', 200);
    }

    /**
     * Get all home page content (banners and video).
     */
    public function getHomeData()
    {
        $banners = HomeBanner::all();
        $video = HomeVideo::first();
        
        return res_data([
            'banners' => $banners,
            'video' => $video
        ], 'تم استرجاع بيانات الصفحة الرئيسية بنجاح', 200);
    }

    /**
     * Get all banners for admin.
     */
    public function getAdminBanners()
    {
        $banners = HomeBanner::all();
        return res_data($banners, 'تم استرجاع البانرات بنجاح', 200);
    }

    /**
     * Add/Upload a new banner.
     */
    public function addBanner(AddBannerRequest $request)
    {
        $data = $request->validated();

        if ($request->hasFile('image')) {
            $destinationPath = public_path('storage/banners');
            if (!File::isDirectory($destinationPath)) {
                File::makeDirectory($destinationPath, 0777, true, true);
            }
            $image = $request->file('image');
            $imageName = time() . '_' . uniqid() . '.' . $image->getClientOriginalExtension();
            $image->move($destinationPath, $imageName);
            $data['image'] = 'banners/' . $imageName;
        }

        $banner = HomeBanner::create($data);
        return res_data($banner, 'تم إضافة البانر بنجاح', 200);
    }

    /**
     * Delete a banner.
     */
    public function deleteBanner(DeleteBannerRequest $request)
    {
        $data = $request->validated();
        $banner = HomeBanner::find($data['id']);

        if (!$banner) {
            return res_data('هذا البانر غير موجود', 'failed', 404);
        }

        // Delete the image file from storage if it exists
        if ($banner->image) {
            $filePath = public_path('storage/' . $banner->image);
            if (File::exists($filePath)) {
                File::delete($filePath);
            }
        }

        $banner->delete();
        return res_data('تم حذف البانر بنجاح', 'success', 200);
    }

    /**
     * Get the video for admin.
     */
    public function getAdminVideo()
    {
        $video = HomeVideo::first();
        return res_data($video, 'تم استرجاع الفيديو بنجاح', 200);
    }

    /**
     * Add or Update the single home video.
     */
    public function updateVideo(UpdateVideoRequest $request)
    {
        $data = $request->validated();

        // Since there is only ever one video, we updateOrCreate on ID 1
        $video = HomeVideo::updateOrCreate(
            ['id' => 1],
            ['video_url' => $data['video_url']]
        );

        return res_data($video, 'تم حفظ رابط الفيديو بنجاح', 200);
    }
}
