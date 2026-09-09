<?php

namespace Modules\Courses\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Modules\Courses\Models\PaymentConfirmationsModel;

// Include the SMS file from Authentication module
require_once base_path('Modules/Authentication/smsfile.php');

class PaymentConfirmationsAdminController extends Controller
{

    public function studentsWithPayments(Request $request)
    {
        // Load all confirmations with only the round and student's id and name
        $confirmations = PaymentConfirmationsModel::with([
            'round' => function ($query) {
                $query->select('id', 'name');
            },
            'student' => function ($query) {
                $query->select('id', 'name');
            }
        ])->get();

        return res_data($confirmations, 'success', 200);
    }

    /**
     * Change the status of a payment confirmation.
     */
    public function changeStatus(Request $request)
    {
        $request->validate([
            'id' => 'required|exists:payment_confirmations,id',
            'status' => 'required|in:approved,rejected',
        ]);

        $confirmation = PaymentConfirmationsModel::with(['round', 'student'])->find($request->id);
        $confirmation->status = $request->status;
        $confirmation->save();

        if ($request->status === 'approved' || $request->status === 'rejected') {
            $statusText = $request->status === 'approved' ? 'تم قبول' : 'تم رفض';
            $roundName = $confirmation->round ? $confirmation->round->name : 'الدورة التدريبية';

            // Use student name from profile if available, otherwise use sender_name from confirmation
            $studentName = ($confirmation->student && $confirmation->student->name) 
                           ? $confirmation->student->name 
                           : $confirmation->sender_name;

            $smsMessage = "عزيزي الطالب / الطالبة: " . $studentName . "\n" .
                "$statusText معاملتك المحولة من قبل منصة نرتقي\n" .
                "علماً بأن المدفوعات كانت لدورة: $roundName";

            // Use the student's registered phone number if available, otherwise the one from confirmation
            $targetPhone = ($confirmation->student && $confirmation->student->phone) 
                           ? $confirmation->student->phone 
                           : $confirmation->phone;

            // Ensure the phone number format is correct (starting with 20 for Egypt if starts with 0)
            if (strpos($targetPhone, '0') === 0) {
                $targetPhone = '2' . $targetPhone;
            }

            // Send SMS
            sendWawpMessage($targetPhone, $smsMessage);
        }

        return res_data('تم تحديث الحالة بنجاح', 200);
    }
}
