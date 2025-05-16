<style>
    .fireworks {
        position: relative;
        overflow: hidden;
    }

    .fireworks::after {
        content: "";
        position: absolute;
        width: 100px;
        height: 100px;
        background-image: url('/cake.png');
        background-repeat: no-repeat;
        background-size: contain;
        animation: fireworks 5s linear infinite;
    }

    .birthdayy {
        color: rgb(0, 0, 0);
    }

    @keyframes fireworks {
        0% {
            transform: translateY(0) rotateZ(0deg);
            opacity: 0;
        }

        20% {
            opacity: 1;
        }

        50% {
            transform: translateY(-100px) rotateZ(180deg);
            opacity: 0;
        }

        80% {
            transform: translateY(0) rotateZ(360deg);
            opacity: 1;
        }

        100% {
            transform: translateY(0) rotateZ(360deg);
            opacity: 0;
        }
    }
</style>

<div class="row">
    <div class="col-xl-12">
        <div class="row">
            <div class="col-xl-12">
                <div class="page-title flex-wrap justify-content-between">
                    <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#checkIn2"
                        id="checkInButton" onclick="manipulateView()">Input Member Code</button>
                    <button type="button" class="btn btn-info" data-bs-toggle="modal" data-bs-target="#exampleModal">
                        Download Excel
                    </button>
                </div>
            </div>

            @foreach ($birthdayMessages as $key => $messages)
                @if (!empty($messages))
                    @foreach ($messages as $memberId => $memberName)
                        @php
                            $bgClass = '';
                            switch ($key) {
                                case 0:
                                    $bgClass = 'bg-info fireworks';
                                    $birthdayMessage = "Today is $memberName's birthday";
                                    break;
                                case 1:
                                    $bgClass = 'bg-warning';
                                    $birthdayMessage = "$key day to <strong>$memberName</strong>'s birthday";
                                    break;
                                case 2:
                                    $bgClass = 'bg-warning';
                                    $birthdayMessage = "$key days to <strong>$memberName</strong>'s birthday";
                                    break;
                                default:
                                    $bgClass = 'bg-primary';
                                    $birthdayMessage = "$key days to <strong>$memberName</strong>'s birthday";
                                    break;
                            }
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
                            <button type="button" class="btn-close" data-bs-dismiss="alert"
                                aria-label="Close"></button>
                        </div>
                    @endforeach
                @endif
            @endforeach

            <!--column-->
            <div class="col-xl-12 wow fadeInUp" data-wow-delay="1.5s">
                <div class="table-responsive full-data">
                    <table class="table-responsive-lg table display dataTablesCard student-tab dataTable no-footer"
                        id="myTable">
                        <thead>
                            <tr>
                                <th>No</th>
                                <th>Image</th>
                                <th>Member Data</th>
                                <th>Last Check In</th>
                                <th>Date</th>
                                <th>Session</th>
                                <th>Status</th>
                                <th>Trainer</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($trainerSessions as $item)
                                <tr>
                                    <td>{{ $loop->iteration }}</td>
                                    <td>
                                        <div class="trans-list">
                                            @if ($item->photos)
                                                <img src="{{ Storage::url($item->photos) }}" class="lazyload"
                                                    width="100" alt="image">
                                            @else
                                                <img src="{{ asset('default.png') }}" class="lazyload" width="100"
                                                    alt="default image">
                                            @endif
                                        </div>
                                    </td>
                                    <td>
                                        <h6>{{ $item->member_name }},</h6>
                                        <h6>{{ $item->member_code }}</h6>
                                    </td>
                                    <td>
                                        @php
                                            $daysLeft = Carbon\Carbon::parse($item->expired_date)->diffInDays(
                                                Carbon\Carbon::now(),
                                            );
                                            $sumDaysLeft = $daysLeft + 1;
                                        @endphp
                                        @if ($sumDaysLeft > 3 && $sumDaysLeft < 6)
                                            <span class="badge badge-warning badge-sm d-inline-block" tabindex="0"
                                                data-bs-toggle="popover" data-bs-trigger="hover focus"
                                                data-bs-content="{{ $sumDaysLeft }} hari lagi berakhir">
                                                @if (!$item->check_in_time && !$item->check_out_time)
                                                    Not Yet
                                                @elseif ($item->check_in_time && $item->check_out_time)
                                                    {{ DateDiff($item->check_out_time, \Carbon\Carbon::now(), true) }}
                                                    day ago
                                                @elseif ($item->check_in_time && !$item->check_out_time)
                                                    Running
                                                @endif
                                            </span>
                                        @elseif($daysLeft <= 3)
                                            <span class="badge badge-danger badge-sm d-inline-block" tabindex="0"
                                                data-bs-toggle="popover" data-bs-trigger="hover focus"
                                                data-bs-content="{{ $sumDaysLeft }} hari lagi berakhir">
                                                @if (!$item->check_in_time && !$item->check_out_time)
                                                    Not Yet
                                                @elseif ($item->check_in_time && $item->check_out_time)
                                                    {{ DateDiff($item->check_out_time, \Carbon\Carbon::now(), true) }}
                                                    day ago
                                                @elseif ($item->check_in_time && !$item->check_out_time)
                                                    Running
                                                @endif
                                            </span>
                                        @else
                                            <span class="badge badge-info badge-lg">
                                                @if (!$item->check_in_time && !$item->check_out_time)
                                                    Not Yet
                                                @elseif ($item->check_in_time && $item->check_out_time)
                                                    {{ DateDiff($item->check_out_time, \Carbon\Carbon::now(), true) }}
                                                    day ago
                                                @elseif ($item->check_in_time && !$item->check_out_time)
                                                    Running
                                                @endif
                                            </span>
                                        @endif
                                    </td>
                                    <td>
                                        <h6>{{ DateFormat($item->start_date, 'DD MMMM YYYY') }}- <br />
                                            {{ DateFormat($item->expired_date, 'DD MMMM YYYY') }}
                                        </h6>
                                    </td>
                                    <td>
                                        <h6>Session Total : {{ $item->ts_number_of_session }}</h6>
                                        <h6>Remaining Session : {{ $item->remaining_sessions }}</h6>
                                    </td>
                                    <td>
                                        @if ($item->leave_day_status == 'Freeze')
                                            <span class="badge badge-secondary d-inline-block" tabindex="0"
                                                data-bs-toggle="popover" data-bs-trigger="hover focus"
                                                data-bs-content="Leave until {{ DateFormat($item->expired_leave_days, 'DD MMMM YYYY') }}">
                                                Freeze
                                            </span>
                                        @else
                                            @if ((!$item->check_in_time && !$item->check_out_time) || ($item->check_in_time && $item->check_out_time))
                                                <span class="badge badge-info badge-lg">Not Start</span>
                                            @elseif ($item->check_in_time && !$item->check_out_time)
                                                <span class="badge badge-info badge-lg">Running</span>
                                            @endif
                                        @endif
                                    </td>
                                    <td>
                                        <h6>{{ $item->trainer_name }}</h6>
                                    </td>
                                    <td>
                                        @php
                                            $now = \Carbon\Carbon::now()->tz('asia/jakarta');
                                        @endphp
                                        @if ($idCodeMaxCount - $item->id_code_count == 0)
                                            <a href="{{ route('resetCheckIn', $item->member_id) }}"
                                                class="btn light btn-warning btn-xs mb-1 btn-block">Reset Check In ?</a>
                                        @else
                                            @if ($item->leave_day_status == 'Freeze')
                                                <a class="btn light btn-info btn-xs mb-1 btn-block">Freeze</a>
                                            @else
                                                @if ($now > $item->expired_leave_days)
                                                    @if ($item->start_date < $now)
                                                        @if ((!$item->check_in_time && !$item->check_out_time) || ($item->check_in_time && $item->check_out_time))
                                                            <a href="{{ route('PTSecondCheckIn', $item->id) }}"
                                                                class="btn light btn-info btn-xs mb-1 btn-block">Check
                                                                In ({{ $idCodeMaxCount - $item->id_code_count }})</a>
                                                        @elseif ($item->check_in_time && !$item->check_out_time)
                                                            <a href="{{ route('PTSecondCheckIn', $item->id) }}"
                                                                class="btn light btn-info btn-xs mb-1 btn-block">Check
                                                                Out ({{ $idCodeMaxCount - $item->id_code_count }})</a>
                                                        @endif
                                                    @endif
                                                @endif
                                            @endif
                                        @endif
                                        @if (Auth::user()->role == 'ADMIN')
                                            <a href="{{ route('trainer-session.edit', $item->id) }}"
                                                class="btn light btn-warning btn-xs mb-1 btn-block">Edit</a>
                                        @endif
                                        @if ($item->leave_day_status == 'Freeze')
                                            <a href="{{ route('cutiTrainerSession', $item->id) }}" target="_blank"
                                                class="btn light btn-secondary btn-xs mb-1 btn-block">Cuti</a>
                                        @endif
                                        <a href="{{ route('trainer-session.show', $item->id) }}"
                                            class="btn light btn-info btn-xs mb-1 btn-block">Detail</a>
                                        @if (Auth::user()->role == 'ADMIN')
                                            <form action="{{ route('trainer-session.destroy', $item->id) }}"
                                                onclick="return confirm('Delete Data ?')" method="POST">
                                                @method('delete')
                                                @csrf
                                                <button type="submit"
                                                    class="btn light btn-danger btn-xs mb-1 btn-block">Delete</button>
                                            </form>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
            <!--/column-->
        </div>
    </div>
</div>

@include('admin.trainer-session.check-in-2')

{{-- MODAL --}}
<div class="modal fade" id="exampleModal" tabindex="-1" aria-labelledby="exampleModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h1 class="modal-title fs-5" id="exampleModalLabel">Download Excel by Date</h1>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="row">
                    <div class="col-6">
                        <div class="mb-3">
                            <label class="form-label">From Date</label>
                            <input type="date" id="fromDate" class="form-control">
                        </div>
                    </div>
                    <div class="col-6">
                        <div class="mb-3">
                            <label class="form-label">To Date</label>
                            <input type="date" id="toDate" class="form-control">
                        </div>
                    </div>
                </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" onclick="reloadPage()" class="btn btn-primary">Download</button>
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<script>
    function reloadPage() {
        // alert("Berhasil");
        var fromDate = document.getElementById("fromDate").value;
        var toDate = document.getElementById("toDate").value;

        window.open(window.location.href + '?excel=1&fromDate=' + fromDate + '&toDate=' + toDate, '_self');
    }

    function updateTableWithFilteredData(data) {
        var tableBody = document.querySelector("#myTable tbody");

        tableBody.innerHTML = "";

        data.forEach(function(item) {
            var row = tableBody.insertRow();
            row.insertCell().textContent = item.id;
            row.insertCell().textContent = item.member_name;
            row.insertCell().textContent = item.member_code;
            row.insertCell().textContent = item.start_date;
            row.insertCell().textContent = item.status;
        });

        var exportButton = document.createElement("button");
        exportButton.addEventListener("click", function() {
            filterData();
        });
    }
</script>
