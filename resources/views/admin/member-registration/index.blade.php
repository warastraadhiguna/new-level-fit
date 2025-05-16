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
            <div id="filteredDataContainer"></div>
            <div class="col-xl-12">
                <div class="page-title flex-wrap justify-content-between">
                    <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#checkIn2"
                        id="checkInButton">Input Card Number</button>
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
                                aria-label="Close">X</button>
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
                                <th>Status</th>
                                <th>Staff</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($memberRegistrations as $item)
                                <tr>
                                    <td>{{ $loop->iteration }}</td>
                                    <td>
                                        <div class="trans-list">
                                            @if ($item->photos)
                                                <img src="{{ Storage::url($item->photos) }}" class="lazyload"
                                                    style="width: 100px; height: 100px; object-fit: cover;" alt="image">
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
                                                {{-- <button class="btn btn-warning btn-sm" type="button" disabled> --}}
                                                @if (!$item->check_in_time && !$item->check_out_time)
                                                    Not Yet
                                                @elseif ($item->check_in_time && $item->check_out_time)
                                                    {{ DateDiff($item->check_out_time, \Carbon\Carbon::now(), true) }}
                                                    day ago
                                                @elseif ($item->check_in_time && !$item->check_out_time)
                                                    Running
                                                @endif
                                                {{-- </button> --}}
                                            </span>
                                        @elseif($sumDaysLeft <= 3)
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
                                        <h6>{{ DateFormat($item->start_date, 'DD MMMM YYYY') }}-{{ DateFormat($item->expired_date, 'DD MMMM YYYY') }}
                                        </h6>
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
                                                <span class="badge badge-primary badge-lg">Running</span>
                                            @endif
                                        @endif
                                    <td>
                                        <h6>{{ $item->staff_name }}</h6>
                                    </td>
                                    <td style="width: 30px">
                                        @php
                                            $now = \Carbon\Carbon::now()->tz('asia/jakarta');
                                        @endphp
                                        {{-- @if ($idCodeMaxCount - $item->id_code_count == 0)
                                            @if (Auth::user()->role == 'ADMIN')
                                                <a href="{{ route('resetCheckIn', $item->member_id) }}"
                                                    class="btn light btn-warning btn-xs mb-1 btn-block">Reset Check In
                                                    ?</a>
                                            @else
                                                <button type="button"
                                                    class="btn light btn-warning btn-xs mb-1 btn-block"
                                                    data-bs-toggle="popover" data-bs-title="Check In tanpa kartu"
                                                    data-bs-content="Member ini sudah check in tanpa kartu sebanyak 3 kali, untuk reset check in tanpa kartu silahkan hubungi admin">Klik
                                                    Disini</button>
                                            @endif
                                        @else
                                            @if ($item->leave_day_status == 'Freeze')
                                                <a class="btn light btn-info btn-xs mb-1 btn-block">Freeze</a>
                                            @else
                                                @if ($now > $item->expired_leave_days)
                                                    @if ($item->start_date < $now)
                                                        @if ((!$item->check_in_time && !$item->check_out_time) || ($item->check_in_time && $item->check_out_time))
                                                            <a href="{{ route('secondCheckIn', $item->id) }}"
                                                                class="btn light btn-info btn-xs mb-1 btn-block">Check
                                                                In ({{ $idCodeMaxCount - $item->id_code_count }})</a>
                                                        @elseif ($item->check_in_time && !$item->check_out_time)
                                                            <a href="{{ route('secondCheckIn', $item->id) }}"
                                                                class="btn light btn-info btn-xs mb-1 btn-block">Check
                                                                Out ({{ $idCodeMaxCount - $item->id_code_count }})</a>
                                                        @endif
                                                    @endif
                                                @endif
                                            @endif
                                        @endif --}}
                                        {{-- <a href="{{ route('member-active.show', $item->id) }}"
                                            class="btn light btn-info btn-xs mb-1 btn-block">Detail</a> --}}
                                        {{-- @if ($item->leave_day_status == 'Freeze')
                                            <a href="{{ route('cuti', $item->id) }}" target="_blank"
                                                class="btn light btn-secondary btn-xs mb-1 btn-block">Agreement Cuti</a>

                                            <form action="{{ route('stopLeaveDays') }}" method="POST">
                                                @method('put')
                                                @csrf
                                                <input type="hidden" name="member_registration_id"
                                                    value="{{ $item->id }}">
                                                <input type="hidden" name="total_days"
                                                    value="{{ $item->total_days }}">
                                                <button type="submit"
                                                    class="btn light btn-outline-secondary btn-xs btn-block mb-1">Hentikan
                                                    Cuti</button>
                                            </form>
                                        @endif --}}
                                        {{-- Dropdown --}}
                                        <div class="btn-group dropstart" role="group">
                                            <button type="button" class="btn btn-primary btn-xs dropdown-toggle"
                                                style="width: 100px" data-bs-toggle="dropdown" aria-expanded="false">
                                                Aksi
                                            </button>
                                            <ul class="dropdown-menu">
                                                <li>
                                                    @if ($idCodeMaxCount - $item->id_code_count == 0)
                                                        @if (Auth::user()->role == 'ADMIN')
                                                            <a href="{{ route('resetCheckIn', $item->member_id) }}"
                                                                class="btn light btn-warning btn-xs mb-1 btn-block">Reset
                                                                Check In
                                                                ?</a>
                                                        @else
                                                            <button type="button"
                                                                class="btn light btn-warning btn-xs mb-1 btn-block"
                                                                data-bs-toggle="popover"
                                                                data-bs-title="Check In tanpa kartu"
                                                                data-bs-content="Member ini sudah check in tanpa kartu sebanyak 3 kali, untuk reset check in tanpa kartu silahkan hubungi admin">Klik
                                                                Disini</button>
                                                        @endif
                                                    @else
                                                        @if ($item->leave_day_status == 'Freeze')
                                                            <a
                                                                class="btn light btn-info btn-xs mb-1 btn-block">Freeze</a>
                                                        @else
                                                            @if ($now > $item->expired_leave_days)
                                                                @if ($item->start_date < $now)
                                                                    @if ((!$item->check_in_time && !$item->check_out_time) || ($item->check_in_time && $item->check_out_time))
                                                                        <a href="{{ route('secondCheckIn', $item->id) }}"
                                                                            class="btn light btn-info btn-xs mb-1 btn-block">Check
                                                                            In
                                                                            ({{ $idCodeMaxCount - $item->id_code_count }})</a>
                                                                    @elseif ($item->check_in_time && !$item->check_out_time)
                                                                        <a href="{{ route('secondCheckIn', $item->id) }}"
                                                                            class="btn light btn-info btn-xs mb-1 btn-block">Check
                                                                            Out
                                                                            ({{ $idCodeMaxCount - $item->id_code_count }})</a>
                                                                    @endif
                                                                @endif
                                                            @endif
                                                        @endif
                                                    @endif
                                                </li>
                                                <li>
                                                    @if (Auth::user()->role == 'ADMIN')
                                                        <a href="{{ route('member-active.edit', $item->id) }}"
                                                            class="btn light btn-warning btn-xs mb-1 btn-block">Edit</a>
                                                    @endif
                                                </li>
                                                <li>
                                                    <a href="{{ route('member-active.show', $item->id) }}"
                                                        class="btn light btn-info btn-xs mb-1 btn-block">Detail</a>
                                                </li>
                                                <li>
                                                    @if ($item->leave_day_status == 'Freeze')
                                                        <a href="{{ route('cuti', $item->id) }}" target="_blank"
                                                            class="btn light btn-secondary btn-xs mb-1 btn-block">Agreement
                                                            Cuti</a>

                                                        <form action="{{ route('stopLeaveDays') }}" method="POST">
                                                            @method('put')
                                                            @csrf
                                                            <input type="hidden" name="member_registration_id"
                                                                value="{{ $item->id }}">
                                                            <input type="hidden" name="total_days"
                                                                value="{{ $item->total_days }}">
                                                            <button type="submit"
                                                                class="btn light btn-outline-secondary btn-xs btn-block mb-1">Hentikan
                                                                Cuti</button>
                                                        </form>
                                                    @endif
                                                </li>
                                                <li>
                                                    <button type="button"
                                                        class="btn light btn-light btn-xs mb-1 btn-block"
                                                        data-bs-toggle="modal"
                                                        data-bs-target=".freeze{{ $item->id }}"
                                                        id="checkInButton">Freeze</button>
                                                </li>
                                                <li>
                                                    <a href="{{ route('renewal', $item->id) }}"
                                                        class="btn light btn-dark btn-xs mb-1 btn-block">Renewal</a>
                                                </li>
                                                <li>
                                                    @if (Auth::user()->role == 'ADMIN')
                                                        <form action="{{ route('member-active.destroy', $item->id) }}"
                                                            method="POST">
                                                            @method('delete')
                                                            @csrf
                                                            <button type="submit"
                                                                class="btn light btn-danger btn-xs btn-block mb-1"
                                                                onclick="return confirm('Delete {{ $item->member_name }} member package ?')">Delete</button>
                                                        </form>
                                                    @endif
                                                </li>
                                            </ul>
                                        </div>
                                        {{-- <button type="button" class="btn light btn-light btn-xs mb-1 btn-block"
                                            data-bs-toggle="modal" data-bs-target=".freeze{{ $item->id }}"
                                            id="checkInButton">Freeze</button> --}}
                                        {{-- <a href="{{ route('renewal', $item->id) }}"
                                            class="btn light btn-dark btn-xs mb-1 btn-block">Renewal</a> --}}
                                        {{-- @if (Auth::user()->role == 'ADMIN')
                                            <form action="{{ route('member-active.destroy', $item->id) }}"
                                                method="POST">
                                                @method('delete')
                                                @csrf
                                                <button type="submit"
                                                    class="btn light btn-danger btn-xs btn-block mb-1"
                                                    onclick="return confirm('Delete {{ $item->member_name }} member package ?')">Delete</button>
                                            </form>
                                        @endif --}}
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


@include('admin.member-registration.check-in')
@include('admin.member-registration.check-in-2')

<script>
    function reloadPage() {
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
