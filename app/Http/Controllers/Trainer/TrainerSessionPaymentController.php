<?php

namespace App\Http\Controllers\Trainer;

use App\Http\Controllers\Controller;
use App\Models\Trainer\TrainerSession;
use App\Models\Trainer\TrainerSessionPayment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class TrainerSessionPaymentController extends Controller
{
    public function store(Request $request)
    {
        $data = $request->validate([
            'trainer_session_id'  => 'required|exists:trainer_sessions,id',
            'value'  => 'required|string',
            'note'  => 'required|string',
            'method_payment_id'  => 'required|exists:method_payments,id',
        ]);
        $data["user_id"] = Auth::user()->id;
        $data["value"] = (int) str_replace(".", "", $data["value"]);
        $data["note"] = trim($data["note"]);

        if ($data["note"] === '') {
            return redirect()->back()->withInput()->withErrors(['note' => 'The note field is required.']);
        }

        if ($data["value"] <= 0) {
            return redirect()->back()->with('errorr', 'Payment value must be greater than 0');
        }

        try {
            DB::transaction(function () use ($data) {
                $trainerSession = TrainerSession::query()
                    ->lockForUpdate()
                    ->findOrFail($data["trainer_session_id"]);

                $price = (int) $trainerSession->package_price + (int) $trainerSession->admin_price;
                $paidAmount = TrainerSessionPayment::query()
                    ->where('trainer_session_id', $trainerSession->id)
                    ->sum('value');

                if ($paidAmount >= $price) {
                    throw new RuntimeException('This trainer session has already been fully paid.');
                }

                if ($paidAmount + $data["value"] > $price) {
                    throw new RuntimeException('The value is more than price should paid!!');
                }

                TrainerSessionPayment::create($data);
            });
        } catch (RuntimeException $exception) {
            return redirect()->back()->with('errorr', $exception->getMessage());
        }

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
