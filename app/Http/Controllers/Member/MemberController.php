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
    public function index()
    {
        $fromDate   = Request()->input('fromDate');
        $toDate     = Request()->input('toDate');

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
                'a.lo_is_used',
                'a.lo_start_date',
                'a.lo_days',
                'a.lo_pt_by',
                'a.lo_end',
                'a.created_at',
                DB::raw("CASE WHEN NOW() < DATE_ADD(a.created_at, INTERVAL a.lo_days DAY) THEN 'Running' ELSE 'Over' END as lo_status")
            )
            ->where('a.status', '=', 'sell')
            ->orderBy('created_at', 'desc')
            ->orderBy('updated_at', 'desc')
            ->get();

        $data = [
            'title'             => 'Member List',
            'members'           => $sell,
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
            'memberPackage'         => MemberPackage::get(),
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
            'memberPackage'         => MemberPackage::get(),
            'methodPayment'         => MethodPayment::get(),
            'fitnessConsultant'     => FitnessConsultant::get(),
            'content'               => 'admin/members/second-edit',
        ];

        return view('admin.layouts.wrapper', $data);
    }

    public function secondUpdate(Request $request, $id)
    {
        $item = Member::find($id);
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

            if ($item->photos != null) {
                $realLocation = "storage/" . $item->photos;
                if (file_exists($realLocation) && !is_dir($realLocation)) {
                    unlink($realLocation);
                }
            }

            $photos = $request->file('photos');
            $file_name = time() . '-' . $photos->getClientOriginalName();

            $data['photos'] = $request->file('photos')->store('assets/member', 'public');
        } else {
            $data['photos'] = $item->photos;
        }


        $item->update($data);

        return redirect()->route('members.index')->with('success', 'Member Updated Successfully');
    }

    public function update(Request $request, string $id)
    {
        $fc = Auth::user();
        // dd($fc->role);
        DB::beginTransaction();
        try {
            $member = Member::findOrFail($id);

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

            $data['start_date'] =  $data['start_date'] . ' ' .  $data['start_time'];
            $dateTime = new \DateTime($data['start_date']);
            $data['start_date'] = $dateTime->format('Y-m-d H:i:s');
            unset($data['start_time']);

            $data['admin_price'] = $package->admin_price;
            $data['days'] = $package->days;

            // Perbarui data anggota
            $member->update(array_intersect_key($data, array_flip([
                'full_name', 'phone_number', 'status', 'nickname',
                'born', 'member_code', 'card_number', 'email', 'ig', 'emergency_contact', 'ec_name', 'gender', 'address', 'photos'
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

    public function destroy(Member $member)
    {
        try {
            if ($member->photos != null) {
                $realLocation = "storage/" . $member->photos;
                if (file_exists($realLocation) && !is_dir($realLocation)) {
                    unlink($realLocation);
                }
            }

            Storage::delete($member->photos);
            $member->delete();
            return redirect()->back()->with('success', 'Member Deleted Successfully');
        } catch (\Throwable $e) {
            return redirect()->back()->with('errorr', 'Member Deleted Failed, please check other session where using this member');
        }
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
