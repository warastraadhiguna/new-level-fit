{{-- <div class="col-xl-12">
    <div class="page-title flex-wrap justify-content-between">
        <a href="{{ route('print-trainer-session-detail-pdf') }}" target="_blank" class="btn btn-info">Print PDF</a>
    </div>
</div> --}}

<div class="col-xl-12">
    <div class="card">
        <div class="card-body">
            <div class="teacher-deatails">
                <h3 class="heading">Member:</h3>

                <table class="table">
                    <thead>
                        <tr>
                            <th scope="col"><b>Full Name</b></th>
                            <th scope="col">{{ $trainerSession->members->full_name }}</th>
                        </tr>
                        <tr>
                            <th scope="col"><b>Member Code</b></th>
                            <th scope="col">{{ $trainerSession->members->member_code }}</th>
                        </tr>
                        <tr>
                            <th scope="col"><b>Phone Number</b></th>
                            <th scope="col">{{ $trainerSession->members->phone_number }}</th>
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
                <h3 class="heading">Trainer Session Detail:</h3>
                <table class="table">
                    <thead>
                        <tr>
                            <th scope="col"><b>Trainer Name</b></th>
                            <th scope="col">{{ $trainerSession->personalTrainers->full_name }}</th>
                        </tr>
                        <tr>
                            <th scope="col"><b>Session Total</b></th>
                            <th scope="col">{{ $trainerSession->trainerPackages->number_of_session }}</th>
                        </tr>
                        <tr>
                            <th scope="col"><b>Remaining Session</b></th>
                            <th scope="col">{{ $remainingSessions }}</th>
                        </tr>
                        {{-- <tr>
                            <th scope="col"><b>Status</b></th>
                            <th scope="col">{{ $trainerSession->status }}</th>
                        </tr> --}}
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
                <h3 class="heading">Trainer Session Check In:</h3>
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
                        @foreach ($checkInTrainerSession as $item)
                            <tr>
                                <td>{{ $loop->iteration }}</td>
                                <td>{{ $item->check_in_date }}</td>
                                <td>{{ $item->users->full_name }}</td>
                                <td>
                                    <form action="{{ route('trainer-session-check-in.destroy', $item->id) }}"
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
            <a href="{{ route('trainer-session.index') }}" class="btn btn-primary">Back</a>
        </div>
    </div>
</div>
