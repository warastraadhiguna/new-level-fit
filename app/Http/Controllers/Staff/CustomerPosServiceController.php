<?php

namespace App\Http\Controllers\Staff;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Support\HandlesDuplicateStaffEmail;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;

class CustomerPosServiceController extends Controller
{
    use HandlesDuplicateStaffEmail;

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
        $this->normalizeStaffEmail($request);

        $data = $request->validate([
            'full_name' => 'required|string|max:200',
            'email'     => $this->staffEmailRules(),
            'gender'    => 'required',
            'role'      => '',
            'club'      => 'required'
        ], $this->staffEmailValidationMessages());

        $data['password'] = bcrypt($request->password);

        try {
            User::create($data);
        } catch (QueryException $exception) {
            if ($this->isDuplicateStaffEmailException($exception)) {
                return redirect()->route('staff.index')
                    ->withErrors(['email' => $this->duplicateStaffEmailMessage()])
                    ->withInput();
            }

            throw $exception;
        }
        return redirect()->route('staff.index')->with('message', 'Customer Service POS Added Successfully');
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
            'gender'    => 'required',
            'club'      => 'required'
        ]);

        $item->update($data);
        return redirect()->route('staff.index')->with('message', 'Customer Service POS Updated Successfully');
    }

    public function destroy(User $user)
    {
        $user->delete();
        return redirect()->back()->with('message', 'Customer Service POS Deleted Successfully');
    }
}
