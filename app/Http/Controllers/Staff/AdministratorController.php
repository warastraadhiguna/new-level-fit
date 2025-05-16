<?php

namespace App\Http\Controllers\Staff;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;

class AdministratorController extends Controller
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
        return redirect('/staff?page=' . Request()->input('page'))->with('success', 'Administrator Berhasil Ditambahkan');
    }

    public function edit(string $id)
    {
        //
    }

    public function update(Request $request, string $id)
    {
        $item = User::find($id);
        $data = $request->validate([
            'full_name' => 'string|max:200',
            'email'     => 'email',
            'gender'    => 'required',
            'role'      => '',
        ]);

        $data['password'] = bcrypt($request->password);

        $item->update($data);
        return redirect('/staff?page=' . Request()->input('page'))->with('success', 'Administrator Berhasil Diubah');
    }

    public function destroy(User $administrator)
    {
        try {
            $administrator->delete();
            return redirect('/staff?page=' . Request()->input('page'))->with('success', 'Administrator Berhasil Dihapus');
        } catch (\Throwable $er) {
            return redirect('/staff?page=' . Request()->input('page'))->with('errorr', 'Gagal menghapus administrator ' . $administrator->full_name);
        }
    }
}
