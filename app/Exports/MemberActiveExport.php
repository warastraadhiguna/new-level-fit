<?php

namespace App\Exports;

use App\Models\Member\MemberRegistration;
use Illuminate\Contracts\View\View;
use Maatwebsite\Excel\Concerns\FromView;

class MemberActiveExport implements FromView
{
    public function view(): View
    {
        $memberRegistrations = MemberRegistration::getActiveList('', '', 'no');

        return view('admin.member-registration.excel-list', [
            'memberRegistrations' => $memberRegistrations,
            'exportType' => 'active',
        ]);
    }
}
