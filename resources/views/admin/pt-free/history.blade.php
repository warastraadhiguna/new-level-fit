<div class="row">
    <div class="col-xl-12">
        <div class="col-xl-12">
            <div class="page-title flex-wrap justify-content-between">
                <div class="d-flex flex-nowrap align-items-center">
                    <input type="date" id="historyFromDate" class="form-control" value="{{ $fromDate }}">
                    <span class="mx-1">to</span>
                    <input type="date" id="historyToDate" class="form-control" value="{{ $toDate }}">
                    <button type="button" onclick="filterPtFreeHistory()" class="btn btn-info mx-1">Filter</button>
                </div>
                @if (Auth::user()->isOwner())
                    <button type="button" class="btn btn-info" data-bs-toggle="modal" data-bs-target="#ptFreeExcelModal">Download Excel</button>
                @endif
            </div>
        </div>
        <div class="row">
            <div class="col-xl-12 wow fadeInUp" data-wow-delay="1.5s">
                <div class="table-responsive full-data">
                    <table class="table-responsive-lg table display dataTablesCard student-tab dataTable no-footer" id="myTable">
                        <thead>
                            <tr><th>No</th><th>Image</th><th>Member Data</th><th>Last Check In</th><th>Date</th><th>Session</th><th>Status</th><th>Trainer</th><th>Action</th></tr>
                        </thead>
                        <tbody>
                            @foreach ($trainerSessions as $item)
                                <tr>
                                    <td>{{ $loop->iteration }}</td>
                                    <td>
                                        <div class="trans-list">
                                            <img src="{{ $item->photos ? Storage::url($item->photos) : asset('default.png') }}" class="lazyload" width="100" alt="image">
                                        </div>
                                    </td>
                                    <td><h6>{{ $item->member_name }},</h6><h6>{{ $item->member_code }}</h6><h6>{{ $item->package_name }}</h6></td>
                                    <td>
                                        @if (!$item->check_in_time && !$item->check_out_time)
                                            <span class="badge badge-info badge-lg">Not Yet</span>
                                        @elseif ($item->check_in_time && $item->check_out_time)
                                            <span class="badge badge-info badge-lg">{{ DateDiff($item->check_out_time, \Carbon\Carbon::now(), true) }} day ago</span>
                                        @else
                                            <span class="badge badge-info badge-lg">Running</span>
                                        @endif
                                    </td>
                                    <td><h6>{{ DateFormat($item->start_date, 'DD MMMM YYYY') }}-<br>{{ DateFormat($item->expired_date, 'DD MMMM YYYY') }}</h6></td>
                                    <td><h6>Session Total : {{ $item->ts_number_of_session }}</h6><h6>Remaining Session : {{ max(0, (int) $item->remaining_sessions) }}</h6></td>
                                    <td><span class="badge {{ $item->remaining_sessions <= 0 ? 'badge-danger' : 'badge-info' }} badge-lg">{{ $item->remaining_sessions <= 0 ? 'Expired' : 'Free' }}</span></td>
                                    <td><h6>{{ $item->trainer_name ?: '-' }}</h6></td>
                                    <td><a href="{{ route('pt-free.show', $item->id) }}" class="btn light btn-info btn-xs btn-block mb-1">Detail Member</a></td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

@if (Auth::user()->isOwner())
    @include('admin.pt-free.partials.excel-modal')
@endif

<script>
    function filterPtFreeHistory() {
        var params = new URLSearchParams({
            fromDate: document.getElementById('historyFromDate').value,
            toDate: document.getElementById('historyToDate').value
        });
        window.location.href = window.location.pathname + '?' + params.toString();
    }
</script>
