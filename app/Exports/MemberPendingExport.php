<?php

namespace App\Exports;

use Illuminate\Contracts\View\View;
use App\Models\Member\MemberRegistration;
use Maatwebsite\Excel\Concerns\FromView;

class MemberPendingExport implements FromView
{
    public function view(): View
    {
        $memberRegistrations = MemberRegistration::getPendingList();

        return view('admin.member-registration.excel-list', [
            'memberRegistrations' => $memberRegistrations,
            'exportType' => 'pending',
        ]);
    }
}
