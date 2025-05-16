<?php

namespace App\Http\Controllers\Member;

use App\Exports\MemberActiveExport;
use App\Exports\MemberPendingExport;
use App\Http\Controllers\Controller;
use App\Models\Member\LeaveDay;
use App\Models\Member\Member;
use App\Models\Member\MemberPackage;
use App\Models\Member\MemberRegistration;
use App\Models\MethodPayment;
use App\Models\SourceCode;
use App\Models\Staff\FitnessConsultant;
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

        $memberRegistrations = DB::table('member_registrations as a')
            ->select(
                'a.id',
                'a.start_date',
                // 'a.description',
                'a.days as member_registration_days',
                'a.package_price as mr_package_price',
                'a.admin_price as mr_admin_price',
                'a.updated_at',
                'b.id as member_id',
                'b.full_name as member_name',
                'b.member_code',
                'b.phone_number',
                'b.born',
                'b.photos',
                'b.gender',
                'b.id_code_count',
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
                'ld.days as number_of_leave_days',
                'ld.total_days'
            )
            ->addSelect(
                DB::raw("'bg-dark' as birthdayCelebrating"), //0 tidak ultah, 3 hari lagi ultah, 2 ha   ri lagi, 1 hari lagi
                // DB::raw('(SELECT SUM(ld.days) FROM leave_days ld WHERE ld.member_registration_id = a.id) AS total_leave_days'),
                DB::raw('DATE_ADD(a.start_date, INTERVAL COALESCE(ld.days, 0) + a.days DAY) as expired_date'),
                // Expired leave days
                DB::raw('DATE_ADD(ld.submission_date, INTERVAL ld.days DAY) as expired_leave_days'),
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
                DB::raw('DATEDIFF(CONCAT(YEAR(CURDATE()), "-", MONTH(b.born), "-", DAY(b.born)), CURDATE()) as days_until_birthday'), // tambahkan ini untuk mendapatkan jumlah hari sampai ulang tahun
                // DB::raw('(COALESCE(ld.total_days, 0) + a.days) as total_leave_days'),
            )
            // ->leftJoin(DB::raw('(SELECT SUM(ld.days) FROM leave_days ld WHERE ld.member_registration_id = a.id) AS total_leave_days'), 'ld.max_id', '=', 'a.id')
            ->join('members as b', 'a.member_id', '=', 'b.id')
            ->join('member_packages as c', 'a.member_package_id', '=', 'c.id')
            ->join('method_payments as e', 'a.method_payment_id', '=', 'e.id')
            ->join('users as f', 'a.user_id', '=', 'f.id')
            ->join('fitness_consultants as g', 'a.fc_id', '=', 'g.id')
            // ->leftJoin('leave_days as ld', 'a.id', '=', 'ld.member_registration_id')
            // LEAVE DAYS
            // ->leftJoin(DB::raw('(select ifnull(total_days,0) as total_days, max_id, lds.* from leave_days as lds inner join (select max(id) as max_id, SUM(days) as total_days from leave_days group by member_registration_id) as view_max_id on view_max_id.max_id =lds.id) as ld'), 'ld.max_id', '=', 'a.id')
            ->leftJoin(DB::raw('(select ifnull(total_days,0) as total_days, max_id, lds.* from leave_days as lds inner join (select max(id) as max_id, SUM(days) as total_days from leave_days group by member_registration_id) as view_max_id on view_max_id.max_id =lds.id) as ld'), 'ld.max_id', '=', 'a.id')

            // ->leftJoin(DB::raw('(SELECT IFNULL(SUM(days), 0) as total_days, submission_date, days, member_registration_id 
            //         FROM leave_days 
            //         WHERE (member_registration_id, id) IN (SELECT member_registration_id, MAX(id) FROM leave_days GROUP BY member_registration_id) 
            //         GROUP BY member_registration_id, submission_date, days) AS ld'), function ($join) {
            //     $join->on('ld.member_registration_id', '=', 'a.id');
            // })

            // ->leftJoin(DB::raw('(select * from leave_days ld where ld.id in (select max(id) from leave_days group by member_registration_id)) as ld'), function ($join) {
            //     $join->on('ld.member_registration_id', '=', 'a.id');
            // })



            ->leftJoin(DB::raw('(select * from (select a.* from (select * from check_in_members) as a inner join (SELECT max(id)
                                as id FROM check_in_members group by member_registration_id) as b on a.id=b.id) as tableH) as h'), 'a.id', '=', 'h.member_registration_id')
            // ->leftJoin(DB::raw('(select DISTINCT member_registration_id from leave_days) as unique_leave_days'), 'a.id', '=', 'unique_leave_days.member_registration_id')
            ->whereRaw('NOW() BETWEEN a.start_date AND DATE_ADD(a.start_date, INTERVAL a.days DAY)')
            ->orderBy('h.check_in_time', 'desc')
            ->orderBy('h.check_out_time', 'desc')
            ->orderBy('a.created_at', 'desc')
            // ->groupBy(
            //     'a.id',
            //     'a.start_date',
            //     'a.days',
            //     'a.package_price',
            //     'a.admin_price',
            //     'a.updated_at',
            //     'b.id',
            //     'b.full_name',
            //     'b.member_code',
            //     'b.phone_number',
            //     'b.born',
            //     'b.photos',
            //     'b.gender',
            //     'b.id_code_count',
            //     'c.package_name',
            //     'c.days',
            //     'c.package_price',
            //     'e.name',
            //     'f.full_name',
            //     'g.full_name',
            //     'g.phone_number',
            // )
            ->get();

        dd($memberRegistrations);
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
            'idCodeMaxCount'        =>  $idCodeMaxCount,
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

        $memberRegistrations = DB::table('member_registrations as a')
            ->select(
                'a.id',
                'a.start_date',
                'a.description',
                'a.days as member_registration_days',
                'a.old_days',
                'a.package_price as mr_package_price',
                'a.admin_price as mr_admin_price',
                'b.id as member_id',
                'b.full_name as member_name',
                'b.member_code',
                'b.phone_number',
                'b.born',
                'b.photos',
                'b.gender',
            )
            ->addSelect(
                DB::raw('DATE_ADD(a.start_date, INTERVAL COALESCE(ld.days, 0) + a.days DAY) as expired_date'),
                DB::raw('DATE_ADD(ld.submission_date, INTERVAL ld.days DAY) as expired_leave_days'),
            )
            ->join('members as b', 'a.member_id', '=', 'b.id')
            ->join('fitness_consultants as g', 'a.fc_id', '=', 'g.id')
            ->leftJoin('leave_days as ld', 'a.id', '=', 'ld.member_registration_id')
            ->whereRaw('NOW() < a.start_date')
            ->distinct()
            ->get();

        $data = [
            'title'                 => 'Member Pending',
            'memberRegistrations'   => $memberRegistrations,
            'content'               => 'admin/member-registration/pending',
        ];

        return view('admin.layouts.wrapper', $data);
    }

    public function oneDayVisit()
    {
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
            ->join(
                'users as f',
                'a.user_id',
                '=',
                'f.id'
            )
            // ->whereRaw('NOW() BETWEEN a.start_date AND DATE_ADD(a.start_date, INTERVAL a.days DAY)')
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
            'sourceCode'            => SourceCode::get(),
            'memberPackage'         => MemberPackage::get(),
            'methodPayment'         => MethodPayment::get(),
            'fitnessConsultant'     => FitnessConsultant::get(),
            'content'               => 'admin/member-registration/create-page',
        ];

        return view('admin.layouts.wrapper', $data);
    }

    public function memberSecondStore(Request $request)
    {
        // dd($request);
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
                // 'member_package_id'     => 'required_if:status,sell|exists:member_packages,id',
                'start_date'            => 'required_if:status,sell',
                // 'method_payment_id'     => 'required_if:status,sell|exists:method_payments,id',
                // 'fc_id'                 => 'required_if:status,sell|exists:fitness_consultants,id',
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
                $data += $request->validate([
                    'member_package_id'     => 'required|exists:member_packages,id',
                    'start_date'            => 'required',
                    'method_payment_id'     => 'required|exists:method_payments,id',
                    'fc_id'                 => 'required|exists:fitness_consultants,id',
                ]);

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
                    'full_name', 'phone_number', 'status', 'nickname',
                    'born', 'member_code', 'card_number', 'email', 'ig', 'emergency_contact', 'ec_name', 'gender', 'address', 'photos'
                ])));

                $data['member_id'] = $newMember->id;

                MemberRegistration::create(array_intersect_key($data, array_flip([
                    'member_id', 'member_package_id', 'start_date',
                    'method_payment_id', 'fc_id', 'user_id', 'description', 'package_price', 'admin_price', 'days'
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
                        'member_id', 'member_package_id', 'start_date',
                        'method_payment_id', 'user_id', 'description', 'package_price', 'admin_price', 'days'
                    ])));
                } else {
                    // Create new member
                    $newMember = Member::create(array_intersect_key($data, array_flip([
                        'full_name', 'phone_number', 'status'
                    ])));

                    $data['member_id'] = $newMember->id;

                    // Create member registration
                    MemberRegistration::create(array_intersect_key($data, array_flip([
                        'member_id', 'member_package_id', 'start_date',
                        'method_payment_id', 'user_id', 'description', 'package_price', 'admin_price', 'days'
                    ])));
                }
            } else {
                $newMember = Member::create(array_intersect_key($data, array_flip([
                    'full_name', 'phone_number', 'status'
                ])));
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
        $query = DB::table('member_registrations as a')
            ->select(
                'a.id',
                'a.start_date',
                'a.description',
                'a.days as member_registration_days',
                'a.package_price as mr_package_price',
                'a.admin_price as mr_admin_price',
                'b.full_name as member_name',
                'b.nickname',
                'b.member_code',
                'b.card_number',
                'b.address',
                'b.phone_number',
                'b.photos',
                'b.gender',
                'b.nickname',
                'b.ig',
                'b.emergency_contact',
                'b.email',
                'b.born',
                'b.ec_name',
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
                // DB::raw('DATE_ADD(a.start_date, INTERVAL a.days DAY) as expired_date'),
                DB::raw('DATE_ADD(a.start_date, INTERVAL COALESCE(ld.days, 0) + a.days DAY) as expired_date'),
                DB::raw('DATE_ADD(ld.submission_date, INTERVAL ld.days DAY) as expired_leave_days'),

                DB::raw('CASE WHEN NOW() > DATE_ADD(a.start_date, INTERVAL a.days DAY) THEN "Over" ELSE "Running" END as status'),
                DB::raw('CASE 
                WHEN NOW() > DATE_ADD(ld.submission_date, INTERVAL ld.days DAY) THEN "Ended" 
                WHEN NOW() BETWEEN ld.submission_date AND DATE_ADD(ld.submission_date, INTERVAL ld.days DAY) THEN "Freeze" 
                ELSE "No Leave Days" 
                END as leave_day_status'),
                DB::raw('(SELECT SUM(ld.days) FROM leave_days ld WHERE ld.member_registration_id = a.id) AS total_leave_days')
            )
            ->leftjoin('members as b', 'a.member_id', '=', 'b.id')
            ->leftJoin(DB::raw('(select * from leave_days ld where ld.id in (select max(id) from leave_days group by member_registration_id)) as ld'), function ($join) {
                $join->on('ld.member_registration_id', '=', 'a.id');
            })
            // ->leftJoin('leave_days as ld', function ($join) {
            //     $join->on('ld.member_registration_id', '=', 'a.id');
            // })
            ->join('member_packages as c', 'a.member_package_id', '=', 'c.id')
            ->join('method_payments as e', 'a.method_payment_id', '=', 'e.id')
            ->join('users as f', 'a.user_id', '=', 'f.id')
            ->join('fitness_consultants as g', 'a.fc_id', '=', 'g.id')
            ->leftJoin(DB::raw('(select * from (select a.* from (select * from check_in_members) as a inner join (SELECT max(id) as id FROM check_in_members group by member_registration_id) as b on a.id=b.id) as tableH) as h'), 'a.id', '=', 'h.member_registration_id')
            ->whereIn('a.member_id', function ($query) use ($id) {
                $query->select('member_id')->from('member_registrations')->where('id', $id);
            })
            ->get();

        $checkInMemberRegistration = MemberRegistration::find($id);

        $data = [
            'title'                     => 'Member Registration Detail',
            'memberRegistration'        => $query,
            'memberRegistrationCheckIn' => $checkInMemberRegistration->memberRegistrationCheckIn,
            'content'                   => 'admin/member-registration/show',
        ];

        return view('admin.layouts.wrapper', $data);
    }

    public function edit(string $id)
    {
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
                'f.full_name as staff_name',
                'g.id as fc_id',
                'g.full_name as fc_name',
                'g.phone_number as fc_phone_number',
                'h.check_in_time',
                'h.check_out_time'
            )
            ->addSelect(
                DB::raw('DATE_ADD(a.start_date, INTERVAL a.days DAY) as expired_date'),
                DB::raw('CASE WHEN NOW() > DATE_ADD(a.start_date, INTERVAL a.days DAY) THEN "Over" ELSE "Running" END as status')
            )
            ->join('members as b', 'a.member_id', '=', 'b.id')
            ->join('member_packages as c', 'a.member_package_id', '=', 'c.id')
            ->join('method_payments as e', 'a.method_payment_id', '=', 'e.id')
            ->join('users as f', 'a.user_id', '=', 'f.id')
            ->join('fitness_consultants as g', 'a.fc_id', '=', 'g.id')
            ->leftJoin(DB::raw('(select * from (select a.* from (select * from check_in_members) as a inner join (SELECT max(id) as id FROM check_in_members group by member_registration_id) as b on a.id=b.id) as tableH) as h'), 'a.id', '=', 'h.member_registration_id')
            ->where('a.id', $id)
            ->get();

        $data = [
            'title'                 => 'Edit Member Active',
            'memberRegistration'    => MemberRegistration::find($id),
            'memberRegistrations'   => $memberActive->first(),
            'memberPackage'         => MemberPackage::get(),
            'methodPayment'         => MethodPayment::get(),
            'fitnessConsultant'     => FitnessConsultant::get(),
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
        // dd($data);
        $data['user_id'] = Auth::user()->id;

        $selectedPackage = MemberPackage::find($data["member_package_id"]);
        $currentPackage = MemberPackage::find($item->member_package_id);

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
            'fitnessConsultant'     => FitnessConsultant::get(),
            'content'               => 'admin/member-registration/renewal',
        ];

        return view('admin.layouts.wrapper', $data);
    }

    public function renewMemberRegistration(Request $request, $id)
    {
        DB::beginTransaction();
        try {
            $memberRegistration = MemberRegistration::findOrFail($id);

            $data = $request->validate([
                'member_package_id' => 'required|exists:member_packages,id',
                'start_date'        => 'required',
                'method_payment_id' => 'required|exists:method_payments,id',
                'fc_id'             => 'required|exists:fitness_consultants,id',
                'description'       => 'nullable',
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

            MemberRegistration::create($data);

            DB::commit();

            return redirect()->route('member-active.index')->with('success', 'Renewal Successfully');
        } catch (Exception $e) {
            DB::rollback();
            throw $e;
        }
    }

    public function freeze(Request $request, string $id)
    {
        $item = MemberRegistration::find($id);
        // Periksa apakah data ditemukan
        if (!$item) {
            return redirect()->route('member-active.index')->with('errorr', 'Member Registration not found');
        }

        // Simpan data ke tabel leave_days
        $leaveDay = new LeaveDay([
            'member_registration_id'    => $item->id,
            'submission_date'           => Carbon::now()->tz('Asia/Jakarta'),
            'price'                     => $request->input('price'),
            'days'                      => $request->input('expired_date'),
        ]);
        // dd($leaveDay->submission_date);
        $leaveDay->price = str_replace(',', '', $leaveDay['price']);
        $leaveDay->save();

        return redirect()->route('member-active.index')->with('success', 'Cuti Membership Successfully Added');
    }

    public function destroy($id)
    {
        $memberRegistration = MemberRegistration::find($id);

        try {
            $memberRegistration->delete();
            return redirect()->back()->with('success', $memberRegistration->members->full_name . 'member package delete successfully');
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
                'b.full_name as member_name', // alias for members table name column
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
                'g.full_name as fc_name',
                'g.phone_number as fc_phone_number',
                'ld.submission_date',
                'ld.days as number_of_leave_days'
            )
            ->addSelect(
                // DB::raw('DATE_ADD(a.start_date, INTERVAL a.days DAY) as expired_date'),
                DB::raw('DATE_ADD(a.start_date, INTERVAL COALESCE(ld.days, 0) + a.days DAY) as expired_date'),
                DB::raw('CASE WHEN NOW() > DATE_ADD(a.start_date, INTERVAL a.days DAY) THEN "Over" ELSE "Running" END as status'),
                DB::raw('CONCAT(YEAR(CURDATE()), "-", MONTH(b.born), "-", DAY(b.born)) as member_birthday')
            )
            ->join('members as b', 'a.member_id', '=', 'b.id')
            ->join('member_packages as c', 'a.member_package_id', '=', 'c.id')
            ->join('method_payments as e', 'a.method_payment_id', '=', 'e.id')
            ->join('users as f', 'a.user_id', '=', 'f.id')
            ->leftJoin('leave_days as ld', 'a.id', '=', 'ld.member_registration_id')
            ->join('fitness_consultants as g', 'a.fc_id', '=', 'g.id')
            ->where('a.id', $id)
            ->first();

        $fileName1 = $memberRegistration->member_name;
        $fileName2 = $memberRegistration->start_date;

        $pdf = Pdf::loadView('admin/member-registration/agreement', [
            'memberRegistration'        => $memberRegistration,
        ]);
        return $pdf->stream('Membership Agreement-' . $fileName1 . '-' . $fileName2 . '.pdf');
    }

    public function cuti($id)
    {
        $memberRegistration = DB::table('member_registrations as a')
            ->select(
                'a.id',
                'a.start_date',
                'a.description',
                'a.package_price as mr_package_price',
                'a.admin_price as mr_admin_price',
                'a.days as member_registration_days',
                'a.old_days',
                'a.updated_at',
                'a.created_at',
                'b.full_name as member_name', // alias for members table name column
                'c.package_name',
                'c.days',
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
                'g.full_name as fc_name',
                'g.phone_number as fc_phone_number',
                'ld.submission_date',
                'ld.days as leave_days_days',
                'ld.price as leave_days_price'
            )
            ->addSelect(
                DB::raw('DATE_ADD(a.start_date, INTERVAL a.days DAY) as expired_date'),
                DB::raw('DATE_ADD(a.start_date, INTERVAL COALESCE(ld.days, 0) + a.days DAY) as expired_date'),
                DB::raw('CASE WHEN NOW() > DATE_ADD(a.start_date, INTERVAL a.days DAY) THEN "Over" ELSE "Running" END as status'),
                DB::raw('CONCAT(YEAR(CURDATE()), "-", MONTH(b.born), "-", DAY(b.born)) as member_birthday'),
                DB::raw('DATE_ADD(ld.submission_date, INTERVAL ld.days DAY) as expired_leave_days'),
            )
            // ->leftJoin(DB::raw('(select * from leave_days ld where ld.id in (select max(id) from leave_days group by member_registration_id)) as ld'), function ($join) {
            //     $join->on('ld.member_registration_id', '=', 'a.id');
            // })
            ->leftJoin('leave_days as ld', 'a.id', '=', 'ld.member_registration_id')
            ->join('members as b', 'a.member_id', '=', 'b.id')
            ->join('member_packages as c', 'a.member_package_id', '=', 'c.id')
            ->join('method_payments as e', 'a.method_payment_id', '=', 'e.id')
            ->join('users as f', 'a.user_id', '=', 'f.id')
            ->join('fitness_consultants as g', 'a.fc_id', '=', 'g.id')
            ->where('a.id', $id)
            ->first();

        $fileName1 = $memberRegistration->member_name;
        $fileName2 = $memberRegistration->start_date;

        $pdf = Pdf::loadView('admin/member-registration/cuti', [
            'memberRegistration'        => $memberRegistration,
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
            ->join('fitness_consultants as g', 'a.fc_id', '=', 'g.id')
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

    public function excel()
    {
        return Excel::download(new MemberActiveExport(), 'member-active.xlsx');
    }
}
