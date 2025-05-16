<?php

namespace App\Http\Controllers\Member;

use App\Http\Controllers\Controller;
use App\Models\Member\Member;
use App\Models\Member\MemberRegistration;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class MemberRenewalController extends Controller
{
    public function renewMemberRegistration(Request $request, $id)
    {
        DB::beginTransaction();
        try {
            $memberRegistration = MemberRegistration::findOrFail($id);

            $data = $request->validate([
                'member_package_id' => 'required|exists:member_packages,id',
                'start_date' => 'required',
                'method_payment_id' => 'required|exists:method_payments,id',
                'fc_id' => 'required|exists:fitness_consultants,id',
                'description' => 'nullable',
            ]);

            // Tambahkan logika tambahan di sini jika diperlukan

            $data['member_id'] = $memberRegistration->member_id;
            $data['user_id'] = Auth::id();

            MemberRegistration::create($data);

            DB::commit();

            return redirect()->route('member.index')->with('success', 'Member Registration Renewal Added Successfully');
        } catch (Exception $e) {
            DB::rollback();
            throw $e;
        }
    }
}
