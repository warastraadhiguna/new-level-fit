<div class="row">
    @php
        $sortLink = function ($column) use ($sort, $direction, $search, $perPage) {
            $nextDirection = $sort === $column && $direction === 'asc' ? 'desc' : 'asc';

            return route('trainer-approval.index', array_filter([
                'search' => $search,
                'per_page' => $perPage,
                'sort' => $column,
                'direction' => $nextDirection,
            ], fn ($value) => $value !== null && $value !== ''));
        };

        $sortIcon = function ($column) use ($sort, $direction) {
            if ($sort !== $column) {
                return 'fa-sort';
            }

            return $direction === 'asc' ? 'fa-sort-up' : 'fa-sort-down';
        };
    @endphp
    <div class="col-xl-12">
        <div class="row">
            <div class="col-xl-12">
                <div class="card border-0 shadow-sm mb-4">
                    <div class="card-body">
                        <form method="GET" action="{{ route('trainer-approval.index') }}" id="trainerApprovalSearchForm">
                            <input type="hidden" name="sort" value="{{ $sort }}">
                            <input type="hidden" name="direction" value="{{ $direction }}">
                            <div class="d-flex justify-content-end align-items-center mb-4">
                                @if ($search)
                                    <a href="{{ route('trainer-approval.index') }}" class="btn btn-danger light">
                                        Reset Search
                                    </a>
                                @endif
                            </div>
                            <div class="d-flex flex-wrap justify-content-between align-items-center gap-3">
                                <div class="d-flex align-items-center gap-2">
                                    <span class="text-muted">Show</span>
                                    <select name="per_page" class="form-control" style="width: 80px;"
                                        onchange="this.form.submit()">
                                        @foreach ([10, 25, 50, 100] as $option)
                                            <option value="{{ $option }}" {{ $perPage == $option ? 'selected' : '' }}>
                                                {{ $option }}
                                            </option>
                                        @endforeach
                                    </select>
                                    <span class="text-muted">entries</span>
                                </div>
                                <div class="d-flex align-items-center gap-2">
                                    <span class="text-muted">Search:</span>
                                    <input type="text" name="search" class="form-control" style="width: 280px;"
                                        value="{{ $search }}" placeholder="Press Enter to search">
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

            <div class="col-xl-12 wow fadeInUp" data-wow-delay="1.5s">
                <div class="table-responsive full-data">
                    <table class="table-responsive-lg table display dataTablesCard student-tab dataTable no-footer"
                        id="trainerApprovalTable">
                        <thead>
                            <tr>
                                <th>No</th>
                                <th>
                                    <a href="{{ $sortLink('member_name') }}" class="text-primary">
                                        Member Data <i class="fa {{ $sortIcon('member_name') }}"></i>
                                    </a>
                                </th>
                                <th>
                                    <a href="{{ $sortLink('check_in_time') }}" class="text-primary">
                                        Last Check In <i class="fa {{ $sortIcon('check_in_time') }}"></i>
                                    </a>
                                </th>
                                <th>
                                    <a href="{{ $sortLink('start_date') }}" class="text-primary">
                                        Date <i class="fa {{ $sortIcon('start_date') }}"></i>
                                    </a>
                                </th>
                                <th>Session</th>
                                <th>
                                    <a href="{{ $sortLink('payment_summary') }}" class="text-primary">
                                        Payment <i class="fa {{ $sortIcon('payment_summary') }}"></i>
                                    </a>
                                </th>
                                <th>Status</th>
                                <th>
                                    <a href="{{ $sortLink('trainer_name') }}" class="text-primary">
                                        Trainer <i class="fa {{ $sortIcon('trainer_name') }}"></i>
                                    </a>
                                </th>
                                <th>
                                    <a href="{{ $sortLink('staff_name') }}" class="text-primary">
                                        Staff <i class="fa {{ $sortIcon('staff_name') }}"></i>
                                    </a>
                                </th>
                                <th>
                                    <a href="{{ $sortLink('created_at') }}" class="text-primary">
                                        Created At <i class="fa {{ $sortIcon('created_at') }}"></i>
                                    </a>
                                </th>
                                <th style="min-width: 190px">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($trainerSessions as $item)
                                @php
                                    $latestCheckIn = $item->latestCheckIn;
                                    $totalFreezeDays = (int) $item->leaveDays->sum('days');
                                    $expiredDate = $item->start_date
                                        ? \Carbon\Carbon::parse($item->start_date)->addDays((int) $item->days + $totalFreezeDays)
                                        : null;
                                    $now = \Carbon\Carbon::now();
                                    $isFrozen = $item->leaveDays->contains(function ($leaveDay) use ($now) {
                                        if (!$leaveDay->submission_date) {
                                            return false;
                                        }

                                        $freezeStart = \Carbon\Carbon::parse($leaveDay->submission_date);
                                        $freezeEnd = $freezeStart->copy()->addDays((int) $leaveDay->days);

                                        return $now->between($freezeStart, $freezeEnd);
                                    });
                                    $paymentSummary = (int) ($item->payment_summary ?? 0);
                                    $totalPayable = max(
                                        0,
                                        (int) $item->package_price + (int) $item->admin_price - (int) $item->discount_amount
                                    );
                                    $trainerPackage = $item->trainerPackageWithTrashed;
                                    $trainerPackageLabel = $trainerPackage
                                        ? $trainerPackage->package_name . ($trainerPackage->trashed() ? ' (Deleted)' : '')
                                        : 'Package Not Available (Deleted)';
                                    $totalSessions = (int) ($item->number_of_session ?: optional($trainerPackage)->number_of_session);
                                    $remainingSessions = max(0, $totalSessions - (int) $item->used_session_count);
                                @endphp
                                <tr>
                                    <td>{{ $trainerSessions->firstItem() + $loop->index }}</td>
                                    <td>
                                        <h6>{{ optional($item->members)->full_name }}</h6>
                                        <h6>{{ optional($item->members)->member_code }}</h6>
                                        <h6>{{ $trainerPackageLabel }}</h6>
                                        <br>
                                        <h6>{{ optional($item->branchStore)->name }}</h6>
                                    </td>
                                    <td>
                                        @if (!$latestCheckIn)
                                            <span class="badge badge-info badge-lg">Not Yet</span>
                                        @elseif (!$latestCheckIn->check_out_time)
                                            <span class="badge badge-primary badge-lg">Running</span>
                                        @else
                                            <span class="badge badge-info badge-lg">
                                                {{ DateDiff($latestCheckIn->check_out_time, \Carbon\Carbon::now(), true) }} day ago
                                            </span>
                                        @endif
                                    </td>
                                    <td class="text-nowrap">
                                        <h6>
                                            {{ $item->start_date ? DateFormat($item->start_date, 'DD MMMM YYYY') : '-' }}-<br>
                                            {{ $expiredDate ? DateFormat($expiredDate, 'DD MMMM YYYY') : '-' }}
                                        </h6>
                                        <small>{{ (int) $item->days }} hari</small>
                                    </td>
                                    <td>
                                        <span class="badge badge-primary d-inline-block">
                                            {{ $trainerPackageLabel }}
                                        </span>
                                        <h6>Session Total : {{ $totalSessions }}</h6>
                                        <h6>Remaining Session : {{ $remainingSessions }}</h6>
                                    </td>
                                    <td>
                                        @if ($paymentSummary >= $totalPayable)
                                            <span class="badge badge-primary badge-lg">Paid</span>
                                        @else
                                            <span class="badge badge-danger badge-lg">
                                                {{ formatRupiah($totalPayable - $paymentSummary) }}
                                            </span>
                                        @endif
                                    </td>
                                    <td>
                                        @if ($isFrozen)
                                            <span class="badge badge-secondary badge-lg">Freeze</span>
                                        @elseif ($latestCheckIn && !$latestCheckIn->check_out_time)
                                            <span class="badge badge-primary badge-lg">Running</span>
                                        @else
                                            <span class="badge badge-info badge-lg">Not Start</span>
                                        @endif
                                    </td>
                                    <td><h6>{{ optional($item->personalTrainers)->full_name ?: '-' }}</h6></td>
                                    <td><h6>{{ optional($item->users)->full_name ?: '-' }}</h6></td>
                                    <td class="text-nowrap">
                                        <h6>{{ DateFormat($item->created_at, 'DD MMMM YYYY') }}</h6>
                                        <small>{{ DateFormat($item->created_at, 'HH:mm') }}</small>
                                    </td>
                                    <td>
                                        <div class="d-flex flex-wrap gap-2">
                                            <button type="button" class="btn btn-primary btn-sm" data-bs-toggle="modal"
                                                data-bs-target="#trainerApprovalModal{{ $item->id }}">
                                                Approval
                                            </button>
                                            <a href="{{ route('trainer-session.edit', $item->id) }}"
                                                class="btn btn-light btn-sm">Edit PT</a>
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="11" class="text-center">Tidak ada PT yang perlu disetujui.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                @foreach ($trainerSessions as $item)
                    @php
                        $hasApprovalError = (string) old('trainer_session_id') === (string) $item->id
                            && $errors->any();
                    @endphp
                    <div class="modal fade" id="trainerApprovalModal{{ $item->id }}" tabindex="-1"
                        aria-labelledby="trainerApprovalModalLabel{{ $item->id }}" aria-hidden="true">
                        <div class="modal-dialog modal-dialog-centered">
                            <div class="modal-content">
                                <form action="{{ route('trainer-approval.update', $item->id) }}" method="POST">
                                    @csrf
                                    @method('PUT')
                                    <input type="hidden" name="trainer_session_id" value="{{ $item->id }}">
                                    <input type="hidden" name="is_approved" value="1">

                                    <div class="modal-header">
                                        <h5 class="modal-title" id="trainerApprovalModalLabel{{ $item->id }}">
                                            Approval PT
                                        </h5>
                                        <button type="button" class="btn-close" data-bs-dismiss="modal"
                                            aria-label="Close"></button>
                                    </div>
                                    <div class="modal-body">
                                        @if ($hasApprovalError)
                                            <div class="alert alert-danger">{{ $errors->first() }}</div>
                                        @endif

                                        <div class="mb-3">
                                            <strong>{{ optional($item->members)->full_name }}</strong>
                                            <span class="d-block text-muted">
                                                {{ optional($item->members)->member_code }} ·
                                                @php
                                                    $trainerPackage = $item->trainerPackageWithTrashed;
                                                @endphp
                                                {{ $trainerPackage
                                                    ? $trainerPackage->package_name . ($trainerPackage->trashed() ? ' (Deleted)' : '')
                                                    : 'Package Not Available (Deleted)' }} ·
                                                {{ optional($item->personalTrainers)->full_name ?: 'Trainer belum dipilih' }}
                                            </span>
                                        </div>
                                        <div>
                                            <label class="form-label">Description</label>
                                            <textarea name="description" rows="5" class="form-control" required
                                                placeholder="Description wajib diubah">{{ $hasApprovalError ? old('description') : $item->description }}</textarea>
                                            <small class="text-muted">Description wajib diubah sebelum PT disetujui.</small>
                                        </div>
                                    </div>
                                    <div class="modal-footer">
                                        <button type="button" class="btn btn-light" data-bs-dismiss="modal">Batal</button>
                                        <button type="submit" class="btn btn-primary">Approve PT</button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                @endforeach

                <div class="mt-3 d-flex flex-wrap justify-content-between align-items-center">
                    <div class="text-muted mb-2">
                        Showing {{ $trainerSessions->firstItem() ?? 0 }} to
                        {{ $trainerSessions->lastItem() ?? 0 }} of
                        {{ $trainerSessions->total() }} data
                    </div>
                    <div class="mb-2">
                        {{ $trainerSessions->links('pagination::bootstrap-4') }}
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

@if (old('trainer_session_id') && $errors->any())
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            var approvalModal = document.getElementById(@json('trainerApprovalModal' . old('trainer_session_id')));
            if (approvalModal && window.bootstrap) {
                new bootstrap.Modal(approvalModal).show();
            }
        });
    </script>
@endif
