<?php

namespace App\Http\Controllers\Trainer;

use App\Http\Controllers\Controller;
use App\Http\Requests\TrainerPackageStoreRequest;
use App\Http\Requests\TrainerPackageUpdateRequest;
use App\Models\BranchStore;
use App\Models\Member\MemberPackage;
use App\Models\Trainer\TrainerPackage;
use App\Models\User;
use Illuminate\Support\Facades\Auth;

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
        $data = $request->all();
        $data['user_id'] = Auth::user()->id;
        $data['status'] = $request->has('status') ? 'LGT' : null;

        TrainerPackage::create($data);

        return redirect()->route('trainer-package.index')->with('success', 'Trainer Package Added Successfully');
    }

    public function edit(string $id)
    {
        //
    }

    public function update(TrainerPackageUpdateRequest $request, string $id)
    {
        $item = TrainerPackage::find($id);
        $data = $request->all();
        $data['status'] = $request->has('status') ? 'LGT' : null;
        $data['user_id'] = Auth::user()->id;
        $item->update($data);
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
