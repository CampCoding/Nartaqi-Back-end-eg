<?php

namespace Modules\Courses\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Modules\Courses\Models\BankAccountsModel;
use Modules\Courses\Models\PaymentConfirmationsModel;

class PaymentConfirmationsController extends Controller
{
    public function getAllBankAccounts(Request $request)
    {
        $query = BankAccountsModel::query();


        $bank_accounts = $query->get();
        return res_data($bank_accounts, 'success', 200);
    }

    public function storePaymentConfirmation(Request $request)
    {
        $data = $request->validate([
            'round_id' => 'required|integer',
            'phone' => 'required|string',
            'amount' => 'required|numeric',
            'sender_name' => 'required|string',
            'receiver_bank' => 'required|string',
            'student_id' => 'nullable|integer',
            'image' => 'nullable'
        ]);

        if (request()->hasFile('image')) {
            $destinationPath = public_path('storage/' . 'payment_confirmations');
            if (! file_exists($destinationPath)) {
                mkdir($destinationPath, 0777, true);
            }
            $image = request()->file('image');
            $imageName = time() . '_' . uniqid() . '.' . $image->getClientOriginalExtension();
            $image->move($destinationPath, $imageName);
            $data['image'] = 'payment_confirmations/' . $imageName;
        }

        if (!isset($data['status'])) {
            $data['status'] = 'pending';
        }

        // // Set student_id to authenticated user if not provided
        // if (!isset($data['student_id']) && auth()->check()) {
        //     $data['student_id'] = auth()->id();
        // }

        $paymentConfirmation = PaymentConfirmationsModel::create($data);

        return res_data($paymentConfirmation, 'تم إرسال تأكيد الدفع بنجاح', 201);
    }
}
