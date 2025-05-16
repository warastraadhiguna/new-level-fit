<?php

namespace App\Http\Controllers\Member;

use App\Http\Controllers\Controller;
use App\Models\Member\MemberPackageCategory;
use Illuminate\Http\Request;

class MemberPackageCategoryController extends Controller
{
    public function index()
    {
        $data = [
            'title'                 => 'Member Package Category List',
            'memberPackageCategory' => MemberPackageCategory::get(),
            'content'               => 'admin/member-package-category/index'
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
            'package_category_name'      => 'required'
        ]);

        MemberPackageCategory::create($data);
        return redirect()->route('member-package-category.index')->with('message', 'Member Package Category Added Successfully');
    }

    public function edit(string $id)
    {
        //
    }

    public function update(Request $request, string $id)
    {
        $item = MemberPackageCategory::find($id);
        $data = $request->validate([
            'package_category_name'      => 'required'
        ]);

        $item->update($data);
        return redirect()->route('member-package-category.index')->with('message', 'Member Package Category Updated Successfully');
    }

    public function destroy($id)
    {
        try {
            $data = MemberPackageCategory::find($id);
            $data->delete();
            return redirect()->back()->with('message', 'Member Package Category Deleted Successfully');
        } catch (\Throwable $th) {
            return redirect()->back()->with('error', 'Deleted Failed, please check other session where using this member package category');
        }
    }
}
