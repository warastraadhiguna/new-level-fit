<?php

namespace App\Http\Controllers\Staff;

use App\Http\Controllers\Controller;
use App\Models\Staff\ClassInstructor;
use Illuminate\Http\Request;

class ClassInstructorController extends Controller
{
    public function index()
    {
        // $data = [
        //     'classInstructor'   => ClassInstructor::get(),
        //     'content'           => 'admin/staff/class-instructor/index'
        // ];

        // return view('admin.layouts.wrapper', $data);
    }

    public function create()
    {
        //
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'full_name' => 'required|string|max:200',
            'gender'    => 'required',
            'role'      => 'required',
        ]);

        ClassInstructor::create($data);
        return redirect()->route('staff.index')->with('message', 'Class Instructor Added Successfully');
    }

    public function edit(string $id)
    {
        //
    }

    public function update(Request $request, string $id)
    {
        $item = ClassInstructor::find($id);
        $data = $request->validate([
            'full_name' => 'required|string|max:200',
            'gender'    => 'required',
            'role'      => '',
        ]);

        $item->update($data);
        return redirect()->route('staff.index')->with('message', 'Class Instructor Updated Successfully');
    }

    public function destroy(ClassInstructor $classInstructor)
    {
        $classInstructor->delete();
        return redirect()->back()->with('message', 'Class Instructor Deleted Successfully');
    }
}
