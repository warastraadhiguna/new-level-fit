<div class="row">
    <div class="col-xl-12">
        <div class="col-xl-12">
            <div class="page-title flex-wrap justify-content-start">
                <div class="d-flex flex-nowrap align-items-center">
                    <input type="date" id="fromDate" class="form-control" value="{{ $fromDate }}">
                    <span class="mx-1">to</span>
                    <input type="date" id="toDate" class="form-control" value="{{ $toDate }}">
                </div>
                <button type="button" onclick="reloadPage()" class="btn btn-info mx-1" data-bs-toggle="modal">
                    Filter
                </button>
                {{-- <button type="button" onclick="reloadPage(1)" class="btn btn-outline-info" data-bs-toggle="modal">
                    PDF
                </button> --}}
            </div>
        </div>
        <div class="row">
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
                                                    style="width: 100px; height: 100px; object-fit: cover;"
                                                    alt="image">
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
                                        |
                                        @if ($item->status == 'Over')
                                            <span class="badge badge-danger d-inline-block" tabindex="0">
                                                Over
                                            </span>
                                        @elseif ($item->status == 'Running')
                                            <span class="badge badge-primary d-inline-block" tabindex="0">
                                                Running
                                            </span>
                                        @else
                                            <span class="badge badge-warning d-inline-block" tabindex="0">
                                                Pending
                                            </span>
                                        @endif
                                    </td>
                                    <td>
                                        <h6>{{ $item->staff_name }}</h6>
                                    </td>
                                    <td>
                                        <a href="{{ route('detail-history-member-registration', $item->id) }}" class="btn light btn-info btn-xs btn-block mb-1">Detail
                                            Member</a>
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

<script>
    function reloadPage() {
        var fromDate = document.getElementById("fromDate").value;
        var toDate = document.getElementById("toDate").value;
        window.open(window.location.pathname + '?fromDate=' + fromDate + '&toDate=' + toDate +
            "&date=" + new Date().toISOString(), '_self');
    }
</script>