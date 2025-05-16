<?php

namespace App\Http\Controllers\Member;

use App\Http\Controllers\Controller;
use App\Models\Member\CheckInMember;
use App\Models\Member\Member;
use App\Models\Member\MemberRegistration;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class MemberCheckInController extends Controller
{
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'card_number' => 'required|exists:members,card_number',
        ], [
            'card_number.exists' => 'CARD NOT FOUND',
        ]);

        if ($validator->fails()) {
            $errorMessage = $validator->errors()->first('card_number');
            echo "<script>alert('$errorMessage');</script>";
            echo "<script>window.location.href = '" . route('member-active.index') . "';</script>";
            return;
        }

        $memberRegistration = MemberRegistration::getActiveList($request->card_number);
        if ($memberRegistration[0]->leave_day_status == "Freeze") {
            return redirect()->back()->with('errorr', $memberRegistration[0]->member_name . ' sedang cuti!!');
        }

        // if (!$memberRegistration) {
        //     return redirect()->back()->with('error', 'Member active not found or has ended');
        // }

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


        $message = "";
        if ($memberRegistration[0]->current_check_in_members_id && !$memberRegistration[0]->check_out_time) {
            // $checkInMember = CheckInMember::where([["member_registration_id", $memberRegistration->current_check_in_members_id], ["check_in_time", $memberRegistration->check_in_time]]);
            //edited by angling
            $checkInMember = CheckInMember::find($memberRegistration[0]->current_check_in_members_id);

            $checkInMember->update([
                'check_out_time' => now()->tz('Asia/Jakarta'),
            ]);
            $message = 'Member Checked Out Successfully';
        } else {
            //edited by angling
            //$checkOutTime = null; // Default value

            // $latestCheckIn = CheckInMember::where('member_registration_id', $memberRegistration->id)
            //     ->orderBy('check_in_time', 'desc')
            //     ->first();

            // if ($latestCheckIn && $latestCheckIn->check_out_time === null) {
            //     $checkOutTime = now()->tz('Asia/Jakarta');
            // }

            $data = [
                'member_registration_id' => $memberRegistration[0]->id,
                'check_in_time' => now()->tz('Asia/Jakarta'),
                'user_id' => Auth::user()->id,
            ];

            CheckInMember::create($data);
            $message = 'Member Checked In Successfully';
        }

        // return redirect()->route('member-active.index')->with('message', $message);
        return view('admin.member-registration.member_details')->with([
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

    public function secondStore($id)
    {
        $memberRegistration = DB::table('member_registrations as a')
            ->select(
                'a.id',
                'a.start_date',
                'a.description',
                'a.days as number_of_days',
                'a.member_id',
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
                'e.name as method_payment_name',
                'f.full_name as staff_name',
                'h.id as current_check_in_members_id',
                'h.check_out_time',
                'h.check_in_time',
            )
            ->addSelect(
                DB::raw('DATE_ADD(a.start_date, INTERVAL a.days DAY) as expired_date'),
                DB::raw('CASE WHEN NOW() > DATE_ADD(a.start_date, INTERVAL a.days DAY) THEN "Over" ELSE "Running" END as status'),
            )
            ->join('members as b', 'a.member_id', '=', 'b.id')
            ->join('member_packages as c', 'a.member_package_id', '=', 'c.id')
            ->join('method_payments as e', 'a.method_payment_id', '=', 'e.id')
            ->join('users as f', 'a.user_id', '=', 'f.id')
            ->whereRaw('CASE WHEN NOW() > DATE_ADD(a.start_date, INTERVAL c.days DAY) THEN "Over" ELSE "Running" END = ?', ['Running'])
            ->leftJoin(DB::raw("(select a.* from check_in_members a inner join (SELECT max(id) as id FROM check_in_members group by member_registration_id) as b on a.id=b.id) as h"), 'h.member_registration_id', '=', 'a.id')
            ->where('a.id', $id)
            ->whereRaw('NOW() <= DATE_ADD(a.start_date, INTERVAL a.days DAY)')
            ->first();

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

        if (!$memberRegistration) {
            return redirect()->back()->with('error', 'Member active not found or has ended');
        }

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
                'check_in_time' => now()->tz('Asia/Jakarta'),
                'user_id' => Auth::user()->id,
            ];

            CheckInMember::create($data);
            $message = 'Member Checked In Successfully';
        }

        return view('admin.member-registration.member_details')->with([
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
}
