<?php

namespace App\Http\Controllers\Staff;

use App\Http\Controllers\Controller;
use App\Models\Staff\FitnessConsultant;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class FitnessConsultantController extends Controller
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
            'full_name' => 'required|string|max:200',
            'email'     => 'required|email',
            'gender'    => 'required',
            'role'      => '',
        ]);

        $data['password'] = bcrypt($request->password);

        User::create($data);
        return redirect('/staff?page=' . Request()->input('page'))->with('success', 'Fitness Consultant Berhasil Ditambahkan');
    }

    public function edit(string $id)
    {
        //
    }

    public function update(Request $request, string $id)
    {
        $item = User::find($id);
        $data = $request->validate([
            'full_name' => 'required|string|max:200',
            'email'     => 'email',
            'gender'    => 'required',
            'role'      => '',
        ]);

        $data['password'] = bcrypt($request->password);

        $item->update($data);
        return redirect('/staff?page=' . Request()->input('page'))->with('success', 'Fitness Consultant Berhasil Diubah');
    }

    public function destroy(User $fitnessConsultant)
    {
        try {
            $fitnessConsultant->delete();
            return redirect('/staff?page=' . Request()->input('page'))->with('success', 'Fitness Consultant Berhasil Dihapus');
        } catch (\Throwable $th) {
            return redirect()->back()->with('errorr', 'Gagal menghapus fitness consultant ' . $fitnessConsultant->full_name . ', fitness consultant ini sedang dipakai member');
        }
    }
}
