@foreach ($trainerSessions as $trainerSession => $item)
    <div class="modal fade freeze{{ $item->id }}" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <form action="{{ route('trainer-session-freeze', $item->id) }}" method="POST"
                    enctype="multipart/form-data">
                    @method('PUT')
                    @csrf
                    <div class="modal-header">
                        <h1 class="modal-title fs-5" id="exampleModalLabel">Freeze Trainer Session</h1>
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
                                    <h5>Member Name</h5>
                                    <input type="text" name="member_code" id="memberCode"
                                        value="{{ old('member_id', $item->member_name) }}" class="form-control"
                                        disabled>
                                </div>
                            </div>
                            <div class="col-xl-6">
                                <div class="mb-3">
                                    <h5>Member Code</h5>
                                    <input type="text" name="member_code" id="memberCode"
                                        value="{{ old('member_id', $item->member_code) }}" class="form-control"
                                        disabled>
                                </div>
                            </div>
                            <div class="col-xl-6">
                                <div class="mb-3">
                                    <h5>Trainer Package</h5>
                                    <input type="text" name="member_code" id="memberCode"
                                        value="{{ old('member_id', $item->package_name) }}" class="form-control"
                                        disabled>
                                </div>
                            </div>
                            <div class="col-xl-6">
                                <div class="mb-3">
                                    <h5>Days Of Trainer Package</h5>
                                    <input type="text" name="member_code" id="memberCode"
                                        value="{{ old('member_id', $item->member_registration_days) }}"
                                        class="form-control" disabled>
                                </div>
                            </div>
                            <input type="hidden" name="start_date"
                                value="{{ DateFormat($item->start_date, 'YYYY-MM-DD') }}"
                                class="form-control mdate-custom" required autocomplete="off" readonly>
                            <div class="col-xl-6">
                                <div class="mb-3">
                                    <label for="exampleFormControlInput1" class="form-label">Periode Cuti</label>
                                    <select name="expired_date" class="form-control" required>
                                        <option selected>Select</option>
                                        <option value="30">1 Month</option>
                                        <option value="60">2 Month</option>
                                        <option value="90">3 Month</option>
                                    </select>
                                </div>
                            </div>
                            <div class="col-xl-6">
                                <div class="mb-3">
                                    <label for="exampleFormControlInput1" class="form-label">Cuti Price</label>
                                    <input type="text" name="price" value="{{ old('price') }}"
                                        class="form-control rupiah" id="exampleFormControlInput1" autocomplete="off"
                                        required>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="submit" class="btn btn-primary">Submit</button>
                        <button type="button" class="btn btn-danger light" data-bs-dismiss="modal">Close</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endforeach
