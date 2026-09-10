<div class="row">
    <div class="col-xl-12">
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-body d-flex flex-wrap justify-content-between align-items-center gap-3">
                <div>
                    <h4 class="mb-1">{{ $member->full_name }}</h4>
                    <div class="text-muted">
                        {{ $member->member_code ?? 'No Member Code' }}
                        &middot; {{ $member->phone_number ?? 'No Phone Number' }}
                        &middot; {{ optional($member->branchStore)->name ?? '-' }}
                    </div>
                </div>
                <div class="d-flex flex-wrap gap-2">
                    <span class="badge badge-primary p-2">{{ $histories->total() }} PT Package</span>
                    <span class="badge badge-success p-2">{{ $totalUsedSessions }} Times PT Training</span>
                    <span class="badge badge-warning p-2">{{ $totalUnusedSessions }} Unused Quota</span>
                    <a href="{{ route('members.index') }}" class="btn btn-light btn-sm">Back</a>
                </div>
            </div>
        </div>
    </div>

    <div class="col-xl-12 wow fadeInUp" data-wow-delay="1.5s">
        <div class="table-responsive full-data">
            <table class="table table-bordered align-middle" style="text-align: left;" width="100%">
                <thead>
                    <tr>
                        <th>No</th>
                        <th>PT Package</th>
                        <th>Trainer &amp; Branch</th>
                        <th>Period</th>
                        <th>Session Usage</th>
                        <th>Payment</th>
                        <th>Status</th>
                        <th>Description</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($histories as $item)
                        @php
                            $totalPrice = max(0, (int) $item->package_price + (int) $item->admin_price - (int) $item->discount_amount);
                            $isPaid = (int) $item->paid_amount >= $totalPrice;
                            $statusClass = in_array($item->pt_status, ['Active', 'Completed'], true)
                                ? 'badge-success'
                                : ($item->pt_status === 'Expired' ? 'badge-danger' : 'badge-warning');
                        @endphp
                        <tr>
                            <td>{{ $histories->firstItem() + $loop->index }}</td>
                            <td>
                                <h6 class="mb-1">{{ $item->package_name ?? 'Deleted Package' }}</h6>
                                <span class="badge {{ $item->is_pt_free ? 'badge-success' : 'badge-primary' }}">
                                    {{ $item->is_pt_free ? 'PT Free' : 'Paid PT' }}
                                </span>
                            </td>
                            <td>
                                <h6 class="mb-1">{{ $item->trainer_name ?? 'Trainer Not Assigned' }}</h6>
                                <small>{{ $item->branch_store_name ?? '-' }}</small>
                            </td>
                            <td class="text-nowrap">
                                @if ($item->start_date)
                                    <div>{{ DateFormat($item->start_date, 'DD MMMM YYYY') }}</div>
                                    <div>{{ DateFormat($item->expired_date, 'DD MMMM YYYY') }}</div>
                                    <small class="text-muted">{{ $item->days }} days</small>
                                @else
                                    <span class="text-muted">Not scheduled</span>
                                @endif
                                @if ((int) $item->freeze_days > 0)
                                    <small class="d-block text-info">Freeze: {{ $item->freeze_days }} days</small>
                                @endif
                            </td>
                            <td class="text-nowrap">
                                <div>Total Quota: <strong>{{ $item->number_of_session }}</strong></div>
                                <div>PT Training: <strong>{{ $item->used_sessions }}</strong></div>
                                <div>Unused Quota: <strong>{{ $item->unused_sessions }}</strong></div>
                                @if ((int) $item->check_in_count > (int) $item->used_sessions)
                                    <small class="text-info">There is an ongoing session</small>
                                @endif
                                <a href="{{ route('members.pt-check-in-history', [$member, $item->id]) }}"
                                    class="btn light btn-info btn-xs d-block mt-2">Check In/Out Detail</a>
                            </td>
                            <td class="text-nowrap">
                                @if ($item->is_pt_free)
                                    <span class="badge badge-success">Free</span>
                                @else
                                    <div>Package: {{ formatRupiah($item->package_price) }}</div>
                                    <div>Admin: {{ formatRupiah($item->admin_price) }}</div>
                                    @if ((int) $item->discount_amount > 0)
                                        <div>Discount: {{ formatRupiah($item->discount_amount) }}</div>
                                    @endif
                                    <div><strong>Total: {{ formatRupiah($totalPrice) }}</strong></div>
                                    <div>Paid: {{ formatRupiah($item->paid_amount) }}</div>
                                    <span class="badge {{ $isPaid ? 'badge-success' : 'badge-danger' }}">
                                        {{ $isPaid ? 'Paid' : 'Unpaid' }}
                                    </span>
                                @endif
                                <small class="d-block mt-1">{{ $item->payment_method_name ?? '-' }}</small>
                            </td>
                            <td>
                                <span class="badge {{ $statusClass }}">{{ $item->pt_status }}</span>
                                <small class="d-block mt-2">Created by {{ $item->staff_name ?? '-' }}</small>
                                <small class="text-muted">{{ DateFormat($item->created_at, 'DD MMMM YYYY, HH:mm') }}</small>
                            </td>
                            <td style="min-width: 180px; white-space: normal;">
                                {{ $item->description ?: '-' }}
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="text-center">No PT history found</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="mt-3">
            {{ $histories->links('pagination::bootstrap-4') }}
        </div>
    </div>
</div>
