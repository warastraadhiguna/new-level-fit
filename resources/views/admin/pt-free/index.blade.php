<style>
    .fireworks { position: relative; overflow: hidden; }
    .fireworks::after {
        content: ""; position: absolute; width: 100px; height: 100px;
        background-image: url('/cake.png'); background-repeat: no-repeat; background-size: contain;
        animation: fireworks 5s linear infinite;
    }
    .birthdayy { color: rgb(0, 0, 0); }
    @keyframes fireworks {
        0% { transform: translateY(0) rotateZ(0deg); opacity: 0; }
        20% { opacity: 1; }
        50% { transform: translateY(-100px) rotateZ(180deg); opacity: 0; }
        80% { transform: translateY(0) rotateZ(360deg); opacity: 1; }
        100% { transform: translateY(0) rotateZ(360deg); opacity: 0; }
    }
</style>

@php
    $isPtReadOnly = Auth::user()->isPt();
@endphp

<div class="row">
    <div class="col-xl-12">
        <div class="row">
            <div class="col-xl-12">
                <div class="page-title flex-wrap justify-content-between">
                    @if ($scope === 'history')
                        <div class="d-flex flex-nowrap align-items-center mb-2">
                            <input type="date" id="historyFromDate" class="form-control" value="{{ $fromDate }}">
                            <span class="mx-1">to</span>
                            <input type="date" id="historyToDate" class="form-control" value="{{ $toDate }}">
                            <button type="button" onclick="filterPtFreeHistory()" class="btn btn-info mx-1">Filter</button>
                        </div>
                    @endif
                    @if (Auth::user()->isOwner())
                        <button type="button" class="btn btn-info mb-2" data-bs-toggle="modal" data-bs-target="#ptFreeExcelModal">
                            Download Excel
                        </button>
                    @endif
                </div>
            </div>

            @if (!$isPtReadOnly)
            @foreach ($birthdayMessages as $key => $messages)
                @foreach ($messages as $memberId => $memberName)
                    @php
                        $bgClass = $key === 0 ? 'bg-info fireworks' : 'bg-warning';
                        $birthdayMessage = $key === 0
                            ? "Today is $memberName's birthday"
                            : "$key " . ($key === 1 ? 'day' : 'days') . " to <strong>$memberName</strong>'s birthday";
                    @endphp
                    <div class="alert alert-primary solid alert-dismissible fade show {{ $bgClass }}">
                        <a href="/member/{{ $memberId }}" class="birthdayy" target="_blank">
                            <svg viewBox="0 0 24 24" width="24" height="24" stroke-width="2" fill="none"
                                stroke-linecap="round" stroke-linejoin="round" class="me-2">
                                <circle cx="12" cy="12" r="10"></circle>
                                <line x1="12" y1="16" x2="12" y2="12"></line>
                                <line x1="12" y1="8" x2="12.01" y2="8"></line>
                            </svg>
                            <span>{!! $birthdayMessage !!}</span>
                        </a>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                @endforeach
            @endforeach
            @endif

            <div class="col-xl-12 wow fadeInUp" data-wow-delay="1.5s">
                <div class="table-responsive full-data">
                    <table class="table-responsive-lg table display dataTablesCard student-tab dataTable no-footer" id="myTable">
                        <thead>
                            <tr>
                                <th>No</th>
                                <th>Member Data</th>
                                <th>Last Check In</th>
                                <th>Date</th>
                                <th>Session</th>
                                <th>Payment</th>
                                <th>Status</th>
                                <th>Trainer</th>
                                @if (!$isPtReadOnly)
                                    <th>Action</th>
                                @endif
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($trainerSessions as $item)
                                <tr>
                                    <td>{{ $loop->iteration }}</td>
                                    <td>
                                        <h6>{{ $item->member_name }},</h6>
                                        <h6>{{ $item->member_code }}</h6>
                                        <h6>{{ $item->package_name }}</h6>
                                        <br>
                                        <h6>{{ $item->branch_store_name }}</h6>
                                    </td>
                                    <td>
                                        @if (!$item->check_in_time && !$item->check_out_time)
                                            <span class="badge badge-info badge-lg">Not Yet</span>
                                        @elseif ($item->check_in_time && $item->check_out_time)
                                            <span class="badge badge-info badge-lg">
                                                {{ DateDiff($item->check_out_time, \Carbon\Carbon::now(), true) }} day ago
                                            </span>
                                        @else
                                            <span class="badge badge-info badge-lg">Running</span>
                                        @endif
                                    </td>
                                    <td>
                                        <h6>
                                            {{ $item->start_date ? DateFormat($item->start_date, 'DD MMMM YYYY') : '-' }}-<br>
                                            {{ $item->expired_date ? DateFormat($item->expired_date, 'DD MMMM YYYY') : '-' }}
                                        </h6>
                                    </td>
                                    <td>
                                        <span class="badge badge-primary d-inline-block">{{ $item->package_name }}</span>
                                        <h6>Session Total : {{ $item->ts_number_of_session }}</h6>
                                        <h6>Remaining Session : {{ max(0, (int) $item->remaining_sessions) }}</h6>
                                    </td>
                                    <td><span class="badge badge-primary badge-lg">Free</span></td>
                                    <td>
                                        @if ($item->leave_day_status === 'Freeze')
                                            <span class="badge badge-secondary d-inline-block" tabindex="0"
                                                data-bs-toggle="popover" data-bs-trigger="hover focus"
                                                data-bs-content="Leave until {{ DateFormat($item->expired_leave_days, 'DD MMMM YYYY') }}">Freeze</span>
                                        @elseif ($scope === 'waiting')
                                            <span class="badge badge-warning badge-lg">Waiting List</span>
                                        @elseif ($scope === 'expired')
                                            <span class="badge badge-danger badge-lg">Expired</span>
                                        @elseif ($scope === 'pending')
                                            <span class="badge badge-warning badge-lg">Not Start</span>
                                        @elseif ($item->check_in_time && !$item->check_out_time)
                                            <span class="badge badge-info badge-lg">Running</span>
                                        @else
                                            <span class="badge badge-info badge-lg">Not Start</span>
                                        @endif
                                    </td>
                                    <td><h6>{{ $item->trainer_name ?: '-' }}</h6></td>
                                    @if (!$isPtReadOnly)
                                    <td>
                                        <div class="btn-group dropstart" role="group">
                                            <button type="button" class="btn btn-primary btn-xs dropdown-toggle"
                                                style="width: 100px" data-bs-toggle="dropdown" aria-expanded="false">Action</button>
                                            <ul class="dropdown-menu">
                                                @if ($scope === 'active' && $item->leave_day_status !== 'Freeze')
                                                    <li>
                                                        <a href="{{ route('pt-free.check-in.session', $item->id) }}" class="btn light btn-info btn-xs mb-1 btn-block">
                                                            {{ $item->check_in_time && !$item->check_out_time ? 'Check Out' : 'Check In' }}
                                                        </a>
                                                    </li>
                                                @endif
                                                @if (Auth::user()->isAdmin())
                                                    <li><a href="{{ route('pt-free.edit', $item->id) }}" class="btn light btn-warning btn-xs mb-1 btn-block">Edit</a></li>
                                                @endif
                                                <li><a href="{{ route('pt-free.show', $item->id) }}" class="btn light btn-info btn-xs mb-1 btn-block">Detail</a></li>
                                                @if (Auth::user()->isAdmin())
                                                    <li>
                                                        @if (Auth::user()->isOwner())
                                                            <form onsubmit="return confirm('Hapus sementara data beserta history dan omzet terkait? Data dapat direstore dari Tempat Sampah Tip-Tap.')" action="{{ route('pt-free.destroy', $item->id) }}" method="POST">
                                                                @csrf
                                                                @method('DELETE')
                                                                <button type="submit" class="btn light btn-danger btn-xs mb-1 btn-block">Hapus sementara</button>
                                                            </form>
                                                        @endif
                                                    </li>
                                                @endif
                                            </ul>
                                        </div>
                                    </td>
                                    @endif
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="ptFreeExcelModal" tabindex="-1" aria-labelledby="ptFreeExcelModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h1 class="modal-title fs-5" id="ptFreeExcelModalLabel">Download Excel by Date</h1>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="row">
                    <div class="col-6">
                        <div class="mb-3">
                            <label class="form-label">From Date</label>
                            <input type="date" id="excelFromDate" class="form-control" value="{{ $fromDate }}">
                        </div>
                    </div>
                    <div class="col-6">
                        <div class="mb-3">
                            <label class="form-label">To Date</label>
                            <input type="date" id="excelToDate" class="form-control" value="{{ $toDate }}">
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" onclick="downloadPtFreeExcel()" class="btn btn-primary">Download</button>
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<script>
    function ptFreeUrl(params) {
        return window.location.pathname + '?' + new URLSearchParams(params).toString();
    }

    function filterPtFreeHistory() {
        window.location.href = ptFreeUrl({
            fromDate: document.getElementById('historyFromDate').value,
            toDate: document.getElementById('historyToDate').value
        });
    }

    function downloadPtFreeExcel() {
        window.location.href = ptFreeUrl({
            excel: '1',
            fromDate: document.getElementById('excelFromDate').value,
            toDate: document.getElementById('excelToDate').value
        });
    }
</script>
