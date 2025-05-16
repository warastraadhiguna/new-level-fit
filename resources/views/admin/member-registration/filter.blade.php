<div class="row">
    <div class="col-xl-12">
        <div class="row">
            <div class="col-xl-12">
                <div class="page-title flex-wrap justify-content-between">
                    <a href="{{ route('member-active.index') }}" class="btn btn-primary">Refresh</a>
                    <form action="{{ route('member-active-filter') }}" class="text-right" method="GET">
                        @csrf
                        <div class="d-flex align-items-center">
                            <div class="col-md-4 d-flex align-items-center">
                                <input type="date" class="form-control input-sm" name="fromDate" id="fromDate"
                                    required>
                            </div>
                            <div class="mt-3 mx-2">
                                <p>to</p>
                            </div>
                            <div class="col-md-4 d-flex align-items-center">
                                <input type="date" class="form-control input-sm" name="toDate" id="toDate"
                                    required>
                            </div>
                            <div>
                                <button type="submit" name="search" class="btn btn-primary mx-2">Filter</button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
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
                                        @endphp
                                        @if ($daysLeft <= 5 && $daysLeft == 3)
                                            <span class="badge badge-warning badge-lg">
                                                @if (!$item->check_in_time && !$item->check_out_time)
                                                    Not Yet
                                                @elseif ($item->check_in_time && $item->check_out_time)
                                                    {{ DateDiff($item->check_out_time, \Carbon\Carbon::now(), true) }}
                                                    day ago
                                                @elseif ($item->check_in_time && !$item->check_out_time)
                                                    Running
                                                @endif
                                            </span>
                                        @elseif($daysLeft <= 2)
                                            <span class="badge badge-danger badge-lg">
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
                                            <span class="badge badge-secondary badge-lg">Freeze</span>
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
                                    <td>
                                        @php
                                            $now = \Carbon\Carbon::now()->tz('asia/jakarta');
                                        @endphp
                                        @if ($item->leave_day_status == 'Freeze')
                                            <a class="btn light btn-info btn-xs mb-1 btn-block">Freeze</a>
                                        @else
                                            @if ($now > $item->expired_leave_days)
                                                @if ($item->start_date < $now)
                                                    @if ((!$item->check_in_time && !$item->check_out_time) || ($item->check_in_time && $item->check_out_time))
                                                        <a href="{{ route('secondCheckIn', $item->id) }}"
                                                            class="btn light btn-info btn-xs mb-1 btn-block">Check
                                                            In</a>
                                                    @elseif ($item->check_in_time && !$item->check_out_time)
                                                        <a href="{{ route('secondCheckIn', $item->id) }}"
                                                            class="btn light btn-info btn-xs mb-1 btn-block">Check
                                                            Out</a>
                                                    @endif
                                                @endif
                                            @endif
                                        @endif
                                        @if (Auth::user()->role == 'ADMIN')
                                            <a href="{{ route('member-active.edit', $item->id) }}"
                                                class="btn light btn-warning btn-xs mb-1 btn-block">Edit</a>
                                        @endif
                                        <div class="btn-group" style="width: 100%;">
                                            <button type="button"
                                                class="btn light btn-secondary btn-xs mb-1 btn-block">Download</button>
                                            <button type="button"
                                                class="btn light btn-secondary btn-xs mb-1 dropdown-toggle dropdown-toggle-split"
                                                data-bs-toggle="dropdown" aria-expanded="false">
                                                <span class="visually-hidden">Toggle Dropdown</span>
                                            </button>
                                            <ul class="dropdown-menu">
                                                <li><a href="{{ route('membership-agreement', $item->id) }}"
                                                        target="_blank" class="dropdown-item">Agrement</a>
                                                </li>
                                            </ul>
                                        </div>
                                        <a href="{{ route('member-active.show', $item->id) }}"
                                            class="btn light btn-info btn-xs mb-1 btn-block">Detail</a>
                                        <button type="button" class="btn light btn-light btn-xs mb-1 btn-block"
                                            data-bs-toggle="modal" data-bs-target=".freeze{{ $item->id }}"
                                            id="checkInButton">Freeze</button>
                                        <a href="{{ route('renewal', $item->id) }}"
                                            class="btn light btn-dark btn-xs mb-1 btn-block">Renewal</a>
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
