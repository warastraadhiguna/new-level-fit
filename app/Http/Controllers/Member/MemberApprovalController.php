<?php

namespace App\Http\Controllers\Member;

use App\Http\Controllers\Controller;
use App\Models\Member\MemberRegistration;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class MemberApprovalController extends Controller
{
    public function index(Request $request)
    {
        $this->ensureFeatureEnabled();

        $search = trim((string) $request->input('search', ''));
        $perPage = (int) $request->input('per_page', 10);
        if (!in_array($perPage, [10, 25, 50, 100], true)) {
            $perPage = 10;
        }

        $memberRegistrations = MemberRegistration::query()
            ->with(['members:id,full_name,member_code,branch_store_id', 'memberPackage:id,package_name'])
            ->whereHas('members', function ($query) {
                $query->where('branch_store_id', Auth::user()->branch_store_id);
            })
            ->where('days', '>', 1)
            ->where('is_approved', false)
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($registrationQuery) use ($search) {
                    $registrationQuery
                        ->where('description', 'like', "%{$search}%")
                        ->orWhereHas('members', function ($memberQuery) use ($search) {
                            $memberQuery->where('full_name', 'like', "%{$search}%")
                                ->orWhere('member_code', 'like', "%{$search}%");
                        })
                        ->orWhereHas('memberPackage', function ($packageQuery) use ($search) {
                            $packageQuery->where('package_name', 'like', "%{$search}%");
                        });
                });
            })
            ->latest('updated_at')
            ->paginate($perPage)
            ->appends([
                'search' => $search,
                'per_page' => $perPage,
            ]);

        return view('admin.layouts.wrapper', [
            'title' => 'Member Approval',
            'memberRegistrations' => $memberRegistrations,
            'search' => $search,
            'perPage' => $perPage,
            'content' => 'admin/member-approval/index',
        ]);
    }

    public function update(Request $request, MemberRegistration $memberRegistration)
    {
        $this->ensureFeatureEnabled();
        $this->ensureRegistrationBelongsToActiveBranch($memberRegistration);

        $data = $request->validate([
            'is_approved' => ['required', Rule::in(['0', '1', 0, 1])],
            'description' => ['required', 'string', 'max:5000'],
        ], [
            'description.required' => 'Description wajib diisi dan diperbarui untuk mengubah approval.',
        ]);

        $description = trim($data['description']);
        if ($description === trim((string) $memberRegistration->description)) {
            return back()
                ->withErrors(['description' => 'Description wajib diubah sebelum status approval disimpan.'])
                ->withInput();
        }

        DB::transaction(function () use ($memberRegistration, $data, $description) {
            $lockedRegistration = MemberRegistration::lockForUpdate()->findOrFail($memberRegistration->id);
            $lockedRegistration->update([
                'is_approved' => (bool) $data['is_approved'],
                'description' => $description,
            ]);
        });

        return back()->with(
            'success',
            (bool) $data['is_approved']
                ? 'Membership berhasil disetujui.'
                : 'Approval membership berhasil dibatalkan.'
        );
    }

    private function ensureFeatureEnabled(): void
    {
        abort_unless((bool) optional(Auth::user()->branchStore)->member_approval_enabled, 404);
    }

    private function ensureRegistrationBelongsToActiveBranch(MemberRegistration $memberRegistration): void
    {
        $belongsToBranch = $memberRegistration->members()
            ->where('branch_store_id', Auth::user()->branch_store_id)
            ->exists();

        abort_unless($belongsToBranch, 404);
    }
}
