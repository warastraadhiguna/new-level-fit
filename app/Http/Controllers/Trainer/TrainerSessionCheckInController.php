<?php

namespace App\Http\Controllers\Trainer;

use App\Http\Controllers\Controller;
use App\Models\Member\Member;
use App\Models\Member\MemberRegistration;
use App\Models\Trainer\CheckInTrainerSession;
use App\Models\Trainer\TrainerSession;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;

class TrainerSessionCheckInController extends Controller
{
    private const DUPLICATE_SCAN_WINDOW_SECONDS = 5;

    public function index()
    {
        $results = DB::table('members')
            ->select(
                'cits.id as cits_id',
                'members.member_code',
                'members.id as member_id',
                'members.full_name as member_name',
                'cits.pt_id as pt_id',
                'pt.full_name as trainer_name',
                'tp.package_name',
                'cits.check_in_time',
                'cits.check_out_time',
                'branch_stores.name as branch_store_name'
            )
            ->join('trainer_sessions as ts', 'ts.member_id', '=', 'members.id')
            ->join('check_in_trainer_sessions as cits', 'cits.trainer_session_id', '=', 'ts.id')
            ->join('personal_trainers as pt', 'cits.pt_id', '=', 'pt.id')
            ->join('branch_stores', 'ts.branch_store_id', '=', 'branch_stores.id')
            ->join('trainer_packages as tp', 'ts.trainer_package_id', '=', 'tp.id')
            ->whereBetween(DB::raw('DATE(cits.check_in_time)'), [NowDate(), NowDate()])
            ->where('ts.branch_store_id', Auth::user()->branch_store_id)
            ->orderBy('cits.check_in_time', 'desc')             
            ->paginate(10);
                    
        $data = [
            'title'                 => 'Trainer Session Check In/Out',
            'results'                => $results,            
            'content'               => 'admin.trainer-session-check-in.index',
        ];

        return view('admin.layouts.wrapper', $data);   
    }
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
            echo "<script>window.location.href = '" . route('trainer-session-check-in.index') . "';</script>";
            return;
        }

        $trainerSession = TrainerSession::checkInPT($request->card_number);

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

        if ($expiredMemberRegistration[0]->branch_store_id !== Auth::user()->branch_store_id) {
            return redirect()->back()->with('errorr', 'Trainer Session hanya bisa dilakukan di cabang lain');
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

        $message = $this->processTrainerSessionCheckInRequest(
            $trainerSession[0]->id,
            $trainerSession[0]->trainer_id
        );

        return view('admin.trainer-session-check-in.member_details')->with([
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

    private function processTrainerSessionCheckInRequest(int $trainerSessionId, ?int $ptId): string
    {
        return DB::transaction(function () use ($trainerSessionId, $ptId) {
            DB::table('trainer_sessions')
                ->where('id', $trainerSessionId)
                ->lockForUpdate()
                ->first();

            $now = now()->tz('Asia/Jakarta');
            $latestCheckIn = CheckInTrainerSession::where('trainer_session_id', $trainerSessionId)
                ->latest('id')
                ->lockForUpdate()
                ->first();

            if (!$latestCheckIn) {
                CheckInTrainerSession::create([
                    'trainer_session_id' => $trainerSessionId,
                    'check_in_time' => $now,
                    'pt_id' => $ptId,
                    'user_id' => Auth::user()->id,
                ]);

                return 'Trainer Session Checked In Successfully';
            }

            if (!$latestCheckIn->check_out_time) {
                if ($this->isDuplicateTrainerSessionScan($latestCheckIn->check_in_time, $now)) {
                    return 'Duplicate scan ignored';
                }

                $latestCheckIn->update([
                    'check_out_time' => $now,
                ]);

                return 'Trainer Session Checked Out Successfully';
            }

            if ($this->isDuplicateTrainerSessionScan($latestCheckIn->check_out_time, $now)) {
                return 'Duplicate scan ignored';
            }

            CheckInTrainerSession::create([
                'trainer_session_id' => $trainerSessionId,
                'check_in_time' => $now,
                'pt_id' => $ptId,
                'user_id' => Auth::user()->id,
            ]);

            return 'Trainer Session Checked In Successfully';
        });
    }

    private function isDuplicateTrainerSessionScan($timestamp, Carbon $referenceTime): bool
    {
        if (!$timestamp) {
            return false;
        }

        return Carbon::parse($timestamp)->diffInSeconds($referenceTime) <= self::DUPLICATE_SCAN_WINDOW_SECONDS;
    }
}
