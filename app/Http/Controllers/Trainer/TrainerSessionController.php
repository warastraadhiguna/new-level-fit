<?php

namespace App\Http\Controllers\Trainer;

use App\Exports\TrainerSessionActiveExport;
use App\Http\Controllers\Controller;
use App\Models\BranchStore;
use App\Models\Member\Member;
use App\Models\Member\MemberRegistration;
use App\Models\MethodPayment;
use App\Models\Staff\PersonalTrainer;
use App\Models\Trainer\PtLeaveDay;
use App\Models\Trainer\TrainerPackage;
use App\Models\Trainer\TrainerSession;
use App\Models\Trainer\TrainerSessionPayment;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use RuntimeException;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Maatwebsite\Excel\Facades\Excel;

class TrainerSessionController extends Controller
{
    public function index()
    {
        $fromDate   = Request()->input('fromDate');
        $toDate     = Request()->input('toDate');

        $excel = Request()->input('excel');
        if ($excel && $excel == "1") {
            return Excel::download(new TrainerSessionActiveExport(), 'trainer-session-active, ' . $fromDate . ' to ' . $toDate . '.xlsx');
        }

        $trainerSessions = TrainerSession::getActivePTList();
        // dd($trainerSessions);

        $birthdayMessages = [
            0 => [],
            1 => [],
            2 => [],
        ];

        $expiredPaymentNumber = env("EXPIRED_PAYMENT_NUMBER", 7);
        $paymentMessages = [];
        foreach ($trainerSessions as $trainerSession) {
            $diff = BirthdayDiff($trainerSession->born);
            if ($diff >= 0 && $diff <= 2) {
                $birthdayMessages[$diff][$trainerSession->member_id] = $trainerSession->member_name;
            }

            $paymentDayDiff = PaymentExpiredDateDiff($trainerSession->start_date);
            $paymentDay = $paymentDayDiff->invert == 0 ? $paymentDayDiff->days : 0;
            if ($paymentDay < $expiredPaymentNumber && $trainerSession->payment_summary < ($trainerSession->ts_package_price + $trainerSession->ts_admin_price - ($trainerSession->ts_discount_amount ?? 0))) {
                $paymentMessages[$paymentDay][] =
                [
                    "message" => $trainerSession->member_name . " (". formatRupiah(($trainerSession->ts_package_price + $trainerSession->ts_admin_price - ($trainerSession->ts_discount_amount ?? 0)) - $trainerSession->payment_summary) . ")",
                    "id" => $trainerSession->id
                ];
            }
        }

        $idCodeMaxCount = env("ID_CODE_MAX_COUNT", 3);

        $data = [
            'title'             => 'Trainer Session List',
            'trainerSessions'   => $trainerSessions,
            'content'           => 'admin/trainer-session/index',
            'idCodeMaxCount'    =>  $idCodeMaxCount,
            'birthdayMessages'  => $birthdayMessages,
            'paymentMessages'       =>  $paymentMessages,
            'isUnpaidPage'      => false,
            'canMoveTrainerSessionBranch' => true,
        ];

        return view('admin.layouts.wrapper', $data);
    }

    public function pending()
    {
        $fromDate   = Request()->input('fromDate');
        $toDate     = Request()->input('toDate');

        $excel = Request()->input('excel');
        if ($excel && $excel == "1") {
            return Excel::download(new TrainerSessionActiveExport(), 'trainer-session-active, ' . $fromDate . ' to ' . $toDate . '.xlsx');
        }

        $trainerSessions = TrainerSession::getPendingPTList();

        $birthdayMessages = [
            0 => [],
            1 => [],
            2 => [],
        ];


        $expiredPaymentNumber = env("EXPIRED_PAYMENT_NUMBER", 7);
        $paymentMessages = [];
        foreach ($trainerSessions as $trainerSession) {
            $diff = BirthdayDiff($trainerSession->born);
            if ($diff >= 0 && $diff <= 2) {
                $birthdayMessages[$diff][$trainerSession->member_id] = $trainerSession->member_name;
            }

            $paymentDayDiff = PaymentExpiredDateDiff($trainerSession->start_date);
            $paymentDay = $paymentDayDiff->invert == 0 ? $paymentDayDiff->days : 0;
            if ($paymentDay < $expiredPaymentNumber && $trainerSession->payment_summary < ($trainerSession->ts_package_price + $trainerSession->ts_admin_price - ($trainerSession->ts_discount_amount ?? 0))) {
                $paymentMessages[$paymentDay][] =
                [
                    "message" => $trainerSession->member_name . " (". formatRupiah(($trainerSession->ts_package_price + $trainerSession->ts_admin_price - ($trainerSession->ts_discount_amount ?? 0)) - $trainerSession->payment_summary) . ")",
                    "id" => $trainerSession->id
                ];
            }
        }

        $idCodeMaxCount = env("ID_CODE_MAX_COUNT", 3);

        $data = [
            'title'             => 'Trainer Session Pending',
            'trainerSessions'   => $trainerSessions,
            'content'           => 'admin/trainer-session/index',
            'idCodeMaxCount'    =>  $idCodeMaxCount,
            'birthdayMessages'  => $birthdayMessages,
            'paymentMessages'   =>  $paymentMessages,
            'isUnpaidPage'      => false,
            'canMoveTrainerSessionBranch' => false,
        ];

        return view('admin.layouts.wrapper', $data);
    }

    public function unpaid()
    {
        $trainerSessions = TrainerSession::getUnpaidPTList(Auth::user()->branch_store_id);

        $birthdayMessages = [
            0 => [],
            1 => [],
            2 => [],
        ];

        foreach ($trainerSessions as $trainerSession) {
            $diff = BirthdayDiff($trainerSession->born);
            if ($diff >= 0 && $diff <= 2) {
                $birthdayMessages[$diff][$trainerSession->member_id] = $trainerSession->member_name;
            }
        }

        $idCodeMaxCount = env("ID_CODE_MAX_COUNT", 3);

        $data = [
            'title'             => 'PT Unpaid List',
            'trainerSessions'   => $trainerSessions,
            'content'           => 'admin/trainer-session/index',
            'idCodeMaxCount'    => $idCodeMaxCount,
            'birthdayMessages'  => $birthdayMessages,
            'paymentMessages'   => [],
            'isUnpaidPage'      => true,
            'canMoveTrainerSessionBranch' => false,
        ];

        return view('admin.layouts.wrapper', $data);
    }

    public function waitingList()
    {
        $trainerSessions = TrainerSession::getPTWaitingList();

        $birthdayMessages = [
            0 => [],
            1 => [],
            2 => [],
        ];


        $expiredPaymentNumber = env("EXPIRED_PAYMENT_NUMBER", 7);
        $paymentMessages = [];
        foreach ($trainerSessions as $trainerSession) {
            $diff = BirthdayDiff($trainerSession->born);
            if ($diff >= 0 && $diff <= 2) {
                $birthdayMessages[$diff][$trainerSession->member_id] = $trainerSession->member_name;
            }

            $paymentDayDiff = PaymentExpiredDateDiff($trainerSession->start_date);
            $paymentDay = $paymentDayDiff->invert == 0 ? $paymentDayDiff->days : 0;
            if ($paymentDay < $expiredPaymentNumber && $trainerSession->payment_summary < ($trainerSession->ts_package_price + $trainerSession->ts_admin_price - ($trainerSession->ts_discount_amount ?? 0))) {
                $paymentMessages[$paymentDay][] =
                [
                    "message" => $trainerSession->member_name . " (". formatRupiah(($trainerSession->ts_package_price + $trainerSession->ts_admin_price - ($trainerSession->ts_discount_amount ?? 0)) - $trainerSession->payment_summary) . ")",
                    "id" => $trainerSession->id
                ];
            }
        }

        $idCodeMaxCount = env("ID_CODE_MAX_COUNT", 3);

        $data = [
            'title'             => 'Trainer Session Waiting List',
            'trainerSessions'   => $trainerSessions,
            'content'           => 'admin/trainer-session/waiting-list',
            'idCodeMaxCount'    =>  $idCodeMaxCount,
            'birthdayMessages'  => $birthdayMessages,
            'paymentMessages'   =>  $paymentMessages,
            'isUnpaidPage'      => false,
        ];

        return view('admin.layouts.wrapper', $data);
    }    

    public function create()
    {
        $branchId = Auth::user()->branch_store_id;
                
        $data = [
            'title'             => 'New Trainer Session',
            'trainerSession'    => TrainerSession::all(),
            'members'           => GetAccessibleNonExpiredMembersForBranch($branchId),
            'personalTrainers'  => PersonalTrainer::where("branch_store_id", $branchId)->get(),
            'trainerPackages'   => TrainerPackage::where("branch_store_id", $branchId)->get(),
            'methodPayment'     => MethodPayment::get(),
            'users'             => User::get(),         
            'fitnessConsultant' => User::where('role', 'FC')->get(),
            'content'           => 'admin/trainer-session/create',
        ];

        return view('admin.layouts.wrapper', $data);
    }

    public function store(Request $request)
    {
        $fc = Auth::user();
        if ($fc->role == 'FC') {
            $data = $request->validate([
                'member_id'             => 'required|exists:members,id',
                'trainer_id'            => 'nullable|exists:personal_trainers,id',
                'start_date'            => 'nullable',
                'days'                  => 'nullable',
                'trainer_package_id'    => 'required|exists:trainer_packages,id',
                'method_payment_id'     => 'required|exists:method_payments,id',
                'first_payment'         => 'required|string',
                'payment_deadline'      => 'nullable|integer|min:0',
                'discount_amount'       => 'nullable|string',
                'user_id'               => 'nullable',
                'description'           => 'nullable',
                'branch_store_id'       => 'required',                        
            ]);
            $data['fc_id']      = $fc->id;
        } else {
            $data = $request->validate([
                'member_id'             => 'required|exists:members,id',
                'trainer_id'            => 'nullable|exists:personal_trainers,id',
                'start_date'            => 'nullable',
                'days'                  => 'nullable',
                'trainer_package_id'    => 'required|exists:trainer_packages,id',
                'method_payment_id'     => 'required|exists:method_payments,id',
                'first_payment'         => 'required|string',
                'payment_deadline'      => 'nullable|integer|min:0',
                'discount_amount'       => 'nullable|string',
                'fc_id'                 => 'nullable|exists:users,id',
                'user_id'               => 'nullable',
                'description'           => 'nullable',           
            ]);
        }

        DB::beginTransaction();
        try {

            $membership = GetLatestNonExpiredMembershipAccess($data['member_id'], '', Auth::user()->branch_store_id);
            if (!$membership) {
                DB::rollback();

                return redirect()->back()->with('errorr', 'Paket member telah expired atau belum dimulai!!');
            }

            if (MembershipHasOneClubBranchRestriction($membership, Auth::user()->branch_store_id)) {
                DB::rollback();

                return redirect()->back()->with('errorr', MembershipOneClubRestrictionMessage($membership->member_name, 'create PT'));
            }

            $package = TrainerPackage::findOrFail($data['trainer_package_id']);

            $data['user_id'] = Auth::user()->id;
            $data['branch_store_id'] = Auth::user()->branch_store_id;            
            if($data['start_date'])
            {
                $startTime = date('H:i:s', strtotime('00:00:00'));

                $data['start_date'] =  $data['start_date'] . ' ' .  $startTime;
                $dateTime = new \DateTime($data['start_date']);
                $data['start_date'] = $dateTime->format('Y-m-d H:i:s');
                unset($startTime);
            }

            $data['package_price'] = $package->package_price;
            $data['admin_price'] = $package->admin_price;
            $data['discount_amount'] = NormalizeSalesDiscount(
                $data['discount_amount'] ?? 0,
                'trainer',
                Auth::user()->branch_store_id
            );
            $data['days'] = $package->days;
            $data['number_of_session'] = $package->number_of_session;
            $firstPayment = (int) str_replace(".", "", $data['first_payment']);
            $data['payment_deadline'] = NormalizePaymentDeadline(
                $data['payment_deadline'] ?? 0,
                $firstPayment,
                (int) $data['package_price'] + (int) $data['admin_price'] - (int) $data['discount_amount'],
                Auth::user()->branch_store_id
            );
            unset($data['first_payment']);

            $newTrainerSession = TrainerSession::create($data);

            TrainerSessionPayment::create([
                "trainer_session_id" =>  $newTrainerSession->id,
                "user_id" =>  Auth::user()->id,
                "value" =>  $firstPayment,
                "note" =>  "First Payment",
                "method_payment_id" => $data['method_payment_id']
            ]);

            DB::commit();
            return redirect()->back()->with('success', 'Trainer Session Added Successfully');
        } catch (\Throwable $th) {
            DB::rollback();
            throw $th;
        }
    }

    public function show($id)
    {
        // dd($id);
        $ts = TrainerSession::find($id);
        $status = $ts->members;
        $memberId = $ts->members->id;

        if ($status == "one_day_visit") {
            // dd("Kondisi Pertama");
            $activePt = DB::table('trainer_sessions as a')
                ->select(
                    'a.id',
                    'a.start_date',
                    'a.description',
                    'a.days as ts_number_of_days',
                    'a.package_price as ts_package_price',
                    'a.admin_price as ts_admin_price',
                    'b.full_name as member_name',
                    'b.address',
                    'b.member_code',
                    'b.card_number',
                    'b.phone_number as member_phone',
                    'b.photos',
                    'b.gender',
                    'b.nickname',
                    'b.ig',
                    'b.emergency_contact',
                    'b.ec_name',
                    'b.email',
                    'b.born',
                    'c.package_name',
                    'c.number_of_session',
                    'c.days',
                    'c.package_price',
                    'c.status as train_pack_status',
                    'd.full_name as trainer_name',
                    'd.phone_number as trainer_phone',
                    'g.full_name as staff_name',
                    'h.full_name as fc_name',
                    'h.phone_number as fc_phone_number',
                    'i.name as method_payment_name',
                )
                ->addSelect(
                    DB::raw('DATE_ADD(a.start_date, INTERVAL a.days DAY) as expired_date'),
                    DB::raw('CASE WHEN NOW() > DATE_ADD(a.start_date, INTERVAL a.days DAY) THEN "Over" ELSE "Running" END as expired_date_status'),
                    DB::raw('IFNULL(c.number_of_session - e.check_in_count, c.number_of_session) as remaining_sessions'),
                    DB::raw('CASE WHEN IFNULL(c.number_of_session - e.check_in_count, c.number_of_session) > 0 THEN "Running" WHEN IFNULL(c.number_of_session - e.check_in_count, c.number_of_session) < 0 THEN "kelebihan" ELSE "over" END AS session_status')
                )
                ->leftJoin('members as b', 'a.member_id', '=', 'b.id')
                ->join('trainer_packages as c', 'a.trainer_package_id', '=', 'c.id')
                ->join('personal_trainers as d', 'a.trainer_id', '=', 'd.id')
                ->leftJoin(DB::raw('(SELECT trainer_session_id, COUNT(id) as check_in_count FROM check_in_trainer_sessions where check_out_time is not null GROUP BY trainer_session_id) as e'), 'e.trainer_session_id', '=', 'a.id')
                ->join('users as g', 'a.user_id', '=', 'g.id')
                ->leftJoin('fitness_consultants as h', 'a.fc_id', '=', 'h.id')
                ->join('method_payments as i', 'a.method_payment_id', '=', 'i.id')
                // ->whereRaw('CASE WHEN IFNULL(c.number_of_session - e.check_in_count, c.number_of_session) = 0 THEN "Running" WHEN IFNULL(c.number_of_session - e.check_in_count, c.number_of_session) < 0 THEN "kelebihan" ELSE "over" END = "Running"')
                ->whereIn('a.member_id', function ($query) use ($id) {
                    $query->select('member_id')->from('trainer_sessions')->where('id', $id);
                })
                ->get();
        } else {
            $activePt = TrainerSession::getActivePTListById($id);
            $pendingTrainerSession = TrainerSession::getPendingPT($memberId);
            $expiredTrainerSession = TrainerSession::getExpiredTrainerSession($memberId);
            // dd($expiredTrainerSession);
            // dd($expiredTrainerSession);
        }

        $trainerSessions = TrainerSession::find($id);

        $totalSessions = $trainerSessions->trainerPackages->number_of_session;

        $checkInTrainerSession = $trainerSessions->trainerSessionCheckIn;

        $checkInCount = $checkInTrainerSession->count();

        $remainingSessions = $totalSessions - $checkInCount;

        $data = [
            'title'                 => 'Trainer Session Detail',
            'checkInTrainerSession' => $checkInTrainerSession,
            'trainerSession'        => $trainerSessions,
            'members'               => Member::get(),
            'pendingTrainerSession' => $pendingTrainerSession,
            'expiredTrainerSession' => $expiredTrainerSession,
            'query'                 => $activePt,
            'totalSessions'         => $totalSessions,
            'remainingSessions'     => $remainingSessions,
            'content'               => 'admin/trainer-session/show',
        ];

        return view('admin.layouts.wrapper', $data);
    }

    public function edit(string $id)
    {
        $branchId = Auth::user()->branch_store_id;
        $trainerSession = TrainerSession::with([
            'members',
            'personalTrainers',
            'trainerPackages',
            'fitnessConsultants',
            'methodPayment',
        ])->findOrFail($id);
                
        $data = [
            'title'                 => 'Edit Trainer Session',
            'trainerSession'        => $trainerSession,
            'trainerSessionPayments' => TrainerSessionPayment::with("user", "methodPayment")->where("trainer_session_id", $id)->get(),
            'members'               => GetAccessibleNonExpiredMembersForBranch($branchId),
            'personalTrainers'      => PersonalTrainer::where("branch_store_id", $branchId)->get(),
            'trainerPackages'       => TrainerPackage::where("branch_store_id", $branchId)->get(),
            'fitnessConsultant'     => User::where('role', 'FC')->get(),
            'methodPayment'         => MethodPayment::get(),          
            'content'               => 'admin/trainer-session/edit',
        ];
        return view('admin.layouts.wrapper', $data);
    }

    public function moveBranchPage(string $id)
    {
        if (Auth::user()->role !== 'ADMIN') {
            abort(403);
        }

        $trainerSession = TrainerSession::with([
            'members',
            'personalTrainers',
            'trainerPackages.branchStore',
            'branchStore',
        ])->findOrFail($id);

        $membership = GetLatestNonExpiredMembershipAccess($trainerSession->member_id);

        $allTargetTrainerPackages = TrainerPackage::with('branchStore')
            ->whereNull('status')
            ->where('branch_store_id', '!=', $trainerSession->branch_store_id)
            ->orderBy('branch_store_id')
            ->orderBy('package_name')
            ->get();

        $targetTrainerPackages = $allTargetTrainerPackages
            ->filter(function ($trainerPackage) use ($membership) {
                return !$membership || !MembershipHasOneClubBranchRestriction($membership, $trainerPackage->branch_store_id);
            })
            ->values();

        $moveBranchUnavailableReason = null;
        if ($targetTrainerPackages->isEmpty()) {
            if ($allTargetTrainerPackages->isEmpty()) {
                $moveBranchUnavailableReason = 'No regular PT package is available in another branch.';
            } elseif ($membership && (string) ($membership->is_all_club ?? '1') === '0') {
                $membershipBranch = BranchStore::find($membership->member_package_branch_store_id);
                $allowedBranchName = $membershipBranch ? $membershipBranch->name : 'the membership branch';
                $moveBranchUnavailableReason = 'This member has a One Club membership, so PT can only be assigned to ' . $allowedBranchName . '.';
            } else {
                $moveBranchUnavailableReason = 'No eligible PT package is available in another branch for this member.';
            }
        }

        $data = [
            'title' => 'Move PT Registration Branch',
            'trainerSession' => $trainerSession,
            'targetTrainerPackages' => $targetTrainerPackages,
            'moveBranchUnavailableReason' => $moveBranchUnavailableReason,
            'content' => 'admin/trainer-session/move-branch',
        ];

        return view('admin.layouts.wrapper', $data);
    }

    public function update(Request $request, string $id)
    {
        $item = TrainerSession::findOrFail($id);
        $data = $request->validate([
            'start_date'            => 'nullable',
            'expired_date'          => 'nullable',
            'description'           => 'nullable',
            'trainer_package_id'    => 'required|exists:trainer_packages,id',
            'trainer_id'            => 'nullable|exists:personal_trainers,id',
            'method_payment_id'     => 'nullable|exists:method_payments,id',
            'fc_id'                 => 'nullable|exists:users,id',
            'discount_amount'       => 'nullable|string',
            'payment_deadline'      => 'nullable|integer|min:0',
        ]);
        $data['user_id'] = Auth::user()->id;
        $data['branch_store_id'] = Auth::user()->branch_store_id;       

        $selectedPackage = TrainerPackage::findOrFail($data["trainer_package_id"]);
        $currentPackage = TrainerPackage::find($item->trainer_package_id);
        if (BranchStoreDiscountIsEnabled('trainer', Auth::user()->branch_store_id)) {
            $data['discount_amount'] = NormalizeSalesDiscount(
                $data['discount_amount'] ?? 0,
                'trainer',
                Auth::user()->branch_store_id
            );
        } else {
            unset($data['discount_amount']);
        }
        $data['trainer_id'] = $data['trainer_id'] ?: null;
        $data['start_date'] = $data['start_date'] ?: null;

        if (!$currentPackage || $selectedPackage->id !== $currentPackage->id) {
            $data['days'] = $selectedPackage->days;
            $data['package_price'] = $selectedPackage->package_price;
            $data['admin_price'] = $selectedPackage->admin_price;
            $data['number_of_session'] = $selectedPackage->number_of_session;
        }

        $paidAmount = TrainerSessionPayment::where('trainer_session_id', $item->id)->sum('value');
        $totalPrice = (int) ($data['package_price'] ?? $item->package_price)
            + (int) ($data['admin_price'] ?? $item->admin_price)
            - (int) ($data['discount_amount'] ?? $item->discount_amount);
        $data['payment_deadline'] = NormalizePaymentDeadline(
            $data['payment_deadline'] ?? 0,
            $paidAmount,
            $totalPrice,
            Auth::user()->branch_store_id
        );

        if ($data['start_date']) {
            $startTime = date('H:i:s', strtotime('00:00:00'));
            $data['start_date'] = $data['start_date'] . ' ' .  $startTime;
            $dateTime = new \DateTime($data['start_date']);
            $data['start_date'] = $dateTime->format('Y-m-d H:i:s');

            if ($request->filled('expired_date')) {
                $expiredDate = new \DateTime($request->input('expired_date'));
                $data['days'] = $expiredDate->diff($dateTime)->days;
            }
        }

        unset($data['expired_date']);

        $item->update($data);

        if (!$data['trainer_id'] || !$data['start_date']) {
            return redirect()->route('trainer-session-waiting-list')->with('success', 'Trainer Session Updated Successfully');
        }

        return redirect()->route('trainer-session.index')->with('success', 'Trainer Session Updated Successfully');
    }

    public function moveBranch(Request $request, string $id)
    {
        if (Auth::user()->role !== 'ADMIN') {
            abort(403);
        }

        $data = $request->validate([
            'trainer_package_id' => 'required|exists:trainer_packages,id',
        ]);

        try {
            $hasPriceDifference = false;

            DB::transaction(function () use ($id, $data, &$hasPriceDifference) {
                $trainerSession = TrainerSession::with(['branchStore', 'trainerPackages'])
                    ->lockForUpdate()
                    ->findOrFail($id);
                $trainerPackage = TrainerPackage::with('branchStore')->findOrFail($data['trainer_package_id']);

                if ($trainerPackage->status !== null) {
                    throw new RuntimeException('Only regular PT packages can be used to move this PT registration.');
                }

                if ((int) $trainerPackage->branch_store_id === (int) $trainerSession->branch_store_id) {
                    throw new RuntimeException('Please choose a PT package from another branch.');
                }

                $membership = GetLatestNonExpiredMembershipAccess($trainerSession->member_id);
                if ($membership && MembershipHasOneClubBranchRestriction($membership, $trainerPackage->branch_store_id)) {
                    throw new RuntimeException(MembershipOneClubRestrictionMessage($membership->member_name, 'move PT'));
                }

                $currentBranchName = data_get($trainerSession, 'branchStore.name', 'Unknown branch');
                $currentPackageName = data_get($trainerSession, 'trainerPackages.package_name', 'Unknown PT package');
                $targetBranchName = data_get($trainerPackage, 'branchStore.name', 'Unknown branch');
                $targetPackageName = $trainerPackage->package_name ?: 'Unknown PT package';

                $moveDescription = '[MOVE_BRANCH] Data dipindahkan dari ' . $currentBranchName . ' - ' . $currentPackageName
                    . ' ke ' . $targetBranchName . ' - ' . $targetPackageName
                    . ' oleh ' . Auth::user()->full_name;

                $existingDescription = trim((string) $trainerSession->description);
                $updatedDescription = $existingDescription
                    ? $existingDescription . PHP_EOL . $moveDescription
                    : $moveDescription;

                $hasPriceDifference = (int) $trainerSession->package_price !== (int) $trainerPackage->package_price
                    || (int) $trainerSession->admin_price !== (int) $trainerPackage->admin_price;

                $trainerSession->update([
                    'trainer_package_id' => $trainerPackage->id,
                    'branch_store_id' => $trainerPackage->branch_store_id,
                    'description' => $updatedDescription,
                    'user_id' => Auth::user()->id,
                ]);
            });

            $message = 'PT registration branch moved successfully.';
            if ($hasPriceDifference) {
                $message .= ' Target package price is different, but current PT billing and payments were kept unchanged.';
            }

            return redirect()->route('trainer-session.index')->with('success', $message);
        } catch (RuntimeException $exception) {
            return redirect()->back()->with('errorr', $exception->getMessage());
        }
    }

    public function freeze(Request $request, string $id)
    {
         $item = TrainerSession::find($id);
        if (!$item) {
            return redirect()->route('trainer-session.index')->with('errorr', 'Trainer Session not found');
        }

        $leaveDay = new PtLeaveDay([
            'trainer_session_id'        => $item->id,
            'submission_date'           => Carbon::now()->tz('Asia/Jakarta'),
            'price'                     => $request->input('price'),
            'days'                      => $request->input('expired_date'),
        ]);
        $leaveDay->price = str_replace(',', '', $leaveDay['price']);
        $leaveDay->save();

        return redirect()->route('trainer-session.index')->with('success', 'Cuti PT Successfully Added');
    }

    public function destroy(TrainerSession $trainerSession)
    {
        try {
            $trainerSession->delete();
            return redirect()->back()->with('success', 'Trainer Session Deleted Successfully');
        } catch (\Throwable $th) {
            return redirect()->back()->with('errorr', 'Deleted Failed, please delete check in first');
        }
    }

    public function cetak_pdf()
    {
        $trainerSessions = DB::table('trainer_sessions as a')
            ->select(
                'a.id',
                'a.start_date',
                'a.description',
                'a.days',
                'b.full_name as member_name',
                'b.member_code',
                'c.package_name',
                'c.number_of_session',
                'c.package_price',
                'd.full_name as trainer_name',
                'g.full_name as staff_name',
                'h.full_name as fc_name',
                'h.phone_number as fc_phone_number',
                'i.name as method_payment_name',
            )
            ->addSelect(
                DB::raw('DATE_ADD(a.start_date, INTERVAL a.days DAY) as expired_date'),
                DB::raw('CASE WHEN NOW() > DATE_ADD(a.start_date, INTERVAL a.days DAY) THEN "Over" ELSE "Running" END as expired_date_status')
            )
            ->join('members as b', 'a.member_id', '=', 'b.id')
            ->join('trainer_packages as c', 'a.trainer_package_id', '=', 'c.id')
            ->join('personal_trainers as d', 'a.trainer_id', '=', 'd.id')
            ->join('users as g', 'a.user_id', '=', 'g.id')
            ->leftJoin('fitness_consultants as h', 'a.fc_id', '=', 'h.id')
            ->join('method_payments as i', 'a.method_payment_id', '=', 'i.id')
            ->leftJoin(DB::raw('(SELECT trainer_session_id, COUNT(id) as check_in_count FROM check_in_trainer_sessions GROUP BY trainer_session_id) as e'), 'e.trainer_session_id', '=', 'a.id')
            ->addSelect(DB::raw('IFNULL(c.number_of_session - e.check_in_count, c.number_of_session) as remaining_sessions'))
            ->addSelect(DB::raw('CASE WHEN IFNULL(c.number_of_session - e.check_in_count, c.number_of_session) > 0 THEN "Running" WHEN IFNULL(c.number_of_session - e.check_in_count, c.number_of_session) < 0 THEN "kelebihan" ELSE "over" END AS session_status'))
            ->whereRaw('CASE WHEN IFNULL(c.number_of_session - e.check_in_count, c.number_of_session) > 0 THEN "Running" WHEN IFNULL(c.number_of_session - e.check_in_count, c.number_of_session) < 0 THEN "kelebihan" ELSE "over" END = "Running"')
            ->get();

        $pdf = Pdf::loadView('admin/trainer-session/trainer-session-pdf', [
            'trainerSessions'        => $trainerSessions,
        ])->setPaper('a4', 'landscape');
        return $pdf->stream('trainer-session-report.pdf');
    }

    public function agreement($id)
    {
        $trainerSession = TrainerSession::agreement($id);
        // dd($trainerSession[0]);
        // dd($trainerSession);
        // foreach ($trainerSession as $item) {
        //     $result = $item->id;
        // }

        // $fileName1 = $trainerSession->member_name;
        // $fileName2 = $trainerSession->start_date;

        $pdf = Pdf::loadView('admin/trainer-session/agreement', [
            'trainerSession'        => $trainerSession[0],
        ]);
        return $pdf->stream('PT Agreement-.pdf');
    }

    public function cuti($id)
    {
        $trainerSession = TrainerSession::getActivePTListById($id);
        // dd($trainerSession[0]);

        $fileName1 = $trainerSession[0]->member_name;
        $fileName2 = $trainerSession[0]->start_date;

        $pdf = Pdf::loadView('admin/trainer-session/cuti', [
            'trainerSession'        => $trainerSession[0],
        ]);
        return $pdf->stream('Cuti Trainer Session -' . $fileName1 . '-' . $fileName2 . '.pdf');
    }

    public function listCuti($id)
    {
        $trainerSession = TrainerSession::getActivePTList();

        $fileName1 = $trainerSession[0]->member_name;
        $fileName2 = $trainerSession[0]->start_date;

        $pdf = Pdf::loadView('admin/trainer-session/cuti', [
            'trainerSession'        => $trainerSession,
        ]);
        return $pdf->stream('Cuti Trainer Session -' . $fileName1 . '-' . $fileName2 . '.pdf');
    }

    public function history()
    {
        $fromDate   = Request()->input('fromDate');
        $fromDate  = $fromDate ? DateFormat($fromDate) : NowDate();

        $toDate     = Request()->input('toDate');
        $toDate = $toDate ? DateFormat($toDate) : NowDate();

        $trainerSessions = TrainerSession::history("", "", $fromDate, $toDate);
        // dd($trainerSessions);

        $idCodeMaxCount = env("ID_CODE_MAX_COUNT", 3);
        $data = [
            'title'                 => 'PT History',
            'fromDate'              => $fromDate,
            'toDate'                => $toDate,
            'trainerSessions'       => $trainerSessions,
            'content'               => 'admin/trainer-session/history',
            'idCodeMaxCount'        => $idCodeMaxCount,
        ];

        return view('admin.layouts.wrapper', $data);
    }

    public function historyDetail($id)
    {
        $ts = TrainerSession::find($id);
        $status = $ts->members;
        $memberId = $ts->members->id;

        $activePt = TrainerSession::historyById($id);

        $trainerSessions = TrainerSession::find($id);

        $totalSessions = $trainerSessions->trainerPackages->number_of_session;

        $checkInTrainerSession = $trainerSessions->trainerSessionCheckIn;

        $checkInCount = $checkInTrainerSession->count();

        $remainingSessions = $totalSessions - $checkInCount;

        $data = [
            'title'                 => 'Trainer Session Detail',
            'checkInTrainerSession' => $checkInTrainerSession,
            'trainerSession'        => $trainerSessions,
            'members'               => Member::get(),
            'query'                 => $activePt,
            'totalSessions'         => $totalSessions,
            'remainingSessions'     => $remainingSessions,
            'content'               => 'admin/trainer-session/detail-history-check-in',
        ];

        return view('admin.layouts.wrapper', $data);
    }
}
