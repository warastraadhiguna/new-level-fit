<?php

namespace App\Exports;

use App\Models\Member\MemberRegistration;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Maatwebsite\Excel\Concerns\FromView;

class MemberUnpaidExport implements FromView
{
    public function view(): View
    {
        $memberRegistrations = MemberRegistration::getActiveList(
            "",
            "",
            "yes",
            Auth::user()->branch_store_id
        );

        return view('admin.member-registration.excel-list', [
            'memberRegistrations' => $memberRegistrations,
            'exportType' => 'unpaid',
        ]);
    }
}
