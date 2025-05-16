<div class="col-xl-12">
    <div class="card">
        <div class="card-body">
            <div class="teacher-deatails">
                <h3 class="heading">Member's Data:</h3>
                <table class="table">
                    <thead>
                        <tr>
                            <th><b>Full Name</b></th>
                            <th>{{ $memberRegistration->member_name }}</th>
                        </tr>
                        <tr>
                            <th><b>Member Code</th>
                            <th>{{ $memberRegistration->member_code }}</th>
                        </tr>
                        <tr>
                            <th><b>Gender</b></th>
                            <th>{{ $memberRegistration->gender }}</th>
                        </tr>
                        <tr>
                            <th><b>Phone Number</b></th>
                            <th>{{ $memberRegistration->phone_number }}</th>
                        </tr>
                        <tr>
                            <th><b>Address</b></th>
                            <th>{{ $memberRegistration->address }}</th>
                        </tr>
                        <tr>
                            <th><b>Description</b></th>
                            <th>{{ $memberRegistration->description }}</th>
                        </tr>
                        <tr>
                            <th><b>Expired Date</b></th>
                            <th>{{ $memberRegistration->expired_date }}</th>
                        </tr>
                        <tr>
                            <th><b>Start Date</b></th>
                            <th>{{ $memberRegistration->start_date }}</th>
                        </tr>
                    </thead>
                </table>
            </div>
        </div>

        <div class="card-body">
            <div class="">
                <h3 class="heading">Package's Data:</h3>
                <table class="table">
                    <thead>
                        <tr>
                            <th><b>Package Name</b></th>
                            <th>{{ $memberRegistration->package_name }}</th>
                        </tr>
                        <tr>
                            <th><b>Number Of Days</th>
                            <th>{{ $memberRegistration->days }}</th>
                        </tr>
                        <tr>
                            <th><b>Package Price</b></th>
                            <th>{{ formatRupiah($memberRegistration->package_price) }}</th>
                        </tr>
                        <tr>
                            <th><b>Source Code</th>
                            <th>{{ $memberRegistration->source_code_name }}</th>
                        </tr>
                        <tr>
                            <th><b>Method Payment</b></th>
                            <th>{{ $memberRegistration->method_payment_name }}</th>
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
            <div class="teacher-deatails">
                <h3 class="heading">Check In:</h3>
                <table class="table">
                    <thead>
                        <tr>
                            <th>No</th>
                            <th>Check In Date</th>
                            <th>Staff</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($memberRegistrationCheckIn as $item)
                            <tr>
                                <td>{{ $loop->iteration }}</td>
                                <td>{{ $item->check_in_date }}</td>
                                <td>{{ $item->users->full_name }}</td>
                                <td>
                                    <form action="{{ route('member-check-in.destroy', $item->id) }}"
                                        onclick="return confirm('Delete Data ?')" method="POST">
                                        @method('delete')
                                        @csrf
                                        <button type="submit" class="btn light btn-danger btn-xs mb-1">Delete</button>
                                    </form>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<div class="col-xl-12">
    <div class="card">
        <div class="card-body">
            <a href="{{ route('member-registration.index') }}" class="btn btn-primary">Back</a>
        </div>
    </div>
</div>
