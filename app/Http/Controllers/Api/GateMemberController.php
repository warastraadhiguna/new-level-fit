<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Member\CheckInMember;
use App\Support\QrCheckInToken;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Validator;

class GateMemberController extends Controller
{
    private const TIMEZONE = 'Asia/Jakarta';
    private const DUPLICATE_SCAN_WINDOW_SECONDS = 5;

    public function toggleQr(Request $request)
    {
        $configuredSecret = (string) config('services.qr_check_in.secret');
        $providedSecret = (string) $request->header('X-QR-Check-In-Secret');

        if ($configuredSecret === '' || $providedSecret === '' || !hash_equals($configuredSecret, $providedSecret)) {
            return response()->json(['message' => 'Unauthorized QR check-in request.'], 401);
        }

        $validator = Validator::make($request->all(), [
            'member_id' => ['required', 'integer', 'exists:members,id'],
            'qr_token' => ['required', 'string'],
        ]);

        if ($validator->fails()) {
            return $this->validationError($validator);
        }

        try {
            $qr = QrCheckInToken::parse($validator->validated()['qr_token'], 'member');
        } catch (\RuntimeException $exception) {
            return response()->json(['message' => $exception->getMessage()], 422);
        }

        $memberId = (int) $validator->validated()['member_id'];
        $branchStoreId = (int) $qr['branch_store_id'];
        $userId = (int) $qr['user_id'];
        $eventTime = Carbon::now(self::TIMEZONE);

        $issuingUserExists = DB::table('users')
            ->where('id', $userId)
            ->where('branch_store_id', $branchStoreId)
            ->whereNull('deleted_at')
            ->exists();
        if (!$issuingUserExists) {
            return response()->json(['message' => 'QR code tidak lagi berlaku. Silakan gunakan QR terbaru.'], 422);
        }

        $membership = $this->findAccessibleActiveMembership($memberId, $branchStoreId, $eventTime);

        if (!$membership) {
            return response()->json(['message' => 'Member aktif tidak ditemukan atau tidak dapat mengakses cabang ini.'], 404);
        }

        $result = DB::transaction(function () use ($membership, $branchStoreId, $userId, $eventTime) {
            DB::table('member_registrations')
                ->where('id', $membership->member_registration_id)
                ->lockForUpdate()
                ->first();

            $latestCheckIn = CheckInMember::where('member_registration_id', $membership->member_registration_id)
                ->latest('id')
                ->lockForUpdate()
                ->first();

            if ($latestCheckIn && !$latestCheckIn->check_out_time) {
                if ($this->isDuplicateQrScan($latestCheckIn->check_in_time, $eventTime)) {
                    return ['status' => 'duplicate', 'check_in' => $latestCheckIn];
                }

                $latestCheckIn->update(['check_out_time' => $eventTime]);

                return ['status' => 'checked_out', 'check_in' => $latestCheckIn->fresh()];
            }

            if ($latestCheckIn && $this->isDuplicateQrScan($latestCheckIn->check_out_time, $eventTime)) {
                return ['status' => 'duplicate', 'check_in' => $latestCheckIn];
            }

            if ($membership->leave_day_status === 'Freeze') {
                return ['error' => $membership->full_name . ' sedang cuti.'];
            }

            if ($this->membershipHasUnpaidPayment($membership, $branchStoreId)) {
                return ['error' => 'Unpaid Member.'];
            }

            $deadlineStatus = $this->membershipPaymentDeadlineStatus($membership, $branchStoreId, $eventTime);
            if ($deadlineStatus && $deadlineStatus['blocked']) {
                return ['error' => $deadlineStatus['message']];
            }

            $checkIn = CheckInMember::create($this->memberCheckInPayload([
                'member_registration_id' => $membership->member_registration_id,
                'branch_store_id' => $branchStoreId,
                'check_in_time' => $eventTime,
                'user_id' => $userId,
            ]));

            return [
                'status' => 'checked_in',
                'check_in' => $checkIn,
                'notice' => $deadlineStatus ? $deadlineStatus['message'] : null,
            ];
        });

        if (isset($result['error'])) {
            return response()->json(['message' => $result['error']], 409);
        }

        $messages = [
            'checked_in' => 'Member Checked In Successfully',
            'checked_out' => 'Member Checked Out Successfully',
            'duplicate' => 'Duplicate scan ignored',
        ];
        $message = $messages[$result['status']];
        if (!empty($result['notice'])) {
            $message .= ' ' . $result['notice'];
        }

        return response()->json([
            'message' => $message,
            'status' => $result['status'],
            'branch_store_id' => $branchStoreId,
            'member' => $this->memberResponse($membership),
            'check_in' => $this->checkInResponse($result['check_in']),
        ]);
    }

    private function isDuplicateQrScan($timestamp, Carbon $referenceTime): bool
    {
        return $timestamp
            && Carbon::parse($timestamp, self::TIMEZONE)->diffInSeconds($referenceTime) <= self::DUPLICATE_SCAN_WINDOW_SECONDS;
    }

    public function index(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'store_branch_id' => ['required', 'integer', 'exists:branch_stores,id'],
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'The given data was invalid.',
                'errors' => $validator->errors(),
            ], 422);
        }

        $branchStoreId = (int) $validator->validated()['store_branch_id'];
        $branchStoreType = DB::table('branch_stores')->where('id', $branchStoreId)->value('type') ?: 'both';
        $today = Carbon::now(self::TIMEZONE)->toDateString();

        $activeMemberships = DB::table('member_registrations as mr')
            ->selectRaw('MAX(mr.id) as member_registration_id')
            ->join('member_packages as mp', 'mp.id', '=', 'mr.member_package_id')
            ->whereDate('mr.start_date', '<=', $today)
            ->whereRaw('DATE(DATE_ADD(mr.start_date, INTERVAL mr.days DAY)) >= ?', [$today])
            ->where(function ($query) use ($branchStoreId) {
                $query
                    // All club dapat masuk ke semua cabang.
                    ->where('mp.is_all_club', 1)
                    // One club hanya dapat masuk ke cabang package yang sama dengan request.
                    ->orWhere(function ($query) use ($branchStoreId) {
                        $query->where('mp.is_all_club', 0)
                            ->where('mp.branch_store_id', $branchStoreId);
                    });
            })
            ->groupBy('mr.member_id');

        $members = DB::table('member_registrations as mr')
            ->select([
                'm.id',
                'm.full_name',
                'm.card_number',
                'm.member_code',
                'm.photos',
                'm.small_photos',
                'mr.start_date',
                'mp.package_name',
                'mp.is_all_club',
            ])
            ->selectRaw('CONCAT(DATE(DATE_ADD(mr.start_date, INTERVAL mr.days DAY)), " 23:59:59") as end_date')
            ->joinSub($activeMemberships, 'active_memberships', function ($join) {
                $join->on('active_memberships.member_registration_id', '=', 'mr.id');
            })
            ->join('members as m', 'm.id', '=', 'mr.member_id')
            ->join('member_packages as mp', 'mp.id', '=', 'mr.member_package_id')
            ->when(in_array($branchStoreType, ['male', 'female'], true), function ($query) use ($branchStoreType) {
                $query->where(function ($query) use ($branchStoreType) {
                    $query->whereNull('m.gender')
                        ->orWhereRaw('LOWER(m.gender) = ?', [$branchStoreType]);
                });
            })
            ->orderBy('m.full_name')
            ->get()
            ->map(function ($member) {
                return [
                    'id' => $member->id,
                    'full_name' => $member->full_name,
                    'card_number' => $member->card_number,
                    'member_code' => $member->member_code,
                    'active_date' => $member->start_date,
                    'end_date' => $member->end_date,
                    'package_name' => $member->package_name,
                    'package_type' => (int) $member->is_all_club === 1 ? 'all_club' : 'one_club',
                    'url_photo' => $this->photoUrl($member->small_photos ?: $member->photos),
                ];
            })
            ->values();

        return response()->json($members);
    }

    public function checkIn(Request $request)
    {
        $validator = $this->gateActionValidator($request, 'check_in_time');

        if ($validator->fails()) {
            return $this->validationError($validator);
        }

        $memberId = (int) $validator->validated()['member_id'];
        $branchStoreId = (int) $validator->validated()['store_branch_id'];
        $checkInTime = $this->parseEventTime($validator->validated()['check_in_time']);
        $membership = $this->findAccessibleActiveMembership($memberId, $branchStoreId, $checkInTime);

        if (!$membership) {
            return response()->json([
                'message' => 'Member active not found or cannot access this branch.',
            ], 404);
        }

        if ($membership->leave_day_status === 'Freeze') {
            return response()->json([
                'message' => $membership->full_name . ' sedang cuti.',
            ], 409);
        }

        if ($this->membershipHasUnpaidPayment($membership, $branchStoreId)) {
            return response()->json([
                'message' => 'Unpaid Member.',
            ], 409);
        }

        $deadlineStatus = $this->membershipPaymentDeadlineStatus($membership, $branchStoreId, $checkInTime);
        if ($deadlineStatus && $deadlineStatus['blocked']) {
            return response()->json([
                'message' => $deadlineStatus['message'],
            ], 409);
        }

        $checkIn = DB::transaction(function () use ($membership, $branchStoreId, $request, $checkInTime) {
            DB::table('member_registrations')
                ->where('id', $membership->member_registration_id)
                ->lockForUpdate()
                ->first();

            $latestCheckIn = CheckInMember::where('member_registration_id', $membership->member_registration_id)
                ->latest('id')
                ->lockForUpdate()
                ->first();

            if ($latestCheckIn && !$latestCheckIn->check_out_time) {
                return null;
            }

            return CheckInMember::create($this->memberCheckInPayload([
                'member_registration_id' => $membership->member_registration_id,
                'branch_store_id' => $branchStoreId,
                'check_in_time' => $checkInTime,
                'user_id' => optional($request->user())->id,
            ]));
        });

        if (!$checkIn) {
            return response()->json([
                'message' => 'Member already checked in.',
                'member' => $this->memberResponse($membership),
            ], 409);
        }

        return response()->json([
            'message' => 'Member Checked In Successfully' . ($deadlineStatus ? ' ' . $deadlineStatus['message'] : ''),
            'status' => 'checked_in',
            'member' => $this->memberResponse($membership),
            'check_in' => $this->checkInResponse($checkIn),
        ]);
    }

    public function checkOut(Request $request)
    {
        $validator = $this->gateActionValidator($request, 'check_out_time');

        if ($validator->fails()) {
            return $this->validationError($validator);
        }

        $memberId = (int) $validator->validated()['member_id'];
        $branchStoreId = (int) $validator->validated()['store_branch_id'];
        $checkOutTime = $this->parseEventTime($validator->validated()['check_out_time']);
        $membership = $this->findAccessibleActiveMembership($memberId, $branchStoreId, $checkOutTime);

        if (!$membership) {
            return response()->json([
                'message' => 'Member active not found or cannot access this branch.',
            ], 404);
        }

        $checkIn = DB::transaction(function () use ($membership, $checkOutTime) {
            DB::table('member_registrations')
                ->where('id', $membership->member_registration_id)
                ->lockForUpdate()
                ->first();

            $latestCheckIn = CheckInMember::where('member_registration_id', $membership->member_registration_id)
                ->latest('id')
                ->lockForUpdate()
                ->first();

            if (!$latestCheckIn || $latestCheckIn->check_out_time) {
                return null;
            }

            $latestCheckIn->update([
                'check_out_time' => $checkOutTime,
            ]);

            return $latestCheckIn->fresh();
        });

        if (!$checkIn) {
            return response()->json([
                'message' => 'Member is not currently checked in.',
                'member' => $this->memberResponse($membership),
            ], 409);
        }

        return response()->json([
            'message' => 'Member Checked Out Successfully',
            'status' => 'checked_out',
            'member' => $this->memberResponse($membership),
            'check_in' => $this->checkInResponse($checkIn),
        ]);
    }

    private function findAccessibleActiveMembership(int $memberId, int $branchStoreId, Carbon $eventTime)
    {
        $eventDate = $eventTime->toDateString();

        $query = DB::table('member_registrations as mr')
            ->select([
                'mr.id as member_registration_id',
                'mr.start_date',
                'mr.created_at as registration_created_at',
                'mr.payment_deadline',
                'mr.package_price as mr_package_price',
                'mr.admin_price as mr_admin_price',
                'm.id',
                'm.full_name',
                'm.card_number',
                'm.member_code',
                'm.photos',
                'm.small_photos',
                'mp.package_name',
                'mp.is_all_club',
                'mp.branch_store_id as member_package_branch_store_id',
            ])
            ->selectRaw('CONCAT(DATE(DATE_ADD(mr.start_date, INTERVAL mr.days DAY)), " 23:59:59") as end_date')
            ->selectRaw('IFNULL((SELECT SUM(value) FROM member_registration_payments mrp WHERE mrp.member_registration_id = mr.id), 0) as payment_summary')
            ->selectRaw('CASE WHEN active_leave.id IS NULL THEN "No Leave Days" ELSE "Freeze" END as leave_day_status')
            ->join('members as m', 'm.id', '=', 'mr.member_id')
            ->join('member_packages as mp', 'mp.id', '=', 'mr.member_package_id')
            ->leftJoin(DB::raw('(
                SELECT ld.id, ld.member_registration_id, ld.submission_date, ld_view.total_days
                FROM leave_days ld
                INNER JOIN (
                    SELECT IFNULL(leave_day_continue_id, id) AS leave_day_continue_id, SUM(days) AS total_days
                    FROM leave_days
                    GROUP BY IFNULL(leave_day_continue_id, id)
                ) ld_view ON ld.id = ld_view.leave_day_continue_id
                WHERE ? BETWEEN ld.submission_date AND DATE_ADD(ld.submission_date, INTERVAL IFNULL(ld_view.total_days, 0) DAY)
            ) as active_leave'), 'active_leave.member_registration_id', '=', 'mr.id')
            ->addBinding($eventTime->toDateTimeString(), 'join')
            ->where('m.id', $memberId)
            ->whereDate('mr.start_date', '<=', $eventDate)
            ->whereRaw('DATE(DATE_ADD(mr.start_date, INTERVAL mr.days DAY)) >= ?', [$eventDate])
            ->where(function ($query) use ($branchStoreId) {
                $query
                    // All club dapat masuk ke semua cabang.
                    ->where('mp.is_all_club', 1)
                    // One club hanya dapat masuk ke cabang package yang sama dengan request.
                    ->orWhere(function ($query) use ($branchStoreId) {
                        $query->where('mp.is_all_club', 0)
                            ->where('mp.branch_store_id', $branchStoreId);
                    });
            })
            ->orderBy('mr.start_date', 'desc')
            ->orderBy('mr.id', 'desc');

        return $query->first();
    }

    private function gateActionValidator(Request $request, string $timeField)
    {
        return Validator::make($request->all(), [
            'store_branch_id' => ['required', 'integer', 'exists:branch_stores,id'],
            'member_id' => ['required', 'integer', 'exists:members,id'],
            $timeField => ['required', 'date'],
        ]);
    }

    private function parseEventTime(string $time): Carbon
    {
        return Carbon::parse($time, self::TIMEZONE)->setTimezone(self::TIMEZONE);
    }

    private function validationError($validator)
    {
        return response()->json([
            'message' => 'The given data was invalid.',
            'errors' => $validator->errors(),
        ], 422);
    }

    private function membershipHasUnpaidPayment($membership, int $branchStoreId): bool
    {
        $isUnpaid = (int) $membership->payment_summary
            < ((int) $membership->mr_package_price + (int) $membership->mr_admin_price);
        $isOneClubPackage = (string) ($membership->is_all_club ?? '1') === '0';

        return $isUnpaid && (BranchStorePaymentIsStrict($branchStoreId) || !$isOneClubPackage);
    }

    private function membershipPaymentDeadlineStatus($membership, int $branchStoreId, Carbon $eventTime): ?array
    {
        $isUnpaid = (int) $membership->payment_summary
            < ((int) $membership->mr_package_price + (int) $membership->mr_admin_price);
        $isOneClubPackage = (string) ($membership->is_all_club ?? '1') === '0';
        $deadlineDays = (int) ($membership->payment_deadline ?? 0);

        if (BranchStorePaymentIsStrict($branchStoreId) || !$isOneClubPackage || !$isUnpaid || $deadlineDays <= 0) {
            return null;
        }

        $deadlineDate = Carbon::parse($membership->registration_created_at, self::TIMEZONE)
            ->addDays($deadlineDays)
            ->startOfDay();
        $formattedDeadline = $deadlineDate->isoFormat('DD MMMM YYYY');
        $isPastDeadline = $eventTime->copy()->startOfDay()->gt($deadlineDate);

        return [
            'blocked' => $isPastDeadline,
            'message' => $isPastDeadline
                ? 'Payment deadline passed on ' . $formattedDeadline . '. Check-in denied.'
                : 'Payment is not fully paid. Payment deadline: ' . $formattedDeadline . '.',
        ];
    }

    private function memberResponse($member): array
    {
        return [
            'id' => $member->id,
            'full_name' => $member->full_name,
            'card_number' => $member->card_number,
            'member_code' => $member->member_code,
            'active_date' => $member->start_date,
            'end_date' => $member->end_date,
            'package_name' => $member->package_name,
            'package_type' => (int) $member->is_all_club === 1 ? 'all_club' : 'one_club',
            'url_photo' => $this->photoUrl($member->small_photos ?: $member->photos),
        ];
    }

    private function checkInResponse(CheckInMember $checkIn): array
    {
        return [
            'id' => $checkIn->id,
            'member_registration_id' => $checkIn->member_registration_id,
            'branch_store_id' => $checkIn->branch_store_id ?? null,
            'check_in_time' => $checkIn->check_in_time,
            'check_out_time' => $checkIn->check_out_time,
        ];
    }

    private function memberCheckInPayload(array $data): array
    {
        if (!$this->memberCheckInHasBranchStoreColumn()) {
            unset($data['branch_store_id']);
        }

        return $data;
    }

    private function memberCheckInHasBranchStoreColumn(): bool
    {
        static $hasColumn = null;

        if ($hasColumn === null) {
            $hasColumn = Schema::hasColumn('check_in_members', 'branch_store_id');
        }

        return $hasColumn;
    }

    private function photoUrl(?string $path): ?string
    {
        if (!$path) {
            return null;
        }

        return asset('storage/' . ltrim($path, '/'));
    }
}
