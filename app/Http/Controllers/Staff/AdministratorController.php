<?php

namespace App\Http\Controllers\Staff;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Support\ApplicationAccess;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AdministratorController extends Controller
{
    public function __construct()
    {
        $this->middleware('admin.only');
    }

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
            'branch_store_id'    => 'required|exists:branch_stores,id',
            'full_name' => 'required|string|max:200',
            'email'     => 'required|email',
            'gender'    => 'required',
            'role'      => 'required|in:ADMIN',
            'password'  => 'required|string|min:6',
            'application_access' => 'required|array|min:1',
            'application_access.*' => 'in:' . implode(',', ApplicationAccess::ADMIN_APPLICATIONS),
        ]);

        $applicationCodes = $this->normalizeApplicationAccess($data['application_access'] ?? []);
        unset($data['application_access']);
        $data['password'] = bcrypt($data['password']);

        DB::transaction(function () use ($data, $applicationCodes) {
            $user = User::create($data);
            $this->syncApplicationAccess($user, $applicationCodes);
        });

        return redirect('/staff?page=' . Request()->input('page'))->with('success', 'Administrator Berhasil Ditambahkan');
    }

    public function branchUpdate(Request $request)
    {
        $request->user()->update([
            'branch_store_id' => $request->branch_store_id,
        ]);

        return back()->with('success', 'Cabang Berhasil Diubah');
    }

    public function update(Request $request, string $id)
    {
        $data = $request->validate([
            'branch_store_id'    => 'required|exists:branch_stores,id',
            'full_name' => 'string|max:200',
            'email'     => 'email',
            'gender'    => 'required',
            'password'  => 'nullable|string|min:6',
            'application_access' => 'required|array|min:1',
            'application_access.*' => 'in:' . implode(',', ApplicationAccess::ADMIN_APPLICATIONS),
        ]);

        $applicationCodes = $this->normalizeApplicationAccess($data['application_access'] ?? []);
        unset($data['application_access']);

        if (!empty($data['password'])) {
            $data['password'] = bcrypt($data['password']);
        } else {
            unset($data['password']);
        }

        DB::transaction(function () use ($id, $data, $applicationCodes) {
            $item = User::where('role', 'ADMIN')->findOrFail($id);
            $item->update($data);
            $this->syncApplicationAccess($item, $applicationCodes);
        });

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

    public function restore($id)
    {
        $administrator = User::withTrashed()
            ->where('role', 'ADMIN')
            ->find($id);

        if (!$administrator) {
            return redirect()->back()->with('errorr', 'Administrator tidak ditemukan');
        }

        $administrator->restore();
        return redirect()->back()->with('success', 'Data berhasil di restore');
    }

    public function forceDelete($id)
    {
        try {
            $administrator = User::onlyTrashed()
                ->where('role', 'ADMIN')
                ->find($id);

            if (!$administrator) {
                return redirect()->back()->with('errorr', 'Administrator tidak ditemukan');
            }

            $administrator->forceDelete();
            return redirect()->back()->with('success', 'Data Deleted Permanently and Successfully');
        } catch (\Throwable $th) {
            return redirect()->back()->with('errorr', 'Gagal menghapus data');
        }
    }

    private function normalizeApplicationAccess(array $applicationCodes)
    {
        return array_values(array_unique(array_intersect(
            $applicationCodes,
            ApplicationAccess::ADMIN_APPLICATIONS
        )));
    }

    private function syncApplicationAccess(User $user, array $applicationCodes)
    {
        foreach (ApplicationAccess::ADMIN_APPLICATIONS as $applicationCode) {
            $user->applicationAccesses()->updateOrCreate(
                ['application_code' => $applicationCode],
                ['is_active' => in_array($applicationCode, $applicationCodes, true)]
            );
        }
    }
}
