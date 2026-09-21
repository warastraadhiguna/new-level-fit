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
            'memberPackage'         => MemberPackage::where("branch_store_id", $branchId)
                ->visibleToUser(Auth::user())
                ->get(),
            'methodPayment'         => MethodPayment::get(),
            'fitnessConsultant'     => User::where('role', 'FC')->get(),

            'content'           => 'admin/merge-create/index'
        ];

        return view('admin.layouts.wrapper', $data);
    }




}
