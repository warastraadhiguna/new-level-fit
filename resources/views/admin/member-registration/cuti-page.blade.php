<!-- Tampilkan data dari tabel members hanya sekali -->
<div class="col-xl-12">
    <div class="card">
        <div class="card-body">
            <div class="teacher-deatails">
                <h3 class="heading">Cuti Date:</h3>
                <table class="table" border="2">
                    <tbody style="color: rgb(85, 85, 85);">
                        <tr>
                            <th><b>Full Name</b></th>
                            <th style="border-right: 2px solid rgb(212, 212, 212);">
                                : {{ $memberRegistration->first()->member_name }}
                            </th>
                            <th><b>Nickname</th>
                            <th>: {{ $memberRegistration->first()->nickname }}</th>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

{{-- @foreach ($memberRegistration as $memberRegistration)
    <div class="col-xl-12">
        <div class="card">
            <div class="card-body">
                <div class="teacher-deatails">
                    <h3 class="heading">Package Info: {{ $loop->iteration }}</h3>
                    <table class="table">
                        <tbody style="color: rgb(85, 85, 85);">
                            <tr>
                                <th><b>Package Name</b></th>
                                <th>{{ $memberRegistration->package_name }}</th>
                                {{-- {{ $memberRegistration->total_leave_days }} --}}
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
    <th>{{ DateFormat($memberRegistration->expired_date, 'DD MMMM YYYY') }}</th>
</tr>
<tr>
    <th><b>Method Payment</b></th>
    <th>{{ $memberRegistration->method_payment_name }}</th>
</tr>
<tr>
    <th><b>Description</b></th>
    <th>{{ $memberRegistration->description }}</th>
</tr>
<tr>
    <th><b>Leave Days</b></th>
    @if ($memberRegistration->leave_day_status == 'Freeze')
        <th>{{ $memberRegistration->total_leave_days }}</th>
    @else
        <th>No Leave Days</th>
    @endif
</tr>
</thead>
</table>
</div>
<a href="{{ route('membership-agreement', $memberRegistration->id) }}" class="btn btn-primary btn-sm"
    target="_blank">Download
    Agrement</a>
</div>
</div>
</div>
@endforeach --}}

@if ($memberRegistration->status == 'Running')
    <div class="col-xl-12">
        <div class="card">
            <div class="card-body">
                <a href="{{ route('member-active.index') }}" class="btn btn-primary">Back</a>
            </div>
        </div>
    </div>
@else
    <div class="col-xl-12">
        <div class="card">
            <div class="card-body">
                <a href="{{ route('member-expired.index') }}" class="btn btn-primary">Back</a>
            </div>
        </div>
    </div>
@endif
