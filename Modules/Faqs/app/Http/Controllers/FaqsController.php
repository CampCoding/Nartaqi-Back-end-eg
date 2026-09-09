<?php

namespace Modules\Faqs\Http\Controllers;

use Illuminate\Http\Request;
use Modules\Faqs\Models\Faq;
use App\Http\Controllers\Controller;
use Modules\Faqs\Http\Requests\FaqRequest;

class FaqsController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        return view('faqs::index');
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return view('faqs::create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request) {}

    /**
     * Show the specified resource.
     */
    public function show($id)
    {
        return view('faqs::show');
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit($id)
    {
        return view('faqs::edit');
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, $id) {}

    /**
     * Remove the specified resource from storage.
     */
    public function destroy($id) {}


    public function getFaq()
    {
        $faq = Faq::where('hidden', '0')->where('type', 'general_faqs')->get();
        return res_data($faq, 'success', 200);
    }

    public function getAdminFaq()
    {
        $faq = Faq::where('type', 'general_faqs')->get();
        return res_data($faq, 'success', 200);
    }

    public function addFaq(FaqRequest $request)
    {
        $data = $request->validated();
        $faq = Faq::create($data);
        return res_data($faq, 'تم الاضافة بنجاح', 200);
    }

    public function updateFaq(FaqRequest $request)
    {
        $data = $request->validated();

        $validatedId = $request->validate([
            'id' => 'required|integer',
        ]);

        $faq = Faq::find($validatedId['id']);
        if (!$faq) {
            return res_data('هذا السؤال غير موجود', 'failed', 404);
        }
        $faq->update($data);
        return res_data($faq, 'تم التعديل بنجاح', 200);
    }

    public function deleteFaq(Request $request)
    {
        $faq = Faq::find($request->id);
        if (!$faq) {
            return res_data('السؤال غير موجود', 404);
        }
        $faq->delete();
        return res_data('تم الحذف بنجاح', 200);
    }

    public function showHideFaq(Request $request)
    {
        $data = $request->validate([
            'id' => 'required|integer',
            'hidden' => 'required|in:0,1',
        ]);

        $faq = Faq::find($data['id']);
        if (!$faq) {
            return res_data('هذا السؤال غير موجود', 'failed', 404);
        }
        $faq->hidden = $data['hidden'];
        $faq->save();

        $message = $data['hidden'] == 0 ? 'تم إظهار االسؤال بنجاح' : 'تم إخفاء السؤال بنجاح';
        return res_data($message, 'success', 200);
    }



    ////////////////////////////////////////////////////////
    public function addcomplaints(FaqRequest $request)
    {
        $data = $request->validated();
        $faq = Faq::create($data);
        return res_data($faq, 'تم الاضافة بنجاح', 200);
    }


    public function getAdmincomplaints()
    {
        $faq = Faq::where('type', 'complaints')->get();
        return res_data($faq, 'success', 200);
    }


    public function getUserComplaints()
    {
        $faq = Faq::where('type', 'complaints')->where('hidden', '0')->get();
        return res_data($faq, 'success', 200);
    }





    public function updatecomplaints(FaqRequest $request)
    {
        $data = $request->validated();

        $validatedId = $request->validate([
            'id' => 'required|integer',
        ]);

        $faq = Faq::find($validatedId['id']);
        if (!$faq) {
            return res_data('هذا السؤال غير موجود', 'failed', 404);
        }
        $faq->update($data);
        return res_data($faq, 'تم التعديل بنجاح', 200);
    }

    public function showHideComplaints(Request $request)
    {
        $data = $request->validate([
            'id' => 'required|integer',
            'hidden' => 'required|in:0,1',
        ]);

        $complaint = Faq::find($data['id']);
        if (!$complaint) {
            return res_data('هذا الشكوى غير موجود', 'failed', 404);
        }
        $complaint->hidden = $data['hidden'];
        $complaint->save();

        $message = $data['hidden'] == '0' ? 'تم إظهار الشكوى بنجاح' : 'تم إخفاء الشكوى بنجاح';
        return res_data($message, 'success', 200);
    }
}
