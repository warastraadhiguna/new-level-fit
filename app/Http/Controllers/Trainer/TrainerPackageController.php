<?php

namespace App\Http\Controllers\Trainer;

use App\Http\Controllers\Controller;
use App\Http\Requests\TrainerPackageStoreRequest;
use App\Http\Requests\TrainerPackageUpdateRequest;
use App\Models\Trainer\TrainerPackage;
use App\Models\Trainer\TrainerPackageType;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class TrainerPackageController extends Controller
{
    public function index()
    {
        $data = [
            'title'                 => 'Trainer Package List',
            'trainerPackage'        => TrainerPackage::get(),
            'users'                 => User::get(),
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
}
