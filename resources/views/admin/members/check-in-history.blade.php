<div class="row">
    <div class="col-xl-12">
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-body d-flex flex-wrap justify-content-between align-items-center gap-3">
                <div>
                    <h4 class="mb-1">{{ $member->full_name }}</h4>
                    <div class="text-muted">
                        {{ $member->member_code ?? 'No Member Code' }}
                        &middot; {{ $package->package_name ?? 'Deleted Package' }}
                        &middot; {{ $package->branch_store_name ?? '-' }}
                    </div>
                    @if ($historyType === 'pt')
                        <div class="mt-1">
                            <span class="badge {{ $package->is_pt_free ? 'badge-success' : 'badge-primary' }}">
                                {{ $package->is_pt_free ? 'PT Free' : 'Paid PT' }}
                            </span>
                            <span class="text-muted ms-1">{{ $package->trainer_name ?? 'Trainer Not Assigned' }}</span>
                        </div>
                    @endif
                </div>
                <div class="d-flex flex-wrap align-items-center gap-2">
                    <span class="badge badge-success p-2">{{ $checkIns->total() }} Times Training</span>
                    <a href="{{ $backRoute }}" class="btn btn-light btn-sm">Back</a>
                </div>
            </div>
        </div>
    </div>

    <div class="col-xl-12 wow fadeInUp" data-wow-delay="1.5s">
        <div class="table-responsive full-data">
            <table class="table table-bordered align-middle" style="text-align: center;" width="100%">
                <thead>
                    <tr>
                        <th>No</th>
                        <th>Check In Time</th>
                        <th>Check Out Time</th>
                        <th>Duration</th>
                        @if ($historyType === 'pt')
                            <th>Trainer</th>
                        @endif
                        <th>Branch</th>
                        <th>Staff</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($checkIns as $item)
                        @php
                            $duration = '-';
                            if ($item->check_in_time && $item->check_out_time) {
                                $seconds = Carbon\Carbon::parse($item->check_in_time)
                                    ->diffInSeconds(Carbon\Carbon::parse($item->check_out_time));
                                $duration = sprintf(
                                    '%02d:%02d:%02d',
                                    floor($seconds / 3600),
                                    floor(($seconds % 3600) / 60),
                                    $seconds % 60
                                );
                            }
                        @endphp
                        <tr>
                            <td>{{ $checkIns->firstItem() + $loop->index }}</td>
                            <td class="text-nowrap">{{ DateFormat($item->check_in_time, 'DD MMMM YYYY, HH:mm:ss') }}</td>
                            <td class="text-nowrap">
                                {{ $item->check_out_time ? DateFormat($item->check_out_time, 'DD MMMM YYYY, HH:mm:ss') : 'Not Yet' }}
                            </td>
                            <td>{{ $duration }}</td>
                            @if ($historyType === 'pt')
                                <td>{{ $item->trainer_name }}</td>
                            @endif
                            <td>{{ $item->branch_store_name ?? $package->branch_store_name ?? '-' }}</td>
                            <td>{{ $item->staff_name ?? '-' }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="{{ $historyType === 'pt' ? 7 : 6 }}">No check-in history found for this package</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="mt-3">
            {{ $checkIns->links('pagination::bootstrap-4') }}
        </div>
    </div>
</div>
