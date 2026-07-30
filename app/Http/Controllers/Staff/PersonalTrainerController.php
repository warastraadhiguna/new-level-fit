<?php

namespace App\Http\Controllers\Staff;

use App\Http\Controllers\Controller;
use App\Models\Staff\PersonalTrainer;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class PersonalTrainerController extends Controller
{
    public function index()
    {
        //
    }

    public function create()
    {
        //
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'branch_store_id'    => 'required|exists:branch_stores,id',
            'full_name'     => 'required|string|max:200',
            'phone_number'  => '',
            'gender'        => 'required',
            'address'       => '',
            'description'   => '',
        ]);
        $data['user_id'] = Auth::user()->id;

        PersonalTrainer::create($data);
        return redirect('/staff?page=' . Request()->input('page'))->with('success', 'Personal Trainer Berhasil Ditambahkan');
    }

    public function edit(string $id)
    {
        //
    }

    public function update(Request $request, string $id)
    {
        $item = PersonalTrainer::find($id);
        $data = $request->validate([
            'branch_store_id'    => 'required|exists:branch_stores,id',
            'full_name'     => 'string|max:200',
            'phone_number'  => 'nullable',
            'gender'        => 'nullable',
            'address'       => 'nullable',
            'description'   => 'nullable',
        ]);
        $data['user_id'] = Auth::user()->id;

        $item->update($data);
        return redirect('/staff?page=' . Request()->input('page'))->with('success', 'Personal Trainer Berhasil Diubah');
    }

    public function destroy(PersonalTrainer $personalTrainer)
    {
        try {
            $personalTrainer->delete();
            return redirect('/staff?page=' . Request()->input('page'))->with('success', 'Personal Trainer Berhasil Dihapus');
        } catch (\Throwable $er) {
            return redirect()->back()->with('errorr', 'Gagal menghapus personal trainer ' . $personalTrainer->full_name . ', personal trainer ini sedang dipakai member');
        }
    }

    public function restore($id)
    {
        $personalTrainer = PersonalTrainer::withTrashed()->find($id);

        if (!$personalTrainer) {
            return redirect()->back()->with('errorr', 'Personal trainer tidak ditemukan');
        }

        $personalTrainer->restore();
        return redirect()->back()->with('success', 'Data berhasil di restore');
    }

    public function forceDelete($id)
    {
        try {
            $personalTrainer = PersonalTrainer::onlyTrashed()->find($id);

            if (!$personalTrainer) {
                return redirect()->back()->with('errorr', 'Personal trainer tidak ditemukan');
            }

            $personalTrainer->forceDelete();
            return redirect()->back()->with('success', 'Data Deleted Permanently and Successfully');
        } catch (\Throwable $th) {
            return redirect()->back()->with('errorr', 'Gagal menghapus data');
        }
    }    
}
