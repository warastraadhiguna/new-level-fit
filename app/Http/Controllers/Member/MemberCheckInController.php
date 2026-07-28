<?php

namespace App\Http\Controllers\Member;

use App\Http\Controllers\Controller;
use App\Models\Member\CheckInMember;
use App\Models\Member\Member;
use App\Models\Member\MemberRegistration;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Validator;

class MemberCheckInController extends Controller
{
    private const DUPLICATE_SCAN_WINDOW_SECONDS = 5;

    public function index()
    {
        $hasBranchStoreColumn = $this->memberCheckInHasBranchStoreColumn();
        $branchStoreNameSql = $hasBranchStoreColumn
            ? 'COALESCE(check_in_branch.name, member_branch.name) as branch_store_name'
            : 'member_branch.name as branch_store_name';

        $results = DB::table('members')
            ->select(
                'cim.id as cim_id',
                'members.member_code',
                'members.id as member_id',
                'members.full_name as member_name',
                'cim.check_in_time',
                'cim.check_out_time',
                DB::raw($branchStoreNameSql)
            )
            ->join('member_registrations as mr', 'mr.member_id', '=', 'members.id')
            ->join('check_in_members as cim', 'cim.member_registration_id', '=', 'mr.id')
            ->leftJoin('branch_stores as member_branch', 'members.branch_store_id', '=', 'member_branch.id')
            ->whereDate('cim.check_in_time', '>=', NowDate())
            ->whereDate('cim.check_in_time', '<=', NowDate())
            ->when($hasBranchStoreColumn, function ($query) {
                $query->leftJoin('branch_stores as check_in_branch', 'cim.branch_store_id', '=', 'check_in_branch.id')
                    ->whereRaw('COALESCE(cim.branch_store_id, members.branch_store_id) = ?', [Auth::user()->branch_store_id]);
            }, function ($query) {
                $query->where('members.branch_store_id', Auth::user()->branch_store_id);
            })
            ->orderBy('cim.check_in_time', 'desc') 
            ->get();

        $data = [
            'title'                 => 'Membership Check In',
            'results'                => $results,            
            'content'               => 'admin.member-check-in.index',
        ];

        return view('admin.layouts.wrapper', $data);        
    }

    public function toggleByCardNumber(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'card_number' => 'required|exists:members,card_number',
        ], [
            'card_number.exists' => 'CARD NOT FOUND',
        ]);

        if ($validator->fails()) {
            $errorMessage = $validator->errors()->first('card_number');
            echo "<script>alert('$errorMessage');</script>";
            echo "<script>window.location.href = '" . route('member-check-in.index') . "';</script>";
            return;
        }

        $memberRegistration = MemberRegistration::getActiveList($request->card_number, "", "");
        if (!$memberRegistration) {
            if(MemberRegistration::expiredRegistrations($request->card_number)->exists()){
                return redirect()->back()->with('error', 'Member expired');
            }
            
            return redirect()->back()->with('error', 'Member pending');
        }
        
        if (MembershipHasOneClubBranchRestriction($memberRegistration[0], Auth::user()->branch_store_id)) {
            return redirect()->back()->with('errorr', MembershipOneClubRestrictionMessage($memberRegistration[0]->member_name, 'check in'));
        }

        if ($memberRegistration[0]->leave_day_status == "Freeze") {
            return redirect()->back()->with('errorr', $memberRegistration[0]->member_name . ' sedang cuti!!');
        }

        if ($this->shouldBlockUnpaidMemberCheckIn($memberRegistration[0])) {
            return redirect()->back()->with('error', 'Unpaid Member');
        }

        $deadlineStatus = $this->memberPaymentDeadlineStatus($memberRegistration[0]);
        if ($deadlineStatus && $deadlineStatus['blocked']) {
            return redirect()->back()->with('error', $deadlineStatus['message']);
        }

        $memberPhoto    = $memberRegistration[0]->photos;
        $memberName     = $memberRegistration[0]->member_name;
        $nickName       = $memberRegistration[0]->nickname;
        $phoneNumber    = $memberRegistration[0]->phone_number;
        $memberCode     = $memberRegistration[0]->member_code;
        $gender         = $memberRegistration[0]->gender;
        $born           = $memberRegistration[0]->born;
        $email          = $memberRegistration[0]->email;
        $ig             = $memberRegistration[0]->ig;
        $eContact       = $memberRegistration[0]->emergency_contact;
        $address        = $memberRegistration[0]->address;
        $memberPackage  = $memberRegistration[0]->package_name;
        $days           = $memberRegistration[0]->days;
        $startDate      = $memberRegistration[0]->start_date;
        $expiredDate    = $memberRegistration[0]->expired_date;

        $message = $this->processCheckInRequest($memberRegistration[0]->id);
        $message = $this->appendPaymentDeadlineNotice($message, $deadlineStatus);

        return view('admin.member-check-in.member_details')->with([
            'message' => $message,
            'memberPhoto'   => $memberPhoto,
            'memberName'    => $memberName,
            'nickName'      => $nickName,
            'memberCode'    => $memberCode,
            'phoneNumber'   => $phoneNumber,
            'born'          => $born,
            'gender'        => $gender,
            'email'         => $email,
            'ig'            => $ig,
            'eContact'      => $eContact,
            'address'       => $address,
            'memberPackage' => $memberPackage,
            'days'          => $days,
            'startDate'     => $startDate,
            'expiredDate'   => $expiredDate
        ]);
    }

    public function toggleByRegistrationId($memberRegistrationId)
    {
        $memberRegistration = DB::table('member_registrations as a')
            ->select(
                'a.id',
                'a.start_date',
                'a.created_at as registration_created_at',
                'a.payment_deadline',
                'a.description',
                'a.days as number_of_days',
                'a.member_id',
                'a.package_price as mr_package_price',
                'a.admin_price as mr_admin_price',
                'b.full_name as member_name',
                'b.nickname',
                'b.member_code',
                'b.phone_number',
                'b.born',
                'b.email',
                'b.ig',
                'b.emergency_contact',
                'b.address',
                'b.photos',
                'b.gender',
                'c.package_name',
                'c.days',
                'c.package_price',
                'c.is_all_club',
                'c.branch_store_id as member_package_branch_store_id',
                'e.name as method_payment_name',
                'f.full_name as staff_name',
                'h.id as current_check_in_members_id',
                'h.check_out_time',
                'h.check_in_time',
                DB::raw('IFNULL((SELECT SUM(value) FROM member_registration_payments mrp WHERE a.id = mrp.member_registration_id), 0) as payment_summary'),
            )
            ->addSelect(
                DB::raw('DATE_ADD(a.start_date, INTERVAL a.days DAY) as expired_date'),
                DB::raw('CASE WHEN a.payment_deadline > 0 THEN DATE_ADD(a.created_at, INTERVAL a.payment_deadline DAY) ELSE NULL END as payment_deadline_date'),
                DB::raw('CASE WHEN NOW() > DATE_ADD(a.start_date, INTERVAL a.days DAY) THEN "Over" ELSE "Running" END as status'),
            )
            ->join('members as b', 'a.member_id', '=', 'b.id')
            ->join('member_packages as c', 'a.member_package_id', '=', 'c.id')
            ->join('method_payments as e', 'a.method_payment_id', '=', 'e.id')
            ->join('users as f', 'a.user_id', '=', 'f.id')
            ->whereRaw('CASE WHEN NOW() > DATE_ADD(a.start_date, INTERVAL c.days DAY) THEN "Over" ELSE "Running" END = ?', ['Running'])
            ->leftJoin(DB::raw("(select a.* from check_in_members a inner join (SELECT max(id) as id FROM check_in_members group by member_registration_id) as b on a.id=b.id) as h"), 'h.member_registration_id', '=', 'a.id')
            ->where('a.id', $memberRegistrationId)
            ->whereRaw('NOW() <= DATE_ADD(a.start_date, INTERVAL a.days DAY)')
            ->first();

        if (!$memberRegistration) {
            return redirect()->back()->with('error', 'Member active not found or has ended');
        }

        if (MembershipHasOneClubBranchRestriction($memberRegistration, Auth::user()->branch_store_id)) {
            return redirect()->back()->with('errorr', MembershipOneClubRestrictionMessage($memberRegistration->member_name, 'check in'));
        }

        if ($this->shouldBlockUnpaidMemberCheckIn($memberRegistration)) {
            return redirect()->back()->with('error', 'Unpaid Member');
        }

        $deadlineStatus = $this->memberPaymentDeadlineStatus($memberRegistration);
        if ($deadlineStatus && $deadlineStatus['blocked']) {
            return redirect()->back()->with('error', $deadlineStatus['message']);
        }

        $memberPhoto    = $memberRegistration->photos;
        $memberName     = $memberRegistration->member_name;
        $nickName       = $memberRegistration->nickname;
        $phoneNumber    = $memberRegistration->phone_number;
        $memberCode     = $memberRegistration->member_code;
        $gender         = $memberRegistration->gender;
        $born           = $memberRegistration->born;
        $email          = $memberRegistration->email;
        $ig             = $memberRegistration->ig;
        $eContact       = $memberRegistration->emergency_contact;
        $address        = $memberRegistration->address;
        $memberPackage  = $memberRegistration->package_name;
        $days           = $memberRegistration->number_of_days;
        $startDate      = $memberRegistration->start_date;
        $expiredDate    = $memberRegistration->expired_date;

        $member = Member::find($memberRegistration->member_id);

        // $member->update([
        //     "id_code_count" => $member->id_code_count++
        // ]);

        $message = "";
        if ($memberRegistration->current_check_in_members_id && !$memberRegistration->check_out_time) {
            $checkInMember = CheckInMember::find($memberRegistration->current_check_in_members_id);
            $checkInMember->update([
                'check_out_time' => now()->tz('Asia/Jakarta'),
            ]);
            $member->update([
                "id_code_count" => $member->id_code_count++
            ]);
            $message = 'Member Checked Out Successfully';
        } else {

            $data = [
                'member_registration_id' => $memberRegistration->id,
                'branch_store_id' => Auth::user()->branch_store_id,
                'check_in_time' => now()->tz('Asia/Jakarta'),
                'user_id' => Auth::user()->id,
            ];

            CheckInMember::create($this->memberCheckInPayload($data));
            $message = 'Member Checked In Successfully';
        }

        $message = $this->appendPaymentDeadlineNotice($message, $deadlineStatus);

        return view('admin.member-check-in.member_details')->with([
            'message' => $message,
            'memberPhoto'   => $memberPhoto,
            'memberName'    => $memberName,
            'nickName'      => $nickName,
            'memberCode'    => $memberCode,
            'phoneNumber'   => $phoneNumber,
            'born'          => $born,
            'gender'        => $gender,
            'email'         => $email,
            'ig'            => $ig,
            'eContact'      => $eContact,
            'address'       => $address,
            'memberPackage' => $memberPackage,
            'days'          => $days,
            'startDate'     => $startDate,
            'expiredDate'   => $expiredDate
        ]);
    }

    public function destroy($id)
    {
        try {
            $checkInMember = CheckInMember::find($id);
            $checkInMember->delete();
            return redirect()->back()->with('success', 'Check In Date Deleted Successfully');
        } catch (\Throwable $th) {
            return redirect()->back()->with('errorr', 'Deleted Failed, please check other page where using this check in');
        }
    }

    private function processCheckInRequest(int $memberRegistrationId): string
    {
        return DB::transaction(function () use ($memberRegistrationId) {
            DB::table('member_registrations')
                ->where('id', $memberRegistrationId)
                ->lockForUpdate()
                ->first();

            $now = now()->tz('Asia/Jakarta');
            $latestCheckIn = CheckInMember::where('member_registration_id', $memberRegistrationId)
                ->latest('id')
                ->lockForUpdate()
                ->first();

            if (!$latestCheckIn) {
                CheckInMember::create($this->memberCheckInPayload([
                    'member_registration_id' => $memberRegistrationId,
                    'branch_store_id' => Auth::user()->branch_store_id,
                    'check_in_time' => $now,
                    'user_id' => Auth::user()->id,
                ]));

                return 'Member Checked In Successfully';
            }

            if (!$latestCheckIn->check_out_time) {
                if ($this->isDuplicateScan($latestCheckIn->check_in_time, $now)) {
                    return 'Duplicate scan ignored';
                }

                $latestCheckIn->update([
                    'check_out_time' => $now,
                ]);

                return 'Member Checked Out Successfully';
            }

            if ($this->isDuplicateScan($latestCheckIn->check_out_time, $now)) {
                return 'Duplicate scan ignored';
            }

            CheckInMember::create($this->memberCheckInPayload([
                'member_registration_id' => $memberRegistrationId,
                'branch_store_id' => Auth::user()->branch_store_id,
                'check_in_time' => $now,
                'user_id' => Auth::user()->id,
            ]));

            return 'Member Checked In Successfully';
        });
    }

    private function isDuplicateScan($timestamp, Carbon $referenceTime): bool
    {
        if (!$timestamp) {
            return false;
        }

        return Carbon::parse($timestamp)->diffInSeconds($referenceTime) <= self::DUPLICATE_SCAN_WINDOW_SECONDS;
    }

    private function memberRegistrationHasUnpaidPayment($memberRegistration): bool
    {
        return (int) $memberRegistration->payment_summary < ((int) $memberRegistration->mr_package_price + (int) $memberRegistration->mr_admin_price);
    }

    private function shouldBlockUnpaidMemberCheckIn($memberRegistration): bool
    {
        $isOneClubPackage = (string) ($memberRegistration->is_all_club ?? '1') === '0';

        return $this->memberRegistrationHasUnpaidPayment($memberRegistration)
            && (BranchStorePaymentIsStrict(Auth::user()->branch_store_id) || !$isOneClubPackage);
    }

    private function memberPaymentDeadlineStatus($memberRegistration): ?array
    {
        $isOneClubPackage = (string) ($memberRegistration->is_all_club ?? '1') === '0';
        $isNextActionCheckIn = !($memberRegistration->current_check_in_members_id && !$memberRegistration->check_out_time);
        $deadlineDays = (int) ($memberRegistration->payment_deadline ?? 0);

        if (BranchStorePaymentIsStrict(Auth::user()->branch_store_id)
            || !$isOneClubPackage
            || !$isNextActionCheckIn
            || !$this->memberRegistrationHasUnpaidPayment($memberRegistration)
            || $deadlineDays <= 0) {
            return null;
        }

        $deadlineValue = $memberRegistration->payment_deadline_date ?? null;
        $createdAt = $memberRegistration->registration_created_at ?? $memberRegistration->created_at ?? null;

        if (!$deadlineValue && $createdAt) {
            $deadlineValue = Carbon::parse($createdAt)->addDays($deadlineDays);
        }

        if (!$deadlineValue) {
            return null;
        }

        $deadlineDate = Carbon::parse($deadlineValue)->startOfDay();
        $formattedDeadline = $deadlineDate->isoFormat('DD MMMM YYYY');
        $isPastDeadline = Carbon::now('Asia/Jakarta')->startOfDay()->gt($deadlineDate);

        return [
            'blocked' => $isPastDeadline,
            'message' => $isPastDeadline
                ? 'Payment deadline passed on ' . $formattedDeadline . '. Check-in denied.'
                : 'Payment is not fully paid. Payment deadline: ' . $formattedDeadline . '.',
        ];
    }

    private function appendPaymentDeadlineNotice(string $message, ?array $deadlineStatus): string
    {
        if (!$deadlineStatus || $deadlineStatus['blocked'] || strpos($message, 'Checked In Successfully') === false) {
            return $message;
        }

        return $message . ' ' . $deadlineStatus['message'];
    }

    private function memberCheckInPayload(array $data): array
    {
        if (!$this->memberCheckInHasBranchStoreColumn()) {
            unset($data['branch_store_id']);
        }

        return $data;
    }

    private function memberCheckInHasBranchStoreColumn(): bool
    {
        static $hasColumn = null;

        if ($hasColumn === null) {
            $hasColumn = Schema::hasColumn('check_in_members', 'branch_store_id');
        }

        return $hasColumn;
    }
}
