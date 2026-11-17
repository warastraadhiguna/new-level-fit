<?php

namespace App\Http\Controllers\Member;

use App\Http\Controllers\Controller;
use App\Models\Member\MemberRegistration;
use App\Models\Member\MemberRegistrationPayment;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class MemberRegistrationPaymentController extends Controller
{
    public function store(Request $request)
    {
        $data = $request->validate([
            'member_registration_id'  => 'required',
            'value'  => 'required|string',
            'received_amount' => 'nullable|string',
            'note'  => 'required|string',
            'method_payment_id'  => 'required',
        ]);

        $data["user_id"] = Auth::user()->id;
        $data["value"] = (int) str_replace(".", "", $data["value"]);
        $receivedAmount = NormalizePosReceivedAmount(
            $data['received_amount'] ?? null,
            $data['value'],
            Auth::user()->branch_store_id
        );
        unset($data['received_amount']);
        if ($receivedAmount !== null) {
            $data['received_amount'] = $receivedAmount;
        }
        $data["note"] = trim($data["note"]);

        if ($data["note"] === '') {
            return redirect()->back()->withInput()->withErrors(['note' => 'The note field is required.']);
        }

        if ($data["value"] <= 0) {
            return redirect()->back()->with('errorr', 'Payment value must be greater than zero.');
        }

        try {
            $payment = DB::transaction(function () use (&$data) {
                $memberRegistration = MemberRegistration::query()
                    ->lockForUpdate()
                    ->findOrFail($data["member_registration_id"]);

                $price = $memberRegistration->total_payable;
                $paidAmount = MemberRegistrationPayment::query()
                    ->where('member_registration_id', $memberRegistration->id)
                    ->lockForUpdate()
                    ->sum('value');

                if ($paidAmount >= $price) {
                    throw ValidationException::withMessages(['value'=>'This membership has already been fully paid.']);
                }

                if ($paidAmount + $data["value"] > $price) {
                    throw ValidationException::withMessages(['value'=>'Nilai pembayaran melebihi sisa tagihan.']);
                }

                $payment = MemberRegistrationPayment::create($data);

                if ($paidAmount + $data["value"] >= $price) {
                    $memberRegistration->update(['payment_deadline' => 0]);
                }

                return $payment;
            });
        } catch (\RuntimeException $e) {
            report($e);
            return redirect()->back()->withInput()->with('errorr', 'Pembayaran belum berhasil disimpan. Silakan coba kembali atau hubungi pengelola.');
        }

        $redirect = redirect("member-active/". $data["member_registration_id"] ."/edit")
            ->with('message', 'Payment Added Successfully');

        if (optional(Auth::user()->branchStore)->pos_inventory_enabled) {
            $redirect->with('payment_receipt_url', route('payment-receipts.member', [
                'id' => $payment->id,
                'autoprint' => 1,
            ]));
        }

        return $redirect;
    }
    public function destroy($id)
    {
        try {
            $memberRegistrationPayment = MemberRegistrationPayment::find($id);
            $memberRegistrationPayment->delete();
            return redirect()->back()->with('success', 'Payment Deleted Successfully');
        } catch (\Throwable $e) {
            return redirect()->back()->with('errorr', 'Payment Deleting Failed');
        }
    }
}
