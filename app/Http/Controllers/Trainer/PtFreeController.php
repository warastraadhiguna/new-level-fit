<?php

namespace App\Http\Controllers\Trainer;

use App\Exports\MemberPTCheckInReportExport;
use App\Exports\PtFreeSessionExport;
use App\Http\Controllers\Controller;
use App\Models\Member\Member;
use App\Models\MethodPayment;
use App\Models\Staff\PersonalTrainer;
use App\Models\Trainer\CheckInTrainerSession;
use App\Models\Trainer\PtLeaveDay;
use App\Models\Trainer\TrainerPackage;
use App\Models\Trainer\TrainerSession;
use App\Models\User;
use App\Support\IdempotentSubmission;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Facades\Excel;

class PtFreeController extends Controller
{
    private const DUPLICATE_SCAN_WINDOW_SECONDS = 5;

    public function create()
    {
        $branchStoreId = (int) Auth::user()->branch_store_id;

        return view('admin.layouts.wrapper', [
            'title' => 'PT Free Registration',
            'members' => GetAccessibleNonExpiredMembersForBranch($branchStoreId),
            'personalTrainers' => PersonalTrainer::where('branch_store_id', $branchStoreId)
                ->orderBy('full_name')
                ->get(),
            'trainerPackages' => TrainerPackage::where('branch_store_id', $branchStoreId)
                ->free()
                ->orderBy('package_name')
                ->get(),
            'fitnessConsultant' => User::where('role', 'FC')->orderBy('full_name')->get(),
            'content' => 'admin.pt-free.form',
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            '_submission_token' => ['required', 'string', 'size:36'],
            'member_id' => ['required', 'integer', 'exists:members,id'],
            'trainer_package_id' => ['required', 'integer', 'exists:trainer_packages,id'],
            'trainer_id' => ['nullable', 'integer', 'exists:personal_trainers,id'],
            'start_date' => ['nullable', 'date'],
            'fc_id' => ['nullable', 'integer', 'exists:users,id'],
            'description' => ['required', 'string', 'max:5000'],
        ]);

        $branchStoreId = (int) Auth::user()->branch_store_id;
        $member = Member::findOrFail($data['member_id']);
        $package = TrainerPackage::where('branch_store_id', $branchStoreId)
            ->free()
            ->findOrFail($data['trainer_package_id']);
        $trainer = !empty($data['trainer_id'])
            ? PersonalTrainer::where('branch_store_id', $branchStoreId)->findOrFail($data['trainer_id'])
            : null;

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
            'pt-free:create:' . $data['member_id'],
            (int) Auth::id()
        );

        if (!$cacheKey) {
            return redirect()->route('pt-free.active')
                ->with('success', 'Registrasi PT Free sudah diproses. Data tidak disimpan dua kali.');
        }

        try {
            DB::transaction(function () use ($data, $member, $package, $trainer, $methodPayment, $branchStoreId) {
                TrainerSession::create([
                    'member_id' => $member->id,
                    'branch_store_id' => $branchStoreId,
                    'trainer_id' => $trainer ? $trainer->id : null,
                    'start_date' => $data['start_date']
                        ? Carbon::parse($data['start_date'], 'Asia/Jakarta')->startOfDay()
                        : null,
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
                    'fc_id' => $data['fc_id'] ?? null,
                    'user_id' => Auth::id(),
                ]);
            });

            IdempotentSubmission::complete($cacheKey);
        } catch (\Throwable $exception) {
            IdempotentSubmission::release($cacheKey);
            throw $exception;
        }

        $route = !$data['start_date'] || !$trainer
            ? 'pt-free.waiting-list'
            : (Carbon::parse($data['start_date'])->isFuture() ? 'pt-free.pending' : 'pt-free.active');

        return redirect()->route($route)
            ->with('success', 'Registrasi PT Free untuk ' . $member->full_name . ' berhasil disimpan.');
    }

    public function active(Request $request)
    {
        return $this->renderSessionList($request, 'active', 'PT Free Active');
    }

    public function pending(Request $request)
    {
        return $this->renderSessionList($request, 'pending', 'PT Free Pending');
    }

    public function expired(Request $request)
    {
        return $this->renderSessionList($request, 'expired', 'PT Free Expired');
    }

    public function waitingList(Request $request)
    {
        return $this->renderSessionList($request, 'waiting', 'PT Free Waiting List');
    }

    public function history(Request $request)
    {
        $request->validate([
            'fromDate' => ['nullable', 'date'],
            'toDate' => ['nullable', 'date', 'after_or_equal:fromDate'],
        ]);

        return $this->renderSessionList($request, 'history', 'PT Free History');
    }

    public function show($id)
    {
        $trainerSession = $this->findBranchSession($id);
        $trainerSession->load(['members', 'trainerPackages', 'personalTrainers', 'users']);
        $checkIns = CheckInTrainerSession::with(['personalTrainer', 'users'])
            ->where('trainer_session_id', $trainerSession->id)
            ->orderByDesc('check_in_time')
            ->paginate(10);
        $freezeDays = (int) PtLeaveDay::where('trainer_session_id', $trainerSession->id)->sum('days');

        return view('admin.layouts.wrapper', [
            'title' => 'PT Free Detail',
            'trainerSession' => $trainerSession,
            'checkIns' => $checkIns,
            'usedSessions' => CheckInTrainerSession::where('trainer_session_id', $trainerSession->id)
                ->whereNotNull('check_out_time')
                ->count(),
            'expiredDate' => $trainerSession->start_date
                ? Carbon::parse($trainerSession->start_date)->addDays((int) $trainerSession->days + $freezeDays)
                : null,
            'content' => 'admin.pt-free.show',
        ]);
    }

    public function edit($id)
    {
        $branchStoreId = (int) Auth::user()->branch_store_id;

        return view('admin.layouts.wrapper', [
            'title' => 'Edit PT Free',
            'trainerSession' => $this->findBranchSession($id)->load(['members', 'trainerPackages', 'personalTrainers']),
            'members' => collect(),
            'personalTrainers' => PersonalTrainer::where('branch_store_id', $branchStoreId)
                ->orderBy('full_name')
                ->get(),
            'trainerPackages' => TrainerPackage::where('branch_store_id', $branchStoreId)
                ->free()
                ->orderBy('package_name')
                ->get(),
            'fitnessConsultant' => User::where('role', 'FC')->orderBy('full_name')->get(),
            'content' => 'admin.pt-free.form',
        ]);
    }

    public function update(Request $request, $id)
    {
        $trainerSession = $this->findBranchSession($id);
        $data = $request->validate([
            'trainer_package_id' => ['required', 'integer', 'exists:trainer_packages,id'],
            'trainer_id' => ['nullable', 'integer', 'exists:personal_trainers,id'],
            'start_date' => ['nullable', 'date'],
            'fc_id' => ['nullable', 'integer', 'exists:users,id'],
            'description' => ['required', 'string', 'max:5000'],
        ]);
        $branchStoreId = (int) Auth::user()->branch_store_id;
        $package = TrainerPackage::where('branch_store_id', $branchStoreId)
            ->free()
            ->findOrFail($data['trainer_package_id']);
        $trainer = !empty($data['trainer_id'])
            ? PersonalTrainer::where('branch_store_id', $branchStoreId)->findOrFail($data['trainer_id'])
            : null;

        $trainerSession->update([
            'trainer_id' => $trainer ? $trainer->id : null,
            'start_date' => $data['start_date']
                ? Carbon::parse($data['start_date'], 'Asia/Jakarta')->startOfDay()
                : null,
            'trainer_package_id' => $package->id,
            'days' => (int) $package->days,
            'number_of_session' => (int) $package->number_of_session,
            'package_price' => 0,
            'admin_price' => 0,
            'discount_amount' => 0,
            'fc_id' => $data['fc_id'] ?? null,
            'description' => trim($data['description']),
            'user_id' => Auth::id(),
        ]);

        $route = !$data['start_date'] || !$trainer
            ? 'pt-free.waiting-list'
            : (Carbon::parse($data['start_date'])->isFuture() ? 'pt-free.pending' : 'pt-free.active');

        return redirect()->route($route)
            ->with('success', 'PT Free berhasil diperbarui.');
    }

    public function destroy($id)
    {
        $trainerSession = $this->findBranchSession($id);

        if (CheckInTrainerSession::where('trainer_session_id', $trainerSession->id)->exists()) {
            return back()->with('errorr', 'PT Free yang sudah memiliki data check-in tidak dapat dihapus.');
        }

        PtLeaveDay::where('trainer_session_id', $trainerSession->id)->delete();
        $trainerSession->delete();

        return back()->with('success', 'PT Free berhasil dihapus.');
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

        return $this->toggleSessionAndRender($session, $member);
    }

    public function checkInBySession($id)
    {
        $session = $this->findBranchSession($id)->load(['members', 'trainerPackages']);
        $member = $session->members;
        $branchStoreId = (int) Auth::user()->branch_store_id;
        $membership = GetLatestNonExpiredMembershipAccess($member->id, '', $branchStoreId);

        if (!$membership) {
            return back()->with('errorr', 'Membership ' . $member->full_name . ' telah expired atau belum dimulai.');
        }
        if ($this->membershipIsFrozen((int) $membership->member_registration_id) || $this->ptSessionIsFrozen($session->id)) {
            return back()->with('errorr', $member->full_name . ' sedang freeze.');
        }

        $freezeDays = (int) PtLeaveDay::where('trainer_session_id', $session->id)->sum('days');
        $expiredAt = $session->start_date
            ? Carbon::parse($session->start_date)->addDays((int) $session->days + $freezeDays)->endOfDay()
            : null;
        $completedSessions = CheckInTrainerSession::where('trainer_session_id', $session->id)
            ->whereNotNull('check_out_time')
            ->count();
        $now = Carbon::now('Asia/Jakarta');
        if (!$session->trainer_id || !$session->start_date || $now->lt(Carbon::parse($session->start_date))
            || $now->gt($expiredAt) || $completedSessions >= (int) $session->number_of_session) {
            return back()->with('errorr', 'PT Free belum dimulai, telah berakhir, atau sesi sudah habis.');
        }

        return $this->toggleSessionAndRender($session, $member);
    }

    private function toggleSessionAndRender(TrainerSession $session, Member $member)
    {
        $branchStoreId = (int) Auth::user()->branch_store_id;
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
            ->whereNotNull('trainer_id')
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

    private function renderSessionList(Request $request, string $scope, string $title)
    {
        $fromDate = $request->input('fromDate') ?: Carbon::now('Asia/Jakarta')->toDateString();
        $toDate = $request->input('toDate') ?: Carbon::now('Asia/Jakarta')->toDateString();
        $expirySql = 'DATE_ADD(ts.start_date, INTERVAL (ts.days + COALESCE(freeze_summary.total_days, 0)) DAY)';
        $remainingSql = '(ts.number_of_session - COALESCE(checkin_summary.completed_sessions, 0))';

        $query = DB::table('trainer_sessions as ts')
            ->select(
                'ts.id',
                'ts.member_id',
                'ts.start_date',
                'ts.days',
                'ts.number_of_session',
                'ts.description',
                'ts.created_at',
                'ts.package_price as ts_package_price',
                'ts.admin_price as ts_admin_price',
                'ts.discount_amount as ts_discount_amount',
                'members.full_name as member_name',
                'members.member_code',
                'members.phone_number',
                'members.born',
                'members.photos',
                'members.id_code_count',
                'tp.package_name',
                'pt.full_name as trainer_name',
                'branch_stores.name as branch_store_name',
                'users.full_name as staff_name',
                'last_checkin.check_in_time',
                'last_checkin.check_out_time'
            )
            ->selectRaw($expirySql . ' as expired_date')
            ->selectRaw('freeze_summary.latest_freeze_end as expired_leave_days')
            ->selectRaw("CASE WHEN COALESCE(freeze_summary.is_currently_frozen, 0) = 1 THEN 'Freeze' ELSE 'No Leave Days' END as leave_day_status")
            ->selectRaw('COALESCE(checkin_summary.completed_sessions, 0) as used_sessions')
            ->selectRaw($remainingSql . ' as remaining_sessions')
            ->selectRaw('ts.number_of_session as ts_number_of_session')
            ->selectRaw('0 as payment_summary')
            ->leftJoin('members', 'ts.member_id', '=', 'members.id')
            ->leftJoin('trainer_packages as tp', 'ts.trainer_package_id', '=', 'tp.id')
            ->leftJoin('personal_trainers as pt', 'ts.trainer_id', '=', 'pt.id')
            ->leftJoin('branch_stores', 'ts.branch_store_id', '=', 'branch_stores.id')
            ->leftJoin('users', 'ts.user_id', '=', 'users.id')
            ->leftJoinSub(
                PtLeaveDay::summaryQuery(),
                'freeze_summary',
                function ($join) {
                    $join->on('ts.id', '=', 'freeze_summary.trainer_session_id');
                }
            )
            ->leftJoinSub(
                CheckInTrainerSession::query()
                    ->selectRaw('trainer_session_id, SUM(CASE WHEN check_out_time IS NOT NULL THEN 1 ELSE 0 END) as completed_sessions')
                    ->groupBy('trainer_session_id'),
                'checkin_summary',
                function ($join) {
                    $join->on('ts.id', '=', 'checkin_summary.trainer_session_id');
                }
            )
            ->leftJoinSub(
                DB::table('check_in_trainer_sessions as latest')
                    ->select('latest.trainer_session_id', 'latest.check_in_time', 'latest.check_out_time')
                    ->joinSub(
                        DB::table('check_in_trainer_sessions')->selectRaw('trainer_session_id, MAX(id) as max_id')->groupBy('trainer_session_id'),
                        'latest_id',
                        function ($join) {
                            $join->on('latest.id', '=', 'latest_id.max_id');
                        }
                    ),
                'last_checkin',
                function ($join) {
                    $join->on('ts.id', '=', 'last_checkin.trainer_session_id');
                }
            )
            ->where('ts.branch_store_id', Auth::user()->branch_store_id)
            ->where('ts.is_pt_free', true);

        if ($scope === 'waiting') {
            $query->where(function ($builder) {
                $builder->whereNull('ts.start_date')->orWhereNull('ts.trainer_id');
            });
        } elseif ($scope === 'pending') {
            $query->whereNotNull('ts.start_date')->whereNotNull('ts.trainer_id')->where('ts.start_date', '>', Carbon::now('Asia/Jakarta'));
        } elseif ($scope === 'active') {
            $query->whereNotNull('ts.start_date')
                ->whereNotNull('ts.trainer_id')
                ->where('ts.start_date', '<=', Carbon::now('Asia/Jakarta'))
                ->whereRaw('NOW() <= ' . $expirySql)
                ->whereRaw($remainingSql . ' > 0');
        } elseif ($scope === 'expired') {
            $query->whereNotNull('ts.start_date')
                ->whereNotNull('ts.trainer_id')
                ->where(function ($builder) use ($expirySql, $remainingSql) {
                    $builder->whereRaw('NOW() > ' . $expirySql)->orWhereRaw($remainingSql . ' <= 0');
                });
        } elseif ($scope === 'history') {
            $query->whereDate('ts.start_date', '>=', $fromDate)
                ->whereDate('ts.start_date', '<=', $toDate);
        }

        if ($request->input('excel') === '1') {
            if ($scope !== 'history') {
                $query->whereDate('ts.created_at', '>=', $fromDate)
                    ->whereDate('ts.created_at', '<=', $toDate);
            }

            return Excel::download(
                new PtFreeSessionExport($query->orderByDesc('ts.id')->get()),
                'pt-free-' . $scope . ', ' . $fromDate . ' to ' . $toDate . '.xlsx'
            );
        }

        $trainerSessions = $query->orderByDesc('last_checkin.check_in_time')
            ->orderByDesc('ts.id')
            ->get();
        $birthdayMessages = [0 => [], 1 => [], 2 => []];
        foreach ($trainerSessions as $trainerSession) {
            $diff = BirthdayDiff($trainerSession->born);
            if ($diff >= 0 && $diff <= 2) {
                $birthdayMessages[$diff][$trainerSession->member_id] = $trainerSession->member_name;
            }
        }
        $contentByScope = [
            'waiting' => 'admin.pt-free.waiting-list',
            'expired' => 'admin.pt-free.expired',
            'history' => 'admin.pt-free.history',
        ];

        return view('admin.layouts.wrapper', [
            'title' => $title,
            'trainerSessions' => $trainerSessions,
            'fromDate' => $fromDate,
            'toDate' => $toDate,
            'scope' => $scope,
            'birthdayMessages' => $birthdayMessages,
            'paymentMessages' => [],
            'idCodeMaxCount' => env('ID_CODE_MAX_COUNT', 3),
            'content' => $contentByScope[$scope] ?? 'admin.pt-free.index',
        ]);
    }

    private function findBranchSession($id): TrainerSession
    {
        return TrainerSession::where('branch_store_id', Auth::user()->branch_store_id)
            ->where('is_pt_free', true)
            ->findOrFail($id);
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
