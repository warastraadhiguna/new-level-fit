<?php

namespace App\Http\Controllers;

use App\Models\Member\Member;
use App\Models\Member\MemberPackage;
use App\Models\MethodPayment;
use App\Models\User;
use Illuminate\Support\Facades\Auth;

class MergeCreateDataController extends Controller
{
    public function index()
    {
        $branchId = Auth::user()->branch_store_id;
        
        $data = [
            'title'             => 'Lead',
            'memberPackage'         => MemberPackage::where("branch_store_id", $branchId)->get(),
            'methodPayment'         => MethodPayment::get(),
            'fitnessConsultant'     => User::where('role', 'FC')->get(),

            'content'           => 'admin/merge-create/index'
        ];

        return view('admin.layouts.wrapper', $data);
    }

    public function create()
    {
        $memberPackage = MemberPackage::where('days', '1')->get();
        
        $data = [
            'title'             => '1 Day Visit Lead',
            'members'           => Member::get(),
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