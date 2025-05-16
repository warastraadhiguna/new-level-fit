<?php

namespace App\Http\Controllers\Report;

use App\Http\Controllers\Controller;
use App\Models\Appointment;
use App\Models\Member\Member;
use Illuminate\Http\Request;

class ReportFitnessController extends Controller
{
    public function index()
    {
        $data = [
            'title'                 => 'Report GYM Club List',
            'content'               => 'admin/gym-report/index'
        ];

        return view('admin.layouts.wrapper', $data);
    }
}
