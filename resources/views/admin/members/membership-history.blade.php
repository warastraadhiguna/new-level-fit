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
                    <span class="badge badge-primary p-2">{{ $histories->total() }} Membership</span>
                    <span class="badge badge-success p-2">{{ $totalWorkouts }} Times Training</span>
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
                        <th>Membership</th>
                        <th>Period</th>
                        <th>Training</th>
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
                            $statusClass = $item->membership_status === 'Active'
                                ? 'badge-success'
                                : ($item->membership_status === 'Expired' ? 'badge-danger' : 'badge-warning');
                        @endphp
                        <tr>
                            <td>{{ $histories->firstItem() + $loop->index }}</td>
                            <td>
                                <h6 class="mb-1">{{ $item->package_name ?? 'Deleted Package' }}</h6>
                                <span class="badge {{ $item->is_all_club ? 'badge-primary' : 'badge-warning' }}">
                                    {{ $item->is_all_club ? 'All Club' : 'One Club' }}
                                </span>
                                <small class="d-block mt-1">{{ $item->branch_store_name ?? '-' }}</small>
                            </td>
                            <td class="text-nowrap">
                                <div>{{ DateFormat($item->start_date, 'DD MMMM YYYY') }}</div>
                                <div>{{ DateFormat($item->expired_date, 'DD MMMM YYYY') }}</div>
                                <small class="text-muted">{{ $item->days }} days</small>
                                @if ((int) $item->freeze_days > 0)
                                    <small class="d-block text-info">Freeze: {{ $item->freeze_days }} days</small>
                                @endif
                            </td>
                            <td>
                                <h5 class="mb-0">{{ $item->workout_count }} times</h5>
                                <small class="text-muted">Member check-in</small>
                            </td>
                            <td class="text-nowrap">
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
                                <small class="d-block mt-1">{{ $item->payment_method_name ?? '-' }}</small>
                            </td>
                            <td>
                                <span class="badge {{ $statusClass }}">{{ $item->membership_status }}</span>
                                <small class="d-block mt-2">Created by {{ $item->staff_name ?? '-' }}</small>
                                <small class="text-muted">{{ DateFormat($item->created_at, 'DD MMMM YYYY, HH:mm') }}</small>
                            </td>
                            <td style="min-width: 180px; white-space: normal;">
                                {{ $item->description ?: '-' }}
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="text-center">No membership history found</td>
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
