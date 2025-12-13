<?php

namespace App\Http\Controllers\Class;

use App\Http\Controllers\Controller;
use App\Http\Requests\ClassScheduleRequest;
use App\Models\Class\ClassSchedule;
use App\Models\Class\ClassSession;
use App\Models\Staff\ClassInstructor;
use Illuminate\Http\Request;

class ClassScheduleController extends Controller
{
    public function index()
    {
        $data = [
            'title'             => 'Class Schedule',
            'classSessions'      => ClassSession::get(), 
            'classSchedules'    => ClassSchedule::get(),
            'classInstructors'  =>  ClassInstructor::get(),
            'content'           => 'admin/class-schedule/index'
        ];

        return view('admin.layouts.wrapper', $data);
    }

    public function store(ClassScheduleRequest $request)
    {
        ClassSchedule::create([
            'name'   => $request->name,
            'class_instructor_id'  => $request->class_instructor_id,
            'price'       => $request->price,
            'capacity'       => $request->capacity,
            'note'       => $request->note,                               
        ]);

        return redirect()->route('class-schedule.index')->with('success', 'Class Schedule Added Successfully');        
    }

    public function update(ClassScheduleRequest $request, ClassSchedule $classSchedule)
    {
        $classSchedule->update([
            'name'   => $request->name,
            'class_instructor_id'  => $request->class_instructor_id,
            'price'       => $request->price,
            'capacity'       => $request->capacity,
            'note'       => $request->note,        
            'is_active'       => $request->is_active,
            'real_capacity'       => $request->real_capacity,                                          
        ]);

        return redirect()->route('class-schedule.index')->with('success', 'Class Schedule Updated Successfully');     
    }

    public function destroy(ClassSchedule $classSchedule)
    {
        try {
            $classSchedule->delete();
            return redirect()->back()->with('success', 'Class Schedule Deleted Successfully');
        } catch (\Throwable $e) {
            return redirect()->back()->with('errorr', 'Class Schedule Deleted Failed');
        }
    }
}