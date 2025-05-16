<?php

namespace App\Http\Controllers\Member;

use App\Exports\MemberActiveExport;
use App\Exports\MemberPendingExport;
use App\Exports\OneVisitExport;
use App\Http\Controllers\Controller;
use App\Models\Member\LeaveDay;
use App\Models\Member\Member;
use App\Models\Member\MemberPackage;
use App\Models\Member\MemberRegistration;
use App\Models\MethodPayment;
use App\Models\Staff\FitnessConsultant;
use App\Models\User;
use Barryvdh\DomPDF\Facade\Pdf;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Facades\Excel;
use Carbon\Carbon;

class MemberRegistrationController extends Controller
{
    public function index()
    {
        $fromDate   = Request()->input('fromDate');
        $toDate     = Request()->input('toDate');

        $excel = Request()->input('excel');
        if ($excel && $excel == "1") {
            return Excel::download(new MemberActiveExport(), 'member-active, ' . $fromDate . ' to ' . $toDate . '.xlsx');
        }

        $memberRegistrations = MemberRegistration::getActiveList();

        $birthdayMessages = [
            0 => [],
            1 => [],
            2 => [],
        ];

        foreach ($memberRegistrations as $memberRegistration) {
            $diff = BirthdayDiff($memberRegistration->born);
            if ($diff >= 0 && $diff <= 2) {
                $birthdayMessages[$diff][$memberRegistration->member_id] = $memberRegistration->member_name;
            }
        }

        $idCodeMaxCount = env("ID_CODE_MAX_COUNT", 3);
        $data = [
            'title'                 => 'Member Active List',
            'memberRegistrations'   => $memberRegistrations,
            'content'               => 'admin/member-registration/index',
            'idCodeMaxCount'        => $idCodeMaxCount,
            'birthdayMessages'      => $birthdayMessages,
        ];

        return view('admin.layouts.wrapper', $data);
    }

    public function pending()
    {
        $fromDate   = Request()->input('fromDate');
        $toDate     = Request()->input('toDate');

        $excel = Request()->input('excel');
        if ($excel && $excel == "1") {
            return Excel::download(new MemberPendingExport(), 'member-pending, ' . $fromDate . ' to ' . $toDate . '.xlsx');
        }

        $memberRegistrations = MemberRegistration::getPendingList();

        $data = [
            'title'                 => 'Member Pending',
            'memberRegistrations'   => $memberRegistrations,
            'content'               => 'admin/member-registration/pending',
        ];

        return view('admin.layouts.wrapper', $data);
    }

    public function oneDayVisit()
    {
        $fromDate   = Request()->input('fromDate');
        $toDate     = Request()->input('toDate');

        $excel = Request()->input('excel');
        if ($excel && $excel == "1") {
            return Excel::download(new OneVisitExport(), 'One Day Visit, ' . $fromDate . ' to ' . $toDate . '.xlsx');
        }

        $memberRegistrations = DB::table('member_registrations as a')
            ->select(
                'a.id',
                'a.start_date',
                'a.description',
                'a.days as member_registration_days',
                'a.package_price as mr_package_price',
                'a.admin_price as mr_admin_price',
                'a.updated_at',
                'b.id as member_id',
                'b.full_name as member_name',
                'b.member_code',
                'b.phone_number',
                'c.package_name',
                'c.days',
                'c.package_price',
                'e.name as method_payment_name',
                'f.full_name as staff_name'
            )
            ->addSelect(
                DB::raw('DATE_ADD(a.start_date, INTERVAL a.days DAY) as expired_date'),
                DB::raw('CASE 
                    WHEN NOW() > DATE_ADD(a.start_date, INTERVAL a.days DAY) THEN "Over" 
                    WHEN NOW() BETWEEN a.start_date AND DATE_ADD(a.start_date, INTERVAL a.days DAY) THEN "Running" 
                    ELSE "Not Started" 
                END as status'),
            )
            ->join('members as b', 'a.member_id', '=', 'b.id')
            ->join('member_packages as c', 'a.member_package_id', '=', 'c.id')
            ->join('method_payments as e', 'a.method_payment_id', '=', 'e.id')
            ->join('users as f', 'a.user_id', '=', 'f.id')
            ->where('b.status', 'one_day_visit')
            ->orderBy('a.created_at', 'desc')
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
        $data = [
            'title'                 => 'Create Member Registration',
            'memberRegistration'    => MemberRegistration::get(),
            'members'               => Member::get(),
            'memberPackage'         => MemberPackage::get(),
            'methodPayment'         => MethodPayment::get(),
            'fitnessConsultant'     => FitnessConsultant::get(),
            'content'               => 'admin/member-registration/create-page',
        ];

        return view('admin.layouts.wrapper', $data);
    }

    public function memberSecondStore(Request $request)
    {
        DB::beginTransaction();
        try {
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
                'fc_candidate_id'       => 'nullable',
                'cancellation_note'     => 'nullable',
                'lo_is_used'            => 'nullable',
                'lo_start_date'         => 'nullable',
                'lo_days'               => 'nullable',
                'lo_pt_by'              => 'nullable',
                'start_date'            => 'required_if:status,sell',
                'description'           => 'nullable',
                'card_number' => [
                    'nullable',
                    function ($attribute, $value, $fail) {
                        if ($value) {
                            $exists = Member::where('card_number', $value)->exists();
                            if ($exists) {
                                $fail('The card number has already been taken.');
                            }
                        }
                    }
                ],
                'member_code' => [
                    'nullable',
                    function ($attribute, $value, $fail) {
                        if ($value) {
                            $exists = Member::where('member_code', $value)->exists();
                            if ($exists) {
                                $fail('The member code has already been taken.');
                            }
                        }
                    }
                ],
            ]);

            if ($request->status == 'sell') {

                $fc = Auth::user()->id;
                if (Auth::user()->role == 'FC') {
                    $data += $request->validate([
                        'member_package_id'     => 'required|exists:member_packages,id',
                        'start_date'            => 'required',
                        'method_payment_id'     => 'required|exists:method_payments,id',
                        // 'fc_id'                 => Auth::user()->id,
                    ]);
                    $data['fc_id'] = $fc;
                } else {
                    $data += $request->validate([
                        'member_package_id'     => 'required|exists:member_packages,id',
                        'start_date'            => 'required',
                        'method_payment_id'     => 'required|exists:method_payments,id',
                        'fc_id'                 => 'required|exists:users,id',
                    ]);
                }

                // $data += $request->validate([
                //     'member_package_id'     => 'required|exists:member_packages,id',
                //     'start_date'            => 'required',
                //     'method_payment_id'     => 'required|exists:method_payments,id',
                //     'fc_id'                 => 'required|exists:users,id',
                // ]);

                if ($request->hasFile('photos')) {
                    if ($request->photos != null) {
                        $realLocation = "storage/" . $request->photos;
                        if (file_exists($realLocation) && !is_dir($realLocation)) {
                            unlink($realLocation);
                        }
                    }
                    $photos = $request->file('photos');
                    $file_name = time() . '-' . $photos->getClientOriginalName();

                    $data['photos'] = $request->file('photos')->store('assets/member', 'public');
                } else {
                    $data['photos'] = $request->photos;
                }

                $data['born'] = Carbon::parse($data['born'])->format('Y-m-d');

                $package = MemberPackage::findOrFail($data['member_package_id']);
                $data['package_price'] = $package->package_price;

                $data['user_id'] = Auth::user()->id;

                $startTime = date('H:i:s', strtotime('00:00:00'));

                $data['start_date'] =  $data['start_date'] . ' ' .  $startTime;
                $dateTime = new \DateTime($data['start_date']);
                $data['start_date'] = $dateTime->format('Y-m-d H:i:s');
                unset($startTime);

                $data['admin_price'] = $package->admin_price;
                $data['days'] = $package->days;

                $newMember = Member::create(array_intersect_key($data, array_flip([
                    'full_name',
                    'phone_number',
                    'status',
                    'nickname',
                    'born',
                    'member_code',
                    'card_number',
                    'email',
                    'ig',
                    'emergency_contact',
                    'ec_name',
                    'gender',
                    'address',
                    'photos'
                ])));

                $data['member_id'] = $newMember->id;

                $createMemberRegistration = MemberRegistration::create(array_intersect_key($data, array_flip([
                    'member_id',
                    'member_package_id',
                    'start_date',
                    'method_payment_id',
                    'fc_id',
                    'user_id',
                    'description',
                    'package_price',
                    'admin_price',
                    'days'
                ])));
            } elseif ($request->status == 'one_day_visit') {
                $data += $request->validate([
                    'start_date'            => 'nullable',
                    'member_package_id'     => 'required|exists:member_packages,id',
                    'method_payment_id'     => 'required|exists:method_payments,id',
                ]);

                $package = MemberPackage::findOrFail($data['member_package_id']);
                $data['package_price'] = $package->package_price;

                $data['user_id'] = Auth::user()->id;
                $data['admin_price'] = $package->admin_price;
                $data['days'] = $package->days;

                $data['start_date'] = Carbon::now()->tz('Asia/Jakarta')->startOfDay();

                $existingMember = Member::where('phone_number', $data['phone_number'])
                    ->orWhere('full_name', $data['full_name'])
                    ->first();

                if ($existingMember) {
                    $data['member_id'] = $existingMember->id;

                    MemberRegistration::create(array_intersect_key($data, array_flip([
                        'member_id',
                        'member_package_id',
                        'start_date',
                        'method_payment_id',
                        'user_id',
                        'description',
                        'package_price',
                        'admin_price',
                        'days'
                    ])));
                } else {
                    // Create new member
                    $newMember = Member::create(array_intersect_key($data, array_flip([
                        'full_name',
                        'phone_number',
                        'status'
                    ])));

                    $data['member_id'] = $newMember->id;

                    // Create member registration
                    MemberRegistration::create(array_intersect_key($data, array_flip([
                        'member_id',
                        'member_package_id',
                        'start_date',
                        'method_payment_id',
                        'user_id',
                        'description',
                        'package_price',
                        'admin_price',
                        'days'
                    ])));
                }
            } else {
                $fc = Auth::user()->role;
                // $user = Auth::user()->id;
                if ($fc == 'FC') {
                    $data['fc_candidate_id'] = Auth::user()->id;
                    $newMember = Member::create(array_intersect_key($data, array_flip([
                        'full_name',
                        'phone_number',
                        'status',
                        'fc_candidate_id',
                        'cancellation_note'
                    ])));
                } else {
                    $newMember = Member::create(array_intersect_key($data, array_flip([
                        'full_name',
                        'phone_number',
                        'status',
                        'fc_candidate_id',
                        'cancellation_note'
                    ])));
                }
            }

            DB::commit();
            if ($request->status == 'one_day_visit') {
                return redirect()->back()->with('success', 'One Day Visit Added Successfully');
            }
            return redirect()->back()->with('success', 'Member Registration Added Successfully');
        } catch (Exception $e) {
            DB::rollback();
            throw $e;
        }
    }

    public function show($id)
    {
        $mr = MemberRegistration::find($id);
        $status = $mr->members->status;
        $memberId = $mr->members->id;

        if ($status == "one_day_visit") {
            $memberRegistrations = DB::table('member_registrations as a')
                ->select(
                    'a.id',
                    'a.start_date',
                    'a.description',
                    'a.days as member_registration_days',
                    'a.old_days',
                    'a.package_price as mr_package_price',
                    'a.admin_price as mr_admin_price',
                    'b.full_name as member_name',
                    'b.address',
                    'b.member_code',
                    'b.phone_number',
                    'b.photos',
                    'b.gender',
                    'b.nickname',
                    'b.ig',
                    'b.emergency_contact',
                    'b.email',
                    'b.born',
                    'b.status as member_status',
                    'c.id as member_package_id',
                    'c.package_name',
                    'c.days',
                    'c.package_price',
                    'c.admin_price',
                    'e.id as method_payment_id',
                    'e.name as method_payment_name',
                    'f.full_name as staff_name'
                )
                ->addSelect(
                    DB::raw('DATE_ADD(a.start_date, INTERVAL a.days DAY) as expired_date'),
                    DB::raw('CASE WHEN NOW() > DATE_ADD(a.start_date, INTERVAL a.days DAY) THEN "Over" ELSE "Running" END as status')
                )
                ->join('members as b', 'a.member_id', '=', 'b.id')
                ->join('member_packages as c', 'a.member_package_id', '=', 'c.id')
                ->join('method_payments as e', 'a.method_payment_id', '=', 'e.id')
                ->join('users as f', 'a.user_id', '=', 'f.id')
                ->where('a.id', $id)
                ->get();
        } else {
            $memberRegistrations = MemberRegistration::getActiveListById("", $id);
            $pendingMemberRegistrations = MemberRegistration::getPendingList($memberId);
            $expiredMemberRegistrations = MemberRegistration::getExpiredList($memberId);
            // $query2 = MemberRegistration::getActiveListById("", $id);
            // dd($expiredMemberRegistrations);
        }

        $checkInMemberRegistration = MemberRegistration::find($id);
        // dd($memberRegistrations);
        $data = [
            'title'                     => 'Member Registration Detail',
            'memberRegistrations'       => $memberRegistrations,
            'pendingMemberRegistrations' => $pendingMemberRegistrations,
            'expiredMemberRegistrations' => $expiredMemberRegistrations,
            'memberRegistration'        => MemberRegistration::find($id),
            'members'                   => Member::get(),
            'memberRegistrationCheckIn' => $checkInMemberRegistration->memberRegistrationCheckIn,
            'status'                    => $status,
            'content'                   => 'admin/member-registration/show',
        ];

        return view('admin.layouts.wrapper', $data);
    }

    public function showOneVisit($id)
    {
        $mr = MemberRegistration::find($id);
        $status = $mr->members->status;
        $memberId = $mr->members->id;

        $memberRegistrations = DB::table('member_registrations as a')
            ->select(
                'a.id',
                'a.start_date',
                'a.description',
                'a.days as member_registration_days',
                'a.old_days',
                'a.package_price as mr_package_price',
                'a.admin_price as mr_admin_price',
                'b.full_name as member_name',
                'b.address',
                'b.member_code',
                'b.phone_number',
                'b.photos',
                'b.gender',
                'b.nickname',
                'b.ig',
                'b.emergency_contact',
                'b.email',
                'b.born',
                'b.status as member_status',
                'c.id as member_package_id',
                'c.package_name',
                'c.days',
                'c.package_price',
                'c.admin_price',
                'e.id as method_payment_id',
                'e.name as method_payment_name',
                'f.full_name as staff_name'
            )
            ->addSelect(
                DB::raw('DATE_ADD(a.start_date, INTERVAL a.days DAY) as expired_date'),
                DB::raw('CASE WHEN NOW() > DATE_ADD(a.start_date, INTERVAL a.days DAY) THEN "Over" ELSE "Running" END as status')
            )
            ->join('members as b', 'a.member_id', '=', 'b.id')
            ->join('member_packages as c', 'a.member_package_id', '=', 'c.id')
            ->join('method_payments as e', 'a.method_payment_id', '=', 'e.id')
            ->join('users as f', 'a.user_id', '=', 'f.id')
            ->where('a.id', $id)
            ->get();

        $checkInMemberRegistration = MemberRegistration::find($id);
        // dd($memberRegistrations);
        $data = [
            'title'                     => 'One Visit Detail',
            'memberRegistrations'       => $memberRegistrations,
            'memberRegistration'        => MemberRegistration::find($id),
            'members'                   => Member::get(),
            'memberRegistrationCheckIn' => $checkInMemberRegistration->memberRegistrationCheckIn,
            'status'                    => $status,
            'content'                   => 'admin/one-visit/show',
        ];

        return view('admin.layouts.wrapper', $data);
    }

    public function edit(string $id)
    {
        $mr = MemberRegistration::find($id);
        $status = $mr->members->status;

        if ($status == "one_day_visit") {
            $memberActive = DB::table('member_registrations as a')
                ->select(
                    'a.id',
                    'a.start_date',
                    'a.description',
                    'a.days as member_registration_days',
                    'a.old_days',
                    'a.package_price as mr_package_price',
                    'a.admin_price as mr_admin_price',
                    'b.full_name as member_name',
                    'b.address',
                    'b.member_code',
                    'b.phone_number',
                    'b.photos',
                    'b.gender',
                    'b.nickname',
                    'b.ig',
                    'b.emergency_contact',
                    'b.email',
                    'b.born',
                    'c.id as member_package_id',
                    'c.package_name',
                    'c.days',
                    'c.package_price',
                    'c.admin_price',
                    'e.id as method_payment_id',
                    'e.name as method_payment_name',
                    'f.full_name as staff_name'
                )
                ->addSelect(
                    DB::raw('DATE_ADD(a.start_date, INTERVAL a.days DAY) as expired_date'),
                    DB::raw('CASE WHEN NOW() > DATE_ADD(a.start_date, INTERVAL a.days DAY) THEN "Over" ELSE "Running" END as status')
                )
                ->join('members as b', 'a.member_id', '=', 'b.id')
                ->join('member_packages as c', 'a.member_package_id', '=', 'c.id')
                ->join('method_payments as e', 'a.method_payment_id', '=', 'e.id')
                ->join('users as f', 'a.user_id', '=', 'f.id')
                ->where('a.id', $id)
                ->get();
        } else {
            $memberActive = MemberRegistration::getActiveListById("", $id);
            // dd("Member Active");
            if (!$memberActive) {
                $memberActive = MemberRegistration::getNewPendingListById("", $id);
            }
            // dd("Member Pending");
        }

        $data = [
            'title'                 => 'Edit Member Active',
            'memberRegistration'    => MemberRegistration::find($id),
            // 'memberRegistrations'   => $memberActive->first(),
            'memberRegistrations'   => $memberActive[0],
            'memberPackage'         => MemberPackage::get(),
            'methodPayment'         => MethodPayment::get(),
            'users'                 => User::where('role', 'FC')->get(),
            'content'               => 'admin/member-registration/edit-page',
        ];

        return view('admin.layouts.wrapper', $data);
    }

    public function update(Request $request, string $id)
    {
        $memberRegistration = DB::table('member_registrations as a')
            ->select(
                'a.id',
                'a.start_date',
                'a.description',
                'a.days'
            )
            ->addSelect(
                DB::raw('DATE_ADD(a.start_date, INTERVAL a.days DAY) as expired_date'),
            )
            ->join('members as b', 'a.member_id', '=', 'b.id')
            ->where('a.id', $id)
            ->get();

        // dd($memberRegistration->first()->expired_date);
        $item = MemberRegistration::find($id);
        $data = $request->validate([
            'start_date'            => 'nullable',
            'expired_date'          => 'nullable',
            'description'           => 'nullable',
            'member_package_id'     => 'required',
            'method_payment_id'     => 'nullable',
            'fc_id'                 => 'nullable',
        ]);

        $data['user_id'] = Auth::user()->id;

        $selectedPackage = MemberPackage::withTrashed()->find($data["member_package_id"]);
        $currentPackage = MemberPackage::withTrashed()->find($item->member_package_id);

        $startTime = date('H:i:s', strtotime('00:00:00'));
        $data['start_date'] = $data['start_date'] . ' ' .  $startTime;
        $dateTime = new \DateTime($data['start_date']);
        $data['start_date'] = $dateTime->format('Y-m-d H:i:s');
        // dd($data['start_date']);

        if ($selectedPackage->id !== $currentPackage->id) {
            $data['days'] = $selectedPackage->days;
            $data['package_price'] = $selectedPackage->package_price;
            $data['admin_price'] = $selectedPackage->admin_price;
        }

        $expiredTime = date('H:i:s', strtotime('00:00:00'));
        $expiredDateString = $request->input('expired_date');
        $data['expired_date'] = Carbon::parse($expiredDateString)->format('Y-m-d') . ' ' . $expiredTime;

        if ($data['expired_date'] !== $memberRegistration->first()->expired_date) {
            $expiredDate = new \DateTime($request->input('expired_date'));
            $daysDifference = $expiredDate->diff($dateTime)->days;
            $data['days'] =  $daysDifference;
        }

        unset($expiredTime);
        unset($startTime);
        unset($data['expired_date']);

        $item->update($data);
        return redirect()->route('member-active.index')->with('success', 'Member Active Updated Successfully');
    }

    public function renewal(string $id)
    {
        $data = [
            'title'                 => 'Renewal Member Active',
            'memberRegistration'    => MemberRegistration::find($id),
            'members'               => Member::get(),
            'memberLastCode'        => Member::latest('id')->first(),
            'memberPackage'         => MemberPackage::get(),
            'methodPayment'         => MethodPayment::get(),
            'fitnessConsultant'     => User::where('role', 'FC')->get(),
            'content'               => 'admin/member-registration/renewal',
        ];

        return view('admin.layouts.wrapper', $data);
    }

    public function renewMemberRegistration(Request $request, $id)
    {
        $statusMember = MemberRegistration::find($id);

        if ($statusMember->members->status == "one_day_visit") {
            DB::beginTransaction();
            try {
                $memberRegistration = MemberRegistration::findOrFail($id);
                $memberId = $memberRegistration->members->id;

                $data = $request->validate([
                    'member_package_id' => 'required|exists:member_packages,id',
                    'start_date'        => 'required',
                    'method_payment_id' => 'required|exists:method_payments,id',
                    'fc_id'             => 'required|exists:users,id',
                    'description'       => 'nullable',

                    // Member
                    'full_name'         => 'nullable',
                    'phone_number'      => 'nullable',
                    'status'            => 'nullable',
                    'nickname'          => 'nullable',
                    'born'              => 'nullable',
                    'email'             => 'nullable',
                    'ig'                => 'nullable',
                    'emergency_contact' => 'nullable',
                    'ec_name'           => 'nullable',
                    'gender'            => 'nullable',
                    'address'           => 'nullable',
                    'photos'            => 'nullable|image|mimes:jpeg,png,jpg,gif,svg|max:2048',
                    'member_code' => [
                        'required',
                        function ($attribute, $value, $fail) use ($id) {
                            if ($value) {
                                $exists = Member::where('member_code', $value)->where('id', '!=', $id)->exists();
                                if ($exists) {
                                    $fail('Nomor member sudah digunakan, harap menggunakan nomor yang lain');
                                }
                            }
                        }
                    ],
                    'card_number' => [
                        'required',
                        function ($attribute, $value, $fail) use ($id) {
                            if ($value) {
                                $exists = Member::where('card_number', $value)->where('id', '!=', $id)->exists();
                                if ($exists) {
                                    $fail('Nomor kartu sudah digunakan, harap menggunakan kartu yang lain');
                                }
                            }
                        }
                    ],
                ]);

                $package = MemberPackage::findOrFail($data['member_package_id']);
                $data['package_price'] = $package->package_price;
                $data['user_id'] = Auth::user()->id;
                $startTime = date('H:i:s', strtotime('00:00:00'));

                $data['start_date'] =  $data['start_date'] . ' ' .  $startTime;
                $dateTime = new \DateTime($data['start_date']);
                $data['start_date'] = $dateTime->format('Y-m-d H:i:s');
                unset($startTime);

                $data['admin_price'] = $package->admin_price;
                $data['days'] = $package->days;
                $data['member_id'] = $memberRegistration->member_id;

                // MEMBER DATA UPDATE
                if ($request->hasFile('photos')) {

                    if ($request->photos != null) {
                        $realLocation = "storage/" . $request->photos;
                        if (file_exists($realLocation) && !is_dir($realLocation)) {
                            unlink($realLocation);
                        }
                    }

                    $photos = $request->file('photos');
                    $file_name = time() . '-' . $photos->getClientOriginalName();

                    $data['photos'] = $request->file('photos')->store('assets/member', 'public');
                } else {
                    $data['photos'] = $request->photos;
                }
                $data['born'] = Carbon::parse($data['born'])->format('Y-m-d');
                Member::findOrFail($memberRegistration->member_id)->update(array_intersect_key($data, array_flip([
                    'full_name',
                    'phone_number',
                    'status',
                    'nickname',
                    'born',
                    'member_code',
                    'card_number',
                    'email',
                    'ig',
                    'emergency_contact',
                    'ec_name',
                    'gender',
                    'address',
                    'photos'
                ])));

                MemberRegistration::create($data);
                DB::commit();

                return redirect()->route('member-active.index')->with('success', 'Renewal Successfully');
            } catch (Exception $e) {
                DB::rollback();
                throw $e;
            }
        } else {
            DB::beginTransaction();
            try {
                $memberRegistration = MemberRegistration::findOrFail($id);

                $fc = Auth::user()->id;

                if (Auth::user()->role == 'FC') {
                    $data = $request->validate([
                        'member_package_id' => 'required|exists:member_packages,id',
                        'start_date'        => 'required',
                        'method_payment_id' => 'required|exists:method_payments,id',
                        'description'       => 'nullable',
                    ]);
                    $data['fc_id'] = $fc;
                } else {
                    $data = $request->validate([
                        'member_package_id' => 'required|exists:member_packages,id',
                        'start_date'        => 'required',
                        'method_payment_id' => 'required|exists:method_payments,id',
                        'fc_id'             => 'required|exists:users,id',
                        'description'       => 'nullable',
                    ]);
                }

                // $data = $request->validate([
                //     'member_package_id' => 'required|exists:member_packages,id',
                //     'start_date'        => 'required',
                //     'method_payment_id' => 'required|exists:method_payments,id',
                //     'fc_id'             => 'required|exists:users,id',
                //     'description'       => 'nullable',
                // ]);

                $package = MemberPackage::findOrFail($data['member_package_id']);
                $data['package_price'] = $package->package_price;

                $data['user_id'] = Auth::user()->id;

                $startTime = date('H:i:s', strtotime('00:00:00'));

                $data['start_date'] =  $data['start_date'] . ' ' .  $startTime;
                $dateTime = new \DateTime($data['start_date']);
                $data['start_date'] = $dateTime->format('Y-m-d H:i:s');
                unset($startTime);

                $data['admin_price'] = $package->admin_price;
                $data['days'] = $package->days;


                $data['member_id'] = $memberRegistration->member_id;

                MemberRegistration::create($data);

                DB::commit();

                return redirect()->route('member-active.index')->with('success', 'Renewal Successfully');
            } catch (Exception $e) {
                DB::rollback();
                throw $e;
            }
        }
    }

    public function freeze(Request $request, string $id)
    {
        $item = MemberRegistration::find($id);
        // Periksa apakah data ditemukan
        if (!$item) {
            return redirect()->route('member-active.index')->with('errorr', 'Member Registration not found');
        }

        $lastLeaveDay = LeaveDay::where("member_registration_id", $id)->orderBy("id", "desc")->first();

        $now = Carbon::now()->tz('Asia/Jakarta');

        $inputData = [
            'member_registration_id' => $item->id,
            'submission_date' => $now,
            'price' => $request->input('price'),
            'days' => $request->input('expired_date'),
        ];

        if ($lastLeaveDay) {
            $submissionDateStr = new \DateTime($lastLeaveDay->submission_date);

            $expiredDate = $submissionDateStr->modify("+{$lastLeaveDay->days} days");
            if ($now <= $expiredDate) {
                $inputData['leave_day_continue_id'] = $lastLeaveDay->leave_day_continue_id ? $lastLeaveDay->leave_day_continue_id : $lastLeaveDay->id;
                $inputData['submission_date'] = $expiredDate;
            }
        }

        $leaveDay = new LeaveDay($inputData);
        $leaveDay->price = str_replace(',', '', $leaveDay['price']);
        $leaveDay->save();

        return redirect()->route('member-active.index')->with('success', 'Cuti Membership Successfully Added');
    }

    public function destroy($id)
    {
        $memberRegistration = MemberRegistration::find($id);

        try {
            $memberRegistration->delete();
            return redirect()->back()->with('success', $memberRegistration->members->full_name . ' member package delete successfully');
        } catch (\Throwable $e) {
            return redirect()->back()->with('errorr', 'Deleted Failed, Delete Member Check In First');
        }
    }

    public function agreement($id)
    {
        $memberRegistration = DB::table('member_registrations as a')
            ->select(
                'a.id',
                'a.start_date',
                'a.description',
                'a.package_price as mr_package_price',
                'a.admin_price as mr_admin_price',
                'a.days as member_registration_days',
                'c.package_name',
                'c.days',
                'b.full_name as member_name',
                'b.ec_name',
                'b.member_code',
                'b.nickname',
                'b.phone_number',
                'b.born',
                'b.photos',
                'b.gender',
                'b.emergency_contact',
                'b.email',
                'b.ig',
                'b.address',
                'c.package_name',
                'c.package_price',
                'c.days',
                'e.name as method_payment_name',
                'f.full_name as staff_name',
                'fc.full_name as fc_name',
                'ld.submission_date',
                'ld.days as number_of_leave_days'
            )
            ->addSelect(
                DB::raw('DATE_ADD(a.start_date, INTERVAL COALESCE(ld.days, 0) + a.days DAY) as expired_date'),
                DB::raw('CASE WHEN NOW() > DATE_ADD(a.start_date, INTERVAL a.days DAY) THEN "Over" ELSE "Running" END as status'),
                DB::raw('CONCAT(YEAR(CURDATE()), "-", MONTH(b.born), "-", DAY(b.born)) as member_birthday')
            )
            ->join('members as b', 'a.member_id', '=', 'b.id')
            ->join('member_packages as c', 'a.member_package_id', '=', 'c.id')
            ->join('method_payments as e', 'a.method_payment_id', '=', 'e.id')
            ->join('users as f', 'a.user_id', '=', 'f.id')
            ->join('users as fc', 'a.fc_id', '=', 'fc.id')
            ->leftJoin('leave_days as ld', 'a.id', '=', 'ld.member_registration_id')
            ->where('a.id', $id)
            ->first();
        // dd($memberRegistration);

        $fileName1 = $memberRegistration->member_name;
        $fileName2 = $memberRegistration->start_date;

        $pdf = Pdf::loadView('admin/member-registration/agreement', [
            'memberRegistration'        => $memberRegistration,
        ]);
        return $pdf->stream('Membership Agreement-' . $fileName1 . '-' . $fileName2 . '.pdf');
    }

    public function cuti($id)
    {
        $memberRegistration = MemberRegistration::getCutiAgreement("", $id);
        // dd($memberRegistration);

        $fileName1 = $memberRegistration[0]->member_name;
        $fileName2 = $memberRegistration[0]->start_date;

        $pdf = Pdf::loadView('admin/member-registration/cuti', [
            'memberRegistration'        => $memberRegistration[0]
        ]);
        return $pdf->stream('Cuti Membership-' . $fileName1 . '-' . $fileName2 . '.pdf');
    }

    public function filter(Request $request)
    {
        $fromDate   = $request->input('fromDate');
        $toDate     = $request->input('toDate');

        $query = DB::table('member_registrations as a')
            ->select(
                'a.id',
                'a.start_date',
                'a.description',
                'a.days as member_registration_days',
                'a.old_days',
                'a.package_price as mr_package_price',
                'a.admin_price as mr_admin_price',
                'a.updated_at',
                'b.full_name as member_name',
                'b.member_code',
                'b.phone_number',
                'b.born',
                'b.photos',
                'b.gender',
                'c.package_name',
                'c.days',
                'c.package_price',
                'e.name as method_payment_name',
                'f.full_name as staff_name',
                'g.full_name as fc_name',
                'g.phone_number as fc_phone_number',
                'h.check_in_time',
                'h.check_out_time',
                'ld.submission_date',
                'ld.days as number_of_leave_days'
            )
            ->addSelect(
                DB::raw("'bg-dark' as birthdayCelebrating"), //0 tidak ultah, 3 hari lagi ultah, 2 hari lagi, 1 hari lagi
                // DB::raw('DATE_ADD(a.start_date, INTERVAL a.days DAY) as expired_date'),
                DB::raw('DATE_ADD(a.start_date, INTERVAL COALESCE(ld.days, 0) + a.days DAY) as expired_date'),
                // Expired leave days
                DB::raw('DATE_ADD(ld.submission_date, INTERVAL ld.days DAY) as expired_leave_days'),
                // DB::raw('CASE WHEN NOW() > DATE_ADD(a.start_date, INTERVAL c.days DAY) THEN "Over" ELSE "Running" END as status'),
                DB::raw('CASE 
                    WHEN NOW() > DATE_ADD(a.start_date, INTERVAL a.days DAY) THEN "Over" 
                    WHEN NOW() BETWEEN a.start_date AND DATE_ADD(a.start_date, INTERVAL a.days DAY) THEN "Running" 
                    ELSE "Not Started" 
                END as status'),
                // Leave Days
                DB::raw('CASE 
                    WHEN NOW() > DATE_ADD(ld.submission_date, INTERVAL ld.days DAY) THEN "Ended" 
                    WHEN NOW() BETWEEN ld.submission_date AND DATE_ADD(ld.submission_date, INTERVAL ld.days DAY) THEN "Freeze" 
                    ELSE "No Leave Days" 
                END as leave_day_status'),

                // DB::raw('CASE WHEN ld.member_registration_id IS NOT NULL THEN "Exist" ELSE "None" END AS cuti'),
                DB::raw('CONCAT(YEAR(CURDATE()), "-", MONTH(b.born), "-", DAY(b.born)) as member_birthday'),
                DB::raw('DATEDIFF(CONCAT(YEAR(CURDATE()), "-", MONTH(b.born), "-", DAY(b.born)), CURDATE()) as days_until_birthday') // tambahkan ini untuk mendapatkan jumlah hari sampai ulang tahun
            )
            ->join('members as b', 'a.member_id', '=', 'b.id')
            ->join('member_packages as c', 'a.member_package_id', '=', 'c.id')
            ->join('method_payments as e', 'a.method_payment_id', '=', 'e.id')
            ->join(
                'users as f',
                'a.user_id',
                '=',
                'f.id'
            )
            // ->join('fitness_consultants as g', 'a.fc_id', '=', 'g.id')
            ->leftJoin('leave_days as ld', 'a.id', '=', 'ld.member_registration_id')
            ->leftJoin(DB::raw('(select * from (select a.* from (select * from check_in_members) as a inner join (SELECT max(id) as id FROM check_in_members group by member_registration_id)
                                as b on a.id=b.id) as tableH) as h'), 'a.id', '=', 'h.member_registration_id')
            ->whereRaw('NOW() BETWEEN a.start_date AND DATE_ADD(a.start_date, INTERVAL a.days DAY)')
            ->where('a.created_at', '>=', $fromDate)
            ->where('a.created_at', '<=', $toDate)
            ->orderBy('h.check_in_time', 'desc')
            ->orderBy('h.check_out_time', 'desc')
            ->orderBy('a.updated_at', 'desc')
            ->get();

        if ($request->fromDate && $request->toDate) {
            $query->whereBetween('a.created_at', [$request->fromDate, $request->toDate]);
        }

        $data = [
            'memberRegistrations'   => $query,
            'request'               => $request,
            'content'               => 'admin/member-registration/filter'
        ];

        return view('admin.layouts.wrapper', $data);
    }

    public function stopLeaveDays()
    {
        try {
            //code...
            $member_registration_id = Request()->input("member_registration_id");
            $now = (DateFormat(Carbon::now()->tz('Asia/Jakarta'), "YYYY-MM-DD HH:mm:ss"));
            DB::beginTransaction();
            $currentLeaveDay = LeaveDay::where([
                ["member_registration_id", $member_registration_id],
                ["submission_date", "<=", $now],
                [DB::raw("DATE_ADD(submission_date, INTERVAL days DAY)"), ">=", $now],
            ])->first();
            $lessLeaveDays = LeaveDay::where([["id", ">", $currentLeaveDay->id], ["member_registration_id", $member_registration_id]]);
            //  hitung total uang lalu tampilkan
            $newDay = DateDiff($currentLeaveDay->submission_date, $now);
            // dd($currentLeaveDay->price);
            if ($newDay == 0) {
                DB::rollback();
                return redirect()->route('member-active.index')->with('errorr', "Cuti yang baru saja dibuat, tidak bisa dihentikan (hapus data)!");
            }

            $currentLeaveDay->update([
                'days' => $newDay - 1
            ]);

            if (sizeof($lessLeaveDays->get()) > 0) {
                $lessLeaveDays->delete();
            }
            DB::commit();
            return redirect()->route('member-active.index')->with('success', 'Leave days successfully stop!');
        } catch (\Throwable $th) {
            DB::rollback();
            return redirect()->route('member-active.index')->with('error', $th->getMessage());
        }
    }

    public function history()
    {
        $fromDate   = Request()->input('fromDate');
        $fromDate  = $fromDate ? DateFormat($fromDate) : NowDate();

        $toDate     = Request()->input('toDate');
        $toDate = $toDate ? DateFormat($toDate) : NowDate();

        $memberRegistrations = MemberRegistration::history("", "", $fromDate, $toDate);
        // dd($memberRegistrations);

        $idCodeMaxCount = env("ID_CODE_MAX_COUNT", 3);
        $data = [
            'title'                 => 'Member Registration History',
            'fromDate'              => $fromDate,
            'toDate'                => $toDate,
            'memberRegistrations'   => $memberRegistrations,
            'content'               => 'admin/member-registration/history',
            'idCodeMaxCount'        => $idCodeMaxCount,
        ];

        return view('admin.layouts.wrapper', $data);
    }

    public function historyDetail($id)
    {
        $mr = MemberRegistration::find($id);
        $status = $mr->members->status;
        $memberId = $mr->members->id;

        // if ($status == "one_day_visit") {
        //     $memberRegistrations = DB::table('member_registrations as a')
        //         ->select(
        //             'a.id',
        //             'a.start_date',
        //             'a.description',
        //             'a.days as member_registration_days',
        //             'a.old_days',
        //             'a.package_price as mr_package_price',
        //             'a.admin_price as mr_admin_price',
        //             'b.full_name as member_name',
        //             'b.address',
        //             'b.member_code',
        //             'b.phone_number',
        //             'b.photos',
        //             'b.gender',
        //             'b.nickname',
        //             'b.ig',
        //             'b.emergency_contact',
        //             'b.email',
        //             'b.born',
        //             'b.status as member_status',
        //             'c.id as member_package_id',
        //             'c.package_name',
        //             'c.days',
        //             'c.package_price',
        //             'c.admin_price',
        //             'e.id as method_payment_id',
        //             'e.name as method_payment_name',
        //             'f.full_name as staff_name'
        //         )
        //         ->addSelect(
        //             DB::raw('DATE_ADD(a.start_date, INTERVAL a.days DAY) as expired_date'),
        //             DB::raw('CASE WHEN NOW() > DATE_ADD(a.start_date, INTERVAL a.days DAY) THEN "Over" ELSE "Running" END as status')
        //         )
        //         ->join('members as b', 'a.member_id', '=', 'b.id')
        //         ->join('member_packages as c', 'a.member_package_id', '=', 'c.id')
        //         ->join('method_payments as e', 'a.method_payment_id', '=', 'e.id')
        //         ->join('users as f', 'a.user_id', '=', 'f.id')
        //         ->where('a.id', $id)
        //         ->get();
        // } else {
        //     $memberRegistrations = MemberRegistration::historyById("", $id);
        // }


        $memberRegistrations = MemberRegistration::historyById("", $id);

        // dd($memberRegistrations);

        $checkInMemberRegistration = MemberRegistration::find($id);
        $data = [
            'title'                     => 'Member Registration Detail',
            'memberRegistrations'       => $memberRegistrations,
            'memberRegistration'        => MemberRegistration::find($id),
            'members'                   => Member::get(),
            'memberRegistrationCheckIn' => $checkInMemberRegistration->memberRegistrationCheckIn,
            'status'                    => $status,
            'content'                   => 'admin/member-registration/detail-history-check-in',
        ];

        return view('admin.layouts.wrapper', $data);
    }
}
