<?php

namespace App\Http\Controllers\Class;

use App\Http\Controllers\Controller;
use App\Http\Requests\ClassDetailRequest;
use App\Models\Class\ClassDetail;
use App\Models\Class\ClassSchedule;
use App\Models\Class\ClassSession;
use App\Models\Member\Member;
use App\Models\Staff\ClassInstructor;
use Illuminate\Support\Facades\Auth;

class ClassDetailController extends Controller
{
    public function index()
    {
        $classScheduleId =  Request()->input('class-schedule');
        if(!$classScheduleId)
        {
            return redirect()->route('class-schedule.index')->with('error', 'Choose Schedule first'); 
        }

        $classSchedule = ClassSchedule::find($classScheduleId);
        if(!$classSchedule)
        {
            return redirect()->route('class-schedule.index')->with('error', 'Choose Schedule first'); 
        }

        $data = [
            'title'             => 'Class Detail',
            'classSchedule'     => $classSchedule,
            'classDetails'      => ClassDetail::where("class_schedule_id", $classScheduleId )->get(),
            'members'           =>  Member::orderBy("full_name")->get(),
            'content'           => 'admin/class-detail/index'
        ];

        return view('admin.layouts.wrapper', $data);
    }

    public function store(ClassDetailRequest $request)
    {
        $classScheduleId = $request->class_schedule_id;
        $data = [
            'class_schedule_id'   => $classScheduleId,
            'user_id'  => Auth::user()->id,
            'name'       => $request->name,
            'phone'       => $request->phone,      
            'email'       => $request->email,                                        
        ];

        if($request->member_id){
            $data['member_id'] = $request->member_id;
        }

        ClassDetail::create($data);

        return redirect()->route('class-detail.index', "class-schedule=$classScheduleId")->with('success', 'Class Detail Added Successfully');        
    }

    public function update(ClassDetailRequest $request, ClassDetail $classDetail)
    {
        $classDetail->update([
            'status'       => $request->status,                                          
        ]);

        return redirect()->route('class-detail.index', "class-schedule=$classDetail->class_schedule_id")->with('success', 'Class Detail Updated Successfully');     
    }

    public function destroy(ClassDetail $classDetail)
    {
        try {
            $classDetail->delete();
            return redirect()->back()->with('success', 'Class Detail Deleted Successfully');
        } catch (\Throwable $e) {
            return redirect()->back()->with('errorr', 'Class Detail Deleted Failed');
        }
    }
}