<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\MethodPayment;
use Illuminate\Http\Request;

class MethodPaymentController extends Controller
{
    public function index()
    {
        $data = [
            'title'         => 'Payment Method',
            'paymentMethod' => MethodPayment::get(),
            'content'       => 'admin/payment-method/index'
        ];

        return view('admin.layouts.wrapper', $data);
    }

    public function create()
    {
        //
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name'  => 'required|string|max:200'
        ]);

        MethodPayment::create($data);
        return redirect()->route('payment-method.index')->with('message', 'Payment Method Added Successfully');
    }

    public function edit(string $id)
    {
        //
    }

    public function update(Request $request, string $id)
    {
        $item = MethodPayment::find($id);
        $data = $request->validate([
            'name'              => 'required|string|max:200'
        ]);

        $item->update($data);
        return redirect()->route('payment-method.index')->with('message', 'Payment Method Updated Successfully');
    }

    public function destroy($id)
    {
        try {
            $paymentMethod = MethodPayment::find($id);
            $paymentMethod->delete();
            return redirect()->back()->with('message', 'Payment Method Deleted Successfully');
        } catch (\Throwable $th) {
            return redirect()->back()->with('error', 'Payment Deleted Failed, please check other session where using this payment method');
        }
    }
}
