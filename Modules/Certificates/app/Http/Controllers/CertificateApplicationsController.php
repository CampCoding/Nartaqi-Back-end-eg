<?php

namespace Modules\Certificates\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Modules\Certificates\Models\CertificateApplication;
use Modules\Certificates\Http\Requests\CertificateRequest;

class CertificateApplicationsController extends Controller
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

    public function applyForCertificate(CertificateRequest $request) {

        $student = $request->user();
        $data = $request->validated();

        $existingApplication = CertificateApplication::where('student_id', $student->id)
        ->where('round_id', $data['round_id'])
        ->first();

        if ($existingApplication) {
            return res_data('لقد قمت بإرسال طلب شهادة لهذه الدورة من قبل.', 'failed', 409);
        }

        $data['student_id'] = $student->id;

        $application = CertificateApplication::create($data);

        if($application){
            return res_data($application, 'تم إرسال طلب الشهادة بنجاح.', 201);
        }else{
            return res_data('حدث خطأ أثناء إرسال طلب الشهادة.', 200);
        }

    }


}
