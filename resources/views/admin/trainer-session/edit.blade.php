<div class="row">
    <div class="card">
        <div class="card-body">
            <form action="{{ route('trainer-session.update', $trainerSession->id) }}" method="POST"
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
                            <label for="exampleFormControlInput1" class="form-label">Member Name</label>
                            <select id="single-select" name="member_id" class="form-control" disabled>
                                <option value="{{ $trainerSession->member_id }}" selected>
                                    {{ data_get($trainerSession, 'members.full_name', 'Deleted member') }} |
                                    {{ data_get($trainerSession, 'members.member_code', '-') }} |
                                    {{ data_get($trainerSession, 'members.phone_number', '-') }}
                                </option>
                            </select>
                        </div>
                    </div>
                    <div class="col-xl-6">
                        <div class="mb-3">
                            <label for="exampleFormControlInput1" class="form-label">
                                Trainer Name <small class="text-muted">(If left empty, this session will go to the Waiting List.)</small>
                            </label>
                            <select id="single-select2" name="trainer_id" class="form-control">
                                <option value="" {{ old('trainer_id', $trainerSession->trainer_id) ? '' : 'selected' }}>
                                    <- No trainer yet (Waiting List) ->
                                </option>
                                @if ($trainerSession->trainer_id)
                                    <option value="{{ $trainerSession->trainer_id }}" {{ old('trainer_id', $trainerSession->trainer_id) == $trainerSession->trainer_id ? 'selected' : '' }}>
                                        {{ $trainerSession->personalTrainers ? $trainerSession->personalTrainers->full_name : "-" }} |
                                        {{ $trainerSession->personalTrainers ? $trainerSession->personalTrainers->phone_number : "-" }}
                                    </option>
                                @endif
                                @foreach ($personalTrainers as $item)
                                    <option value="{{ $item->id }}">
                                        {{ $item->full_name }} | {{ $item->phone_number }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <div class="col-xl-6">
                        <div class="mb-3">
                            <label for="exampleFormControlInput1" class="form-label">Trainer Package</label>
                            <select id="single-select3" name="trainer_package_id" class="form-control">
                                <option value="{{ $trainerSession->trainer_package_id }}" selected>
                                    {{ data_get($trainerSession, 'trainerPackages.package_name', 'Deleted trainer package') }} |
                                    {{ formatRupiah(data_get($trainerSession, 'trainerPackages.package_price', 0)) }}
                                    |
                                    {{ data_get($trainerSession, 'trainerPackages.number_of_session', 0) }}
                                    Session |
                                    {{ old('trainer_package_id', $trainerSession->days) }} Days |
                                    {{ data_get($trainerSession, 'trainerPackages.status') == 'LGT' ? 'LGT' : 'Non LGT' }}
                                </option>
                                @foreach ($trainerPackages as $item)
                                    <option value="{{ $item->id }}">
                                        {{ $item->package_name }} | {{ formatRupiah($item->package_price) }} |
                                        {{ formatRupiah($item->number_of_session) }} Session |
                                        {{ formatRupiah($item->days) }} Days
                                    </option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <div class="col-xl-6" id="parentInput1">
                        <div class="mb-3">
                            <label class="form-label">
                                Start Date <small class="text-muted">(If left empty, this session will go to the Waiting List.)</small>
                            </label>
                            <input type="text" name="start_date" id="input1"
                                value="{{ old('start_date', $trainerSession->start_date? DateFormat($trainerSession->start_date, 'DD MMMM YYYY') : "") }}"
                                class="form-control mdate-custom" placeholder="Choose start date">
                        </div>
                    </div>
                    <div class="col-xl-6" id="parentInput2">
                        <div class="mb-3">
                            <label class="form-label">
                                Expired Date <small class="text-muted">(Required only when Start Date is filled.)</small>
                            </label>
                            <input type="text" name="expired_date" id="input2"
                                value="{{ old('expired_date', $trainerSession->start_date ? DateFormat(ConvertToDate($trainerSession->start_date)->addDays($trainerSession->days), 'DD MMMM YYYY') : '') }}"
                                class="form-control mdate-custom" autocomplete="off">
                        </div>
                    </div>
                    <div class="col-xl-6">
                        <div class="mb-3">
                            <label for="exampleFormControlInput1" class="form-label">Method Payment</label>
                            <select id="single-select10" name="method_payment_id" class="form-control">
                                <option value="{{ $trainerSession->method_payment_id }}" selected>
                                    {{ old('method_payment_id', data_get($trainerSession, 'methodPayment.name', 'Deleted method payment')) }}
                                </option>
                                @foreach ($methodPayment as $item)
                                    <option value="{{ $item->id }}">{{ $item->name }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    @if (Auth::user()->role == 'CS' || Auth::user()->isAdmin())
                        <div class="col-xl-6">
                            <div class="mb-3">
                            <label for="exampleFormControlInput1" class="form-label">Fitness Consultant</label>
                            <select id="single-select8" name="fc_id" class="form-control">
                                <option value="">Tidak ada Fitness Consultant</option>
                                @foreach ($fitnessConsultant as $item)
                                    <option value="{{ $item->id }}"
                                        {{ (string) old('fc_id', $trainerSession->fc_id) === (string) $item->id ? 'selected' : '' }}>
                                        {{ $item->full_name }}
                                    </option>
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
                                placeholder="Enter Description">{{ old('description', $trainerSession->description) }}</textarea>
                        </div>
                    </div>
                    @include('admin.partials.payment-deadline-field', ['paymentDeadline' => $trainerSession->payment_deadline])
                    @if (optional(Auth::user()->branchStore)->trainer_discount_enabled)
                        <div class="col-xl-6">
                            <div class="mb-3">
                                <label class="form-label">Diskon PT</label>
                                <input type="text" name="discount_amount"
                                    value="{{ old('discount_amount', $trainerSession->discount_amount) }}"
                                    class="form-control rupiah" placeholder="0">
                                <small class="text-muted">Diskon nominal rupiah, biaya admin tetap dihitung.</small>
                            </div>
                        </div>
                    @endif
                </div>
                
                

                <div class="d-flex justify-content-between">
                    <button type="submit" class="btn btn-primary">Update</button>
                    <div class="d-flex">
                        <button type="button" class="btn btn-secondary me-2" onclick="window.scrollTo(0, document.body.scrollHeight)">Payment</button>
                        <a href="{{ route('trainer-session.index') }}" class="btn btn-danger">Back</a>
                    </div>
                </div>                
            </form>
        </div>
    </div>
</div>
            <hr/>    
<div class="row">            
</div> <span class="alert alert-primary solid alert-dismissible fade show bg-info text-center">Payment Status : {{ $trainerSessionPayments->sum('value') < $trainerSession->total_payable ? "UNPAID" : "PAID" }}</span>
</div>
<div class="row">
    @if ($trainerSessionPayments->sum('value') < $trainerSession->total_payable)
        <div class="col-xl-12">
            <div class="page-title flex-wrap">
                <div>                    
                    <button type="button" class="btn btn-primary" data-bs-toggle="modal"
                        data-bs-target="#modalAdd">
                        + New Payment
                    </button>        
                </div>
            </div>
        </div>    
    @endif        
    <div class="card">
        <div class="card-body">
            <div class="table-responsive">
            <table class="table display mb-0">
                <thead>
                    <tr>
                        <th>No</th>
                        <th>Payment Date</th>
                        <th>Value</th>
                        <th>Method Payment</th>                             
                        <th>Note</th>       
                        <th>Staff</th>
                        <th class="text-center" style="min-width: 120px;">Action</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($trainerSessionPayments as $trainerSessionPayment)
                        <tr>
                            <td>{{ $loop->iteration }}</td>
                            <td>{{ DateFormat($trainerSessionPayment->created_at, "DD MMMM YY H:m:s") }}</td>
                            <td>{{ formatRupiah($trainerSessionPayment->value) }}</td>
                            <td>{{ data_get($trainerSessionPayment, 'methodPayment.name', 'Deleted method payment') }}</td>
                            <td>{{ $trainerSessionPayment->note }}</td>
                            <td>{{ data_get($trainerSessionPayment, 'user.full_name', 'Deleted user') }}</td>
                            <td style="min-width: 120px;">
                                <div class="d-grid gap-1">
                                @if (optional(Auth::user()->branchStore)->pos_inventory_enabled)
                                    <a href="{{ route('payment-receipts.trainer', ['id' => $trainerSessionPayment->id, 'autoprint' => 1]) }}"
                                        target="_blank" rel="noopener" class="btn light btn-primary btn-xs w-100">Cetak Struk</a>
                                @endif
                                @if (Auth::user()->isAdmin())
                                    <form action="{{ route('trainer-session-payment.destroy', $trainerSessionPayment->id) }}" method="POST" class="m-0">
                                        @method('delete')
                                        @csrf
                                        <button type="submit"
                                            class="btn light btn-danger btn-xs w-100"
                                            onclick="return confirm('Delete {{ $trainerSessionPayment->value }} payment ?')">Delete</button>
                                    </form>
                                @endif
                                </div>
                            </td>                                                                      
                        </tr>
                    @endforeach
                </tbody>
            </table>
            </div>
        </div>
    </div>
</div>
<div class="modal fade" id="modalAdd" tabindex="-1" aria-labelledby="exampleModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-center">
        <div class="modal-content">
            <form action="{{ route('trainer-session-payment.store') }}" method="POST" enctype="multipart/form-data">
                @csrf
                <div class="modal-header">
                    <h1 class="modal-title fs-5" id="exampleModalLabel">Create Payment</h1>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="row">
                        <div class="col-xl-12">
                            <div class="mb-3">                                
                                <label for="exampleFormControlInput1" class="form-label">Underpayment</label>
                                <input type="text"  placeholder="0"  class="form-control" value="{{ formatRupiah($trainerSession->total_payable - $trainerSessionPayments->sum('value')) }}"
                                    autocomplete="off" readonly>
                            </div>
                        </div>                        
                        <div class="col-xl-12">
                            <div class="mb-3">                               
                                <input type="hidden" name="trainer_session_id" value="{{ $trainerSession->id }}">
                                <label for="exampleFormControlInput1" class="form-label">Value</label>
                                <input type="text" name="value" id="value" placeholder="0"  class="form-control"
                                    autocomplete="off" required>
                            </div>
                        </div>
                        @include('admin.partials.pos-received-amount-field', ['columnClass' => 'col-xl-12', 'fieldId' => 'payment_received_amount'])
                        <div class="col-xl-12">
                            <div class="mb-3">
                                <label for="exampleFormControlInput1" class="form-label">Method Payment</label>
                                <select id="single-select3" name="method_payment_id" class="form-control" required>
                                    <option value="">
                                        <- Choose ->
                                    </option>
                                    @foreach ($methodPayment as $item)
                                        <option value="{{ $item->id }}" {{ old('method_payment_id') == $item->id ? 'selected' : '' }}>{{ $item->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>                          
                        <div class="col-xl-12">
                            <div class="mb-3">
                                <label for="exampleFormControlInput1" class="form-label">Note</label>
                                <input type="hidden" name="value_sum" value="{{  $trainerSessionPayments->sum('value')}}">
                                <input type="hidden" name="price" value="{{ $trainerSession->total_payable }}">
                                <input type="text" name="note" placeholder="Note..."    class="form-control"
                                    autocomplete="off" required>
                            </div>
                        </div>                        
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="submit" class="btn btn-primary">Save</button>
                    <button type="button" class="btn btn-danger light" data-bs-dismiss="modal">Close</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>

  const input = document.getElementById('value');

  input.addEventListener('input', function(e) {
    // Ambil nilai input
    let value = e.target.value;

    // Hapus semua karakter selain angka dan titik
    // (titik ini kita anggap sebagai pemisah ribuan, bukan desimal)
    value = value.replace(/[^0-9.]/g, '');

    // Hapus titik yang bukan pemisah ribuan (misal titik ganda atau titik di akhir)
    // Untuk memudahkan, kita hapus semua titik dulu, lalu pasang titik pemisah ribuan kembali:
    let numbersOnly = value.replace(/\./g, '');

    // Format angka dengan titik sebagai pemisah ribuan
    // Contoh: 1234567 -> 1.234.567
    let formatted = '';
    let len = numbersOnly.length;

    for (let i = 0; i < len; i++) {
      // dari kanan ke kiri, tambahkan titik tiap 3 angka
      if (i > 0 && (len - i) % 3 === 0) {
        formatted += '.';
      }
      formatted += numbersOnly.charAt(i);
    }

    // Set value input ke format yang sudah diubah
    e.target.value = formatted;
  });
</script>
