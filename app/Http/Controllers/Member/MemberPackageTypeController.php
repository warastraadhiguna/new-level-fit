<?php

namespace App\Http\Controllers\Member;

use App\Http\Controllers\Controller;
use App\Models\Member\MemberPackageType;
use Illuminate\Http\Request;

class MemberPackageTypeController extends Controller
{
    public function index()
    {
        $data = [
            'title'             => 'Member Package Type List',
            'memberPackageType' => MemberPackageType::get(),
            'content'           => 'admin/member-package-type/index'
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
            'package_type_name'      => 'required'
        ]);

        MemberPackageType::create($data);
        return redirect()->route('member-package-type.index')->with('message', 'Member Package Type Added Successfully');
    }

    public function edit(string $id)
    {
        //
    }

    public function update(Request $request, string $id)
    {
        $item = MemberPackageType::find($id);
        $data = $request->validate([
            'package_type_name'      => 'required'
        ]);

        $item->update($data);
        return redirect()->route('member-package-type.index')->with('message', 'Member Package Type Updated Successfully');
    }

    public function destroy(MemberPackageType $memberPackageType)
    {
        try {
            $memberPackageType->delete();
            return redirect()->back()->with('message', 'Member Package Type Deleted Successfully');
        } catch (\Throwable $th) {
            return redirect()->back()->with('error', 'Deleted Failed, please check other page where using this member package type');
        }
    }
}
