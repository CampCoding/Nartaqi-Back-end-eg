<?php

namespace Modules\Courses\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Modules\Courses\Models\BankAccountsModel;

class BankAccountsController extends Controller
{
    public function getBankAccounts(Request $request)
    {
        $accounts = BankAccountsModel::all();
        return res_data($accounts, 'success', 200);
    }

    public function addBankAccount(Request $request)
    {
        $data = $request->validate([
            'bank_name' => 'required|string',
            'account_holder_name' => 'required|string',
            'account_number' => 'required|string',
            'iban' => 'nullable|string',
            'image' => 'nullable'
        ]);

        if (request()->hasFile('image')) {
            $destinationPath = public_path('storage/' . 'bank_accounts');
            if (! file_exists($destinationPath)) {
                mkdir($destinationPath, 0777, true);
            }
            $image = request()->file('image');
            $imageName = time() . '_' . uniqid() . '.' . $image->getClientOriginalExtension();
            $image->move($destinationPath, $imageName);
            $data['image'] = 'bank_accounts/' . $imageName;
        }

        $account = BankAccountsModel::create($data);
        return res_data($account, 'تم الاضافة بنجاح', 201);
    }

    public function editBankAccount(Request $request)
    {
        $data = $request->validate([
            'id' => 'required|exists:bank_accounts,id',
            'bank_name' => 'nullable|string',
            'account_holder_name' => 'nullable|string',
            'account_number' => 'nullable|string',
            'iban' => 'nullable|string',
            'image' => 'nullable'
        ]);

        $account = BankAccountsModel::find($data['id']);

        if (!$account) {
            return res_data('هذا الحساب البنكي غير موجود', 'failed', 404);
        }

        if (request()->hasFile('image')) {
            $destinationPath = public_path('storage/' . 'bank_accounts');
            if (! file_exists($destinationPath)) {
                mkdir($destinationPath, 0777, true);
            }
            $image = request()->file('image');
            $imageName = time() . '_' . uniqid() . '.' . $image->getClientOriginalExtension();
            $image->move($destinationPath, $imageName);
            $data['image'] = 'bank_accounts/' . $imageName;
        } else {
            unset($data['image']);
        }

        $account->update($data);
        return res_data($account, 'تم التعديل بنجاح', 200);
    }

    public function deleteBankAccount(Request $request)
    {
        $data = $request->validate([
            'id' => 'required|exists:bank_accounts,id',
        ]);

        $account = BankAccountsModel::find($data['id']);
        if (!$account) {
            return res_data('هذا الحساب البنكي غير موجود', 'failed', 404);
        }

        $account->delete();
        return res_data('تم الحذف بنجاح', 'success', 200);
    }
}
