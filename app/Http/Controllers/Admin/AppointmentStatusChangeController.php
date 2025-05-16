<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AppointmentStatusChangeController extends Controller
{
    public function appointment_status_show($id)
    {
        $appointment = DB::table('appointments')->select('status')->where('id', '=', $id)->first();

        if ($appointment->status == 'Hide' || $appointment->status == 'Missed Guest') {
            $status = 'Show';
        }

        $values = array('status' => $status);
        DB::table('appointments')->where('id', $id)->update($values);
        session()->flash('msg', 'Sukses');
        return redirect()->route('appoitment.index');
    }

    public function appointment_status_hide($id)
    {
        $appointment = DB::table('appointments')->select('status')->where('id', '=', $id)->first();

        if ($appointment->status == 'Show' || $appointment->status == 'Missed Guest') {
            $status = 'Hide';
        }

        $values = array('status' => $status);
        DB::table('appointments')->where('id', $id)->update($values);
        session()->flash('msg', 'Sukses');
        return redirect()->route('appoitment.index');
    }

    public function appointment_status_missed_guest($id)
    {
        $appointment = DB::table('appointments')->select('status')->where('id', '=', $id)->first();

        if ($appointment->status == 'Show' || $appointment->status == 'Hide') {
            $status = 'Missed Guest';
        }

        $values = array('status' => $status);
        DB::table('appointments')->where('id', $id)->update($values);
        session()->flash('msg', 'Sukses');
        return redirect()->route('appoitment.index');
    }
}
