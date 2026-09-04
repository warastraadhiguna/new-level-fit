<?php

namespace App\Http\Controllers\Pos;

use App\Http\Controllers\Controller;
use App\Models\Member\MemberRegistrationPayment;
use App\Models\Trainer\TrainerSessionPayment;
use Illuminate\Support\Facades\Auth;

class PaymentReceiptController extends Controller
{
    public function member($id)
    {
        $this->ensureReceiptEnabled();

        $payment = MemberRegistrationPayment::with([
            'user',
            'methodPayment',
            'memberRegistration.members',
            'memberRegistration.memberPackage.branchStore',
        ])
            ->whereHas('memberRegistration.memberPackage', function ($query) {
                $query->where('branch_store_id', Auth::user()->branch_store_id);
            })
            ->findOrFail($id);

        $registration = $payment->memberRegistration;
        $paidToDate = MemberRegistrationPayment::where('member_registration_id', $registration->id)
            ->where('id', '<=', $payment->id)
            ->sum('value');

        return view('admin.pos.payment-receipt', [
            'receiptNumber' => 'MRP-' . str_pad($payment->id, 8, '0', STR_PAD_LEFT),
            'receiptTitle' => $registration->members->status === 'one_day_visit' || (int) $registration->days <= 1
                ? 'Pembayaran One Day Visit'
                : 'Pembayaran Membership',
            'branchStore' => $registration->memberPackage->branchStore,
            'customerName' => $registration->members->full_name,
            'customerCode' => $registration->members->member_code,
            'packageName' => optional($registration->memberPackage)->package_name,
            'packagePrice' => (int) $registration->package_price,
            'adminPrice' => (int) $registration->admin_price,
            'discountAmount' => (int) $registration->discount_amount,
            'totalPayable' => $registration->total_payable,
            'payment' => $payment,
            'paidToDate' => (int) $paidToDate,
            'backUrl' => route('member-active.edit', $registration->id),
        ]);
    }

    public function trainer($id)
    {
        $this->ensureReceiptEnabled();

        $payment = TrainerSessionPayment::with([
            'user',
            'methodPayment',
            'trainerSession.members',
            'trainerSession.trainerPackages',
            'trainerSession.branchStore',
        ])
            ->whereHas('trainerSession', function ($query) {
                $query->where('branch_store_id', Auth::user()->branch_store_id);
            })
            ->findOrFail($id);

        $session = $payment->trainerSession;
        $paidToDate = TrainerSessionPayment::where('trainer_session_id', $session->id)
            ->where('id', '<=', $payment->id)
            ->sum('value');

        return view('admin.pos.payment-receipt', [
            'receiptNumber' => 'TSP-' . str_pad($payment->id, 8, '0', STR_PAD_LEFT),
            'receiptTitle' => 'Pembayaran Personal Trainer',
            'branchStore' => $session->branchStore,
            'customerName' => $session->members->full_name,
            'customerCode' => $session->members->member_code,
            'packageName' => optional($session->trainerPackages)->package_name,
            'packagePrice' => (int) $session->package_price,
            'adminPrice' => (int) $session->admin_price,
            'discountAmount' => (int) $session->discount_amount,
            'totalPayable' => $session->total_payable,
            'payment' => $payment,
            'paidToDate' => (int) $paidToDate,
            'backUrl' => route('trainer-session.edit', $session->id),
        ]);
    }

    private function ensureReceiptEnabled(): void
    {
        abort_unless(
            Auth::user()->branch_store_id
                && optional(Auth::user()->branchStore)->pos_inventory_enabled,
            404
        );
    }
}
