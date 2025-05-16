<div class="row">
    <div class="card">
        <div class="card-body">
            <form action="{{ route('renewMemberRegistration', $memberRegistration->id) }}" method="POST"
                enctype="multipart/form-data">
                {{-- @method('PUT') --}}
                @csrf
                <div class="modal-body">
                    @if ($errors->any())
                        <div class="alert alert-danger">
                            <ul>
                                @foreach ($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif
                    @if ($memberRegistration->members->status == 'one_day_visit')
                        <div class="row">
                            <div class="col-xl-6" id="nickname">
                                <div class="mb-3">
                                    <label for="exampleFormControlInput1" class="form-label">Nick Name</label>
                                    <input type="text" name="nickname" value="{{ old('nickname') }}"
                                        class="form-control" id="exampleFormControlInput1" autocomplete="off">
                                </div>
                            </div>
                            <div class="col-xl-6" id="born">
                                <div class="mb-3">
                                    <label class="form-label">Born</label>
                                    <input type="text" name="born" value="{{ old('born') }}"
                                        class="form-control mdate-custom" placeholder="Choose born date">
                                </div>
                            </div>
                            <div class="col-xl-6" id="member_code">
                                <div class="mb-3">
                                    <label for="exampleFormControlInput1" class="form-label">Member Number</label>
                                    <div class="d-flex">
                                        <input type="text" name="member_code" value="{{ old('member_code') }}"
                                            class="form-control" id="exampleFormControlInput1" autocomplete="off">
                                    </div>
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
                            <div class="col-xl-6" id="email">
                                <div class="mb-3">
                                    <label for="exampleFormControlInput1" class="form-label">Email</label>
                                    <input type="text" name="email" value="{{ old('email') }}"
                                        class="form-control" id="exampleFormControlInput1" autocomplete="off">
                                </div>
                            </div>
                            <div class="col-xl-6" id="ig">
                                <div class="mb-3">
                                    <label for="exampleFormControlInput1" class="form-label">Instagram</label>
                                    <input type="text" name="ig" value="{{ old('ig') }}"
                                        class="form-control" id="exampleFormControlInput1" autocomplete="off">
                                </div>
                            </div>
                            <div class="col-xl-6" id="emergency_contact">
                                <div class="mb-3">
                                    <label for="exampleFormControlInput1" class="form-label">Emergency Contact</label>
                                    <input type="text" name="emergency_contact"
                                        value="{{ old('emergency_contact') }}" class="form-control"
                                        id="exampleFormControlInput1" autocomplete="off">
                                </div>
                            </div>
                            <div class="col-xl-6" id="ec_name">
                                <div class="mb-3">
                                    <label for="exampleFormControlInput1" class="form-label">Emergency Contact
                                        Name</label>
                                    <input type="text" name="ec_name" value="{{ old('ec_name') }}"
                                        class="form-control" id="exampleFormControlInput1" autocomplete="off">
                                </div>
                            </div>
                            <div class="col-xl-6" id="gender">
                                <div class="mb-3">
                                    <label for="exampleFormControlInput1" class="form-label">Gender</label>
                                    <select name="gender" class="form-control" aria-label="Default select example">
                                        <option disabled selected value>
                                            <- Choose ->
                                        </option>
                                        <option value="Male">Male</option>
                                        <option value="Female">Female</option>
                                    </select>
                                </div>
                            </div>
                            <div class="col-xl-6" id="formFile">
                                <div class="mb-3">
                                    <label for="formFile" class="form-label">Photo</label>
                                    <input class="form-control" type="file" name="photos"
                                        onchange="loadFile(event)" id="formFile">
                                </div>
                                <img id="output" class="img-fluid mt-2 mb-4" width="100" />
                            </div>
                            <div class="col-xl-6" id="address">
                                <div class="mb-3">
                                    <label for="exampleFormControlTextarea1" class="form-label text-primary">
                                        Address
                                    </label>
                                    <textarea class="form-control" name="address" id="exampleFormControlTextarea1" rows="6"
                                        placeholder="Enter Address">{{ old('address') }}</textarea>
                                </div>
                            </div>
                            <input type="hidden" name="status" value="sell">
                        </div>
                    @endif
                    <div class="row">
                        <input type="hidden" name="member_id" value="{{ $memberRegistration->id }}">
                        <div class="col-xl-6">
                            <div class="mb-3">
                                <label class="form-label">Full Name</label>
                                <input type="text" value="{{ $memberRegistration->members->full_name }}"
                                    class="form-control" readonly>
                            </div>
                        </div>
                        <div class="col-xl-6">
                            <div class="mb-3">
                                <label class="form-label">Phone Number</label>
                                <input type="text" value="{{ $memberRegistration->members->phone_number }}"
                                    class="form-control" readonly>
                            </div>
                        </div>
                        <div class="col-xl-6">
                            <div class="mb-3">
                                <label for="exampleFormControlInput1" class="form-label">Member Package</label>
                                <select name="member_package_id" class="form-control" id="single-select">
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
                        <div class="col-xl-6">
                            <div class="mb-3">
                                <label class="form-label">Start Date</label>
                                <input type="text" name="start_date" value="{{ old('start_date') }}"
                                    class="form-control editDate mdate-custom3" required autocomplete="off">
                            </div>
                        </div>
                        <input type="hidden" class="form-control" name="start_time"
                            value="{{ old('start_time', date('H:i', strtotime($memberRegistration->start_date))) }}"
                            autocomplete="off">
                        <div class="col-xl-6">
                            <div class="mb-3">
                                <label for="exampleFormControlInput1" class="form-label">Method Payment</label>
                                <select name="method_payment_id" class="form-control" id="single-select5">
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
                                    <select id="single-select3" name="fc_id" class="form-control" required>
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
                        <div class="col-xl-6">
                            <div class="mb-3">
                                <label for="exampleFormControlTextarea1" class="form-label text-primary">
                                    Description
                                </label>
                                <textarea class="form-control" name="description" id="exampleFormControlTextarea1" rows="6"
                                    placeholder="Enter Description">{{ old('description', $memberRegistration->description) }}</textarea>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="d-flex justify-content-between">
                    <button type="submit" class="btn btn-primary">Renewal</button>
                    <a href="{{ route('member-active.index') }}" class="btn btn-info text-right">Back</a>
                </div>
            </form>
        </div>
    </div>
</div>
