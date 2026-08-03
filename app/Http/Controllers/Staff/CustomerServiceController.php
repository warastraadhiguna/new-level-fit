<?php

namespace App\Http\Controllers\Staff;

use App\Http\Controllers\Controller;
use App\Models\Staff\CustomerService;
use App\Models\User;
use App\Support\HandlesDuplicateStaffEmail;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;

class CustomerServiceController extends Controller
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
            'branch_store_id'    => 'required|exists:branch_stores,id',
            'full_name' => 'required|string|max:200',
            'email'     => $this->staffEmailRules(),
            'gender'    => 'required',
            'role'      => '',
        ], $this->staffEmailValidationMessages());

        $data['password'] = bcrypt($request->password);

        try {
            User::create($data);
        } catch (QueryException $exception) {
            if ($this->isDuplicateStaffEmailException($exception)) {
                return redirect('/staff?page=' . Request()->input('page'))
                    ->withErrors(['email' => $this->duplicateStaffEmailMessage()])
                    ->withInput();
            }

            throw $exception;
        }
        return redirect('/staff?page=' . Request()->input('page'))->with('success', 'Customer Service Berhasil Ditambahkan');
    }

    public function edit(string $id)
    {
        //
    }

    public function update(Request $request, string $id)
    {
        $item = User::find($id);
        $this->normalizeStaffEmail($request);

        $data = $request->validate([
            'branch_store_id'    => 'required|exists:branch_stores,id',
            'full_name' => 'required|string|max:200',
            'email'     => $this->staffEmailRules((int) $id),
            'gender'    => 'required',
            'role'      => '',
        ], $this->staffEmailValidationMessages());

        $data['password'] = bcrypt($request->password);

        try {
            $item->update($data);
        } catch (QueryException $exception) {
            if ($this->isDuplicateStaffEmailException($exception)) {
                return redirect('/staff?page=' . Request()->input('page'))
                    ->withErrors(['email' => $this->duplicateStaffEmailMessage()])
                    ->withInput();
            }

            throw $exception;
        }
        return redirect('/staff?page=' . Request()->input('page'))->with('success', 'Customer Service Berhasil Diubah');
    }

    public function destroy(User $customerService)
    {
        try {
            $customerService->delete();
            return redirect('/staff?page=' . Request()->input('page'))->with('success', 'Customer Service Berhasil Dihapus');
        } catch (\Throwable $th) {
            return redirect('/staff?page=' . Request()->input('page'))->with('errorr', 'Gagal menghapus customer service ' . $customerService->full_name . ', customer service ini sedang dipakai member');
        }
    }

    public function restore($id)
    {
        User::withTrashed()->find($id)->restore();
        return redirect()->back()->with('success', 'Data berhasil di restore');
    }

    public function forceDelete($id)
    {
        try {
            User::onlyTrashed()->find($id)->forceDelete();;
            return redirect()->back()->with('success', 'Data Deleted Permanently and Successfully');
        } catch (\Throwable $th) {
            return redirect()->back()->with('errorr', 'Gagal menghapus data');
        }
    }      
}
