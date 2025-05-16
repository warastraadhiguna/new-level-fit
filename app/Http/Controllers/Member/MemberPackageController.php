<?php

namespace App\Http\Controllers\Member;

use App\Http\Controllers\Controller;
use App\Http\Requests\MemberPackageStoreRequest;
use App\Http\Requests\MemberPackageUpdateRequest;
use App\Models\Member\MemberPackage;
use App\Models\Member\MemberPackageCategory;
use App\Models\Member\MemberPackageType;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class MemberPackageController extends Controller
{
    public function index()
    {
        $data = [
            'title'                     => 'Member Package List',
            'memberPackage'             => MemberPackage::get(),
            'memberPackageType'         => MemberPackageType::get(),
            'memberPackageCategories'   => MemberPackageCategory::get(),
            'users'                     => User::get(),
            'content'                   => 'admin/member-package/index'
        ];

        return view('admin.layouts.wrapper', $data);
    }

    public function create()
    {
        //
    }

    public function store(MemberPackageStoreRequest $request)
    {
        $data = $request->all();
        $data['user_id'] = Auth::user()->id;
        MemberPackage::create($data);
        return redirect()->route('member-package.index')->with('success', 'Member Package Added Successfully');
    }

    public function edit(string $id)
    {
        //
    }

    public function update(MemberPackageUpdateRequest $request, string $id)
    {
        $item = MemberPackage::find($id);
        $data = $request->all();
        $data['user_id'] = Auth::user()->id;
        $item->update($data);
        return redirect()->route('member-package.index')->with('success', 'Member Package Updated Successfully');
    }

    public function destroy(MemberPackage $memberPackage)
    {
        try {
            $memberPackage->delete();
            return redirect()->back()->with('success', 'Member Package Deleted Successfully');
        } catch (\Throwable $th) {
            return redirect()->back()->with('errorr', 'Gagal menghapus paket ' . $memberPackage->package_name . ', paket member ini sedang dipakai member');
        }
    }

    public function dataSoft()
    {
        $data = [
            'title'             => 'Old Member Package',
            'memberPackages'    => MemberPackage::onlyTrashed()->get(),
            'content'           => 'admin/member-package/soft'
        ];

        return view('admin.layouts.wrapper', $data);
    }

    public function restore($id)
    {
        MemberPackage::withTrashed()->find($id)->restore();
        return redirect()->back()->with('success', 'Data berhasil di restore');
    }

    public function forceDelete($id)
    {
        // $MemberPackage = MemberPackage::onlyTrashed()->find($id)->forceDelete();

        try {
            MemberPackage::onlyTrashed()->find($id)->forceDelete();;
            return redirect()->back()->with('success', 'Member Package Deleted Permanently Successfully');
        } catch (\Throwable $th) {
            return redirect()->back()->with('errorr', 'Gagal menghapus paket member');
        }
    }
}
