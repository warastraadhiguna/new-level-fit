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

    .move-branch-package-list {
        border: 1px solid #ddd;
        border-radius: 8px;
        max-height: 240px;
        overflow-y: auto;
    }

    .move-branch-package-option {
        border-left: 0;
        border-right: 0;
        color: #2f2a70;
        cursor: pointer;
        text-align: left;
        white-space: normal;
    }

    .move-branch-package-option:first-child {
        border-top: 0;
    }

    .move-branch-package-option:last-child {
        border-bottom: 0;
    }

    .move-branch-package-option.active {
        background-color: #4c3fb3;
        border-color: #4c3fb3;
        color: #fff;
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
                    @if (empty($isUnpaidPage))
                        <button type="button" class="btn btn-info" data-bs-toggle="modal" data-bs-target="#exampleModal">
                            Download Excel
                        </button>
                    @endif
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
            <hr/>
            @foreach ($paymentMessages as $key => $messages)
                @if (!empty($messages))
                    @foreach ($messages as $message)
                        <div class="alert alert-primary solid alert-dismissible fade show bg-danger">
                            <a href="/trainer-session/{{ $message["id"] }}/edit" class="birthdayy" target="_blank">
                                <svg viewBox="0 0 24 24" width="24" height="24" stroke-width="2" fill="none"
                                    stroke-linecap="round" stroke-linejoin="round" class="me-2">
                                    <circle cx="12" cy="12" r="10"></circle>
                                    <line x1="12" y1="16" x2="12" y2="12"></line>
                                    <line x1="12" y1="8" x2="12.01" y2="8"></line>
                                </svg>
                                <span>{!! "$key days to <strong>". $message['message'] ."</strong>'s payment expired date" !!}</span>
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
                                {{-- <th>Image</th> --}}
                                <th>Member Data</th>
                                <th>Last Check In</th>
                                <th>Date</th>
                                <th>Session</th>
                                <th>Payment</th>                                     
                                <th>Status</th>
                                <th>Trainer</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($trainerSessions as $item)
                                <tr>
                                    <td>{{ $loop->iteration }}</td>
                                    {{-- <td>
                                        <div class="trans-list">
                                            @if ($item->photos)
                                                <img src="{{ Storage::url($item->photos) }}" class="lazyload"
                                                    width="100" alt="image">
                                            @else
                                                <img src="{{ asset('default.png') }}" class="lazyload" width="100"
                                                    alt="default image">
                                            @endif
                                        </div>
                                    </td> --}}
                                    <td>
                                        <h6>{{ $item->member_name }},</h6>
                                        <h6>{{ $item->member_code }}</h6>
                                        <br/>
                                        <h6>{{ $item->branch_store_name }}</h6>                                           
                                    </td>
                                    <td>
                                        @php
                                            $daysLeft = $item->expired_date ? Carbon\Carbon::parse($item->expired_date)->diffInDays(
                                                Carbon\Carbon::now(),
                                            ) : null;
                                            $sumDaysLeft = $daysLeft !== null ? $daysLeft + 1 : null;
                                        @endphp
                                        @if ($sumDaysLeft !== null && $sumDaysLeft > 3 && $sumDaysLeft < 6)
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
                                        @elseif($daysLeft !== null && $daysLeft <= 3)
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
                                        <h6>{{ $item->start_date ? DateFormat($item->start_date, 'DD MMMM YYYY') : '-' }}- <br />
                                            {{ $item->expired_date ? DateFormat($item->expired_date, 'DD MMMM YYYY') : '-' }}
                                        </h6>
                                    </td>
                                    <td>
                                        <h6>Session Total : {{ $item->ts_number_of_session }}</h6>
                                        <h6>Remaining Session : {{ $item->remaining_sessions }}</h6>
                                    </td>
                                    <td>
                                            @if ($item->payment_summary >= ($item->ts_package_price+$item->ts_admin_price))
                                                <span class="badge badge-primary badge-lg">Paid</span>
                                            @else
                                                <span class="badge badge-danger badge-lg">{{ formatRupiah($item->ts_package_price+$item->ts_admin_price - $item->payment_summary) }}</span>                                            
                                            @endif
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
                                        @if (!empty($isUnpaidPage))
                                            <a class="btn light btn-danger btn-xs mb-1 btn-block">Unpaid</a>
                                        @elseif ($idCodeMaxCount - $item->id_code_count == 0)
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
                                            @if (!empty($canMoveTrainerSessionBranch))
                                                <button type="button" class="btn light btn-primary btn-xs mb-1 btn-block"
                                                    data-bs-toggle="modal"
                                                    data-bs-target="#modalMoveTrainerSessionBranch{{ $item->id }}">
                                                    Move Branch
                                                </button>
                                            @endif
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

@if (Auth::user()->role == 'ADMIN' && !empty($canMoveTrainerSessionBranch))
    @foreach ($trainerSessions as $item)
        <div class="modal fade move-trainer-session-branch-modal" id="modalMoveTrainerSessionBranch{{ $item->id }}" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog modal-lg modal-dialog-centered">
                <div class="modal-content">
                    <form action="{{ route('trainer-session.move-branch', $item->id) }}" method="POST"
                        onsubmit="return validateMoveBranchForm(this)">
                        @csrf
                        @method('PUT')
                        <div class="modal-header">
                            <h1 class="modal-title fs-5">Move PT Registration Branch</h1>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <div class="modal-body">
                            <div class="alert alert-warning">
                                Branch will follow the selected PT package. Current trainer, price, duration, and sessions will be kept.
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Member</label>
                                <input type="text" class="form-control"
                                    value="{{ $item->member_name }} | {{ $item->member_code }}" readonly>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Current Branch</label>
                                <input type="text" class="form-control" value="{{ $item->branch_store_name }}" readonly>
                            </div>
                            <div class="mb-3 move-branch-package-field">
                                <label class="form-label">Target PT Package</label>
                                @php
                                    $targetTrainerPackages = $trainerPackagesForMove->filter(function ($trainerPackage) use ($item) {
                                        return (int) $trainerPackage->branch_store_id !== (int) $item->branch_store_id;
                                    });
                                @endphp
                                @if ($targetTrainerPackages->isEmpty())
                                    <div class="alert alert-info mb-0">
                                        No PT package is available from another branch.
                                    </div>
                                @else
                                <input type="hidden" name="trainer_package_id" class="move-branch-package-id">
                                <input type="text" class="form-control move-branch-package-search"
                                    placeholder="Search PT package by branch or package name" autocomplete="off">
                                <div class="form-text move-branch-package-selected">No package selected.</div>
                                <div class="list-group mt-2 move-branch-package-list">
                                    @foreach ($targetTrainerPackages as $trainerPackage)
                                        @php
                                            $trainerPackageLabel = trim(implode(' | ', [
                                                data_get($trainerPackage, 'branchStore.name', 'No branch'),
                                                $trainerPackage->package_name,
                                                $trainerPackage->number_of_session . ' Sessions',
                                                $trainerPackage->days . ' Days',
                                                formatRupiah($trainerPackage->package_price),
                                                'Admin ' . formatRupiah($trainerPackage->admin_price),
                                            ]));
                                        @endphp
                                        <button type="button" class="list-group-item list-group-item-action move-branch-package-option"
                                            data-package-id="{{ $trainerPackage->id }}"
                                            data-package-label="{{ $trainerPackageLabel }}"
                                            data-search="{{ strtolower($trainerPackageLabel) }}">
                                            {{ $trainerPackageLabel }}
                                        </button>
                                    @endforeach
                                </div>
                                <div class="text-muted mt-2 move-branch-package-empty d-none">No package found.</div>
                                @endif
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="submit" class="btn btn-primary" {{ $targetTrainerPackages->isEmpty() ? 'disabled' : '' }}>Move Branch</button>
                            <button type="button" class="btn btn-danger light" data-bs-dismiss="modal">Close</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    @endforeach
@endif

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

    function validateMoveBranchForm(form) {
        var packageInput = form.querySelector('.move-branch-package-id');

        if (packageInput && !packageInput.value) {
            alert('Please choose a target PT package first.');
            return false;
        }

        return confirm('Move this PT registration to the selected package branch?');
    }

    document.querySelectorAll('.move-trainer-session-branch-modal').forEach(function(modalElement) {
        var searchInput = modalElement.querySelector('.move-branch-package-search');
        var packageInput = modalElement.querySelector('.move-branch-package-id');
        var selectedText = modalElement.querySelector('.move-branch-package-selected');
        var emptyText = modalElement.querySelector('.move-branch-package-empty');
        var packageOptions = modalElement.querySelectorAll('.move-branch-package-option');

        if (!searchInput || !packageInput) {
            return;
        }

        searchInput.addEventListener('input', function() {
            var keyword = searchInput.value.trim().toLowerCase();
            var visibleCount = 0;

            packageOptions.forEach(function(option) {
                var isMatch = !keyword || option.dataset.search.indexOf(keyword) !== -1;
                option.classList.toggle('d-none', !isMatch);

                if (isMatch) {
                    visibleCount += 1;
                }
            });

            if (emptyText) {
                emptyText.classList.toggle('d-none', visibleCount > 0);
            }
        });

        packageOptions.forEach(function(option) {
            option.addEventListener('click', function() {
                packageOptions.forEach(function(otherOption) {
                    otherOption.classList.remove('active');
                });

                option.classList.add('active');
                packageInput.value = option.dataset.packageId;

                if (selectedText) {
                    selectedText.textContent = 'Selected: ' + option.dataset.packageLabel;
                }
            });
        });
    });

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
