<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Laravel\Sanctum\Sanctum;
use App\Models\Member\MemberRegistration;
use App\Models\Member\MemberRegistrationPayment;
use App\Services\MemberInstallmentService;
use App\Models\BranchStore;
use App\Models\Trainer\TrainerSession;
use App\Models\Trainer\TrainerSessionPayment;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        Sanctum::ignoreMigrations();
    }

    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        $normalizeDiscount = function ($value): int {
            return max(0, (int) str_replace(['.', ','], '', (string) $value));
        };

        MemberRegistration::saving(function (MemberRegistration $registration) use ($normalizeDiscount) {
            $package = $registration->memberPackage()->first();
            $branch = $package ? BranchStore::find($package->branch_store_id) : null;
            $discount = $normalizeDiscount($registration->discount_amount);

            if (!$branch || !$branch->member_discount_enabled) {
                $registration->discount_amount = $registration->exists
                    ? (int) $registration->getOriginal('discount_amount')
                    : 0;
                return;
            }

            if ($discount > (int) $registration->package_price) {
                throw \Illuminate\Validation\ValidationException::withMessages([
                    'discount_amount' => 'Diskon membership tidak boleh melebihi harga paket.',
                ]);
            }

            if ($registration->exists) {
                $paid = (int) $registration->payments()->sum('value');
                $total = (int) $registration->package_price + (int) $registration->admin_price - $discount;
                if ($paid > $total) {
                    throw \Illuminate\Validation\ValidationException::withMessages([
                        'discount_amount' => 'Diskon terlalu besar karena pembayaran yang sudah masuk melebihi total setelah diskon.',
                    ]);
                }
            }

            $registration->discount_amount = $discount;
        });

        TrainerSession::saving(function (TrainerSession $session) use ($normalizeDiscount) {
            $branch = BranchStore::find($session->branch_store_id);
            $discount = $normalizeDiscount($session->discount_amount);

            if (!$branch || !$branch->trainer_discount_enabled) {
                $session->discount_amount = $session->exists
                    ? (int) $session->getOriginal('discount_amount')
                    : 0;
                return;
            }

            if ($discount > (int) $session->package_price) {
                throw \Illuminate\Validation\ValidationException::withMessages([
                    'discount_amount' => 'Diskon PT tidak boleh melebihi harga paket.',
                ]);
            }

            if ($session->exists) {
                $paid = (int) \App\Models\Trainer\TrainerSessionPayment::where('trainer_session_id', $session->id)->sum('value');
                $total = (int) $session->package_price + (int) $session->admin_price - $discount;
                if ($paid > $total) {
                    throw \Illuminate\Validation\ValidationException::withMessages([
                        'discount_amount' => 'Diskon terlalu besar karena pembayaran yang sudah masuk melebihi total setelah diskon.',
                    ]);
                }
            }

            $session->discount_amount = $discount;
        });

        MemberRegistration::created(function (MemberRegistration $registration) {
            app(MemberInstallmentService::class)->initialize($registration);
        });

        MemberRegistrationPayment::creating(function (MemberRegistrationPayment $payment) {
            $registration = MemberRegistration::find($payment->member_registration_id);
            if (!$registration) return;

            $paid = (int) $registration->payments()->sum('value');
            if ($paid + (int) $payment->value > $registration->total_payable) {
                throw \Illuminate\Validation\ValidationException::withMessages([
                    'value' => 'Pembayaran melebihi total tagihan setelah diskon.',
                ]);
            }

            if (!$registration->is_installment_plan) return;

            if ($registration->installment_status === 'cancelled') {
                throw \Illuminate\Validation\ValidationException::withMessages([
                    'value' => 'Kontrak cicilan sudah dibatalkan dan deposit bulan ke-12 telah hangus.',
                ]);
            }

            if (!$registration->payments()->exists()) {
                $minimum = (int) $registration->admin_price + ((int) $registration->installment_monthly_amount * 2);
                if ((int) $payment->value < $minimum) {
                    throw \Illuminate\Validation\ValidationException::withMessages([
                        'value' => 'Pembayaran awal paket cicilan minimal Rp ' . number_format($minimum, 0, ',', '.') . ' (admin + bulan 1 + deposit bulan 12).',
                    ]);
                }
            }
        });

        $refreshInstallment = function (MemberRegistrationPayment $payment) {
            $registration = MemberRegistration::find($payment->member_registration_id);
            if ($registration) app(MemberInstallmentService::class)->refresh($registration);
        };
        MemberRegistrationPayment::created($refreshInstallment);
        MemberRegistrationPayment::deleted($refreshInstallment);

        TrainerSessionPayment::creating(function (TrainerSessionPayment $payment) {
            $session = TrainerSession::find($payment->trainer_session_id);
            if (!$session) return;

            $paid = (int) TrainerSessionPayment::where('trainer_session_id', $session->id)->sum('value');
            if ($paid + (int) $payment->value > $session->total_payable) {
                throw \Illuminate\Validation\ValidationException::withMessages([
                    'value' => 'Pembayaran melebihi total tagihan PT setelah diskon.',
                ]);
            }
        });
    }
}
