<?php

namespace App\Http\Controllers;

use App\Models\Member\Member;
use App\Models\Member\MemberPackage;
use App\Models\Member\MemberRegistration;
use App\Models\MethodPayment;
use App\Models\Staff\FitnessConsultant;
use App\Models\Staff\PersonalTrainer;
use App\Models\Trainer\TrainerPackage;
use App\Models\Trainer\TrainerSession;
use App\Models\User;
use Illuminate\Http\Request;

class MergeCreateDataController extends Controller
{
    public function index()
    {
        $data = [
            'title'             => 'Lead',
            // Create Member
            'members'           => Member::get(),
            'users'             => User::get(),
            'memberLastCode'    => Member::latest('id')->first(),

            // Create Member Registration
            'memberRegistration'    => MemberRegistration::get(),
            'memberPackage'         => MemberPackage::get(),
            'methodPayment'         => MethodPayment::get(),
            'fitnessConsultant'     => User::where('role', 'FC')->get(),

            // Create Trainer Session
            'trainerSession'    => TrainerSession::all(),
            'personalTrainers'  => PersonalTrainer::get(),
            'trainerPackages'   => TrainerPackage::get(),

            'content'           => 'admin/merge-create/index'
        ];

        return view('admin.layouts.wrapper', $data);
    }

    public function create()
    {
        $memberPackage = MemberPackage::where('days', '1')->get();
        // dd($memberPackage);
        $data = [
            'title'             => '1 Day Visit Lead',
            'members'           => Member::get(),
            'users'             => User::get(),

            // Create Member Registration
            'memberRegistration'    => MemberRegistration::get(),
            // 'memberPackage'         => MemberPackage::find(35),
            'memberPackage'         => $memberPackage,
            'methodPayment'         => MethodPayment::all(),
            'content'           => 'admin/one-visit/onevisit'
        ];

        return view('admin.layouts.wrapper', $data);
    }

    public function openMembers()
    {
        $members = Member::where('status', 'one_day_visit')->get();

        return response()->json($members);
    }
}
