<?php

namespace App\Http\Controllers\Member;

use App\Http\Controllers\Controller;
use App\Models\Member\Member;
use Illuminate\Http\Request;
use PDF;

class MemberPrintCardController extends Controller
{
    public function edit(string $id)
    {
        // dd($id);
        $member = Member::find($id);

        $pdf = PDF::loadView('admin/member/print-member-card', [
            'member'               => $member,
        ]);
        return $pdf->stream('laporan-member.pdf');
    }
}
