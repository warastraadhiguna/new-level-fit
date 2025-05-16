<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\AppointmentStoreRequest;
use App\Models\Appointment;
use App\Models\Staff\CustomerService;
use App\Models\Staff\FitnessConsultant;
use Illuminate\Http\Request;

class AppointmentController extends Controller
{
    public function index()
    {
        $data = [
            'title'                 => 'Appointment List',
            'appointment'            => Appointment::get(),
            'fitnessConsultants'    => FitnessConsultant::get(),
            'customerServices'      => CustomerService::get(),
            'content'               => 'admin/appointment/index'
        ];

        return view('admin.layouts.wrapper', $data);
    }

    public function create()
    {
        $data = [
            'title'                 => 'New Appointment',
            'content'               => 'admin/appointment/create',
            'appointment'            => Appointment::get(),
            'fitnessConsultants'    => FitnessConsultant::get(),
            'customerServices'      => CustomerService::get(),
        ];
        return view('admin.layouts.wrapper', $data);
    }

    public function store(AppointmentStoreRequest $request)
    {
        $data = $request->all();
        $data['appointment_code'] = 'AP-' . mt_rand(00000, 99999);

        Appointment::create($data);
        return redirect()->route('appointment.index')->with('message', 'Appointment Added Successfully');
    }

    public function edit(string $id)
    {
        $data = [
            'title'                 => 'Edit Appointment',
            'appointment'            => Appointment::find($id),
            'fitnessConsultants'    => FitnessConsultant::get(),
            'customerServices'      => CustomerService::get(),
            'content'               => 'admin/appointment/edit'
        ];
        return view('admin.layouts.wrapper', $data);
    }

    public function update(Request $request, string $id)
    {
        $item = Appointment::find($id);
        $data = $request->validate([
            'date_time'             => '',
            'buddy_referral_code'   => '',
            'referral_name'         => '',
            'full_name'             => '',
            'phone_number'          => '',
            'email'                 => '',
            'description'           => '',
            'fc_id'                 => 'exists:fitness_consultants,id',
            'cs_id'                 => 'exists:customer_services,id'
        ]);

        $item->update($data);
        return redirect()->route('appointment.index')->with('message', 'Appointment Updated Successfully');
    }

    public function destroy($id)
    {
        try {
            $appointment = Appointment::find($id);
            $appointment->delete();
            return redirect()->back()->with('message', 'Appointment Deleted Successfully');
        } catch (\Throwable $th) {
            return redirect()->back()->with('error', 'Deleted Failed, please check other page where using this appointment');
        }
    }
}
