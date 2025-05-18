<div class="row">
    <div class="card">
        <div class="card-body">
            <form action="{{ route('member-active.update', $memberRegistration->id) }}" method="POST"
                enctype="multipart/form-data">
                @method('PUT')
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
                    <div class="row">
                        {{-- <input type="date" id="input1">
                        <input type="date" id="input2"> --}}
                        <div class="col-xl-6">
                            <div class="mb-3">
                                <label for="exampleFormControlInput1" class="form-label">Full Name</label>
                                <select id="single-select4" name="member_id" class="form-control" required disabled>
                                    <option value="{{ $memberRegistration->member_id }}" selected>
                                        {{ old('member_id', $memberRegistrations->member_name) }} |
                                        {{ old('member_id', $memberRegistrations->member_code) }} |
                                        {{ old('member_id', $memberRegistrations->phone_number) }}
                                    </option>
                                </select>
                            </div>
                        </div>
                        <div class="col-xl-6">
                            <div class="mb-3">
                                <label for="exampleFormControlInput1" class="form-label">Member Package</label>
                                <select name="member_package_id" class="form-control" id="single-select">
                                    <option value="{{ $memberRegistrations->member_package_id }}" selected>
                                        {{ old('member_package_id', $memberRegistrations->package_name) }}
                                        |
                                        {{ old('member_package_id', FormatRupiah($memberRegistrations->mr_package_price)) }}
                                        |
                                        {{ old('member_package_id', FormatRupiah($memberRegistrations->mr_admin_price)) }}
                                    </option>
                                    @foreach ($memberPackage as $item)
                                        <option value="{{ $item->id }}">{{ $item->package_name }} |
                                            {{ formatRupiah($item->package_price) }} |
                                            {{ formatRupiah($item->admin_price) }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                        <div class="col-xl-6" id="parentInput1">
                            <div class="mb-3">
                                <label class="form-label">Start Date</label>
                                <input type="text" name="start_date" id="input1"
                                    value="{{ old('start_date', DateFormat($memberRegistrations->start_date, 'DD MMMM YYYY')) }}"
                                    class="form-control mdate-custom" required autocomplete="off">
                            </div>
                        </div>
                        <div class="col-xl-6" id="parentInput2">
                            <div class="mb-3">
                                <label class="form-label">Expired Date</label>
                                <input type="text" name="expired_date" id="input2"
                                    value="{{ old('expired_date', DateFormat($memberRegistrations->expired_date, 'DD MMMM YYYY')) }}"
                                    class="form-control mdate-custom" required autocomplete="off">
                            </div>
                        </div>
                        <input type="hidden" id="expired_time" class="form-control editTime" name="expired_time"
                            autocomplete="off">
                        <div class="col-xl-6">
                            <div class="mb-3">
                                <label for="exampleFormControlInput1" class="form-label">Method Payment</label>
                                <select name="method_payment_id" class="form-control" id="single-select5">
                                    <option value="{{ $memberRegistrations->method_payment_id }}" selected>
                                        {{ old('method_payment_id', $memberRegistrations->method_payment_name) }}
                                    </option>
                                    @foreach ($methodPayment as $item)
                                        <option value="{{ $item->id }}">{{ $item->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                        @if ($memberRegistration->fc_id)
                            <div class="col-xl-6">
                                <div class="mb-3">
                                    <label for="exampleFormControlInput1" class="form-label">Fitness Consultant</label>
                                    <select id="single-select3" name="fc_id" class="form-control">
                                        <option value="{{ $memberRegistrations->fc_id }}" selected>
                                            {{ old('fc_id', $memberRegistrations->staff_name) }}
                                        </option>
                                        @foreach ($users as $item)
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
                    <button type="submit" class="btn btn-primary">Update</button>
                    <div class="d-flex">
                        <button type="button" class="btn btn-secondary me-2" onclick="window.scrollTo(0, document.body.scrollHeight)">Payment</button>
                        @if ($memberRegistration->members->status == 'sell')
                            <a href="{{ route('member-active.index') }}" class="btn btn-info">Back</a>
                        @else
                            <a href="{{ route('oneDayVisit') }}" class="btn btn-info">Back</a>
                        @endif
                    </div>
                </div>
            </form>
        </div>
    </div>
            <hr/>    
</div> <span class="alert alert-primary solid alert-dismissible fade show bg-info text-center">Payment Status : {{ $memberRegistrationPayments->sum('value') < ($memberRegistration->package_price+ $memberRegistration->admin_price)? "UNPAID" : "PAID" }}</span>

<div class="row">
    @if ($memberRegistrationPayments->sum('value') < ($memberRegistration->package_price+ $memberRegistration->admin_price))     
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
            <table class="table-responsive-lg table display">
                <thead>
                    <tr>
                        <th>No</th>
                        <th>Payment Date</th>
                        <th>Value</th>
                        <th>Method Payment</th>                        
                        <th>Note</th>       
                        <th>Staff</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($memberRegistrationPayments as $memberRegistrationPayment)
                        <tr>
                            <td>{{ $loop->iteration }}</td>
                            <td>{{ DateFormat($memberRegistrationPayment->created_at, "DD MMMM YY H:m:s") }}</td>
                            <td>{{ formatRupiah($memberRegistrationPayment->value) }}</td>
                            <td>{{ $memberRegistrationPayment->methodPayment->name }}</td>                            
                            <td>{{ $memberRegistrationPayment->note }}</td>
                            <td>{{ $memberRegistrationPayment->user->full_name }}</td>
                            <td>
                                @if (Auth::user()->role == 'ADMIN')
                                    <form action="{{ route('member-registration-payment.destroy', $memberRegistrationPayment->id) }}"
                                        method="POST">
                                        @method('delete')
                                        @csrf
                                        <button type="submit"
                                            class="btn light btn-danger btn-xs btn-block mb-1"
                                            onclick="return confirm('Delete {{ $memberRegistrationPayment->value }} payment ?')">Delete</button>
                                    </form>
                                @endif    
                            </td>                                                                      
                        </tr>
                    @endforeach
                </tbody>
            </table>                                
        </div>
    </div>
</div>
<div class="modal fade" id="modalAdd" tabindex="-1" aria-labelledby="exampleModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-center">
        <div class="modal-content">
            <form action="{{ route('member-registration-payment.store') }}" method="POST" enctype="multipart/form-data">
                @csrf
                <div class="modal-header">
                    <h1 class="modal-title fs-5" id="exampleModalLabel">Create Payment</h1>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="row">
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
                                <label for="exampleFormControlInput1" class="form-label">Underpayment</label>
                                <input type="text"  placeholder="0"  class="form-control" value="{{   formatRupiah(($memberRegistration->package_price+ $memberRegistration->admin_price) - $memberRegistrationPayments->sum('value')) }}"
                                    autocomplete="off" readonly>
                            </div>
                        </div>                        
                        <div class="col-xl-12">
                            <div class="mb-3">                               
                                <input type="hidden" name="member_registration_id" value="{{ $memberRegistration->id }}">
                                <label for="exampleFormControlInput1" class="form-label">Value</label>
                                <input type="text" name="value" id="value" placeholder="0"  class="form-control"
                                    autocomplete="off" required>
                            </div>
                        </div>
                        <div class="col-xl-12">
                            <div class="mb-3">
                                <label for="exampleFormControlInput1" class="form-label">Note</label>
                                <input type="hidden" name="value_sum" value="{{  $memberRegistrationPayments->sum('value')}}">
                                <input type="hidden" name="price" value="{{  $memberRegistration->package_price+ $memberRegistration->admin_price}}">
                                <input type="text" name="note" placeholder="Note..."    class="form-control"
                                    autocomplete="off" >
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