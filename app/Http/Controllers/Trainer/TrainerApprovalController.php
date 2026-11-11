<?php

namespace App\Http\Controllers\Trainer;

use App\Http\Controllers\Controller;
use App\Models\Member\Member;
use App\Models\Staff\PersonalTrainer;
use App\Models\Trainer\CheckInTrainerSession;
use App\Models\Trainer\TrainerSession;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class TrainerApprovalController extends Controller
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

        $query = TrainerSession::query()
            ->with([
                'members:id,full_name,member_code',
                'trainerPackageWithTrashed:id,package_name,number_of_session,status,deleted_at',
                'personalTrainers:id,full_name',
                'branchStore:id,name',
                'users:id,full_name',
                'latestCheckIn',
                'leaveDays:id,trainer_session_id,submission_date,days',
            ])
            ->withSum('payments as payment_summary', 'value')
            ->withCount([
                'trainerSessionCheckIn as used_session_count' => function ($query) {
                    $query->whereNotNull('check_out_time');
                },
            ])
            ->addSelect([
                'latest_check_in_at' => CheckInTrainerSession::query()
                    ->select('check_in_time')
                    ->whereColumn('trainer_session_id', 'trainer_sessions.id')
                    ->latest('id')
                    ->limit(1),
            ])
            ->where('branch_store_id', $branchId)
            ->where('is_pt_free', false)
            ->where('is_approved', false)
            ->where(function ($query) {
                $query->whereDoesntHave('trainerPackageWithTrashed')
                    ->orWhereHas('trainerPackageWithTrashed', function ($packageQuery) {
                        $packageQuery->whereNull('status');
                    });
            })
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($sessionQuery) use ($search) {
                    $sessionQuery
                        ->where('description', 'like', "%{$search}%")
                        ->orWhereHas('members', function ($memberQuery) use ($search) {
                            $memberQuery->where('full_name', 'like', "%{$search}%")
                                ->orWhere('member_code', 'like', "%{$search}%");
                        })
                        ->orWhereHas('trainerPackageWithTrashed', function ($packageQuery) use ($search) {
                            $packageQuery->where('package_name', 'like', "%{$search}%");
                        })
                        ->orWhereHas('personalTrainers', function ($trainerQuery) use ($search) {
                            $trainerQuery->where('full_name', 'like', "%{$search}%");
                        })
                        ->orWhereHas('users', function ($staffQuery) use ($search) {
                            $staffQuery->where('full_name', 'like', "%{$search}%");
                        });
                });
            });

        $duration = '(trainer_sessions.days + COALESCE((SELECT SUM(days) FROM pt_leave_days WHERE pt_leave_days.trainer_session_id = trainer_sessions.id), 0))';
        $expiry = DB::connection()->getDriverName() === 'sqlite'
            ? "datetime(trainer_sessions.start_date, '+' || $duration || ' days')"
            : "DATE_ADD(trainer_sessions.start_date, INTERVAL $duration DAY)";
        // Match the table's session total, including the package fallback for legacy rows.
        $totalSessions = 'COALESCE(NULLIF(trainer_sessions.number_of_session, 0), (SELECT number_of_session FROM trainer_packages WHERE trainer_packages.id = trainer_sessions.trainer_package_id), 0)';
        $usedSessions = '(SELECT COUNT(*) FROM check_in_trainer_sessions WHERE check_in_trainer_sessions.trainer_session_id = trainer_sessions.id AND check_out_time IS NOT NULL)';
        $now = now()->toDateTimeString();
        if ($status === 'expired') {
            $query->where(function ($query) use ($expiry, $now, $usedSessions, $totalSessions) {
                $query->whereRaw("$expiry < ?", [$now])->orWhereRaw("$usedSessions >= $totalSessions");
            });
        } elseif ($status === 'active' || $status === 'pending') {
            $query->whereRaw("$usedSessions < $totalSessions");
            if ($status === 'active') {
                $query->whereNotNull('trainer_sessions.trainer_id')
                    ->where('trainer_sessions.start_date', '<=', $now)->whereRaw("$expiry >= ?", [$now]);
            } else {
                $query->where(function ($query) use ($expiry, $now) {
                    $query->whereNull('trainer_sessions.start_date')->orWhereRaw("$expiry >= ?", [$now]);
                })->where(function ($query) use ($now) {
                    $query->whereNull('trainer_sessions.trainer_id')->orWhereNull('trainer_sessions.start_date')
                        ->orWhere('trainer_sessions.start_date', '>', $now);
                });
            }
        }
        if ($paymentStatus !== 'all') {
            $paid = 'COALESCE((SELECT SUM(value) FROM trainer_session_payments WHERE trainer_session_payments.trainer_session_id = trainer_sessions.id), 0)';
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
                        ->whereColumn('members.id', 'trainer_sessions.member_id'),
                    $direction
                );
                break;
            case 'check_in_time':
                $query->orderBy('latest_check_in_at', $direction);
                break;
            case 'start_date':
                $query->orderBy('trainer_sessions.start_date', $direction);
                break;
            case 'payment_summary':
                $query->orderByRaw('COALESCE(payment_summary, 0) ' . $direction);
                break;
            case 'trainer_name':
                $query->orderBy(
                    PersonalTrainer::query()
                        ->select('full_name')
                        ->whereColumn('personal_trainers.id', 'trainer_sessions.trainer_id'),
                    $direction
                );
                break;
            case 'staff_name':
                $query->orderBy(
                    User::query()
                        ->select('full_name')
                        ->whereColumn('users.id', 'trainer_sessions.user_id'),
                    $direction
                );
                break;
            default:
                $sort = 'created_at';
                $query->orderBy('trainer_sessions.created_at', $direction);
                break;
        }

        $trainerSessions = $query
            ->orderByDesc('trainer_sessions.id')
            ->paginate($perPage)
            ->appends($request->only(['search', 'per_page', 'sort', 'direction', 'status', 'payment_status']));

        return view('admin.layouts.wrapper', [
            'title' => 'PT Approval',
            'trainerSessions' => $trainerSessions,
            'search' => $search,
            'status' => $status,
            'paymentStatus' => $paymentStatus,
            'perPage' => $perPage,
            'sort' => $sort,
            'direction' => $direction,
            'content' => 'admin/trainer-approval/index',
        ]);
    }

    public function update(Request $request, TrainerSession $trainerSession)
    {
        $this->ensureFeatureEnabled();
        $this->ensureSessionBelongsToActiveBranch($trainerSession);

        $data = $request->validate([
            'is_approved' => ['required', Rule::in(['0', '1', 0, 1])],
            'description' => ['required', 'string', 'max:5000'],
        ], [
            'description.required' => 'Description wajib diisi dan diperbarui untuk mengubah approval.',
        ]);

        $description = trim($data['description']);
        if ($description === trim((string) $trainerSession->description)) {
            return back()
                ->withErrors(['description' => 'Description wajib diubah sebelum status approval disimpan.'])
                ->withInput();
        }

        DB::transaction(function () use ($trainerSession, $data, $description) {
            $lockedSession = TrainerSession::lockForUpdate()->findOrFail($trainerSession->id);
            $lockedSession->update([
                'is_approved' => (bool) $data['is_approved'],
                'description' => $description,
            ]);
        });

        return back()->with(
            'success',
            (bool) $data['is_approved']
                ? 'PT berhasil disetujui.'
                : 'Approval PT berhasil dibatalkan.'
        );
    }

    private function ensureFeatureEnabled(): void
    {
        abort_unless((bool) optional(Auth::user()->branchStore)->trainer_approval_enabled, 404);
    }

    private function ensureSessionBelongsToActiveBranch(TrainerSession $trainerSession): void
    {
        $trainerPackage = $trainerSession->trainerPackageWithTrashed()->first();
        $isEligible = (int) $trainerSession->branch_store_id === (int) Auth::user()->branch_store_id
            && !$trainerSession->is_pt_free
            && (!$trainerPackage || $trainerPackage->status === null);

        abort_unless($isEligible, 404);
    }
}
