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
}
