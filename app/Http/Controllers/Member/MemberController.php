<?php

namespace App\Http\Controllers\Member;

use App\Exports\MemberExport;
use App\Http\Controllers\Controller;
use App\Models\Member\Member;
use App\Models\Member\MemberPackage;
use App\Models\Member\MemberRegistration;
use App\Models\MethodPayment;
use App\Models\Staff\FitnessConsultant;
use App\Models\Staff\PersonalTrainer;
use App\Models\Trainer\TrainerSession;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Exception;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Imports\HeadingRowFormatter;

HeadingRowFormatter::default('none');

use Maatwebsite\Excel\Facades\Excel;

class MemberController extends Controller
{
    public function index(Request $request)
    {
        $branchId  = Auth::user()->branch_store_id;
        $fromDate   = Request()->input('fromDate');
        $toDate     = Request()->input('toDate');
        $search     = trim((string) $request->input('search', ''));
        $perPage    = (int) $request->input('per_page', 10);
        $perPage    = in_array($perPage, [10, 25, 50, 100]) ? $perPage : 10;
        $sort       = (string) $request->input('sort', 'created_at');
        $direction  = strtolower((string) $request->input('direction', 'desc')) == 'asc' ? 'asc' : 'desc';
        $sortableColumns = [
            'full_name' => 'a.full_name',
            'member_code' => 'a.member_code',
            'branch' => 'branch_stores.name',
        ];
        $sortColumn = $sortableColumns[$sort] ?? 'a.created_at';

        $excel = Request()->input('excel');
        if ($excel && $excel == "1") {
            return Excel::download(new MemberExport(), 'Members, ' . $fromDate . ' to ' . $toDate . '.xlsx');
        }

        // -- LO
        //     CASE WHEN NOW() < DATE_ADD(mbr.created_at, INTERVAL mbr.lo_days DAY) THEN 'Running'
        //         ELSE 'Over'
        //         END as lo_status,

        $sell = DB::table('members as a')
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
                'a.small_photos',
                'a.lo_is_used',
                'a.lo_start_date',
                'a.lo_days',
                'a.lo_pt_by',
                'a.lo_end',
                'a.created_at',
                'branch_stores.name as branch_store_name',
                DB::raw("CASE WHEN NOW() < DATE_ADD(a.created_at, INTERVAL a.lo_days DAY) THEN 'Running' ELSE 'Over' END as lo_status")
            )
            ->join("branch_stores", "a.branch_store_id", "=", "branch_stores.id")
            ->where('a.status', '=', 'sell')
            ->where('a.branch_store_id', $branchId)
            ->when($search, function ($query) use ($search) {
                $query->where(function ($q) use ($search) {
                    $q->where('a.full_name', 'like', "%{$search}%")
                        ->orWhere('a.nickname', 'like', "%{$search}%")
                        ->orWhere('a.member_code', 'like', "%{$search}%")
                        ->orWhere('a.card_number', 'like', "%{$search}%")
                        ->orWhere('a.phone_number', 'like', "%{$search}%")
                        ->orWhere('branch_stores.name', 'like', "%{$search}%");
                });
            })
            ->orderBy($sortColumn, $direction)
            ->orderBy('a.updated_at', 'desc')
            ->paginate($perPage)
            ->appends($request->only(['search', 'per_page', 'sort', 'direction']));

        $data = [
            'title'             => 'Member List',
            'members'           => $sell,
            'search'            => $search,
            'perPage'           => $perPage,
            'sort'              => $sort,
            'direction'         => $direction,
            // 'users'             => User::get(),
            'content'           => 'admin/members/index'
        ];

        return view('admin.layouts.wrapper', $data);
    }

    public function dayVisit()
    {
        $memberRegistrations = DB::table('member_registrations as a')
            ->select(
                'a.id',
                'a.start_date',
                'a.description',
                'a.days as member_registration_days',
                'a.old_days',
                'a.package_price as mr_package_price',
                'a.admin_price as mr_admin_price',
                'a.updated_at',
                'b.id as member_id',
                'b.full_name as member_name',
                'b.phone_number',
                'c.package_name',
                'c.days',
                'c.package_price',
                'e.name as method_payment_name',
                'f.full_name as staff_name',
            )
            ->addSelect(
                // DB::raw('DATE_ADD(a.start_date, INTERVAL COALESCE(ld.days, 0) + a.days DAY) as expired_date'),
                DB::raw('CASE 
                    WHEN NOW() > DATE_ADD(a.start_date, INTERVAL a.days DAY) THEN "Over" 
                    WHEN NOW() BETWEEN a.start_date AND DATE_ADD(a.start_date, INTERVAL a.days DAY) THEN "Running" 
                    ELSE "Not Started" 
                END as status')
            )
            ->join('members as b', 'a.member_id', '=', 'b.id')
            ->join('member_packages as c', 'a.member_package_id', '=', 'c.id')
            ->join('method_payments as e', 'a.method_payment_id', '=', 'e.id')
            ->join('users as f', 'a.user_id', '=', 'f.id')
            ->whereRaw('NOW() BETWEEN a.start_date AND DATE_ADD(a.start_date, INTERVAL a.days DAY)')
            //         ->where('a.status', '=', 'one_day_visit')
            //         ->orderBy('created_at', 'desc')
            ->get();

        $data = [
            'title'                 => '1 Day Visit',
            'memberRegistrations'   => $memberRegistrations,
            'content'               => 'admin/one-visit/index'
        ];

        return view('admin.layouts.wrapper', $data);
    }

    public function create()
    {
        return Excel::download(new MemberExport(), 'members.xlsx');
    }

    public function store(Request $request)
    {
        // 
    }

    public function edit(string $id)
    {
        $data = [
            'title'                 => 'Edit Missed Guest',
            'members'               => Member::find($id),
            'memberLastCode'        => Member::latest('id')->first(),
            'memberPackage'         => MemberPackage::visibleToUser(Auth::user())->get(),
            'methodPayment'         => MethodPayment::get(),
            'fitnessConsultant'     => User::where('role', 'FC')->get(),
            'content'               => 'admin/members/edit',
        ];

        return view('admin.layouts.wrapper', $data);
    }

    public function secondEdit($id)
    {
        $data = [
            'title'                 => 'Edit Member',
            'members'               => Member::find($id),
            'memberLastCode'        => Member::latest('id')->first(),
            'memberPackage'         => MemberPackage::visibleToUser(Auth::user())->get(),
            'methodPayment'         => MethodPayment::get(),
            'fitnessConsultant'     => FitnessConsultant::get(),
            'content'               => 'admin/members/second-edit',
        ];

        return view('admin.layouts.wrapper', $data);
    }

    public function secondUpdate(Request $request, $id)
    {
        $item = Member::find($id);
        $oldSmallPhoto = null;
        $data = $request->validate([
            'full_name'             => 'nullable',
            'nickname'              => 'nullable',
            'member_code'           => 'nullable',
            'card_number'           => 'nullable',
            'gender'                => 'nullable',
            'born'                  => 'nullable',
            'phone_number'          => 'nullable',
            'email'                 => 'nullable',
            'ig'                    => 'nullable',
            'emergency_contact'     => 'nullable',
            'ec_name'               => 'nullable',
            'address'               => 'nullable',
            'photos'                => 'nullable|image|mimes:jpeg,png,jpg,gif,svg|max:1048',
            'status'                => 'nullable',
        ]);

        $data['born'] = Carbon::parse($data['born'])->format('Y-m-d');

        if ($request->hasFile('photos')) {
            $oldSmallPhoto = $item->small_photos;

            if ($item->photos != null) {
                $realLocation = "storage/" . $item->photos;
                if (file_exists($realLocation) && !is_dir($realLocation)) {
                    unlink($realLocation);
                }
            }

            $photos = $request->file('photos');
            $file_name = time() . '-' . $photos->getClientOriginalName();

            $data['photos'] = $request->file('photos')->store('assets/member', 'public');
            $data['small_photos'] = null;
        } else {
            $data['photos'] = $item->photos;
        }


        $item->update($data);

        if ($oldSmallPhoto) {
            Storage::disk('public')->delete($oldSmallPhoto);
        }

        return redirect()->route('members.index')->with('success', 'Member Updated Successfully');
    }

    public function update(Request $request, string $id)
    {
        $fc = Auth::user();
        // dd($fc->role);
        DB::beginTransaction();
        try {
            $member = Member::findOrFail($id);
            $oldSmallPhoto = null;

            if ($fc->role == 'FC') {
                $data = $request->validate([
                    'full_name'             => 'required',
                    'phone_number'          => 'required',
                    'status'                => 'required',
                    'nickname'              => 'nullable',
                    'born'                  => 'nullable',
                    'email'                 => 'nullable',
                    'ig'                    => 'nullable',
                    'emergency_contact'     => 'nullable',
                    'ec_name'               => 'nullable',
                    'gender'                => 'nullable',
                    'address'               => 'nullable',
                    'photos'                => 'nullable|image|mimes:jpeg,png,jpg,gif,svg|max:2048',
                    'member_package_id'     => 'required|exists:member_packages,id',
                    'start_date'            => 'required',
                    'start_time'            => 'required',
                    'method_payment_id'     => 'required|exists:method_payments,id',
                    'description'           => 'nullable',
                    'member_code' => [
                        'nullable',
                        function ($attribute, $value, $fail) use ($id) {
                            if ($value) {
                                $exists = Member::where('member_code', $value)->where('id', '!=', $id)->exists();
                                if ($exists) {
                                    $fail('The member number has already been taken.');
                                }
                            }
                        }
                    ],
                    'card_number' => [
                        'nullable',
                        function ($attribute, $value, $fail) use ($id) {
                            if ($value) {
                                $exists = Member::where('card_number', $value)->where('id', '!=', $id)->exists();
                                if ($exists) {
                                    $fail('The card number has already been taken.');
                                }
                            }
                        }
                    ],
                ]);
                $data['fc_id']  = Auth::user()->id;
            } else {
                $data = $request->validate([
                    'full_name'             => 'required',
                    'phone_number'          => 'required',
                    'status'                => 'required',
                    'nickname'              => 'nullable',
                    'born'                  => 'nullable',
                    'email'                 => 'nullable',
                    'ig'                    => 'nullable',
                    'emergency_contact'     => 'nullable',
                    'ec_name'               => 'nullable',
                    'gender'                => 'nullable',
                    'address'               => 'nullable',
                    'photos'                => 'nullable|image|mimes:jpeg,png,jpg,gif,svg|max:2048',
                    'member_package_id'     => 'required|exists:member_packages,id',
                    'start_date'            => 'required',
                    'start_time'            => 'required',
                    'method_payment_id'     => 'required|exists:method_payments,id',
                    'fc_id'                 => 'exists:users,id',
                    'description'           => 'nullable',
                    'member_code' => [
                        'nullable',
                        function ($attribute, $value, $fail) use ($id) {
                            if ($value) {
                                $exists = Member::where('member_code', $value)->where('id', '!=', $id)->exists();
                                if ($exists) {
                                    $fail('The member number has already been taken.');
                                }
                            }
                        }
                    ],
                    'card_number' => [
                        'nullable',
                        function ($attribute, $value, $fail) use ($id) {
                            if ($value) {
                                $exists = Member::where('card_number', $value)->where('id', '!=', $id)->exists();
                                if ($exists) {
                                    $fail('The card number has already been taken.');
                                }
                            }
                        }
                    ],
                ]);
            }

            if ($request->hasFile('photos')) {
                $oldSmallPhoto = $member->small_photos;

                if ($request->photos != null) {
                    $realLocation = "storage/" . $request->photos;
                    if (file_exists($realLocation) && !is_dir($realLocation)) {
                        unlink($realLocation);
                    }
                }

                $photos = $request->file('photos');
                $file_name = time() . '-' . $photos->getClientOriginalName();

                $data['photos'] = $request->file('photos')->store('assets/member', 'public');
                $data['small_photos'] = null;
            } else {
                $data['photos'] = $request->photos;
            }

            $data['born'] = Carbon::parse($data['born'])->format('Y-m-d');

            $package = MemberPackage::findOrFail($data['member_package_id'])
                ->ensureAssignableBy(Auth::user());
            $data['package_price'] = $package->package_price;

            $data['user_id'] = Auth::user()->id;

            $data['start_date'] =  $data['start_date'] . ' ' .  $data['start_time'];
            $dateTime = new \DateTime($data['start_date']);
            $data['start_date'] = $dateTime->format('Y-m-d H:i:s');
            unset($data['start_time']);

            $data['admin_price'] = $package->admin_price;
            $data['days'] = $package->days;

            // Perbarui data anggota
            $member->update(array_intersect_key($data, array_flip([
                'full_name', 'phone_number', 'status', 'nickname',
                'born', 'member_code', 'card_number', 'email', 'ig', 'emergency_contact', 'ec_name', 'gender', 'address', 'photos', 'small_photos'
            ])));

            // Buat atau perbarui data pendaftaran anggota
            $registrationData = array_intersect_key($data, array_flip([
                'member_package_id', 'start_date',
                'method_payment_id', 'fc_id', 'user_id', 'description', 'package_price', 'admin_price', 'days'
            ]));
            if ($member->registration) {
                $member->registration->update($registrationData);
            } else {
                MemberRegistration::create(array_merge(['member_id' => $member->id], $registrationData));
            }

            DB::commit();

            if ($oldSmallPhoto) {
                Storage::disk('public')->delete($oldSmallPhoto);
            }

            return redirect()->route('members.index')->with('success', 'Member Missed Guest Updated Successfully');
        } catch (Exception $e) {
            DB::rollback();
            throw $e;
        }
    }

    public function show($id)
    {
        $data = [
            'title'     => 'Detail Member Registration',
            'members'   => Member::find($id),
            'content'   => 'admin/members/detail',
        ];

        return view('admin.layouts.wrapper', $data);
    }

    public function membershipHistory(Member $member)
    {
        $paymentSummary = DB::table('member_registration_payments')
            ->selectRaw('member_registration_id, SUM(value) as paid_amount')
            ->groupBy('member_registration_id');
        $freezeSummary = DB::table('leave_days')
            ->selectRaw('member_registration_id, SUM(days) as freeze_days')
            ->groupBy('member_registration_id');
        $checkInSummary = DB::table('check_in_members')
            ->selectRaw('member_registration_id, COUNT(*) as workout_count')
            ->groupBy('member_registration_id');

        $histories = DB::table('member_registrations as mr')
            ->select(
                'mr.id',
                'mr.start_date',
                'mr.days',
                'mr.package_price',
                'mr.admin_price',
                'mr.discount_amount',
                'mr.description',
                'mr.created_at',
                'mp.package_name',
                'mp.is_all_club',
                'bs.name as branch_store_name',
                'methods.name as payment_method_name',
                'users.full_name as staff_name',
                DB::raw('COALESCE(payments.paid_amount, 0) as paid_amount'),
                DB::raw('COALESCE(freezes.freeze_days, 0) as freeze_days'),
                DB::raw('COALESCE(check_ins.workout_count, 0) as workout_count'),
                DB::raw('DATE_ADD(mr.start_date, INTERVAL (mr.days + COALESCE(freezes.freeze_days, 0)) DAY) as expired_date'),
                DB::raw("CASE
                    WHEN NOW() < mr.start_date THEN 'Not Started'
                    WHEN NOW() > DATE_ADD(mr.start_date, INTERVAL (mr.days + COALESCE(freezes.freeze_days, 0)) DAY) THEN 'Expired'
                    ELSE 'Active'
                END as membership_status")
            )
            ->leftJoin('member_packages as mp', 'mr.member_package_id', '=', 'mp.id')
            ->leftJoin('branch_stores as bs', 'mp.branch_store_id', '=', 'bs.id')
            ->leftJoin('method_payments as methods', 'mr.method_payment_id', '=', 'methods.id')
            ->leftJoin('users', 'mr.user_id', '=', 'users.id')
            ->leftJoinSub($paymentSummary, 'payments', function ($join) {
                $join->on('mr.id', '=', 'payments.member_registration_id');
            })
            ->leftJoinSub($freezeSummary, 'freezes', function ($join) {
                $join->on('mr.id', '=', 'freezes.member_registration_id');
            })
            ->leftJoinSub($checkInSummary, 'check_ins', function ($join) {
                $join->on('mr.id', '=', 'check_ins.member_registration_id');
            })
            ->where('mr.member_id', $member->id)
            ->where('mr.days', '>', 1)
            ->orderByDesc('mr.start_date')
            ->orderByDesc('mr.id')
            ->paginate(10);

        return view('admin.layouts.wrapper', [
            'title' => 'Membership History',
            'member' => $member->load('branchStore'),
            'histories' => $histories,
            'totalWorkouts' => (int) DB::table('check_in_members')
                ->join('member_registrations', 'check_in_members.member_registration_id', '=', 'member_registrations.id')
                ->where('member_registrations.member_id', $member->id)
                ->where('member_registrations.days', '>', 1)
                ->count(),
            'content' => 'admin.members.membership-history',
        ]);
    }

    public function ptHistory(Member $member)
    {
        $paymentSummary = DB::table('trainer_session_payments')
            ->selectRaw('trainer_session_id, SUM(value) as paid_amount')
            ->groupBy('trainer_session_id');
        $freezeSummary = DB::table('pt_leave_days')
            ->selectRaw('trainer_session_id, SUM(days) as freeze_days')
            ->groupBy('trainer_session_id');
        $checkInSummary = DB::table('check_in_trainer_sessions')
            ->selectRaw('trainer_session_id, COUNT(*) as check_in_count, SUM(CASE WHEN check_out_time IS NOT NULL THEN 1 ELSE 0 END) as used_sessions')
            ->groupBy('trainer_session_id');

        $histories = DB::table('trainer_sessions as ts')
            ->select(
                'ts.id',
                'ts.start_date',
                'ts.days',
                'ts.number_of_session',
                'ts.package_price',
                'ts.admin_price',
                'ts.discount_amount',
                'ts.description',
                'ts.is_pt_free',
                'ts.created_at',
                'tp.package_name',
                'trainers.full_name as trainer_name',
                'bs.name as branch_store_name',
                'methods.name as payment_method_name',
                'users.full_name as staff_name',
                DB::raw('COALESCE(payments.paid_amount, 0) as paid_amount'),
                DB::raw('COALESCE(freezes.freeze_days, 0) as freeze_days'),
                DB::raw('COALESCE(check_ins.used_sessions, 0) as used_sessions'),
                DB::raw('COALESCE(check_ins.check_in_count, 0) as check_in_count'),
                DB::raw('GREATEST(ts.number_of_session - COALESCE(check_ins.used_sessions, 0), 0) as unused_sessions'),
                DB::raw('DATE_ADD(ts.start_date, INTERVAL (ts.days + COALESCE(freezes.freeze_days, 0)) DAY) as expired_date'),
                DB::raw("CASE
                    WHEN ts.start_date IS NULL OR ts.trainer_id IS NULL THEN 'Waiting'
                    WHEN COALESCE(check_ins.used_sessions, 0) >= ts.number_of_session THEN 'Completed'
                    WHEN NOW() < ts.start_date THEN 'Not Started'
                    WHEN NOW() > DATE_ADD(ts.start_date, INTERVAL (ts.days + COALESCE(freezes.freeze_days, 0)) DAY) THEN 'Expired'
                    ELSE 'Active'
                END as pt_status")
            )
            ->leftJoin('trainer_packages as tp', 'ts.trainer_package_id', '=', 'tp.id')
            ->leftJoin('personal_trainers as trainers', 'ts.trainer_id', '=', 'trainers.id')
            ->leftJoin('branch_stores as bs', 'ts.branch_store_id', '=', 'bs.id')
            ->leftJoin('method_payments as methods', 'ts.method_payment_id', '=', 'methods.id')
            ->leftJoin('users', 'ts.user_id', '=', 'users.id')
            ->leftJoinSub($paymentSummary, 'payments', function ($join) {
                $join->on('ts.id', '=', 'payments.trainer_session_id');
            })
            ->leftJoinSub($freezeSummary, 'freezes', function ($join) {
                $join->on('ts.id', '=', 'freezes.trainer_session_id');
            })
            ->leftJoinSub($checkInSummary, 'check_ins', function ($join) {
                $join->on('ts.id', '=', 'check_ins.trainer_session_id');
            })
            ->where('ts.member_id', $member->id)
            ->whereNull('tp.status')
            ->orderByDesc('ts.start_date')
            ->orderByDesc('ts.id')
            ->paginate(10);

        $totals = DB::table('trainer_sessions as ts')
            ->leftJoin('trainer_packages as tp', 'ts.trainer_package_id', '=', 'tp.id')
            ->leftJoinSub($checkInSummary, 'check_ins', function ($join) {
                $join->on('ts.id', '=', 'check_ins.trainer_session_id');
            })
            ->where('ts.member_id', $member->id)
            ->whereNull('tp.status')
            ->selectRaw('COALESCE(SUM(COALESCE(check_ins.used_sessions, 0)), 0) as used_sessions')
            ->selectRaw('COALESCE(SUM(GREATEST(ts.number_of_session - COALESCE(check_ins.used_sessions, 0), 0)), 0) as unused_sessions')
            ->first();

        return view('admin.layouts.wrapper', [
            'title' => 'PT History',
            'member' => $member->load('branchStore'),
            'histories' => $histories,
            'totalUsedSessions' => (int) $totals->used_sessions,
            'totalUnusedSessions' => (int) $totals->unused_sessions,
            'content' => 'admin.members.pt-history',
        ]);
    }

    public function membershipCheckInHistory(Member $member, MemberRegistration $memberRegistration)
    {
        abort_unless((int) $memberRegistration->member_id === (int) $member->id, 404);

        $package = DB::table('member_registrations as mr')
            ->select(
                'mr.id',
                'mr.start_date',
                'mr.days',
                'mp.package_name',
                'bs.name as branch_store_name'
            )
            ->leftJoin('member_packages as mp', 'mr.member_package_id', '=', 'mp.id')
            ->leftJoin('branch_stores as bs', 'mp.branch_store_id', '=', 'bs.id')
            ->where('mr.id', $memberRegistration->id)
            ->first();
        abort_unless($package, 404);

        $checkIns = DB::table('check_in_members as check_ins')
            ->select(
                'check_ins.id',
                'check_ins.check_in_time',
                'check_ins.check_out_time',
                'branches.name as branch_store_name',
                'users.full_name as staff_name',
                DB::raw('NULL as trainer_name')
            )
            ->leftJoin('branch_stores as branches', 'check_ins.branch_store_id', '=', 'branches.id')
            ->leftJoin('users', 'check_ins.user_id', '=', 'users.id')
            ->where('check_ins.member_registration_id', $memberRegistration->id)
            ->orderByDesc('check_ins.check_in_time')
            ->orderByDesc('check_ins.id')
            ->paginate(25);

        return view('admin.layouts.wrapper', [
            'title' => 'Membership Check In/Out History',
            'member' => $member,
            'package' => $package,
            'checkIns' => $checkIns,
            'historyType' => 'membership',
            'backRoute' => route('members.membership-history', $member),
            'content' => 'admin.members.check-in-history',
        ]);
    }

    public function ptCheckInHistory(Member $member, TrainerSession $trainerSession)
    {
        abort_unless((int) $trainerSession->member_id === (int) $member->id, 404);

        $package = DB::table('trainer_sessions as ts')
            ->select(
                'ts.id',
                'ts.start_date',
                'ts.days',
                'ts.is_pt_free',
                'tp.package_name',
                'trainers.full_name as trainer_name',
                'bs.name as branch_store_name'
            )
            ->leftJoin('trainer_packages as tp', 'ts.trainer_package_id', '=', 'tp.id')
            ->leftJoin('personal_trainers as trainers', 'ts.trainer_id', '=', 'trainers.id')
            ->leftJoin('branch_stores as bs', 'ts.branch_store_id', '=', 'bs.id')
            ->where('ts.id', $trainerSession->id)
            ->first();
        abort_unless($package, 404);

        $checkIns = DB::table('check_in_trainer_sessions as check_ins')
            ->select(
                'check_ins.id',
                'check_ins.check_in_time',
                'check_ins.check_out_time',
                'branches.name as branch_store_name',
                'users.full_name as staff_name',
                DB::raw("COALESCE(check_in_trainers.full_name, session_trainers.full_name, '-') as trainer_name")
            )
            ->join('trainer_sessions as ts', 'check_ins.trainer_session_id', '=', 'ts.id')
            ->leftJoin('personal_trainers as check_in_trainers', 'check_ins.pt_id', '=', 'check_in_trainers.id')
            ->leftJoin('personal_trainers as session_trainers', 'ts.trainer_id', '=', 'session_trainers.id')
            ->leftJoin('branch_stores as branches', 'check_ins.branch_store_id', '=', 'branches.id')
            ->leftJoin('users', 'check_ins.user_id', '=', 'users.id')
            ->where('check_ins.trainer_session_id', $trainerSession->id)
            ->orderByDesc('check_ins.check_in_time')
            ->orderByDesc('check_ins.id')
            ->paginate(25);

        return view('admin.layouts.wrapper', [
            'title' => 'PT Check In/Out History',
            'member' => $member,
            'package' => $package,
            'checkIns' => $checkIns,
            'historyType' => 'pt',
            'backRoute' => route('members.pt-history', $member),
            'content' => 'admin.members.check-in-history',
        ]);
    }

    public function destroy(Member $member)
    {
        app(\App\Services\TipTapService::class)->trash(auth()->user(), 'member', (int) $member->id);
        return redirect()->route('members.index')->with('success', 'Member dan seluruh history dipindahkan ke Tempat Sampah Tip-Tap. Omzet sudah disesuaikan.');
    }

    public function updateSmallPhoto(Request $request, string $id)
    {
        $data = $request->validate([
            'small_photo_data' => ['required', 'string'],
        ]);

        $member = Member::findOrFail($id);

        if (!$member->photos) {
            return redirect()->route('members.index')->with('errorr', 'Photo utama member belum tersedia');
        }

        if (!preg_match('/^data:image\/(jpeg|jpg|png);base64,/', $data['small_photo_data'])) {
            return redirect()->route('members.index')->with('errorr', 'Format small photo tidak valid');
        }

        $imageData = preg_replace('/^data:image\/(jpeg|jpg|png);base64,/', '', $data['small_photo_data']);
        $imageData = base64_decode($imageData, true);

        if ($imageData === false) {
            return redirect()->route('members.index')->with('errorr', 'Small photo gagal diproses');
        }

        if ($member->small_photos) {
            Storage::disk('public')->delete($member->small_photos);
        }

        $path = 'assets/member/small/member-' . $member->id . '-' . time() . '.jpg';
        Storage::disk('public')->put($path, $imageData);

        $fullName = str_ends_with($member->full_name, '~')
            ? substr($member->full_name, 0, -1)
            : $member->full_name . '~';

        $member->update([
            'small_photos' => $path,
            'full_name'    => $fullName,
        ]);

        return redirect()->route('members.index')->with('success', 'Small photo berhasil diupdate');
    }

    public function cetak_pdf()
    {
        $members    = Member::orderBy('full_name')->get();
        $users = User::get();

        $pdf = Pdf::loadView('admin/members/member-report', [
            'members'   => $members,
            'users'     => $users,
        ]);
        return $pdf->stream('member-report.pdf');
    }

    public function resetCheckIn(Request $request, string $id)
    {
        $item = Member::find($id);
        $name = $item->full_name;
        $item->id_code_count = 0;
        $item->save();

        return redirect()->route('member-active.index')->with('success', 'Member ' . $name . ' Reset Check In Successfully');
    }

    public function layoutOrientation($id)
    {
        $data = [
            'title'                 => 'Layout Orientation',
            'members'               => Member::find($id),
            'memberLastCode'        => Member::latest('id')->first(),
            'memberPackage'         => MemberPackage::get(),
            'methodPayment'         => MethodPayment::get(),
            'fitnessConsultant'     => FitnessConsultant::get(),
            'personalTrainer'       => PersonalTrainer::get(),
            'content'               => 'admin/members/lo',
        ];

        return view('admin.layouts.wrapper', $data);
    }

    public function updateLO(Request $request, string $id)
    {
        $item = Member::find($id);
        $data = $request->validate([
            'lo_pt_by'  => 'required|exists:personal_trainers,id',
        ]);

        $data['lo_is_used'] = 1;
        $data['lo_start_date'] = Carbon::now()->tz('Asia/Jakarta');

        $item->update($data);
        return redirect()->route('members.index')->with('success', 'LO digunakan');
    }

    public function stopLO(Request $request, string $id)
    {
        $item = Member::find($id);
        // dd($item);
        
        $data['lo_end'] = Carbon::now()->tz('Asia/Jakarta');

        $item->update($data);
        return redirect()->route('members.index')->with('success', 'LO sudah dihentikan');
    }
}
