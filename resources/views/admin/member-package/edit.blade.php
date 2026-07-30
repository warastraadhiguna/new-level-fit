<!-- Modal Edit -->
@foreach ($memberPackage as $item => $value)
    <div class="modal fade" id="modalEdit{{ $value->id }}" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <form action="{{ route('member-package.update', $value->id) }}" method="POST"
                    enctype="multipart/form-data" class="member-package-form js-idempotent-package-form">
                    @method('PUT')
                    @csrf
                    <input type="hidden" name="_submission_token" value="{{ (string) \Illuminate\Support\Str::uuid() }}">
                    <input type="hidden" name="form_context" value="edit-{{ $value->id }}">
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
                                <label for="exampleFormControlInput1" class="form-label">Branch</label>
                                <select name="branch_store_id" class="form-control" aria-label="Default select example"
                                    required>
                                    @foreach($branch_stores as $branch_store)                                        
                                        <option value="{{ $branch_store->id }}"
                                            data-installment-enabled="{{ $branch_store->member_installment_enabled ? '1' : '0' }}"
                                            {{ (string) old('branch_store_id', $value->branch_store_id) === (string) $branch_store->id ? 'selected' : '' }}>
                                            {{ $branch_store->name }}
                                        </option>
                                    @endforeach
                                </select>
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
                        <div class="col-xl-6">
                            <div class="mb-3">
                                <label for="exampleFormControlInput1" class="form-label">Status</label>
                                    <select name="is_all_club" class="form-control" aria-label="Default select example"
                                        required>
                                        <option value="0" {{ $value->is_all_club == '0'? 'selected' : '' }}>One Club</option>                                    
                                        <option value="1" {{ $value->is_all_club == '1'? 'selected' : '' }}>All Club</option>
                                    </select>
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
                        <div class="col-xl-6 installment-setting">
                            <div class="mb-3">
                                <label class="form-label">Metode Pembayaran</label>
                                <select name="is_installment_plan" class="form-control" required>
                                    <option value="0" {{ (string) old('is_installment_plan', (int) $value->is_installment_plan) === '0' ? 'selected' : '' }}>Pembayaran biasa</option>
                                    <option value="1" {{ (string) old('is_installment_plan', (int) $value->is_installment_plan) === '1' ? 'selected' : '' }}>Cicilan tahunan (bulan 1 + 12 di awal)</option>
                                </select>
                            </div>
                        </div>
                        <div class="col-xl-6 installment-setting">
                            <div class="mb-3">
                                <label class="form-label">Nominal Cicilan per Bulan</label>
                                <input type="text" name="installment_monthly_amount"
                                    value="{{ old('installment_monthly_amount', $value->installment_monthly_amount) }}"
                                    class="form-control rupiah" autocomplete="off">
                                <small class="text-muted">Package Price wajib sama dengan nominal ini × 12.</small>
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
