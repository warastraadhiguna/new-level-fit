<div class="row">
    <div class="card">
        <div class="card-body">
            <form action="{{ route('storeAppointment', $members->id) }}" method="POST" enctype="multipart/form-data"
                id="addMemberForm">
                {{-- @method('PUT') --}}
                @csrf
                @if ($errors->any())
                    <div class="alert alert-danger">
                        <ul>
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif
                <h4>Personal Information</h4>
                {{-- <table class="table">
                    <tbody style="color: rgb(85, 85, 85);">
                        <tr>
                            <th><b>Full Name</b></th>
                            <th>{{ $members->full_name }}</th>
                        </tr>
                        <tr>
                            <th><b>Phone Number</th>
                            <th>{{ $members->phone_number }}
                            </th>
                        </tr>
                    </tbody>
                </table> --}}
                <div class="row">
                    <div class="col-xl-6">
                        <div class="mb-3">
                            <label for="exampleFormControlInput1" class="form-label">Full Name</label>
                            <input type="text" name="full_name" value="{{ old('full_name', $members->full_name) }}"
                                class="form-control" id="exampleFormControlInput1" autocomplete="off" readonly>
                        </div>
                    </div>
                    <div class="col-xl-6">
                        <div class="mb-3">
                            <label for="exampleFormControlInput1" class="form-label">Phone Number</label>
                            <input type="text" name="phone_number"
                                value="{{ old('phone_number', $members->phone_number) }}" class="form-control"
                                id="exampleFormControlInput1" autocomplete="off" readonly>
                        </div>
                    </div>
                    {{-- <div class="col-xl-6">
                        <div class="mb-3">
                            <label class="form-label">Appointment Date</label>
                            <input type="text" name="appointment_date" value="{{ old('appointment_date') }}"
                                class="form-control editDate mdate-custom3" placeholder="Choose start date">
                        </div>
                    </div> --}}
                    <div class="col-xl-6">
                        <div class="mb-3">
                            <label class="form-label">Date of Birth</label>
                            <input type="text" name="appointment_date" value="{{ old('appointment_date') }}"
                                class="form-control mdate-custom" placeholder="Choose born date">
                        </div>
                    </div>
                    {{-- <div class="col-xl-6">
                        <div class="mb-3">
                            <label for="exampleFormControlTextarea1" class="form-label text-primary">
                                Description
                            </label>
                            <textarea class="form-control" name="cancellation_note" id="exampleFormControlTextarea1" rows="6"
                                placeholder="Enter Description">{{ old('cancellation_note', $members->cancellation_note) }}</textarea>
                        </div>
                    </div> --}}
                </div>
                <div class="d-flex justify-content-between">
                    <button type="submit" class="btn btn-primary">Set Appointment</button>
                    <a href="{{ route('missed-guest.index') }}" class="btn btn-info text-right">Back</a>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
    document.getElementById('submitButton').addEventListener('click', function() {
        document.getElementById('addMemberForm').submit();
    });
</script>
