<?php

namespace App\Http\Controllers\Staff;

use App\Http\Controllers\Controller;
use App\Models\Staff\ClassInstructor;
use Illuminate\Http\Request;

class ClassInstructorController extends Controller
{
    public function store(Request $request)
    {
        $data = $request->validate([
            'full_name'     => 'required|string|max:200',
            'phone_number'  => '',
            'gender'        => 'required',
            'description'   => '',
        ]);

        ClassInstructor::create($data);
        return redirect('/staff?page=' . Request()->input('page'))->with('success', 'Class Instructor Berhasil Ditambahkan');
    }

    public function update(Request $request, string $id)
    {
        $item = ClassInstructor::find($id);
        $data = $request->validate([
            'full_name'     => 'string|max:200',
            'phone_number'  => 'nullable',
            'gender'        => 'nullable',
            'description'   => 'nullable',
        ]);

        $item->update($data);
        return redirect('/staff?page=' . Request()->input('page'))->with('success', 'Class Instructor Berhasil Diubah');
    }

    public function destroy(ClassInstructor $classInstructor)
    {
        try {
            $classInstructor->delete();
            return redirect('/staff?page=' . Request()->input('page'))->with('success', 'Class Instructor Berhasil Dihapus');
        } catch (\Throwable $er) {
            return redirect()->back()->with('errorr', 'Gagal menghapus personal trainer ' . $classInstructor->full_name . ', personal trainer ini sedang dipakai member');
        }
    }

    public function restore($id)
    {
        ClassInstructor::withTrashed()->find($id)->restore();
        return redirect()->back()->with('success', 'Data berhasil di restore');
    }

    public function forceDelete($id)
    {
        try {
            ClassInstructor::onlyTrashed()->find($id)->forceDelete();;
            return redirect()->back()->with('success', 'Data Deleted Permanently and Successfully');
        } catch (\Throwable $th) {
            return redirect()->back()->with('errorr', 'Gagal menghapus data');
        }
    }    
}