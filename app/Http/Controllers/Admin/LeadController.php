<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\LeadStoreRequest;
use App\Models\Lead;
use App\Models\Staff\CustomerService;
use App\Models\Staff\FitnessConsultant;
use Illuminate\Http\Request;

class LeadController extends Controller
{
    public function index()
    {
        $data = [
            'title'                 => 'Leads List',
            'lead'                  => Lead::get(),
            'fitnessConsultants'    => FitnessConsultant::get(),
            'customerServices'      => CustomerService::get(),
            'content'               => 'admin/lead/index'
        ];

        return view('admin.layouts.wrapper', $data);
    }

    public function create()
    {
        $data = [
            'title'                 => 'New Lead',
            'lead'                  => Lead::get(),
            'fitnessConsultants'    => FitnessConsultant::get(),
            'customerServices'      => CustomerService::get(),
            'content'               => 'admin/lead/create',
        ];
        return view('admin.layouts.wrapper', $data);
    }

    public function store(LeadStoreRequest $request)
    {
        $data = $request->all();
        $data['guest_code'] = 'LC-' . mt_rand(00000, 99999);

        Lead::create($data);
        return redirect()->route('leads.index')->with('message', 'Lead Added Successfully');
    }

    public function edit(string $id)
    {
        $data = [
            'title'                 => 'Edit Lead',
            'lead'                  => Lead::find($id),
            'fitnessConsultants'    => FitnessConsultant::get(),
            'customerServices'      => CustomerService::get(),
            'content'               => 'admin/lead/edit'
        ];
        return view('admin.layouts.wrapper', $data);
    }

    public function update(Request $request, string $id)
    {
        $item = Lead::find($id);
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
        return redirect()->route('leads.index')->with('message', 'lead Updated Successfully');
    }

    public function destroy($id)
    {
        try {
            $lead = Lead::find($id);
            $lead->delete();
            return redirect()->back()->with('message', 'Lead Deleted Successfully');
        } catch (\Throwable $th) {
            return redirect()->back()->with('error', 'Deleted Failed, please check other page where using this lead');
        }
    }
}
