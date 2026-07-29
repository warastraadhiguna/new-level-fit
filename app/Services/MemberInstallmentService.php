<?php

namespace App\Services;

use App\Models\Member\MemberRegistration;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class MemberInstallmentService
{
    public function initialize(MemberRegistration $registration): void
    {
        if ($registration->installments()->exists()) {
            $this->refresh($registration);
            return;
        }

        $registration->loadMissing('memberPackage.branchStore');
        $package = $registration->memberPackage;
        $branch = $package ? $package->branchStore : null;

        if (!$package || !$branch || !$package->is_installment_plan || !$branch->member_installment_enabled) {
            return;
        }

        $monthly = (int) $package->installment_monthly_amount;
        if ($monthly <= 0) {
            return;
        }

        $registration->forceFill([
            'is_installment_plan' => true,
            'installment_monthly_amount' => $monthly,
            'installment_status' => 'pending',
            'installment_deposit_status' => 'held',
            'installment_grace_days' => (int) $branch->member_installment_grace_days,
            'installment_cancel_days' => (int) $branch->member_installment_cancel_days,
        ])->saveQuietly();

        $start = Carbon::parse($registration->start_date)->startOfDay();
        $rows = [];
        for ($month = 1; $month <= 12; $month++) {
            $rows[] = [
                'member_registration_id' => $registration->id,
                'month_number' => $month,
                'payment_order' => $month === 1 ? 1 : ($month === 12 ? 2 : $month + 1),
                'type' => $month === 12 ? 'deposit' : 'monthly',
                'due_date' => ($month === 12 ? $start : $start->copy()->addMonthsNoOverflow($month - 1))->toDateString(),
                'amount' => $monthly,
                'paid_amount' => 0,
                'status' => 'pending',
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }
        DB::table('member_registration_installments')->insert($rows);
        $this->refresh($registration->fresh());
    }

    public function refresh(MemberRegistration $registration): void
    {
        if (!$registration->is_installment_plan) {
            return;
        }
        if ($registration->installment_status === 'cancelled') {
            return;
        }

        $available = max(0, (int) $registration->payments()->sum('value') - (int) $registration->admin_price);
        $now = Carbon::now()->startOfDay();
        $earliestUnpaid = null;

        foreach ($registration->installments()->orderBy('payment_order')->get() as $installment) {
            $allocated = min($available, (int) $installment->amount);
            $available -= $allocated;
            $status = $allocated >= $installment->amount ? 'paid' : ($allocated > 0 ? 'partial' : 'pending');
            if ($status !== 'paid' && $installment->type !== 'deposit') {
                $earliestUnpaid = $earliestUnpaid ?: Carbon::parse($installment->due_date);
                if ($now->gt(Carbon::parse($installment->due_date)->addDays((int) $registration->installment_grace_days))) {
                    $status = 'overdue';
                }
            }
            $installment->forceFill([
                'paid_amount' => $allocated,
                'status' => $status,
                'paid_at' => $status === 'paid' ? ($installment->paid_at ?: now()) : null,
            ])->save();
        }

        $initialPaid = $registration->installments()->whereIn('month_number', [1, 12])
            ->where('status', 'paid')->count() === 2;
        $cancelled = $earliestUnpaid
            && $now->gt($earliestUnpaid->copy()->addDays((int) $registration->installment_cancel_days));
        $overdue = $earliestUnpaid
            && $now->gt($earliestUnpaid->copy()->addDays((int) $registration->installment_grace_days));
        $complete = $registration->installments()->where('status', '!=', 'paid')->doesntExist();

        $registration->forceFill([
            'installment_status' => $cancelled ? 'cancelled' : ($complete ? 'completed' : ($overdue ? 'suspended' : ($initialPaid ? 'active' : 'pending'))),
            'installment_deposit_status' => $cancelled ? 'forfeited' : ($complete ? 'applied' : 'held'),
            'installment_cancelled_at' => $cancelled ? ($registration->installment_cancelled_at ?: now()) : null,
        ])->saveQuietly();

        if ($cancelled) {
            $registration->installments()->where('type', 'deposit')->update(['status' => 'forfeited']);
        }
    }

    public function canCheckIn(MemberRegistration $registration): bool
    {
        $this->refresh($registration);
        return in_array($registration->fresh()->installment_status, ['active', 'completed'], true);
    }
}
