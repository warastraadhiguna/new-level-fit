<?php

namespace App\Http\Controllers\Trainer;

use App\Exports\MemberPTCheckInReportExport;
use App\Http\Controllers\Controller;
use App\Models\Member\Member;
use App\Models\MethodPayment;
use App\Models\Staff\PersonalTrainer;
use App\Models\Trainer\CheckInTrainerSession;
use App\Models\Trainer\PtLeaveDay;
use App\Models\Trainer\TrainerPackage;
use App\Models\Trainer\TrainerSession;
use App\Support\IdempotentSubmission;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Facades\Excel;

class PtFreeController extends Controller
{
    private const DUPLICATE_SCAN_WINDOW_SECONDS = 5;

    public function store(Request $request, Member $member)
    {
        $data = $request->validate([
            '_submission_token' => ['required', 'string', 'size:36'],
            'trainer_package_id' => ['required', 'integer', 'exists:trainer_packages,id'],
            'trainer_id' => ['required', 'integer', 'exists:personal_trainers,id'],
            'start_date' => ['required', 'date'],
            'description' => ['required', 'string', 'max:5000'],
        ]);

        $branchStoreId = (int) Auth::user()->branch_store_id;
        $package = TrainerPackage::where('branch_store_id', $branchStoreId)
            ->free()
            ->findOrFail($data['trainer_package_id']);
        $trainer = PersonalTrainer::where('branch_store_id', $branchStoreId)
            ->findOrFail($data['trainer_id']);

        $membership = GetLatestNonExpiredMembershipAccess($member->id, '', $branchStoreId);
        if (!$membership) {
            return back()->with('errorr', 'Member tidak memiliki membership aktif yang berlaku di cabang ini.');
        }

        if (MembershipHasOneClubBranchRestriction($membership, $branchStoreId)) {
            return back()->with('errorr', MembershipOneClubRestrictionMessage($member->full_name, 'menerima PT Free'));
        }

        $methodPayment = MethodPayment::orderBy('id')->first();
        if (!$methodPayment) {
            return back()->with('errorr', 'Method payment belum tersedia. PT Free tidak dapat disimpan.');
        }

        $cacheKey = IdempotentSubmission::claim(
            $data['_submission_token'],
            'pt-free:create:' . $member->id,
            (int) Auth::id()
        );

        if (!$cacheKey) {
            return redirect()->route('members.index')
                ->with('success', 'Pemberian PT Free sudah diproses. Data tidak disimpan dua kali.');
        }

        try {
            DB::transaction(function () use ($data, $member, $package, $trainer, $methodPayment, $branchStoreId) {
                TrainerSession::create([
                    'member_id' => $member->id,
                    'branch_store_id' => $branchStoreId,
                    'trainer_id' => $trainer->id,
                    'start_date' => Carbon::parse($data['start_date'], 'Asia/Jakarta')->startOfDay(),
                    'trainer_package_id' => $package->id,
                    'is_pt_free' => true,
                    'days' => (int) $package->days,
                    'old_days' => 0,
                    'package_price' => 0,
                    'admin_price' => 0,
                    'discount_amount' => 0,
                    'payment_deadline' => 0,
                    'number_of_session' => (int) $package->number_of_session,
                    'description' => trim($data['description']),
                    'method_payment_id' => $methodPayment->id,
                    'fc_id' => null,
                    'user_id' => Auth::id(),
                ]);
            });

            IdempotentSubmission::complete($cacheKey);
        } catch (\Throwable $exception) {
            IdempotentSubmission::release($cacheKey);
            throw $exception;
        }

        return redirect()->route('members.index')
            ->with('success', 'PT Free berhasil diberikan kepada ' . $member->full_name . '.');
    }

    public function checkInIndex()
    {
        $today = Carbon::now('Asia/Jakarta')->toDateString();
        $results = $this->checkInLogQuery()
            ->where(function ($query) use ($today) {
                $query->whereDate('cits.check_in_time', $today)
                    ->orWhereDate('cits.check_out_time', $today);
            })
            ->orderByDesc('cits.check_in_time')
            ->paginate(10);

        return view('admin.layouts.wrapper', [
            'title' => 'PT Free Check In/Out',
            'results' => $results,
            'content' => 'admin.pt-free.check-in',
        ]);
    }

    public function checkIn(Request $request)
    {
        $data = $request->validate([
            'card_number' => ['required', 'string', 'exists:members,card_number'],
        ], [
            'card_number.exists' => 'CARD NOT FOUND',
        ]);

        $branchStoreId = (int) Auth::user()->branch_store_id;
        $member = Member::where('card_number', trim($data['card_number']))->firstOrFail();
        $membership = GetLatestNonExpiredMembershipAccess($member->id, '', $branchStoreId);
        if (!$membership) {
            return back()->with('errorr', 'Membership ' . $member->full_name . ' telah expired atau belum dimulai.');
        }

        if ($this->membershipIsFrozen((int) $membership->member_registration_id)) {
            return back()->with('errorr', $member->full_name . ' sedang freeze.');
        }

        $session = $this->findEligibleSession($member->id, $branchStoreId);
        if (!$session) {
            return back()->with('errorr', 'PT Free tidak ditemukan, belum dimulai, telah berakhir, atau sesi sudah habis.');
        }

        if ($this->ptSessionIsFrozen($session->id)) {
            return back()->with('errorr', $member->full_name . ' sedang freeze.');
        }

        $message = DB::transaction(function () use ($session, $branchStoreId) {
            TrainerSession::whereKey($session->id)->lockForUpdate()->firstOrFail();
            $now = Carbon::now('Asia/Jakarta');
            $latest = CheckInTrainerSession::where('trainer_session_id', $session->id)
                ->latest('id')
                ->lockForUpdate()
                ->first();

            if (!$latest || $latest->check_out_time) {
                $lastTime = $latest ? $latest->check_out_time : null;
                if ($lastTime && Carbon::parse($lastTime)->diffInSeconds($now) <= self::DUPLICATE_SCAN_WINDOW_SECONDS) {
                    return 'Scan duplikat diabaikan.';
                }

                CheckInTrainerSession::create([
                    'trainer_session_id' => $session->id,
                    'branch_store_id' => $branchStoreId,
                    'check_in_time' => $now,
                    'pt_id' => $session->trainer_id,
                    'user_id' => Auth::id(),
                ]);

                return 'PT Free check-in berhasil untuk ' . $session->members->full_name . '.';
            }

            if (Carbon::parse($latest->check_in_time)->diffInSeconds($now) <= self::DUPLICATE_SCAN_WINDOW_SECONDS) {
                return 'Scan duplikat diabaikan.';
            }

            $latest->update(['check_out_time' => $now]);

            return 'PT Free check-out berhasil untuk ' . $session->members->full_name . '.';
        });

        $freezeDays = (int) PtLeaveDay::where('trainer_session_id', $session->id)->sum('days');
        $expiredDate = Carbon::parse($session->start_date)
            ->addDays((int) $session->days + $freezeDays);

        return view('admin.trainer-session-check-in.member_details')->with([
            'message' => $message,
            'memberPhoto' => $member->photos,
            'memberName' => $member->full_name,
            'nickName' => $member->nickname,
            'memberCode' => $member->member_code,
            'phoneNumber' => $member->phone_number,
            'born' => $member->born,
            'gender' => $member->gender,
            'email' => $member->email,
            'ig' => $member->ig,
            'eContact' => $member->emergency_contact,
            'address' => $member->address,
            'memberPackage' => $session->trainerPackages->package_name,
            'days' => $session->days,
            'startDate' => $session->start_date,
            'expiredDate' => $expiredDate,
            'returnRoute' => route('pt-free.check-in.index'),
        ]);
    }

    public function report(Request $request)
    {
        $request->validate([
            'fromDate' => ['nullable', 'date'],
            'toDate' => ['nullable', 'date', 'after_or_equal:fromDate'],
            'memberId' => ['nullable', 'integer', 'exists:members,id'],
            'ptId' => ['nullable', 'integer', 'exists:personal_trainers,id'],
            'excel' => ['nullable', 'in:0,1'],
        ]);

        $fromDate = $request->input('fromDate') ?: Carbon::now('Asia/Jakarta')->toDateString();
        $toDate = $request->input('toDate') ?: Carbon::now('Asia/Jakarta')->toDateString();
        $memberId = $request->input('memberId');
        $ptId = $request->input('ptId');

        $results = $this->checkInLogQuery()
            ->where(function ($query) use ($fromDate, $toDate) {
                $query->whereBetween(DB::raw('DATE(cits.check_in_time)'), [$fromDate, $toDate])
                    ->orWhereBetween(DB::raw('DATE(cits.check_out_time)'), [$fromDate, $toDate]);
            })
            ->when($memberId, function ($query) use ($memberId) {
                $query->where('members.id', $memberId);
            })
            ->when($ptId, function ($query) use ($ptId) {
                $query->whereRaw('COALESCE(cits.pt_id, ts.trainer_id) = ?', [$ptId]);
            })
            ->orderByDesc('cits.check_in_time')
            ->paginate(10);

        if ($request->input('excel') === '1') {
            return Excel::download(
                new MemberPTCheckInReportExport(true),
                'Member-PT-Free-checkin-report, ' . $fromDate . ' to ' . $toDate . '.xlsx'
            );
        }

        return view('admin.layouts.wrapper', [
            'title' => 'Report Member PT Free Check In',
            'results' => $results,
            'fromDate' => $fromDate,
            'toDate' => $toDate,
            'members' => Member::all(),
            'memberId' => $memberId,
            'ptId' => $ptId,
            'trainers' => PersonalTrainer::all(),
            'content' => 'admin.gym-report.report-member-pt-checkin',
        ]);
    }

    private function findEligibleSession(int $memberId, int $branchStoreId): ?TrainerSession
    {
        $now = Carbon::now('Asia/Jakarta');

        return TrainerSession::with(['members', 'trainerPackages', 'personalTrainers'])
            ->where('member_id', $memberId)
            ->where('branch_store_id', $branchStoreId)
            ->where('is_pt_free', true)
            ->whereNotNull('start_date')
            ->where('start_date', '<=', $now)
            ->latest('start_date')
            ->get()
            ->first(function (TrainerSession $session) use ($now) {
                $freezeDays = (int) PtLeaveDay::where('trainer_session_id', $session->id)->sum('days');
                $expiresAt = Carbon::parse($session->start_date)->addDays((int) $session->days + $freezeDays)->endOfDay();
                if ($now->gt($expiresAt)) {
                    return false;
                }

                $completedSessions = CheckInTrainerSession::where('trainer_session_id', $session->id)
                    ->whereNotNull('check_out_time')
                    ->count();

                return $completedSessions < (int) $session->number_of_session;
            });
    }

    private function membershipIsFrozen(int $registrationId): bool
    {
        return DB::table('leave_days')
            ->where('member_registration_id', $registrationId)
            ->whereRaw('NOW() BETWEEN submission_date AND DATE_ADD(submission_date, INTERVAL days DAY)')
            ->exists();
    }

    private function ptSessionIsFrozen(int $trainerSessionId): bool
    {
        return PtLeaveDay::where('trainer_session_id', $trainerSessionId)
            ->whereRaw('NOW() BETWEEN submission_date AND DATE_ADD(submission_date, INTERVAL days DAY)')
            ->exists();
    }

    private function checkInLogQuery()
    {
        return DB::table('check_in_trainer_sessions as cits')
            ->select(
                'cits.id',
                'members.member_code',
                'members.full_name as member_name',
                'tp.package_name',
                DB::raw("COALESCE(check_in_pt.full_name, pt.full_name, '-') as trainer_name"),
                'cits.check_in_time',
                'cits.check_out_time',
                'branch_stores.name as branch_store_name',
                'users.full_name as staff_name'
            )
            ->join('trainer_sessions as ts', 'cits.trainer_session_id', '=', 'ts.id')
            ->join('members', 'ts.member_id', '=', 'members.id')
            ->join('trainer_packages as tp', 'ts.trainer_package_id', '=', 'tp.id')
            ->leftJoin('personal_trainers as pt', 'ts.trainer_id', '=', 'pt.id')
            ->leftJoin('personal_trainers as check_in_pt', 'cits.pt_id', '=', 'check_in_pt.id')
            ->leftJoin('branch_stores', 'ts.branch_store_id', '=', 'branch_stores.id')
            ->join('users', 'cits.user_id', '=', 'users.id')
            ->where('ts.branch_store_id', Auth::user()->branch_store_id)
            ->where('ts.is_pt_free', true);
    }
}
