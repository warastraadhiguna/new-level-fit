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
                                                    <th>{{ $memberRegistration->total_leave_days }}</th>
                                                @else
                                                    <th>No Leave Days</th>
                                                @endif
                                            </tr>
                                        @endif
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    </div>
</div>

<div class="col-xl-12">
    <div class="card">
        <div class="card-body">
            <a href="{{ route('oneDayVisit') }}" class="btn btn-primary">Back</a>
        </div>
    </div>
</div>
