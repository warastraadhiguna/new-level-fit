<?php

namespace App\Http\Controllers\Member;

use App\Exports\MissedGuestExport;
use App\Http\Controllers\Controller;
use App\Models\Appointment;
use App\Models\Member\Member;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Http\Request;

class MissedGuestController extends Controller
{
    public function index()
    {
        $fromDate   = Request()->input('fromDate');
        $toDate     = Request()->input('toDate');

        $excel = Request()->input('excel');
        if ($excel && $excel == "1") {
            return Excel::download(new MissedGuestExport(), 'Missed Guest, ' . $fromDate . ' to ' . $toDate . '.xlsx');
        }
        
        $auth = Auth::user()->id;
        $authRole = Auth::user()->role;

        if ($authRole == "FC"){
            $data = [
                'title'             => 'Missed Guest',
                'members'           => Member::where('fc_candidate_id', $auth)->where('status', 'missed_guest')->get(),
                'fitnessConsultant' => User::where('role', 'FC')->get(),
                'content'           => 'admin/members/missed-guest'
            ];
        } else {
            $data = [
                'title'             => 'Missed Guest',
                'members'           => Member::where('status', 'missed_guest')->get(),
                'fitnessConsultant' => User::where('role', 'FC')->get(),
                'content'           => 'admin/members/missed-guest'
            ];
        }
        return view('admin.layouts.wrapper', $data);

    }

    public function appointment($id)
    {
        $data = [
            'title'             => 'Missed Guest',
            'members'           => Member::find($id),
            'fitnessConsultant' => User::where('role', 'FC')->get(),
            'content'           => 'admin/members/appointment'
        ];

        return view('admin.layouts.wrapper', $data);
    }

    public function storeAppointment(Request $request, $id)
    {
        $members = Member::find($id);
        // dd($members->id);
        $data = $request->validate([
            'member_id'         => 'nullable|exists:members,id',
            'appointment_date'  => 'nullable'
        ]);
        
        $time = '00:00:00';
        $data['appointment_date'] = Carbon::parse($data['appointment_date'])->format('Y-m-d' . ' ' . $time);
        
        $data['member_id'] = $members->id;
        // dd($data['member_id']);
        Appointment::create($data);
        return redirect()->route('missed-guest.index')->with('success', 'Appointment Successfully');
    }
}