<?php

namespace App\Http\Controllers\Report;

use App\Http\Controllers\Controller;
use App\Models\Appointment;
use App\Models\Staff\CustomerService;
use App\Models\Staff\FitnessConsultant;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AppointmentListController extends Controller
{
    public function index()
    {
        $data = [
            'title'                 => 'Report GYM Club List',
            'appointment'           => Appointment::get(),
            'fitnessConsultants'    => FitnessConsultant::get(),
            'customerServices'      => CustomerService::get(),
            'content'               => 'admin/gym-report/appointment-list/index'
        ];

        return view('admin.layouts.wrapper', $data);
    }

    public function filter(Request $request)
    {
        $appointment = Appointment::orderBy('id', 'desc')
            ->when(
                $request->startDate && $request->endDate,
                function (Builder $builder) use ($request) {
                    $builder->whereBetween(
                        DB::raw('DATE(created_at)'),
                        [
                            $request->startDate,
                            $request->endDate
                        ]
                    );
                }
            )->paginate(10);

        $data = [
            'appointment'           => $appointment,
            'request'               => $request,
            'fitnessConsultants'     => User::get(),
            'customerServices'       => User::get(),
            'content'               => 'admin/gym-report/appointment-list/list'
        ];

        return view('admin.layouts.wrapper', $data);
    }

    public function allData()
    {
        $data = [
            'title'                 => 'Report GYM Club List',
            'appointment'           => Appointment::get(),
            'fitnessConsultants'     => FitnessConsultant::get(),
            'customerServices'       => CustomerService::get(),
            'content'               => 'admin/gym-report/appointment-list/all-data'
        ];

        return view('admin.layouts.wrapper', $data);
    }
}
