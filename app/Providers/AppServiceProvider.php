<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Laravel\Sanctum\Sanctum;
use App\Models\Member\MemberRegistration;
use App\Models\Member\MemberRegistrationPayment;
use App\Services\MemberInstallmentService;

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
        MemberRegistration::created(function (MemberRegistration $registration) {
            app(MemberInstallmentService::class)->initialize($registration);
        });

        MemberRegistrationPayment::creating(function (MemberRegistrationPayment $payment) {
            $registration = MemberRegistration::find($payment->member_registration_id);
            if (!$registration || !$registration->is_installment_plan) return;

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
    }
}
