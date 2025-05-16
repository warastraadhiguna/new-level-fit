<?php

namespace App\Http\Controllers\Trainer;

use App\Http\Controllers\Controller;
use App\Models\Member\Member;
use App\Models\Member\MemberRegistration;
use App\Models\Trainer\CheckInTrainerSession;
use App\Models\Trainer\LGT;
use App\Models\Trainer\TrainerSession;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class TrainerSessionCheckInController extends Controller
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
            echo "<script>window.location.href = '" . route('trainer-session.index') . "';</script>";
            return;
        }

        $trainerSession = TrainerSession::checkInPT($request->card_number);
        // dd($trainerSession[0]->trainer_id);

        if (!empty($trainerSession) && isset($trainerSession[0])) {
            if ($trainerSession[0]->leave_day_status == "Freeze") {
                return redirect()->back()->with('errorr', $trainerSession[0]->member_name . ' sedang cuti!!');
            }
        }

        if (!$trainerSession) {
            return redirect()->back()->with('errorr', 'Trainer session not found or has ended');
        }

        $expiredMemberRegistration = MemberRegistration::getActiveList($request->card_number);
        if (!$expiredMemberRegistration || sizeof($expiredMemberRegistration) == 0) {
            return redirect()->back()->with('errorr', 'Paket member ' . $trainerSession[0]->member_name . ' telah expired atau belum dimulai!!');
        }


        $memberPhoto    = $trainerSession[0]->photos;
        $memberName     = $trainerSession[0]->member_name;
        $nickName       = $trainerSession[0]->nickname;
        $phoneNumber    = $trainerSession[0]->phone_number;
        $memberCode     = $trainerSession[0]->member_code;
        $gender         = $trainerSession[0]->gender;
        $born           = $trainerSession[0]->born;
        $email          = $trainerSession[0]->email;
        $ig             = $trainerSession[0]->ig;
        $eContact       = $trainerSession[0]->emergency_contact;
        $address        = $trainerSession[0]->address;
        $memberPackage  = $trainerSession[0]->package_name;
        $days           = $trainerSession[0]->days;
        $startDate      = $trainerSession[0]->start_date;
        $expiredDate    = $trainerSession[0]->expired_date;

        $message = "";
        // $package = TrainerSession::findOrFail('trainer_session_id');
        // dd($package);
        if ($trainerSession[0]->current_check_in_trainer_sessions_id && !$trainerSession[0]->check_out_time) {
            $checkInTrainerSession = CheckInTrainerSession::find($trainerSession[0]->current_check_in_trainer_sessions_id);
            $checkInTrainerSession->update([
                'check_out_time' => now()->tz('Asia/Jakarta'),
            ]);
            $message = 'Trainer Session Checked Out Successfully';
        } else {
            $data = [
                'trainer_session_id'    => $trainerSession[0]->id,
                'check_in_time'         => now()->tz('Asia/Jakarta'),
                // 'pt_id'                 => $trainerSession[0]->trainer_id,
                'user_id'               => Auth::user()->id,
            ];

            $data['pt_id']  = $trainerSession[0]->trainer_id;

            CheckInTrainerSession::create($data);
            $message = 'Trainer Session Checked In Successfully';
        }

        return view('admin.trainer-session.member_details')->with([
            'message'           => $message,
            'memberPhoto'       => $memberPhoto,
            'memberName'        => $memberName,
            'nickName'          => $nickName,
            'memberCode'        => $memberCode,
            'phoneNumber'       => $phoneNumber,
            'born'              => $born,
            'gender'            => $gender,
            'email'             => $email,
            'ig'                => $ig,
            'eContact'          => $eContact,
            'address'           => $address,
            'memberPackage'     => $memberPackage,
            'days'              => $days,
            'startDate'         => $startDate,
            'expiredDate'       => $expiredDate
        ]);
    }

    public function secondStore($id)
    {
        $trainerSession = TrainerSession::checkInPT("", $id);
        // dd($trainerSession);
        $expiredMemberRegistration = MemberRegistration::getActiveList("", $trainerSession[0]->member_id);

        if (!$expiredMemberRegistration || sizeof($expiredMemberRegistration) == 0) {
            return redirect()->back()->with('errorr', 'Paket member ' . $trainerSession[0]->member_name . ' telah expired atau belum dimulai!!');
        }

        $expiredMemberRegistration = MemberRegistration::getActiveList("", $trainerSession[0]->member_id);
        if (!$expiredMemberRegistration || sizeof($expiredMemberRegistration) == 0) {
            return redirect()->back()->with('errorr', 'Paket member ' . $trainerSession[0]->member_name . ' telah expired atau belum dimulai!!');
        }

        if (!empty($trainerSession) && isset($trainerSession[0])) {
            if ($trainerSession[0]->leave_day_status == "Freeze") {
                return redirect()->back()->with('errorr', $trainerSession[0]->member_name . ' sedang cuti!!');
            }
        }

        $member = Member::find($trainerSession[0]->member_id);

        if (!$trainerSession) {
            return redirect()->back()->with('error', 'PT Session not found or has ended');
        }

        $memberPhoto    = $trainerSession[0]->photos;
        $memberName     = $trainerSession[0]->member_name;
        $nickName       = $trainerSession[0]->nickname;
        $phoneNumber    = $trainerSession[0]->phone_number;
        $memberCode     = $trainerSession[0]->member_code;
        $gender         = $trainerSession[0]->gender;
        $born           = $trainerSession[0]->born;
        $email          = $trainerSession[0]->email;
        $ig             = $trainerSession[0]->ig;
        $eContact       = $trainerSession[0]->emergency_contact;
        $address        = $trainerSession[0]->address;
        $memberPackage  = $trainerSession[0]->package_name;
        $days           = $trainerSession[0]->days;
        $startDate      = $trainerSession[0]->start_date;
        $expiredDate    = $trainerSession[0]->expired_date;


        $message = "";
        if ($trainerSession[0]->current_check_in_trainer_sessions_id && !$trainerSession[0]->check_out_time) {
            $checkInPT = CheckInTrainerSession::find($trainerSession[0]->current_check_in_trainer_sessions_id);
            $checkInPT->update([
                'check_out_time' => now()->tz('Asia/Jakarta'),
            ]);
            $member->update([
                "id_code_count" => $member->id_code_count++
            ]);
            $message = 'PT Checked Out Successfully';
        } else {
            $data = [
                'trainer_session_id'    => $trainerSession[0]->id,
                'check_in_time'         => now()->tz('Asia/Jakarta'),
                'user_id'               => Auth::user()->id,
            ];

            CheckInTrainerSession::create($data);
            $message = 'PT Checked In Successfully';
        }

        return view('admin.trainer-session.member_details')->with([
            'message'       => $message,
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

    public function lgtStore(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'card_number' => 'required|exists:members,card_number',
        ], [
            'card_number.exists' => 'CARD NOT FOUND',
        ]);

        if ($validator->fails()) {
            $errorMessage = $validator->errors()->first('card_number');
            echo "<script>alert('$errorMessage');</script>";
            echo "<script>window.location.href = '" . route('lgt') . "';</script>";
            return;
        }

        $trainerSession = TrainerSession::checkInLGT($request->card_number);

        if (!empty($trainerSession) && isset($trainerSession[0])) {
            if ($trainerSession[0]->leave_day_status == "Freeze") {
                return redirect()->back()->with('errorr', $trainerSession[0]->member_name . ' sedang cuti!!');
            }
        }


        // $expiredMemberRegistration = MemberRegistration::getActiveList($request->card_number);
        // if (!$expiredMemberRegistration || sizeof($expiredMemberRegistration) == 0) {
        //     return redirect()->back()->with('errorr', 'Paket member ' . $trainerSession[0]->member_name . ' telah expired atau belum dimulai!!');
        // }

        $expiredMemberRegistration = MemberRegistration::getActiveList($request->card_number);
        if (!$expiredMemberRegistration || sizeof($expiredMemberRegistration) == 0) {
            return redirect()->back()->with('errorr', 'Paket member telah expired atau belum dimulai!!');
        }

        if (!$trainerSession) {
            return redirect()->back()->with('errorr', 'LGT not found or has ended');
        }

        $memberPhoto    = $trainerSession[0]->photos;
        $memberName     = $trainerSession[0]->member_name;
        $nickName       = $trainerSession[0]->nickname;
        $phoneNumber    = $trainerSession[0]->phone_number;
        $memberCode     = $trainerSession[0]->member_code;
        $gender         = $trainerSession[0]->gender;
        $born           = $trainerSession[0]->born;
        $email          = $trainerSession[0]->email;
        $ig             = $trainerSession[0]->ig;
        $eContact       = $trainerSession[0]->emergency_contact;
        $address        = $trainerSession[0]->address;
        $memberPackage  = $trainerSession[0]->package_name;
        $days           = $trainerSession[0]->days;
        $startDate      = $trainerSession[0]->start_date;
        $expiredDate    = $trainerSession[0]->expired_date;


        $message = "";
        if ($trainerSession[0]->current_check_in_trainer_sessions_id && !$trainerSession[0]->check_out_time) {
            $checkInTrainerSession = CheckInTrainerSession::find($trainerSession[0]->current_check_in_trainer_sessions_id);
            $checkInTrainerSession->update([
                'check_out_time' => now()->tz('Asia/Jakarta'),
            ]);
            $message = 'LGT Checked Out Successfully';
        } else {
            $data = [
                'trainer_session_id' => $trainerSession[0]->id,
                'check_in_time' => now()->tz('Asia/Jakarta'),
                'user_id' => Auth::user()->id,
            ];

            CheckInTrainerSession::create($data);
            $message = 'LGT Checked In Successfully';
        }

        // return redirect()->route('trainer-session.index')->with('message', $message);
        return view('admin.lgt.member_details')->with([
            'message' => $message,
            'memberPhoto'       => $memberPhoto,
            'memberName'        => $memberName,
            'nickName'          => $nickName,
            'memberCode'        => $memberCode,
            'phoneNumber'       => $phoneNumber,
            'born'              => $born,
            'gender'            => $gender,
            'email'             => $email,
            'ig'                => $ig,
            'eContact'          => $eContact,
            'address'           => $address,
            'memberPackage'     => $memberPackage,
            'days'              => $days,
            'startDate'         => $startDate,
            'expiredDate'       => $expiredDate
        ]);
    }

    public function lgtSecondStore($id)
    {
        $trainerSession = TrainerSession::lgtActive("", $id);
        $expiredMemberRegistration = MemberRegistration::getActiveList("", $trainerSession[0]->member_id);

        if (!$expiredMemberRegistration || sizeof($expiredMemberRegistration) == 0) {
            return redirect()->back()->with('errorr', 'Paket member ' . $trainerSession[0]->member_name . ' telah expired atau belum dimulai!!');
        }

        $expiredMemberRegistration = MemberRegistration::getActiveList("", $trainerSession[0]->member_id);
        if (!$expiredMemberRegistration || sizeof($expiredMemberRegistration) == 0) {
            return redirect()->back()->with('errorr', 'Paket member ' . $trainerSession[0]->member_name . ' telah expired atau belum dimulai!!');
        }

        if (!empty($trainerSession) && isset($trainerSession[0])) {
            if ($trainerSession[0]->leave_day_status == "Freeze") {
                return redirect()->back()->with('errorr', $trainerSession[0]->member_name . ' sedang cuti!!');
            }
        }

        $member = Member::find($trainerSession[0]->member_id);

        if (!$trainerSession) {
            return redirect()->back()->with('error', 'PT Session not found or has ended');
        }

        $memberPhoto    = $trainerSession[0]->photos;
        $memberName     = $trainerSession[0]->member_name;
        $nickName       = $trainerSession[0]->nickname;
        $phoneNumber    = $trainerSession[0]->phone_number;
        $memberCode     = $trainerSession[0]->member_code;
        $gender         = $trainerSession[0]->gender;
        $born           = $trainerSession[0]->born;
        $email          = $trainerSession[0]->email;
        $ig             = $trainerSession[0]->ig;
        $eContact       = $trainerSession[0]->emergency_contact;
        $address        = $trainerSession[0]->address;
        $memberPackage  = $trainerSession[0]->package_name;
        $days           = $trainerSession[0]->days;
        $startDate      = $trainerSession[0]->start_date;
        $expiredDate    = $trainerSession[0]->expired_date;


        $message = "";
        if ($trainerSession[0]->current_check_in_trainer_sessions_id && !$trainerSession[0]->check_out_time) {
            $checkInPT = CheckInTrainerSession::find($trainerSession[0]->current_check_in_trainer_sessions_id);
            $checkInPT->update([
                'check_out_time' => now()->tz('Asia/Jakarta'),
            ]);
            $member->update([
                "id_code_count" => $member->id_code_count++
            ]);
            $message = 'PT Checked Out Successfully';
        } else {
            $data = [
                'trainer_session_id'    => $trainerSession[0]->id,
                'check_in_time'         => now()->tz('Asia/Jakarta'),
                'user_id'               => Auth::user()->id,
            ];

            CheckInTrainerSession::create($data);
            $message = 'PT Checked In Successfully';
        }

        return view('admin.lgt.member_details')->with([
            'message'       => $message,
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
            $checkInTrainerSession = CheckInTrainerSession::find($id);
            $checkInTrainerSession->delete();
            return redirect()->back()->with('success', 'Check In Date Deleted Successfully');
        } catch (\Throwable $th) {
            return redirect()->back()->with('errorr', 'Deleted Failed, please check other page where using this check in');
        }
    }

    public function checkMemberExistence()
    {
        $data = request()->get('member_code'); // Assuming 'member_code' is the key from the AJAX request
        $model = new CheckInTrainerSession(); // Replace 'YourModel' with the actual name of your model
        $where = ['member_code' => $data]; // Adjust the condition based on your model

        return $model->where($where)->exists();
    }
}
