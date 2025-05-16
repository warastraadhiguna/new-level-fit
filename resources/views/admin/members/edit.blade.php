<div class="row">
    <div class="card">
        <div class="card-body">
            <form action="{{ route('members.update', $members->id) }}" method="POST" enctype="multipart/form-data"
                id="addMemberForm">
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
                            <label for="exampleFormControlInput1" class="form-label">Full Name</label>
                            <input type="text" name="full_name" value="{{ old('full_name', $members->full_name) }}"
                                class="form-control" id="exampleFormControlInput1" autocomplete="off">
                        </div>
                    </div>
                    <div class="col-xl-6">
                        <div class="mb-3">
                            <label for="exampleFormControlInput1" class="form-label">Phone Number</label>
                            <input type="text" name="phone_number"
                                value="{{ old('phone_number', $members->phone_number) }}" class="form-control"
                                id="exampleFormControlInput1" autocomplete="off" required>
                        </div>
                    </div>

                    <input type="hidden" name="status" value="sell">

                    <div class="col-xl-6" id="nickname">
                        <div class="mb-3">
                            <label for="exampleFormControlInput1" class="form-label">Nick Name</label>
                            <input type="text" name="nickname" value="{{ old('nickname', $members->nickname) }}"
                                class="form-control" id="exampleFormControlInput1" autocomplete="off">
                        </div>
                    </div>
                    <div class="col-xl-6" id="born">
                        <div class="mb-3">
                            <label class="form-label">Date of Birth</label>
                            <input type="text" name="born" value="{{ old('born', $members->born) }}"
                                class="form-control" placeholder="Choose born date" id="mdate">
                        </div>
                    </div>
                    <div class="col-xl-6">
                        <div class="mb-3">
                            <label for="exampleFormControlInput1" class="form-label">Member Code</label>
                            <input type="text" name="member_code"
                                value="{{ old('member_code', $members->member_code) }}" class="form-control"
                                id="exampleFormControlInput1" autocomplete="off">
                        </div>
                    </div>
                    <div class="col-xl-6" id="card_number">
                        <div class="mb-3">
                            <label for="exampleFormControlInput1" class="form-label">Card Number</label>
                            <div class="d-flex">
                                <input type="text" name="card_number" value="{{ old('card_number') }}"
                                    class="form-control" id="exampleFormControlInput1" autocomplete="off">
                            </div>
                        </div>
                    </div>
                    <div class="col-xl-6">
                        <div class="mb-3">
                            <label for="exampleFormControlInput1" class="form-label">Email</label>
                            <input type="email" name="email" value="{{ old('email', $members->email) }}"
                                class="form-control" id="exampleFormControlInput1" autocomplete="off">
                        </div>
                    </div>
                    <div class="col-xl-6">
                        <div class="mb-3">
                            <label for="exampleFormControlInput1" class="form-label">Instagram</label>
                            <input type="text" name="ig" value="{{ old('ig', $members->ig) }}"
                                class="form-control" id="exampleFormControlInput1" autocomplete="off">
                        </div>
                    </div>
                    <div class="col-xl-6">
                        <div class="mb-3">
                            <label for="exampleFormControlInput1" class="form-label">Emergency
                                Contact</label>
                            <input type="text" name="emergency_contact"
                                value="{{ old('emergency_contact', $members->emergency_contact) }}"
                                class="form-control" id="exampleFormControlInput1" autocomplete="off">
                        </div>
                    </div>
                    <div class="col-xl-6" id="ec_name">
                        <div class="mb-3">
                            <label for="exampleFormControlInput1" class="form-label">Emergency Contact
                                Name</label>
                            <input type="text" name="ec_name" value="{{ old('ec_name') }}" class="form-control"
                                id="exampleFormControlInput1" autocomplete="off">
                        </div>
                    </div>
                    <div class="col-xl-6">
                        <div class="mb-3">
                            <label for="exampleFormControlInput1" class="form-label">Gender</label>
                            <select name="gender" class="form-control" aria-label="Default select example">
                                <option value="{{ $members->gender }}" selected>
                                    {{ old('gender', $members->gender) }}
                                </option>
                                <option value="Male">Male</option>
                                <option value="Female">Female</option>
                            </select>
                        </div>
                    </div>
                    <div class="col-xl-6">
                        <div class="mb-3">
                            <label for="exampleFormControlTextarea1" class="form-label text-primary">
                                Address
                            </label>
                            <textarea class="form-control" name="address" id="exampleFormControlTextarea1" rows="6"
                                placeholder="Enter Address">{{ old('address', $members->address) }}</textarea>
                        </div>
                    </div>
                    <div class="col-xl-6">
                        <div class="mb-3">
                            <label for="formFile" class="form-label">Photo</label>
                            <input class="form-control" type="file" name="photos" onchange="loadFile(event)"
                                id="formFile">
                        </div>
                        <img id="output" class="img-fluid mt-2 mb-4" width="200" />
                    </div>
                    <div class="row mt-4">
                        <div class="col-xl-6" id="member_package">
                            <div class="mb-3">
                                <label for="exampleFormControlInput1" class="form-label">Member Package</label>
                                <select id="single-select2" name="member_package_id" class="form-control" required>
                                    <option>
                                        <- Choose ->
                                    </option>
                                    @foreach ($memberPackage as $item)
                                        <option value="{{ $item->id }}">{{ $item->package_name }} |
                                            {{ $item->days }} Days |
                                            {{ formatRupiah($item->package_price) }} |
                                            {{ formatRupiah($item->admin_price) }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                        <div class="col-xl-6" id="start_date">
                            <div class="mb-3">
                                <label class="form-label">Start Date</label>
                                <input type="text" name="start_date" value="{{ old('start_date') }}"
                                    class="form-control editDate mdate-custom3" placeholder="Choose start date">
                            </div>
                        </div>
                        <input type="hidden" class="form-control editTime" name="start_time" autocomplete="off">
                        <div class="col-xl-6" id="method_payment">
                            <div class="mb-3">
                                <label for="exampleFormControlInput1" class="form-label">Method Payment</label>
                                <select id="single-select3" name="method_payment_id" class="form-control">
                                    <option>
                                        <- Choose ->
                                    </option>
                                    @foreach ($methodPayment as $item)
                                        <option value="{{ $item->id }}">{{ $item->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                        @if (Auth::user()->role == 'CS' || Auth::user()->role == 'ADMIN')
                            <div class="col-xl-6" id="fitness_consultant">
                                <div class="mb-3">
                                    <label for="exampleFormControlInput1" class="form-label">Fitness
                                        Consultant</label>
                                    <select id="single-select4" name="fc_id" class="form-control">
                                        <option>
                                            <- Choose ->
                                        </option>
                                        @foreach ($fitnessConsultant as $item)
                                            <option value="{{ $item->id }}">{{ $item->full_name }}</option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                        @endif
                        <div class="col-xl-6" id="description">
                            <div class="mb-3">
                                <label for="exampleFormControlTextarea1" class="form-label text-primary">
                                    Description
                                </label>
                                <textarea class="form-control" name="description" id="exampleFormControlTextarea1" rows="6"
                                    placeholder="Enter Description">{{ old('description') }}</textarea>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="d-flex justify-content-between">
                    <button type="submit" class="btn btn-primary">Update</button>
                    @if ($members->status == 'sell')
                        <a href="{{ route('members.index') }}" class="btn btn-info text-right">Back</a>
                    @else
                        <a href="{{ route('missed-guest.index') }}" class="btn btn-info text-right">Back</a>
                    @endif
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
