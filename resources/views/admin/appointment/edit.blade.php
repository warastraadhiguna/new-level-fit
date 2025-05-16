<div class="row">
    <div class="card">
        <div class="card-body">
            <form action="{{ route('buddy-referral.update', $buddyReferral->id) }}" method="POST"
                enctype="multipart/form-data">
                @method('PUT')
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
                <div class="row">
                    <div class="col-xl-6">
                        <div class="mb-3">
                            <label for="exampleFormControlInput1" class="form-label">Date Time</label>
                            <input type="text" name="date_time"
                                value="{{ old('date_time', $buddyReferral->date_time) }}" id="date-formatEdit"
                                class="form-control" required>
                        </div>
                    </div>
                    <div class="col-xl-6">
                        <div class="mb-3">
                            <label for="exampleFormControlInput1" class="form-label">Referral Name</label>
                            <input type="text" name="referral_name"
                                value="{{ old('referral_name', $buddyReferral->referral_name) }}" class="form-control"
                                id="exampleFormControlInput1" autocomplete="off" required>
                        </div>
                    </div>
                    <div class="col-xl-6">
                        <div class="mb-3">
                            <label for="exampleFormControlInput1" class="form-label">Full Name</label>
                            <input type="text" name="full_name"
                                value="{{ old('referral_name', $buddyReferral->full_name) }}" class="form-control"
                                id="exampleFormControlInput1" autocomplete="off" required>
                        </div>
                    </div>
                    <div class="col-xl-6">
                        <div class="mb-3">
                            <label for="exampleFormControlInput1" class="form-label">Phone Number</label>
                            <input type="text" name="phone_number"
                                value="{{ old('phone_number', $buddyReferral->phone_number) }}" class="form-control"
                                id="exampleFormControlInput1" autocomplete="off" required>
                        </div>
                    </div>
                    <div class="col-xl-6">
                        <div class="mb-3">
                            <label for="exampleFormControlInput1" class="form-label">Email</label>
                            <input type="text" name="email" value="{{ old('email', $buddyReferral->email) }}"
                                class="form-control" id="exampleFormControlInput1" autocomplete="off" required>
                        </div>
                    </div>
                    <div class="col-xl-6">
                        <div class="mb-3">
                            <label for="exampleFormControlInput1" class="form-label">FC Name</label>
                            <select name="fc_id " class="form-control" aria-label="Default select example">
                                <option value="{{ $buddyReferral->fc_id }}" selected>
                                    {{ old('fc_id ', $buddyReferral->fitnessConsultants->full_name) }}
                                </option>
                                @foreach ($fitnessConsultants as $item)
                                    <option value="{{ $item->id }}">{{ $item->full_name }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <div class="col-xl-6">
                        <div class="mb-3">
                            <label for="exampleFormControlInput1" class="form-label">Staff Name</label>
                            <select name="cs_id " class="form-control" aria-label="Default select example">
                                <option value="{{ $buddyReferral->cs_id }}" selected>
                                    {{ old('cs_id ', $buddyReferral->customerServices->full_name) }}
                                </option>
                                @foreach ($customerServices as $item)
                                    <option value="{{ $item->id }}">{{ $item->full_name }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <div class="col-xl-6">
                        <div class="mb-3">
                            <label for="exampleFormControlInput1" class="form-label">Description</label>
                            <textarea class="form-control" name="description" cols="10" rows="5">{{ $buddyReferral->description }}</textarea>
                        </div>
                    </div>
                </div>
                <button type="submit" class="btn btn-primary">Update</button>
                <a href="{{ route('appointment.index') }}" class="btn btn-danger">Back</a>
            </form>
        </div>
    </div>
</div>
