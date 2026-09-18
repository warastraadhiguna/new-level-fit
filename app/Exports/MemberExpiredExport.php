<?php

namespace App\Exports;

use App\Models\Member\MemberRegistration;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Maatwebsite\Excel\Concerns\FromView;

class MemberExpiredExport implements FromView
{
    public function view(): View
    {
        $memberRegistrationsOver = MemberRegistration::expiredRegistrations(
            '',
            Auth::user()->branch_store_id
        )->get();

        return view('admin.member-registration.excel-list', [
            'memberRegistrations' => $memberRegistrationsOver,
            'exportType' => 'expired',
        ]);
    }
}
