@foreach ($members as $member => $value)
    <div class="modal fade" id="modalEdit{{ $value->id }}" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <form action="{{ route('member.update', $value->id) }}" method="POST" enctype="multipart/form-data">
                    @method('PUT')
                    @csrf
                    <div class="modal-header">
                        <h1 class="modal-title fs-5" id="exampleModalLabel">Edit Member</h1>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
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
                        <div class="row">
                            <div class="col-xl-6">
                                <div class="mb-3">
                                    <label for="exampleFormControlInput1" class="form-label">First Name</label>
                                    <input type="text" name="first_name"
                                        value="{{ old('first_name', $value->first_name) }}" class="form-control"
                                        id="exampleFormControlInput1" autocomplete="off">
                                </div>
                            </div>
                            <div class="col-xl-6">
                                <div class="mb-3">
                                    <label for="exampleFormControlInput1" class="form-label">Last Name</label>
                                    <input type="text" name="last_name"
                                        value="{{ old('last_name', $value->last_name) }}" class="form-control"
                                        id="exampleFormControlInput1" autocomplete="off">
                                </div>
                            </div>
                            <div class="col-xl-6">
                                <div class="mb-3">
                                    <label for="exampleFormControlInput1" class="form-label">Gender</label>
                                    <select name="gender" class="form-control" aria-label="Default select example">
                                        <option value="{{ $value->gender }}" selected>
                                            {{ old('gender', $value->gender) }}
                                        </option>
                                        <option value="Male">Male</option>
                                        <option value="Female">Female</option>
                                    </select>
                                </div>
                            </div>
                            <div class="col-xl-6">
                                <div class="mb-3">
                                    <label for="exampleFormControlInput1" class="form-label">Phone Number</label>
                                    <input type="text" name="phone_number"
                                        value="{{ old('phone_number', $value->phone_number) }}" class="form-control"
                                        id="exampleFormControlInput1" autocomplete="off">
                                </div>
                            </div>
                            <div class="col-xl-6">
                                <div class="mb-3">
                                    <label for="exampleFormControlInput1" class="form-label">Source Code</label>
                                    <select name="source_code_id" class="form-control"
                                        aria-label="Default select example">
                                        <option value="{{ $value->source_code_id }}" selected>
                                            {{ old('source_code_id', $value->sourceCode->name) }}
                                        </option>
                                        @foreach ($sourceCode as $item)
                                            <option value="{{ $item->id }}">{{ $item->name }}</option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                            <div class="col-xl-6">
                                <div class="mb-3">
                                    <label for="exampleFormControlInput1" class="form-label">Member Package</label>
                                    <select name="member_package_id" class="form-control"
                                        aria-label="Default select example">
                                        <option value="{{ $value->member_package_id }}" selected>
                                            {{ old('member_package_id', $value->memberPackage->package_name) }}
                                        </option>
                                        @foreach ($memberPackage as $item)
                                            <option value="{{ $item->id }}">{{ $item->package_name }}</option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                            <div class="col-xl-6">
                                <div class="mb-3">
                                    <label for="exampleFormControlInput1" class="form-label">Method Payment</label>
                                    <select name="method_payment_id" class="form-control"
                                        aria-label="Default select example">
                                        <option value="{{ $value->method_payment_id }}" selected>
                                            {{ old('method_payment_id', $value->methodPayment->name) }}
                                        </option>
                                        @foreach ($methodPayment as $item)
                                            <option value="{{ $item->id }}">{{ $item->name }}</option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                            <div class="col-xl-6">
                                <div class="mb-3">
                                    <label for="exampleFormControlInput1" class="form-label">Sold By</label>
                                    <select name="sold_by_id" class="form-control" aria-label="Default select example">
                                        <option value="{{ $value->sold_by_id }}" selected>
                                            {{ old('sold_by_id', $value->soldBy->name) }}
                                        </option>
                                        @foreach ($soldBy as $item)
                                            <option value="{{ $item->id }}">{{ $item->name }}</option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                            <div class="col-xl-6">
                                <div class="mb-3">
                                    <label for="exampleFormControlInput1" class="form-label">Refferal Name</label>
                                    <select name="refferal_id" class="form-control"
                                        aria-label="Default select example" required>
                                        <option value="{{ $value->refferal_id }}" selected>
                                            {{ old('refferal_id', $value->refferalName->name) }}
                                        </option>
                                        @foreach ($refferalName as $item)
                                            <option value="{{ $item->id }}">{{ $item->name }}</option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                            <div class="col-xl-6">
                                <div class="mb-3">
                                    <label for="exampleFormControlInput1" class="form-label">Status</label>
                                    <select name="status" class="form-control" value="{{ old('status') }}"
                                        aria-label="Default select example" required>
                                        <option value="{{ $value->status }}" selected>
                                            {{ old('status', $value->status) }}
                                        </option>
                                        <option value="Active">Active</option>
                                        <option value="Inactive">Inactive</option>
                                    </select>
                                </div>
                            </div>
                            <div class="col-xl-6">
                                <div class="mb-3">
                                    <label for="exampleFormControlTextarea1" class="form-label text-primary">
                                        Description
                                    </label>
                                    <textarea class="form-control" name="description" id="exampleFormControlTextarea1" rows="6"
                                        placeholder="Enter Description">{{ old('description', $value->description) }}</textarea>
                                </div>
                            </div>
                            <div class="col-xl-6">
                                <div class="mb-3">
                                    <label for="formFile" class="form-label">Photo</label>
                                    <input class="form-control" type="file" name="photos"
                                        onchange="loadFile(event)" id="formFile">
                                    <img src="{{ Storage::disk('local')->url($value->photos) }}"
                                        class="img-fluid mt-4" width="200" alt="">
                                </div>
                                <img id="outputEdit" class="img-fluid mb-4" width="200">
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="submit" class="btn btn-primary">Update</button>
                        <button type="button" class="btn btn-danger light" data-bs-dismiss="modal">Close</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endforeach
