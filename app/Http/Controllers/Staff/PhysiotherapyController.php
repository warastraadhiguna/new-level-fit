<?php

namespace App\Http\Controllers\Staff;

use App\Http\Controllers\Controller;
use App\Models\Staff\Physiotherapy;
use Illuminate\Http\Request;

class PhysiotherapyController extends Controller
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
            'gender'    => 'required',
            'club'      => 'required'
        ]);

        Physiotherapy::create($data);
        return redirect()->route('staff.index')->with('message', 'Physiotherapy Added Successfully');
    }

    public function edit(string $id)
    {
        //
    }

    public function update(Request $request, string $id)
    {
        $item = Physiotherapy::find($id);
        $data = $request->validate([
            'full_name' => 'required|string|max:200',
            'gender'    => 'required',
            'club'      => 'required'
        ]);

        $item->update($data);
        return redirect()->route('staff.index')->with('message', 'Physiotherapy Updated Successfully');
    }

    public function destroy(Physiotherapy $physiotherapy)
    {
        $physiotherapy->delete();
        return redirect()->back()->with('message', 'Physiotherapy Deleted Successfully');
    }
}
