<div class="row">
    <div class="card">
        <div class="card-body">
            <form action="{{ route('trainer.store') }}" method="POST" enctype="multipart/form-data">
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
                            <label for="exampleFormControlInput1" class="form-label">Trainer Name</label>
                            <select name="trainer_id" id="single-select" class="form-control"
                                aria-label="Default select example" required>
                                <option disabled selected value>
                                    <- Choose ->
                                </option>
                                @foreach ($personalTrainer as $item)
                                    <option value="{{ $item->id }}">{{ $item->full_name }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <div class="col-xl-6">
                        <div class="mb-3">
                            <label for="exampleFormControlInput1" class="form-label">Member Name</label>
                            <select name="member_id" id="single-select2" class="form-control"
                                aria-label="Default select example" required>
                                <option disabled selected value>
                                    <- Choose ->
                                </option>
                                @foreach ($members as $item)
                                    <option value="{{ $item->id }}">{{ $item->full_name }} |
                                        {{ $item->member_code }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <div class="col-xl-6">
                        <div class="mb-3">
                            <label for="exampleFormControlInput1" class="form-label">Trainer Package</label>
                            <select name="trainer_package_id" id="single-select3" class="form-control"
                                aria-label="Default select example" required>
                                <option disabled selected value>
                                    <- Choose ->
                                </option>
                                @foreach ($trainerPackage as $item)
                                    <option value="{{ $item->id }}">{{ $item->package_name }} |
                                        {{ formatRupiah($item->package_price) }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <div class="col-xl-6">
                        <div class="mb-3">
                            <label for="exampleFormControlInput1" class="form-label">Transaction Type</label>
                            <select name="transaction_type_id" id="single-select4" class="form-control"
                                aria-label="Default select example" required>
                                <option disabled selected value>
                                    <- Choose ->
                                </option>
                                @foreach ($trainerTransactionType as $item)
                                    <option value="{{ $item->id }}">{{ $item->transaction_name }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <div class="col-xl-6">
                        <div class="mb-3">
                            <label for="exampleFormControlInput1" class="form-label">Method Payment</label>
                            <select name="method_payment_id" id="single-select5" class="form-control"
                                aria-label="Default select example" required>
                                <option disabled selected value>
                                    <- Choose ->
                                </option>
                                @foreach ($methodPayment as $item)
                                    <option value="{{ $item->id }}">{{ $item->name }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <div class="col-xl-6">
                        <div class="mb-3">
                            <label for="exampleFormControlInput1" class="form-label">FC Name</label>
                            <select name="fc_id" id="single-select6" class="form-control"
                                aria-label="Default select example" required>
                                <option disabled selected value>
                                    <- Choose ->
                                </option>
                                @foreach ($fc as $item)
                                    <option value="{{ $item->id }}">{{ $item->full_name }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <div class="col-xl-6">
                        <div class="mb-3">
                            <label for="exampleFormControlTextarea1" class="form-label text-primary">
                                Description
                            </label>
                            <textarea class="form-control" name="description" id="exampleFormControlTextarea1" rows="6"
                                placeholder="Enter Description">{{ old('description') }}</textarea>
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
                </div>
                <button type="submit" class="btn btn-primary">Save</button>
                <a href="{{ route('trainer.index') }}" class="btn btn-danger">Back</a>
            </form>
        </div>
    </div>
</div>
