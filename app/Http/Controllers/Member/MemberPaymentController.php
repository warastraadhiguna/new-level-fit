<?php

namespace App\Http\Controllers\Member;

use App\Http\Controllers\Controller;
use App\Models\Member\Member;
use Illuminate\Http\Request;

class MemberPaymentController extends Controller
{
    public function index()
    {
        $data = [
            'title'         => 'Member Payment List',
            'memberPayment' => Member::get(),
            'content'       => 'admin/member-payment/index'
        ];

        return view('admin.layouts.wrapper', $data);
    }
}
