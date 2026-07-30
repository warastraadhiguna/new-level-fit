<?php

namespace App\Http\Controllers\Member;

use App\Http\Controllers\Controller;
use App\Http\Requests\MemberPackageStoreRequest;
use App\Http\Requests\MemberPackageUpdateRequest;
use App\Models\BranchStore;
use App\Models\Member\MemberPackage;
use App\Models\Member\MemberPackageCategory;
use App\Models\Member\MemberPackageType;
use App\Models\User;
use App\Support\IdempotentSubmission;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class MemberPackageController extends Controller
{
    public function index()
    {
        $data = [
            'title'                     => 'Member Package List',
            'memberPackage'             => MemberPackage::with(['branchStore', 'users'])->get(),
            'memberPackageType'         => MemberPackageType::get(),
            'memberPackageCategories'   => MemberPackageCategory::get(),
            'users'                     => User::get(),
            'branch_stores'             => BranchStore::get(),
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
        $cacheKey = IdempotentSubmission::claim(
            $request->input('_submission_token'),
            'member-package:create',
            (int) Auth::id()
        );

        if (!$cacheKey) {
            return redirect()->route('member-package.index')
                ->with('success', 'Permintaan ini sudah diterima. Data tidak disimpan dua kali.');
        }

        try {
            DB::transaction(function () use ($request) {
                $data = $request->validated();
                unset($data['_submission_token']);
                $data['user_id'] = Auth::id();

                MemberPackage::create($data);
            });

            IdempotentSubmission::complete($cacheKey);
        } catch (\Throwable $exception) {
            IdempotentSubmission::release($cacheKey);
            throw $exception;
        }

        return redirect()->route('member-package.index')->with('success', 'Member Package Added Successfully');
    }

    public function edit(string $id)
    {
        //
    }

    public function update(MemberPackageUpdateRequest $request, string $id)
    {
        $cacheKey = IdempotentSubmission::claim(
            $request->input('_submission_token'),
            'member-package:update:' . $id,
            (int) Auth::id()
        );

        if (!$cacheKey) {
            return redirect()->route('member-package.index')
                ->with('success', 'Permintaan update ini sudah diterima dan tidak diproses ulang.');
        }

        try {
            DB::transaction(function () use ($request, $id) {
                $item = MemberPackage::findOrFail($id);
                $data = $request->validated();
                unset($data['_submission_token']);
                $data['user_id'] = Auth::id();

                $item->update($data);
            });

            IdempotentSubmission::complete($cacheKey);
        } catch (\Throwable $exception) {
            IdempotentSubmission::release($cacheKey);
            throw $exception;
        }

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
            'memberPackages'    => MemberPackage::onlyTrashed()->with(['branchStore', 'users'])->get(),
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
