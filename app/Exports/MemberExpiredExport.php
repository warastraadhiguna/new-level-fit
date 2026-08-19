<?php

namespace App\Exports;

use App\Models\Member\MemberRegistration;
use Illuminate\Contracts\View\View;
use Maatwebsite\Excel\Concerns\FromView;

class MemberExpiredExport implements FromView
{
    public function view(): View
    {
        $memberRegistrationsOver = MemberRegistration::expiredRegistrations()->get();

        return view('admin.member-registration.excel-list', [
            'memberRegistrations' => $memberRegistrationsOver,
            'exportType' => 'expired',
        ]);
    }
}
