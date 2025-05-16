<div class="row">
    <div class="card">
        <div class="card-body">
            <form action="{{ route('prosesLayoutOrientation', $members->id) }}" method="POST"
                enctype="multipart/form-data" id="addMemberForm">
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
                    <div class="col-xl-12 mb-4">
                        @if ($members->photos)
                            <img src="{{ Storage::url($members->photos ?? '') }}" class="lazyload" width="100"
                                alt="image">
                        @else
                            <img src="{{ asset('default.png') }}" width="250" class="img-fluid" alt="">
                        @endif
                    </div>
                    <hr />
                    <div class="col-xl-4">
                        <div class="mb-3">
                            <label for="exampleFormControlInput1" class="form-label">Full Name</label>
                            <input type="text" name="full_name" value="{{ old('full_name', $members->full_name) }}"
                                class="form-control" id="exampleFormControlInput1" style="background-color: #ebeaea"
                                disabled>
                        </div>
                    </div>
                    <div class="col-xl-4">
                        <div class="mb-3">
                            <label for="exampleFormControlInput1" class="form-label">Phone Number</label>
                            <input type="text" name="phone_number"
                                value="{{ old('phone_number', $members->phone_number) }}" class="form-control"
                                id="exampleFormControlInput1" style="background-color: #ebeaea" disabled>
                        </div>
                    </div>

                    <div class="col-xl-4">
                        <div class="mb-3">
                            <label for="exampleFormControlInput1" class="form-label">Nick Name</label>
                            <input type="text" name="nickname" value="{{ old('nickname', $members->nickname) }}"
                                class="form-control" id="exampleFormControlInput1" style="background-color: #ebeaea"
                                disabled>
                        </div>
                    </div>
                    <div class="col-xl-4">
                        <div class="mb-3">
                            <label class="form-label">Date of Birth</label>
                            <input type="text" name="born" value="{{ old('born', $members->born) }}"
                                class="form-control" placeholder="Choose born date" style="background-color: #ebeaea"
                                disabled>
                        </div>
                    </div>
                    <div class="col-xl-4">
                        <div class="mb-3">
                            <label for="exampleFormControlInput1" class="form-label">Member Code</label>
                            <input type="text" name="member_code"
                                value="{{ old('member_code', $members->member_code) }}" class="form-control"
                                id="exampleFormControlInput1" style="background-color: #ebeaea" disabled>
                        </div>
                    </div>
                    <div class="col-xl-4">
                        <div class="mb-3">
                            <label for="exampleFormControlInput1" class="form-label">Gender</label>
                            <input type="text" name="gender" value="{{ old('gender', $members->gender) }}"
                                class="form-control" id="exampleFormControlInput1" style="background-color: #ebeaea"
                                disabled>
                        </div>
                    </div>
                </div>

                <div class="col-xl-6" id="fitness_consultant">
                    <div class="mb-3">
                        <label for="exampleFormControlInput1" class="form-label">PT By</label>
                        <select id="single-select4" name="lo_pt_by" class="form-control" required>
                            <option>
                                <- Choose ->
                            </option>
                            @foreach ($personalTrainer as $item)
                                <option value="{{ $item->id }}">{{ $item->full_name }}
                            @endforeach
                        </select>
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
