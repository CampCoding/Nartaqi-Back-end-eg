<?php

namespace Modules\Certificates\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Modules\Certificates\Models\UserCertificate;

class UserCertificatesController extends Controller
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

    public function userCertificates(Request $request)
    {
        $student = $request->user();
        if (!$student) {
            return res_data('Unauthorized', 'error', 401);
        }

        $certificates = UserCertificate::where('student_id', $student->id)->get();

        if($certificates ->isNotEmpty()){
            return res_data($certificates, 'success', 201);
        }else{
            return res_data( 'لا توجد شهادات لهذا الطالب', 200);
        }

    }
}