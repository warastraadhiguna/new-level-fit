<!-- Modal Edit -->
@foreach ($memberPackage as $item => $value)
    <div class="modal fade" id="modalEdit{{ $value->id }}" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <form action="{{ route('member-package.update', $value->id) }}" method="POST"
                    enctype="multipart/form-data">
                    @method('PUT')
                    @csrf
                    <div class="modal-header">
                        <h1 class="modal-title fs-5" id="exampleModalLabel">Edit Member Package</h1>
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
                                    <label for="exampleFormControlInput1" class="form-label">Package Name</label>
                                    <input type="text" name="package_name"
                                        value="{{ old('package_name', $value->package_name) }}" class="form-control"
                                        id="exampleFormControlInput1" autocomplete="off" required>
                                </div>
                            </div>
                            <div class="col-xl-6">
                                <div class="mb-3">
                                    <label for="exampleFormControlInput1" class="form-label">Number Of Days</label>
                                    <input type="number" name="days" value="{{ old('days', $value->days) }}"
                                        class="form-control" id="exampleFormControlInput1" autocomplete="off" required>
                                </div>
                            </div>
                            <div class="col-xl-6">
                                <div class="mb-3">
                                    <label for="exampleFormControlInput1" class="form-label">Package Price</label>
                                    <input type="text" name="package_price"
                                        value="{{ old('package_price', $value->package_price) }}"
                                        class="form-control rupiah" id="exampleFormControlInput1" autocomplete="off">
                                </div>
                            </div>
                            <div class="col-xl-6">
                                <div class="mb-3">
                                    <label for="exampleFormControlInput1" class="form-label">Admin Price</label>
                                    <input type="text" name="admin_price"
                                        value="{{ old('admin_price', $value->admin_price) }}"
                                        class="form-control rupiah" id="exampleFormControlInput1" autocomplete="off">
                                </div>
                            </div>
                            <div class="col-xl-12">
                                <div class="mb-3">
                                    <label for="exampleFormControlTextarea1" class="form-label text-primary">
                                        Description
                                    </label>
                                    <textarea class="form-control" name="description" id="exampleFormControlTextarea1" rows="6"
                                        placeholder="Enter Description">{{ old('description', $value->description) }}</textarea>
                                </div>
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
