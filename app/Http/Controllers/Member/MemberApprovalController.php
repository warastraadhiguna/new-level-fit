<?php

namespace App\Http\Controllers\Member;

use App\Http\Controllers\Controller;
use App\Models\Member\CheckInMember;
use App\Models\Member\Member;
use App\Models\Member\MemberRegistration;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class MemberApprovalController extends Controller
{
    public function index(Request $request)
    {
        $this->ensureFeatureEnabled();

        $branchId = (int) Auth::user()->branch_store_id;
        $search = trim((string) $request->input('search', ''));
        $status = $request->input('status', 'all');
        $paymentStatus = $request->input('payment_status', 'all');
        if (!in_array($status, ['all', 'active', 'pending', 'expired'], true)) $status = 'all';
        if (!in_array($paymentStatus, ['all', 'paid', 'unpaid'], true)) $paymentStatus = 'all';
        $perPage = (int) $request->input('per_page', 10);
        $sort = (string) $request->input('sort', 'created_at');
        $direction = strtolower((string) $request->input('direction', 'desc')) === 'asc' ? 'asc' : 'desc';
        if (!in_array($perPage, [10, 25, 50, 100], true)) {
            $perPage = 10;
        }

        $query = MemberRegistration::query()
            ->with([
                'members:id,full_name,member_code,branch_store_id',
                'members.branchStore:id,name',
                'memberPackageWithTrashed:id,package_name,is_all_club,deleted_at',
                'methodPayment:id,name',
                'users:id,full_name',
                'latestCheckIn',
                'leaveDays:id,member_registration_id,submission_date,days,leave_day_continue_id',
            ])
            ->withSum('payments as payment_summary', 'value')
            ->addSelect([
                'latest_check_in_at' => CheckInMember::query()
                    ->select('check_in_time')
                    ->whereColumn('member_registration_id', 'member_registrations.id')
                    ->latest('id')
                    ->limit(1),
            ])
            ->where(function ($query) use ($branchId) {
                $query->whereHas('members', function ($memberQuery) use ($branchId) {
                    $memberQuery->where('branch_store_id', $branchId);
                })->orWhereHas('memberPackageWithTrashed', function ($packageQuery) {
                    $packageQuery->where('is_all_club', 1);
                });
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
                        ->orWhereHas('memberPackageWithTrashed', function ($packageQuery) use ($search) {
                            $packageQuery->where('package_name', 'like', "%{$search}%");
                        })
                        ->orWhereHas('users', function ($staffQuery) use ($search) {
                            $staffQuery->where('full_name', 'like', "%{$search}%");
                        });
                });
            });

        // Use the same freeze extension as the expiry date shown in the table.
        $duration = '(member_registrations.days + COALESCE((SELECT SUM(days) FROM leave_days WHERE leave_days.member_registration_id = member_registrations.id), 0))';
        $expiry = DB::connection()->getDriverName() === 'sqlite'
            ? "datetime(member_registrations.start_date, '+' || $duration || ' days')"
            : "DATE_ADD(member_registrations.start_date, INTERVAL $duration DAY)";
        $now = now()->toDateTimeString();
        if ($status === 'active') {
            $query->where('member_registrations.start_date', '<=', $now)->whereRaw("$expiry >= ?", [$now]);
        } elseif ($status === 'pending') {
            $query->where('member_registrations.start_date', '>', $now);
        } elseif ($status === 'expired') {
            $query->whereRaw("$expiry < ?", [$now]);
        }
        if ($paymentStatus !== 'all') {
            $paid = 'COALESCE((SELECT SUM(value) FROM member_registration_payments WHERE member_registration_payments.member_registration_id = member_registrations.id), 0)';
            $payable = '(COALESCE(package_price, 0) + COALESCE(admin_price, 0) - COALESCE(discount_amount, 0))';
            $payable = "CASE WHEN $payable > 0 THEN $payable ELSE 0 END";
            $operator = $paymentStatus === 'paid' ? '>=' : '<';
            $query->whereRaw("$paid $operator ($payable)");
        }

        switch ($sort) {
            case 'member_name':
                $query->orderBy(
                    Member::query()
                        ->select('full_name')
                        ->whereColumn('members.id', 'member_registrations.member_id'),
                    $direction
                );
                break;
            case 'check_in_time':
                $query->orderBy('latest_check_in_at', $direction);
                break;
            case 'start_date':
                $query->orderBy('member_registrations.start_date', $direction);
                break;
            case 'payment_summary':
                $query->orderByRaw('COALESCE(payment_summary, 0) ' . $direction);
                break;
            case 'staff_name':
                $query->orderBy(
                    User::query()
                        ->select('full_name')
                        ->whereColumn('users.id', 'member_registrations.user_id'),
                    $direction
                );
                break;
            default:
                $sort = 'created_at';
                $query->orderBy('member_registrations.created_at', $direction);
                break;
        }

        $memberRegistrations = $query
            ->orderByDesc('member_registrations.id')
            ->paginate($perPage)
            ->appends($request->only(['search', 'per_page', 'sort', 'direction', 'status', 'payment_status']));

        return view('admin.layouts.wrapper', [
            'title' => 'Member Approval',
            'memberRegistrations' => $memberRegistrations,
            'search' => $search,
            'status' => $status,
            'paymentStatus' => $paymentStatus,
            'perPage' => $perPage,
            'sort' => $sort,
            'direction' => $direction,
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

        $isAllClub = $memberRegistration->memberPackageWithTrashed()
            ->where('is_all_club', 1)
            ->exists();

        abort_unless($belongsToBranch || $isAllClub, 404);
    }
}
