<?php

namespace Modules\Certificates\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Modules\Certificates\Models\UserCertificate;
use Modules\Certificates\Models\CertificateApplication;
use Modules\Certificates\Http\Requests\AdminCertificatesRequest;

class AdminCertificatesController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        return view('certificates::index');
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return view('certificates::create');
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
        return view('certificates::show');
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit($id)
    {
        return view('certificates::edit');
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, $id) {}

    /**
     * Remove the specified resource from storage.
     */
    public function destroy($id) {}

    public function getApplications(Request $request)
    {
        $student_id = $request->student_id;
        if(!$student_id) {
            return res_data('معرف الطالب مطلوب.', 'failed', 400);
        }

        $exist = UserCertificate::where('student_id', $student_id)->exists();
        if(!$exist) {
            return res_data('لا يوجد شهادات صادرة لهذا الطالب.', 'failed', 404);
        }

        $applications = CertificateApplication::with(['student', 'round'])->where('student_id',$student_id)->get();
        return res_data($applications, 'تم جلب الطلبات بنجاح', 200);
    }

    public function generateCertificate(AdminCertificatesRequest $request)
    {
        $data = $request->validated();

        $existingCertificate = UserCertificate::where('student_id', $data['student_id'])
        ->where('round_id', $data['round_id'])
        ->first();

        if ($existingCertificate) {
            return res_data('لقد قمت بإستلام شهادتك لهذه الدورة من قبل.', 'failed', 409);
        }

        $certificate = UserCertificate::create([
            'student_id' => $data['student_id'],
            'round_id' => $data['round_id'],
            'application_id' => $data['application_id'],
            'certification_name' => $data['certification_name'],
            'pdf_path' => $data['pdf_path'],
        ]);

        if(!$certificate) {
            return res_data('حدث خطأ أثناء إنشاء الشهادة.', 'failed', 500);
        }

        $status = CertificateApplication::where('id', $data['application_id'])->update(['status' => 'received']);
        if(!$status) {
            return res_data('حدث خطأ أثناء تحديث حالة الطلب.', 'failed', 500);
        }

        return res_data($certificate, 'تم إنشاء الشهادة بنجاح', 201);

    }

    public function uploadCertificatePdf(Request $request)
    {
        $request->validate([
            'pdf' => 'required|mimes:pdf',
        ]);

        if ($request->hasFile('pdf')) {
            $file = $request->file('pdf');
            $filename = time() . '_' . $file->getClientOriginalName();
           $path = $file->storeAs('certificates/pdf', $filename, 'public');

            if ($path) {
                return res_data(['pdf_path' => asset('storage/' . $path)], 'تم رفع الملف بنجاح', 200);
            } else {
                return res_data('حدث خطأ أثناء رفع الملف.', 'failed', 500);
            }
        } else {
            return res_data('لم يتم رفع أي ملف.', 'failed', 400);
        }
    }

    public function editCertificate(AdminCertificatesRequest $request, $id)
    {
        $data = $request->validated();

        $certificate = UserCertificate::find($id);
        if (!$certificate) {
            return res_data('الشهادة غير موجودة.', 'failed', 404);
        }

        $certificate->update($data);

        return res_data($certificate, 'تم تحديث الشهادة بنجاح', 200);
    }

    public function deleteCertificate($id)
    {
        $certificate = UserCertificate::find($id);
        if (!$certificate) {
            return res_data('الشهادة غير موجودة.', 'failed', 404);
        }

        $certificate->delete();

        return res_data('تم حذف الشهادة بنجاح', 200);
    }

}
