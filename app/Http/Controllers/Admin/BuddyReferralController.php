<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\BuddyReferralStoreRequest;
use App\Models\BuddyReferral;
use App\Models\Staff\CustomerService;
use App\Models\Staff\FitnessConsultant;
use Illuminate\Http\Request;

class BuddyReferralController extends Controller
{
    public function index()
    {
        $data = [
            'title'                 => 'Buddy Referral List',
            'buddyReferral'         => BuddyReferral::get(),
            'fitnessConsultants'    => FitnessConsultant::get(),
            'customerServices'      => CustomerService::get(),
            'content'               => 'admin/buddy-referral/index'
        ];

        return view('admin.layouts.wrapper', $data);
    }

    public function create()
    {
        $data = [
            'title'                 => 'New Buddy Referral',
            'content'               => 'admin/buddy-referral/create',
            'buddyReferral'         => BuddyReferral::get(),
            'fitnessConsultants'    => FitnessConsultant::get(),
            'customerServices'      => CustomerService::get(),
        ];
        return view('admin.layouts.wrapper', $data);
    }

    public function store(BuddyReferralStoreRequest $request)
    {
        $data = $request->all();
        $data['buddy_referral_code'] = 'BR-' . mt_rand(00000, 99999);

        BuddyReferral::create($data);
        return redirect()->route('buddy-referral.index')->with('message', 'Buddy Referral Added Successfully');
    }

    public function edit(string $id)
    {
        $data = [
            'title'                 => 'Edit Buddy Referral',
            'buddyReferral'         => BuddyReferral::find($id),
            'fitnessConsultants'    => FitnessConsultant::get(),
            'customerServices'      => CustomerService::get(),
            'content'               => 'admin/buddy-referral/edit'
        ];
        return view('admin.layouts.wrapper', $data);
    }

    public function update(Request $request, string $id)
    {
        $item = BuddyReferral::find($id);
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
        return redirect()->route('buddy-referral.index')->with('message', 'Buddy Referral Updated Successfully');
    }

    public function destroy(BuddyReferral $buddyReferral)
    {
        try {
            $buddyReferral->delete();
            return redirect()->back()->with('message', 'Buddy Referral Deleted Successfully');
        } catch (\Throwable $th) {
            return redirect()->back()->with('error', 'Deleted Failed, please check other page where using this buddy referral');
        }
    }
}
