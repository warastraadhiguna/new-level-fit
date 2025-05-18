<?php

namespace App\Http\Controllers\Trainer;

use App\Http\Controllers\Controller;
use App\Models\Trainer\TrainerSessionPayment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class TrainerSessionPaymentController extends Controller
{
    public function store(Request $request)
    {
        $data = $request->validate([
            'trainer_session_id'  => 'required',
            'value'  => 'required|string',
            'note'  => 'required|string',
            'method_payment_id'  => 'required',
        ]);
        $data["user_id"] = Auth::user()->id;
        $data["value"] = str_replace(".", "", $data["value"]);
        if ($data["value"] + $request->value_sum > $request->price) {
            return redirect()->back()->with('errorr', 'The value is more than price should paid!!');
        }

        TrainerSessionPayment::create($data);
        return redirect("trainer-session/". $data["trainer_session_id"] ."/edit")->with('message', 'Payment  Added Successfully');
    }
    public function destroy($id)
    {
        try {
            $trainerSessionPayment = TrainerSessionPayment::find($id);
            $trainerSessionPayment->delete();
            return redirect()->back()->with('success', 'Payment Deleted Successfully');
        } catch (\Throwable $e) {
            return redirect()->back()->with('errorr', 'Payment Deleting Failed');
        }
    }
}
