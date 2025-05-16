<?php

namespace App\Exports;

use App\Models\Member\Member;
use Carbon\Carbon;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Concerns\FromView;

class MemberExport implements FromView
{
    public function view(): View
    {
        $nowTime = Carbon::now()->tz('Asia/Jakarta');
        $nowTimeString = DateFormat($nowTime, "Y-MM-DD");
        $fromDate   = Request()->input('fromDate');
        $toDate     = Request()->input('toDate');

        $toDate = $toDate ? $toDate : $nowTimeString;
        $fromDate = $fromDate ? $fromDate : $nowTimeString;

        $members = DB::table('members as a')
            ->select(
                'a.id',
                'a.full_name',
                'a.nickname',
                'a.member_code',
                'a.card_number',
                'a.gender',
                'a.born',
                'a.phone_number',
                'a.email',
                'a.ig',
                'a.emergency_contact',
                'a.ec_name',
                'a.address',
                'a.status',
                'a.photos',
                'a.created_at'
            )
            ->where('a.status', '=', 'sell')
            ->where('a.created_at', '>=', $fromDate)
            ->where('a.created_at', '<=', $toDate)
            ->orderBy('created_at', 'desc')
            ->get();

        return view('admin.members.excel', [
            'members' => $members
        ]);
    }
}