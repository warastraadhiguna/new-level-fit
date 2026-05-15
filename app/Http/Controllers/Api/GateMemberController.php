<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class GateMemberController extends Controller
{
    public function index(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'store_branch_id' => ['required', 'integer'],
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'The given data was invalid.',
                'errors' => $validator->errors(),
            ], 422);
        }

        $branchStoreId = (int) $validator->validated()['store_branch_id'];
        $today = Carbon::now('Asia/Jakarta')->toDateString();

        $activeMemberships = DB::table('member_registrations as mr')
            ->selectRaw('MAX(mr.id) as member_registration_id')
            ->join('member_packages as mp', 'mp.id', '=', 'mr.member_package_id')
            ->whereDate('mr.start_date', '<=', $today)
            ->whereRaw('DATE(DATE_ADD(mr.start_date, INTERVAL mr.days DAY)) >= ?', [$today])
            ->where(function ($query) use ($branchStoreId) {
                $query
                    // All club dapat masuk ke semua cabang.
                    ->where('mp.is_all_club', 1)
                    // One club hanya dapat masuk ke cabang package yang sama dengan request.
                    ->orWhere(function ($query) use ($branchStoreId) {
                        $query->where('mp.is_all_club', 0)
                            ->where('mp.branch_store_id', $branchStoreId);
                    });
            })
            ->groupBy('mr.member_id');

        $members = DB::table('member_registrations as mr')
            ->select([
                'm.id',
                'm.full_name',
                'm.card_number',
                'm.member_code',
                'm.photos',
                'mr.start_date',
                'mp.package_name',
                'mp.is_all_club',
            ])
            ->selectRaw('DATE(DATE_ADD(mr.start_date, INTERVAL mr.days DAY)) as end_date')
            ->joinSub($activeMemberships, 'active_memberships', function ($join) {
                $join->on('active_memberships.member_registration_id', '=', 'mr.id');
            })
            ->join('members as m', 'm.id', '=', 'mr.member_id')
            ->join('member_packages as mp', 'mp.id', '=', 'mr.member_package_id')
            ->orderBy('m.full_name')
            ->get()
            ->map(function ($member) {
                return [
                    'id' => $member->id,
                    'full_name' => $member->full_name,
                    'card_number' => $member->card_number,
                    'member_code' => $member->member_code,
                    'active_date' => $member->start_date,
                    'end_date' => $member->end_date,
                    'package_name' => $member->package_name,
                    'package_type' => (int) $member->is_all_club === 1 ? 'all_club' : 'one_club',
                    'url_photo' => $this->photoUrl($member->photos),
                ];
            })
            ->values();

        return response()->json($members);
    }

    private function photoUrl(?string $path): ?string
    {
        if (!$path) {
            return null;
        }

        return asset('storage/' . ltrim($path, '/'));
    }
}
