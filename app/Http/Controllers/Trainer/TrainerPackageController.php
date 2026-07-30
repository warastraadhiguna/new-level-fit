<?php

namespace App\Http\Controllers\Trainer;

use App\Http\Controllers\Controller;
use App\Http\Requests\TrainerPackageStoreRequest;
use App\Http\Requests\TrainerPackageUpdateRequest;
use App\Models\BranchStore;
use App\Models\Member\MemberPackage;
use App\Models\Trainer\TrainerPackage;
use App\Models\User;
use App\Support\IdempotentSubmission;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class TrainerPackageController extends Controller
{
    public function index()
    {
        $data = [
            'title'                 => 'Trainer Package List',
            'memberPackage'         => MemberPackage::with("branchStore")->get(),            
            'trainerPackage'        => TrainerPackage::with(['branchStore', 'users'])->get(),
            'users'                 => User::get(),
            'branch_stores'         => BranchStore::get(),            
            'content'               => 'admin/trainer-package/index'
        ];

        return view('admin.layouts.wrapper', $data);
    }

    public function create()
    {
        //
    }

    // public function store(TrainerPackageStoreRequest $request)
    // {
    //     $data = $request->all();
    //     $data['user_id'] = Auth::user()->id;
    //     TrainerPackage::create($data);
    //     return redirect()->route('trainer-package.index')->with('success', 'Trainer Package Added Successfully');
    // }

    public function store(TrainerPackageStoreRequest $request)
    {
        $cacheKey = IdempotentSubmission::claim(
            $request->input('_submission_token'),
            'trainer-package:create',
            (int) Auth::id()
        );

        if (!$cacheKey) {
            return redirect()->route('trainer-package.index')
                ->with('success', 'Permintaan ini sudah diterima. Data tidak disimpan dua kali.');
        }

        try {
            DB::transaction(function () use ($request) {
                $data = $request->validated();
                unset($data['_submission_token']);
                $data['user_id'] = Auth::id();
                $data['status'] = $request->has('status') ? 'LGT' : null;

                TrainerPackage::create($data);
            });

            IdempotentSubmission::complete($cacheKey);
        } catch (\Throwable $exception) {
            IdempotentSubmission::release($cacheKey);
            throw $exception;
        }

        return redirect()->route('trainer-package.index')->with('success', 'Trainer Package Added Successfully');
    }

    public function edit(string $id)
    {
        //
    }

    public function update(TrainerPackageUpdateRequest $request, string $id)
    {
        $cacheKey = IdempotentSubmission::claim(
            $request->input('_submission_token'),
            'trainer-package:update:' . $id,
            (int) Auth::id()
        );

        if (!$cacheKey) {
            return redirect()->route('trainer-package.index')
                ->with('success', 'Permintaan update ini sudah diterima dan tidak diproses ulang.');
        }

        try {
            DB::transaction(function () use ($request, $id) {
                $item = TrainerPackage::findOrFail($id);
                $data = $request->validated();
                unset($data['_submission_token']);
                $data['status'] = $request->has('status') ? 'LGT' : null;
                $data['user_id'] = Auth::id();

                $item->update($data);
            });

            IdempotentSubmission::complete($cacheKey);
        } catch (\Throwable $exception) {
            IdempotentSubmission::release($cacheKey);
            throw $exception;
        }

        return redirect()->route('trainer-package.index')->with('success', 'Trainer Package Updated Successfully');
    }

    public function destroy(TrainerPackage $trainerPackage)
    {
        try {
            $trainerPackage->delete();
            return redirect()->back()->with('success', 'Trainer Package Deleted Successfully');
        } catch (\Throwable $th) {
            return redirect()->back()->with('errorr', 'Failed, please check other session where using this trainer package');
        }
    }

    public function dataSoft()
    {
        $data = [
            'title'             => 'Old Trainer Package',
            'trainerPackages'   => TrainerPackage::onlyTrashed()->with(['branchStore', 'users'])->get(),
            'content'           => 'admin/trainer-package/soft'
        ];

        return view('admin.layouts.wrapper', $data);
    }

    public function restore($id)
    {
        $trainerPackage = TrainerPackage::withTrashed()->find($id);

        if (!$trainerPackage) {
            return redirect()->back()->with('errorr', 'Trainer Package tidak ditemukan');
        }

        $trainerPackage->restore();
        return redirect()->back()->with('success', 'Data berhasil di restore');
    }

    public function forceDelete($id)
    {
        try {
            $trainerPackage = TrainerPackage::onlyTrashed()->find($id);

            if (!$trainerPackage) {
                return redirect()->back()->with('errorr', 'Trainer Package tidak ditemukan');
            }

            $trainerPackage->forceDelete();
            return redirect()->back()->with('success', 'Trainer Package Deleted Permanently Successfully');
        } catch (\Throwable $th) {
            return redirect()->back()->with('errorr', 'Gagal menghapus paket trainer');
        }
    }
}
