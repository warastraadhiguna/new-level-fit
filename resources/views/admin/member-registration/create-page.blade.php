<div class="row">
    <div class="card">
        <div class="card-body">
            <form action="{{ route('member-second-store') }}" method="POST" enctype="multipart/form-data">
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
                            <label for="exampleFormControlInput1" class="form-label">Member's Name</label>
                            <select id="single-select4" name="member_id" class="form-control" required>
                                <option>
                                    <- Choose ->
                                </option>
                                @foreach ($members as $item)
                                    <option value="{{ $item->id }}">{{ $item->full_name }} |
                                        {{ $item->member_code ?? 'No member code' }} | {{ $item->gender }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <div class="col-xl-6">
                        <div class="mb-3">
                            <label for="exampleFormControlInput1" class="form-label">Member Package</label>
                            <select id="single-select" name="member_package_id" class="form-control" required>
                                <option>
                                    <- Choose ->
                                </option>
                                @foreach ($memberPackage as $item)
                                    <option value="{{ $item->id }}">{{ $item->package_name }} |
                                        {{ formatRupiah($item->package_price) }} | {{ $item->days }} Days</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <div class="col-xl-6">
                        <div class="mb-3">
                            <label class="form-label">Start Date</label>
                            <input type="text" name="start_date" value="{{ old('start_date') }}"
                                class="form-control" placeholder="Choose start date" id="mdate" required>
                        </div>
                    </div>
                    <div class="col-xl-6">
                        <div class="mb-3">
                            <label for="exampleFormControlInput1" class="form-label">Method Payment</label>
                            <select id="single-select2" name="method_payment_id" class="form-control" required>
                                <option>
                                    <- Choose ->
                                </option>
                                @foreach ($methodPayment as $item)
                                    <option value="{{ $item->id }}">{{ $item->name }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    {{-- <div class="col-xl-6">
                        <div class="mb-3">
                            <label for="exampleFormControlInput1" class="form-label">Sold By</label>
                            <select id="single-select2" name="fc_id" class="form-control" required>
                                <option>
                                    <- Choose ->
                                </option>
                                @foreach ($fitnessConsultant as $item)
                                    <option value="{{ $item->id }}">
                                        {{ $item->full_name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                    </div> --}}
                    {{-- <div class="col-xl-6">
                        <div class="mb-3">
                            <label for="exampleFormControlInput1" class="form-label">Referral Name</label>
                            <select id="single-select3" name="refferal_id" class="form-control" required>
                                <option>
                                    <- Choose ->
                                </option>
                                @foreach ($referralName as $item)
                                    <option value="{{ $item->id }}">{{ $item->full_name }} | Fitness Consultant
                                    </option>
                                @endforeach
                                @foreach ($members as $item)
                                    <option value="{{ $item->id }}">{{ $item->full_name }} | Member</option>
                                @endforeach
                            </select>
                        </div>
                    </div> --}}
                    <div class="col-xl-6">
                        <div class="mb-3">
                            <label for="exampleFormControlTextarea1" class="form-label text-primary">
                                Description
                            </label>
                            <textarea class="form-control" name="description" id="exampleFormControlTextarea1" rows="6"
                                placeholder="Enter Description">{{ old('description') }}</textarea>
                        </div>
                    </div>
                </div>
                <div class="d-flex justify-content-between">
                    <button type="submit" class="btn btn-primary">Save</button>
                    <a href="{{ route('member-active.index') }}" class="btn btn-info text-right">Member
                        List</a>
                </div>
            </form>
        </div>
    </div>
</div>
