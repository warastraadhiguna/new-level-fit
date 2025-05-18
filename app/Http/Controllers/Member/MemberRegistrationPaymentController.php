<?php

namespace App\Http\Controllers\Member;

use App\Http\Controllers\Controller;
use App\Models\Member\MemberRegistrationPayment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class MemberRegistrationPaymentController extends Controller
{
    public function store(Request $request)
    {
        $data = $request->validate([
            'member_registration_id'  => 'required',
            'value'  => 'required|string',
            'note'  => 'required|string',
            'method_payment_id'  => 'required',
        ]);
        $data["user_id"] = Auth::user()->id;
        $data["value"] = str_replace(".", "", $data["value"]);
        if ($data["value"] + $request->value_sum > $request->price) {
            return redirect()->back()->with('errorr', 'The value is more than price should paid!!');
        }

        MemberRegistrationPayment::create($data);
        return redirect("member-active/". $data["member_registration_id"] ."/edit")->with('message', 'Payment  Added Successfully');
    }
    public function destroy($id)
    {
        try {
            $memberRegistrationPayment = MemberRegistrationPayment::find($id);
            $memberRegistrationPayment->delete();
            return redirect()->back()->with('success', 'Payment Deleted Successfully');
        } catch (\Throwable $e) {
            return redirect()->back()->with('errorr', 'Payment Deleting Failed');
        }
    }
}
