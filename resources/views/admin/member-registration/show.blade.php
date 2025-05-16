<!-- Tampilkan data dari tabel members hanya sekali -->
<div class="col-xl-12">
    <div class="card">
        <div class="card-body">
            <div class="teacher-deatails">
                <h3 class="heading">Member's Profile:</h3>
                <table class="table" border="2">
                    <tbody style="color: rgb(85, 85, 85);">
                        <tr>
                            <th><b>Full Name</b></th>
                            <th style="border-right: 2px solid rgb(212, 212, 212);">
                                : {{ $memberRegistration->members->full_name }}
                            </th>
                            @if ($status == 'sell')
                                <th><b>Nickname</th>
                                <th>: {{ $memberRegistration->members->nickname }}</th>
                            @endif
                        </tr>
                        @if ($status == 'sell')
                            <tr>
                                <th><b>Member Code</th>
                                <th style="border-right: 2px solid rgb(212, 212, 212);">
                                    : {{ $memberRegistration->members->member_code }}</th>
                                <th><b>Card Number</th>
                                <th>: {{ $memberRegistration->members->card_number }}</th>
                            </tr>
                        @endif
                        <tr>
                            @if ($status == 'sell')
                                <th><b>Date of Birth</th>
                                <th style="border-right: 2px solid rgb(212, 212, 212);">
                                    : {{ DateFormat($memberRegistration->members->born, 'DD MMMM YYYY') }}</th>
                            @endif
                            <th><b>Phone Number</th>
                            <th>: {{ $memberRegistration->members->phone_number }}</th>
                        </tr>
                        @if ($status == 'sell')
                            <tr>
                                <th><b>Gender</th>
                                <th style="border-right: 2px solid rgb(212, 212, 212);">
                                    : {{ $memberRegistration->members->gender }}</th>
                                <th><b>Address</th>
                                <th>: {{ $memberRegistration->members->address }}</th>
                            </tr>
                            <tr>
                                <th><b>Email</th>
                                <th style="text-transform: lowercase; border-right: 2px solid rgb(212, 212, 212);">:
                                    {{ $memberRegistration->members->email }}</th>
                                <th><b>Instragram</th>
                                <th>: {{ $memberRegistration->members->ig }}</th>
                            </tr>
                            <tr>
                                <th><b>Emergency Contact</th>
                                <th style="text-transform: lowercase; border-right: 2px solid rgb(212, 212, 212);">:
                                    {{ $memberRegistration->members->emergency_contact }}</th>
                                <th><b>Emergency Contact Name</th>
                                <th>: {{ $memberRegistration->members->ec_name }}</th>
                        @endif
                        </tr>
                        </thead>
                </table>
            </div>
        </div>
    </div>
</div>

<div class="col-xl-12">
    <div class="card">
        <div class="card-body">
            @foreach ($memberRegistrations as $memberRegistration)
                <div class="accordion accordion-flush" id="accordionFlushExample{{ $loop->iteration }}">
                    <div class="accordion-item">
                        <h2 class="accordion-header">
                            <button class="accordion-button collapsed bg-info text-white" type="button"
                                data-bs-toggle="collapse" data-bs-target="#flush-collapseOne{{ $loop->iteration }}"
                                aria-expanded="false" aria-controls="flush-collapseOne{{ $loop->iteration }}">
                                Active Package Info (Click Here)
                            </button>
                        </h2>
                        <div id="flush-collapseOne{{ $loop->iteration }}" class="accordion-collapse collapse"
                            data-bs-parent="#accordionFlushExample{{ $loop->iteration }}">
                            <div class="accordion-body">
                                <table class="table">
                                    <tbody style="color: rgb(85, 85, 85);">
                                        <tr>
                                            <th><b>Package Name</b></th>
                                            <th>{{ $memberRegistration->package_name }}</th>
                                        </tr>
                                        <tr>
                                            <th><b>Number Of Days</th>
                                            <th>{{ $memberRegistration->member_registration_days }} Days</th>
                                        </tr>
                                        <tr>
                                            <th><b>Package Price</b></th>
                                            <th>{{ formatRupiah($memberRegistration->mr_package_price) }}</th>
                                        </tr>
                                        <tr>
                                            <th><b>Admin Price</b></th>
                                            <th>{{ formatRupiah($memberRegistration->mr_admin_price) }}</th>
                                        </tr>
                                        <tr>
                                            <th><b>Start Date</th>
                                            <th>{{ DateFormat($memberRegistration->start_date, 'DD MMMM YYYY') }}</th>
                                        </tr>
                                        <tr>
                                            <th><b>Expired Date</th>
                                            <th>{{ DateFormat($memberRegistration->expired_date, 'DD MMMM YYYY') }}
                                            </th>
                                        </tr>
                                        <tr>
                                            <th><b>Method Payment</b></th>
                                            <th>{{ $memberRegistration->method_payment_name }}</th>
                                        </tr>
                                        <tr>
                                            <th><b>Description</b></th>
                                            <th>{{ $memberRegistration->description }}</th>
                                        </tr>
                                        @if ($status == 'sell')
                                            <tr>
                                                <th><b>Leave Days</b></th>
                                                @if ($memberRegistration->leave_day_status == 'Freeze')
                                                    {{-- <th>{{ $memberRegistration->total_leave_days }}</th> --}}
                                                    <th>{{ $memberRegistration->total_days }} Days</th>
                                                @else
                                                    <th>No Leave Days</th>
                                                @endif
                                            </tr>
                                        @endif
                                    </tbody>
                                </table>
                                <a href="{{ route('membership-agreement', $memberRegistration->id) }}"
                                    class="btn btn-primary btn-sm" target="_blank">Download
                                    Agrement</a>
                            </div>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    </div>
</div>

@if (isset($pendingMemberRegistrations))
    <div class="col-xl-12">
        <div class="card">
            <div class="card-body">
                @foreach ($pendingMemberRegistrations as $pendingMemberRegistration)
                    <div class="accordion accordion-flush" id="accordionFlushExample{{ $loop->iteration }}">
                        <div class="accordion-item">
                            <h2 class="accordion-header">
                                <button class="accordion-button collapsed bg-warning text-white" type="button"
                                    data-bs-toggle="collapse"
                                    data-bs-target="#pending-flush-collapseOne{{ $loop->iteration }}"
                                    aria-expanded="false"
                                    aria-controls="pending-flush-collapseOne{{ $loop->iteration }}">
                                    Pending Package Info (Click Here)
                                </button>
                            </h2>
                            <div id="pending-flush-collapseOne{{ $loop->iteration }}"
                                class="accordion-collapse collapse"
                                data-bs-parent="#accordionFlushExample{{ $loop->iteration }}">
                                <div class="accordion-body">
                                    <table class="table">
                                        <tbody style="color: rgb(85, 85, 85);">
                                            <tr>
                                                <th><b>Package Name</b></th>
                                                <th>{{ $pendingMemberRegistration->package_name }}</th>
                                            </tr>
                                            <tr>
                                                <th><b>Number Of Days</th>
                                                <th>{{ $pendingMemberRegistration->member_registration_days }} Days
                                                </th>
                                            </tr>
                                            <tr>
                                                <th><b>Package Price</b></th>
                                                <th>{{ formatRupiah($pendingMemberRegistration->mr_package_price) }}
                                                </th>
                                            </tr>
                                            <tr>
                                                <th><b>Admin Price</b></th>
                                                <th>{{ formatRupiah($pendingMemberRegistration->mr_admin_price) }}</th>
                                            </tr>
                                            <tr>
                                                <th><b>Start Date</th>
                                                <th>{{ DateFormat($pendingMemberRegistration->start_date, 'DD MMMM YYYY') }}
                                                </th>
                                            </tr>
                                            <tr>
                                                <th><b>Expired Date</th>
                                                <th>{{ DateFormat($pendingMemberRegistration->expired_date, 'DD MMMM YYYY') }}
                                                </th>
                                            </tr>
                                            <tr>
                                                <th><b>Method Payment</b></th>
                                                <th>{{ $pendingMemberRegistration->method_payment_name }}</th>
                                            </tr>
                                            <tr>
                                                <th><b>Description</b></th>
                                                <th>{{ $pendingMemberRegistration->description }}</th>
                                            </tr>
                                            @if ($status == 'sell')
                                                <tr>
                                                    <th><b>Leave Days</b></th>
                                                    @if ($pendingMemberRegistration->leave_day_status == 'Freeze')
                                                        {{-- <th>{{ $pendingMemberRegistration->total_leave_days }}</th> --}}
                                                        <th>{{ $pendingMemberRegistration->total_days }}</th>
                                                    @else
                                                        <th>No Leave Days</th>
                                                    @endif
                                                </tr>
                                            @endif
                                        </tbody>
                                    </table>
                                    <a href="{{ route('membership-agreement', $pendingMemberRegistration->id) }}"
                                        class="btn btn-primary btn-sm" target="_blank">Download
                                        Agrement</a>
                                </div>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    </div>
@endif


@if (isset($expiredMemberRegistrations))
    <div class="col-xl-12">
        <div class="card">
            <div class="card-body">

                <div class="accordion accordion-flush" id="accordionFlushExample">
                    <div class="accordion-item">
                        <h2 class="accordion-header">
                            <button class="accordion-button collapsed bg-danger text-white" type="button"
                                data-bs-toggle="collapse" data-bs-target="#expired-flush-collapseOne"
                                aria-expanded="false" aria-controls="expired-flush-collapseOne">
                                Expired Package Info (Click Here)
                            </button>
                        </h2>
                        <div id="expired-flush-collapseOne" class="accordion-collapse collapse"
                            data-bs-parent="#accordionFlushExample">
                            <div class="accordion-body">
                                @foreach ($expiredMemberRegistrations as $expiredMemberRegistrations)
                                    <table class="table">
                                        <thead>
                                            <tr>
                                                <th colspan="2" class="text-center">Expired Member Package
                                                    {{ $loop->iteration }}</th>
                                            </tr>
                                        </thead>
                                        <tbody style="color: rgb(85, 85, 85);">
                                            <tr>
                                                <th><b>Package Name</b></th>
                                                <th>{{ $expiredMemberRegistrations->package_name }}</th>
                                            </tr>
                                            <tr>
                                                <th><b>Number Of Days</th>
                                                <th>{{ $expiredMemberRegistrations->member_registration_days }} Days
                                                </th>
                                            </tr>
                                            <tr>
                                                <th><b>Package Price</b></th>
                                                <th>{{ formatRupiah($expiredMemberRegistrations->mr_package_price) }}
                                                </th>
                                            </tr>
                                            <tr>
                                                <th><b>Admin Price</b></th>
                                                <th>{{ formatRupiah($expiredMemberRegistrations->mr_admin_price) }}
                                                </th>
                                            </tr>
                                            <tr>
                                                <th><b>Start Date</th>
                                                <th>{{ DateFormat($expiredMemberRegistrations->start_date, 'DD MMMM YYYY') }}
                                                </th>
                                            </tr>
                                            <tr>
                                                <th><b>Expired Date</th>
                                                <th>{{ DateFormat($expiredMemberRegistrations->expired_date, 'DD MMMM YYYY') }}
                                                </th>
                                            </tr>
                                            <tr>
                                                <th><b>Method Payment</b></th>
                                                <th>{{ $expiredMemberRegistrations->method_payment_name }}</th>
                                            </tr>
                                            <tr>
                                                <th><b>Description</b></th>
                                                <th>{{ $expiredMemberRegistrations->description }}</th>
                                            </tr>
                                            {{-- @if ($status == 'sell')
                                            <tr>
                                                <th><b>Leave Days</b></th>
                                                @if ($expiredMemberRegistrations->leave_day_status == 'Freeze')
                                                    <th>{{ $expiredMemberRegistrations->total_leave_days }}</th>
                                                @else
                                                    <th>No Leave Days</th>
                                                @endif
                                            </tr>
                                        @endif --}}
                                        </tbody>
                                    </table>
                                    <a href="{{ route('membership-agreement', $expiredMemberRegistrations->id) }}"
                                        class="btn btn-primary btn-sm" target="_blank">Download
                                        Agrement</a>
                                    <hr />
                                    <hr />
                                @endforeach
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endif

<div class="col-xl-12">
    <div class="card">
        <div class="card-body">
            <div class="accordion accordion-flush" id="accordionFlushExample">
                <div class="accordion-item">
                    <h2 class="accordion-header">
                        <button class="accordion-button collapsed bg-primary text-white" type="button"
                            data-bs-toggle="collapse" data-bs-target="#flush-collapseOne" aria-expanded="false"
                            aria-controls="flush-collapseOne">
                            Check In & Checkout Time (Click Here)
                        </button>
                    </h2>
                    <div id="flush-collapseOne" class="accordion-collapse collapse"
                        data-bs-parent="#accordionFlushExample">
                        <div class="accordion-body">
                            <table class="table">
                                <thead>
                                    <tr>
                                        <th>No</th>
                                        <th>Check In Time</th>
                                        <th>Check Out Time</th>
                                        <th>Duration</th>
                                        <th>Action</th>
                                    </tr>
                                </thead>
                                @foreach ($memberRegistrationCheckIn as $item)
                                    <tbody style="color: rgb(85, 85, 85);">
                                        <tr>
                                            <td>{{ $loop->iteration }}</td>
                                            <td>{{ DateFormat($item->check_in_time, 'DD MMMM YYYY, HH:mm') }}</td>
                                            <td>{{ $item->check_out_time ? DateFormat($item->check_out_time, 'DD MMMM YYYY, HH:mm') : 'Not Yet' }}
                                            </td>
                                            @php
                                                $checkInTime = \Carbon\Carbon::parse($item->check_in_time);
                                                $checkOutTime = \Carbon\Carbon::parse($item->check_out_time);

                                                $totalDuration = $checkInTime->diffInSeconds($checkOutTime);
                                                $hours = floor($totalDuration / 3600);
                                                $minutes = floor(($totalDuration % 3600) / 60);
                                                $seconds = $totalDuration % 60;

                                                $formattedDuration = sprintf(
                                                    '%02d:%02d:%02d',
                                                    $hours,
                                                    $minutes,
                                                    $seconds,
                                                );
                                            @endphp
                                            <td>{{ $item->check_out_time ? $formattedDuration : 'Not Yet' }}</td>
                                            <td>
                                                @if (Auth::user()->role == 'ADMIN')
                                                    <form action="{{ route('member-check-in.destroy', $item->id) }}"
                                                        onclick="return confirm('Delete Data ?')" method="POST">
                                                        @method('delete')
                                                        @csrf
                                                        <button type="submit"
                                                            class="btn light btn-danger btn-xs mb-1 btn-block">Delete</button>
                                                    </form>
                                                @endif
                                            </td>
                                        </tr>
                                    </tbody>
                                @endforeach
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="col-xl-12">
    <div class="card">
        <div class="card-body">
            @if ($memberRegistration->status == 'Running' || $memberRegistration->member_status == 'sell')
                <a href="{{ route('member-active.index') }}" class="btn btn-primary">Back</a>
            @elseif ($memberRegistration->member_status == 'one_day_visit')
                <a href="{{ route('oneDayVisit') }}" class="btn btn-primary">Back</a>
            @else
                <a href="{{ route('member-expired.index') }}" class="btn btn-primary">Back</a>
            @endif
        </div>
    </div>
</div>
