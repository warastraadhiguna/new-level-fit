<?php

namespace App\Exports;

use Illuminate\Contracts\View\View;
use App\Models\Member\MemberRegistration;
use Illuminate\Support\Facades\Auth;
use Maatwebsite\Excel\Concerns\FromView;

class MemberPendingExport implements FromView
{
    public function view(): View
    {
        $memberRegistrations = MemberRegistration::getPendingList('', Auth::user()->branch_store_id);

        return view('admin.member-registration.excel-list', [
            'memberRegistrations' => $memberRegistrations,
            'exportType' => 'pending',
        ]);
    }
}
