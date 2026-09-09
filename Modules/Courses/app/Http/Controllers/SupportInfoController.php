<?php

namespace Modules\Courses\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Modules\Courses\Models\SupportInfoModel;
use Modules\Courses\Models\SocialAccountsModel;
use Modules\Courses\Models\StudentinquiryModel;
use Modules\Courses\Http\Requests\UpdateSocialAccountRequest;
use Modules\Courses\Http\Requests\AddSocialAccountRequest;

class SupportInfoController extends Controller
{
    public function getSupportInfo(Request $request)
    {
        $data = SupportInfoModel::first();
        return res_data($data, 'success', 200);
    }

    public function addSupportInfo(Request $request)
    {
        $data = $request->validate([
            'whatsapp_number' => 'required|string',
            'whatsapp_message' => 'nullable|string',
            'show_whatsapp' => 'required|in:0,1',
            'phone_number' => 'nullable|string',
            'support_email' => 'nullable|email',
            'show_email' => 'required|in:0,1',
            'working_hours_text' => 'nullable|string',
            'response_time_text' => 'nullable|string',
            'active' => 'required|in:0,1',
        ]);

        $supportInfo = SupportInfoModel::create($data);
        return res_data($supportInfo, 'success', 200);
    }

    public function updateSupportInfo(Request $request)
    {
        $data = $request->validate([
            'id' => 'required|exists:support_info,id',
            'whatsapp_number' => 'required|string',
            'whatsapp_message' => 'nullable|string',
            'show_whatsapp' => 'required|in:0,1',
            'phone_number' => 'nullable|string',
            'support_email' => 'nullable|email',
            'show_email' => 'required|in:0,1',
            'working_hours_text' => 'nullable|string',
            'response_time_text' => 'nullable|string',
            'active' => 'required|in:0,1',
            'location' => 'nullable|string',
        ]);

        $supportInfo = SupportInfoModel::find($data['id']);
        $supportInfo->update($data);
        return res_data($supportInfo, 'success', 200);
    }

    /**
     * Get all social accounts
     */
    public function getSocialAccounts(Request $request)
    {
        $data = SocialAccountsModel::all();
        return res_data($data, 'success', 200);
    }

    /**
     * Add a new social account
     */
    public function addSocialAccount(AddSocialAccountRequest $request)
    {
        $data = $request->validated();

        // Handle image upload
        if (request()->hasFile('image')) {
            $destinationPath = public_path('storage/social_accounts');
            if (!file_exists($destinationPath)) {
                mkdir($destinationPath, 0777, true);
            }
            $image = request()->file('image');
            $imageName = time() . '_' . uniqid() . '.' . $image->getClientOriginalExtension();
            $image->move($destinationPath, $imageName);
            $data['image'] = 'social_accounts/' . $imageName;
        }

        $socialAccount = SocialAccountsModel::create($data);
        return res_data($socialAccount, 'تم إضافة الحساب بنجاح', 200);
    }

    /**
     * Update a social account
     */
    public function updateSocialAccount(UpdateSocialAccountRequest $request)
    {
        $data = $request->validated();

        $socialAccount = SocialAccountsModel::find($data['id']);

        if (!$socialAccount) {
            return res_data('الحساب غير موجود', 'failed', 404);
        }

        // Handle image upload
        if (request()->hasFile('image')) {
            $destinationPath = public_path('storage/social_accounts');
            if (!file_exists($destinationPath)) {
                mkdir($destinationPath, 0777, true);
            }
            $image = request()->file('image');
            $imageName = time() . '_' . uniqid() . '.' . $image->getClientOriginalExtension();
            $image->move($destinationPath, $imageName);
            $data['image'] = 'social_accounts/' . $imageName;
        }

        $socialAccount->update($data);
        return res_data($socialAccount, 'تم تحديث الحساب بنجاح', 200);
    }

    /**
     * Delete a social account
     */
    public function deleteSocialAccount(Request $request)
    {
        $data = $request->validate([
            'id' => 'required|exists:social_accounts_platform,id',
        ]);

        $socialAccount = SocialAccountsModel::find($data['id']);

        if (!$socialAccount) {
            return res_data('الحساب غير موجود', 'failed', 404);
        }

        $socialAccount->delete();
        return res_data('تم حذف الحساب بنجاح', 'success', 200);
    }

    /**
     * Create a student inquiry
     */
    public function makeInquiry(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string',
            'message_type' => 'required|string',
            'content' => 'required|string',
            'phone' => 'required|string'
        ]);

        $inquiry = StudentinquiryModel::create($data);
        return res_data($inquiry, 'تم إرسال الاستفسار بنجاح', 200);
    }
}
