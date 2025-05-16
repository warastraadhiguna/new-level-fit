<?php

namespace App\Http\Controllers\Staff;

use App\Http\Controllers\Controller;
use App\Models\Staff\PTLeader;
use Illuminate\Http\Request;

class PTLeaderController extends Controller
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

        PTLeader::create($data);
        return redirect()->route('staff.index')->with('message', 'PT Leader Added Successfully');
    }

    public function edit(string $id)
    {
        //
    }

    public function update(Request $request, string $id)
    {
        $item = PTLeader::find($id);
        $data = $request->validate([
            'full_name' => 'required|string|max:200',
            'gender'    => 'required',
            'club'      => 'required'
        ]);

        $item->update($data);
        return redirect()->route('staff.index')->with('message', 'PT Leader Updated Successfully');
    }

    public function destroy(PTLeader $ptLeader)
    {
        $ptLeader->delete();
        return redirect()->back()->with('message', 'PT Leader Deleted Successfully');
    }
}
