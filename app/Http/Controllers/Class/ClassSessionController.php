<?php

namespace App\Http\Controllers\Class;

use App\Http\Controllers\Controller;
use App\Http\Requests\ClassSessionRequest;
use App\Models\Class\ClassSession;
use App\Models\Staff\ClassInstructor;
use Illuminate\Http\Request;

class ClassSessionController extends Controller
{
    public function index()
    {
        $data = [
            'title'             => 'Class Session',
            'classSessions'     => ClassSession::get(),
            'classInstructors'  => ClassInstructor::get(),
            'content'           => 'admin/class-session/index'
        ];

        return view('admin.layouts.wrapper', $data);
    }

    public function store(ClassSessionRequest $request)
    {
        ClassSession::create([
            'name'   => $request->name,
            'class_instructor_id'  => $request->class_instructor_id,
            'price'       => $request->price,
            'capacity'       => $request->capacity,
            'note'       => $request->note,                               
        ]);

        return redirect()->route('class-session.index')->with('success', 'Class Session Added Successfully');        
    }

    public function update(ClassSessionRequest $request, ClassSession $classSession)
    {
        $classSession->update([
            'name'   => $request->name,
            'class_instructor_id'  => $request->class_instructor_id,
            'price'       => $request->price,
            'capacity'       => $request->capacity,
            'note'       => $request->note,                               
        ]);

        return redirect()->route('class-session.index')->with('success', 'Class Session Updated Successfully');     
    }

    public function destroy(ClassSession $classSession)
    {
        try {
            $classSession->delete();
            return redirect()->back()->with('success', 'Class Session Deleted Successfully');
        } catch (\Throwable $e) {
            return redirect()->back()->with('errorr', 'Class Session Deleted Failed');
        }
    }
}