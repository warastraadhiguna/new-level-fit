<div class="col-xl-12">
    <div class="card">
        <div class="card-body">
            <div class="teacher-deatails">
                <h3 class="heading">Member's Profile:</h3>
                <table class="table" border="2">
                    <tbody style="color: rgb(85, 85, 85);">
                        <tr>
                            <th scope="col">
                                <b>Full Name</b>
                            </th>
                            <th style="border-right: 2px solid rgb(212, 212, 212);">
                                : {{ $query->first()->member_name }}
                            </th>
                            <th scope="col">
                                <b>Nick Name</b>
                            </th>
                            <th style="border-right: 2px solid rgb(212, 212, 212);">
                                : {{ $query->first()->nickname }}
                            </th>
                        </tr>
                        <tr>
                            <th scope="col">
                                <b>Member Number</b>
                            </th>
                            <th style="border-right: 2px solid rgb(212, 212, 212);">
                                : {{ $query->first()->member_code }}
                            </th>
                            <th scope="col">
                                <b>Card Number</b>
                            </th>
                            <th style="border-right: 2px solid rgb(212, 212, 212);">
                                : {{ $query->first()->card_number }}
                            </th>
                        </tr>
                        <tr>
                            <th scope="col">
                                <b>Date of birth</b>
                            </th>
                            <th style="border-right: 2px solid rgb(212, 212, 212);">
                                : {{ DateFormat($query->first()->born, 'DD MMMM YYYY') }}
                            </th>
                            <th scope="col">
                                <b>Phone Number</b>
                            </th>
                            <th style="border-right: 2px solid rgb(212, 212, 212);">
                                : {{ $query->first()->member_phone }}
                            </th>
                        </tr>
                        <tr>
                            <th scope="col">
                                <b>Gender</b>
                            </th>
                            <th style="border-right: 2px solid rgb(212, 212, 212);">
                                : {{ $query->first()->gender }}
                            </th>
                            <th scope="col">
                                <b>Address</b>
                            </th>
                            <th style="border-right: 2px solid rgb(212, 212, 212);">
                                : {{ $query->first()->address }}
                            </th>
                        </tr>
                        <tr>
                            <th><b>Email</th>
                            <th style="text-transform: lowercase; border-right: 2px solid rgb(212, 212, 212);">:
                                {{ $query->first()->email }}</th>
                            <th><b>Instragram</th>
                            <th>: {{ $query->first()->ig }}</th>
                        </tr>
                        <tr>
                            <th><b>Emergency Contact</th>
                            <th style="text-transform: lowercase; border-right: 2px solid rgb(212, 212, 212);">:
                                {{ $query->first()->emergency_contact }}</th>
                            <th><b>Emergency Contact Name</th>
                            <th>: {{ $query->first()->ec_name }}</th>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

{{-- @foreach ($query as $query)
    <div class="col-xl-12">
        <div class="card">
            <div class="card-body">
                <div class="teacher-deatails">
                    <h3 class="heading">Package Info: {{ $loop->iteration }}</h3>
                    <table class="table">
                        <tbody style="color: rgb(85, 85, 85);">
                            <tr>
                                <th><b>Package Name</b></th>
                                <th>{{ $query->package_name }}</th>
                            </tr>
                            <tr>
                                <th><b>Number Of Days</th>
                                <th>{{ $query->ts_number_of_days }} Days</th>
                            </tr>
                            <tr>
                                <th><b>Package Price</b></th>
                                <th>{{ formatRupiah($query->ts_package_price) }}</th>
                            </tr>
                            <tr>
                                <th><b>Start Date</th>
                                <th>{{ DateFormat($query->start_date, 'DD MMMM YYYY') }}</th>
                            </tr>
                            <tr>
                                <th><b>Expired Date</th>
                                <th>{{ DateFormat($query->expired_date, 'DD MMMM YYYY') }}</th>
                            </tr>
                            <tr>
                                <th><b>Method Payment</b></th>
                                <th>{{ $query->method_payment_name }}</th>
                            </tr>
                            <tr>
                                <th><b>Description</b></th>
                                <th>{{ $query->description }}</th>
                            </tr>
                        </tbody>
                    </table>
                </div>
                <a href="{{ route('pt-agreement', $query->id) }}" class="btn btn-primary btn-sm"
                    target="_blank">Download
                    Agrement {{ $loop->iteration }}</a>
            </div>
        </div>
    </div>
@endforeach --}}

@if ($remainingSessions == 0)
    <div class="col-xl-12">
        <div class="card">
            <div class="card-body">
                <a href="{{ route('trainer-session-over.index') }}" class="btn btn-primary">Back</a>
            </div>
        </div>
    </div>
@else
    <div class="col-xl-12">
        <div class="card">
            <div class="card-body">
                <a href="{{ route('trainer-session.index') }}" class="btn btn-primary">Back</a>
            </div>
        </div>
    </div>
@endif
