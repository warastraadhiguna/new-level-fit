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
                                    {{ old('member_id', $trainerSession->members->full_name) }} |
                                    {{ old('member_id', $trainerSession->members->member_code) }} |
                                    {{ old('member_id', $trainerSession->members->phone_number) }}
                                </option>
                            </select>
                        </div>
                    </div>
                    <div class="col-xl-6">
                        <div class="mb-3">
                            <label for="exampleFormControlInput1" class="form-label">Trainer Name</label>
                            <select id="single-select2" name="trainer_id" class="form-control">
                                <option value="{{ $trainerSession->trainer_id }}" selected>
                                    {{ old('trainer_id', $trainerSession->personalTrainers->full_name) }} |
                                    {{ old('trainer_id', $trainerSession->personalTrainers->phone_number) }}
                                </option>
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
                                    {{ old('trainer_package_id', $trainerSession->trainerPackages->package_name) }} |
                                    {{ old('trainer_package_id', formatRupiah($trainerSession->trainerPackages->package_price)) }}
                                    |
                                    {{ old('trainer_package_id', $trainerSession->trainerPackages->number_of_session) }}
                                    Session |
                                    {{ old('trainer_package_id', $trainerSession->days) }} Days | {{ $item->status == 'LGT' ? 'LGT' : 'Non LGT' }}
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
                            <label class="form-label">Start Date</label>
                            <input type="text" name="start_date" id="input1"
                                value="{{ old('start_date', DateFormat($trainerSession->start_date, 'DD MMMM YYYY')) }}"
                                class="form-control mdate-custom">
                        </div>
                    </div>
                    <div class="col-xl-6" id="parentInput2">
                        <div class="mb-3">
                            <label class="form-label">Expired Date</label>
                            <input type="text" name="expired_date" id="input2"
                                value="{{ old('expired_date', DateFormat($trainerSessions->expired_date, 'DD MMMM YYYY')) }}"
                                class="form-control mdate-custom" required autocomplete="off">
                        </div>
                    </div>
                    <div class="col-xl-6">
                        <div class="mb-3">
                            <label for="exampleFormControlInput1" class="form-label">Method Payment</label>
                            <select id="single-select10" name="method_payment_id" class="form-control">
                                <option value="{{ $trainerSession->method_payment_id }}" selected>
                                    {{ old('method_payment_id', $trainerSession->methodPayment->name) }}
                                </option>
                                @foreach ($methodPayment as $item)
                                    <option value="{{ $item->id }}">{{ $item->name }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    @if (Auth::user()->role == 'CS' || Auth::user()->role == 'ADMIN')
                        <div class="col-xl-6">
                            <div class="mb-3">
                                <label for="exampleFormControlInput1" class="form-label">Fitness Consultant</label>
                                <select id="single-select8" name="fc_id" class="form-control" required>
                                    <option value="{{ $trainerSession->fc_id }}" selected>
                                        {{ old('fc_id', $trainerSession->fitnessConsultants->full_name) }}
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
                                placeholder="Enter Description">{{ old('description', $trainerSession->description) }}</textarea>
                        </div>
                    </div>
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
</div> <span class="alert alert-primary solid alert-dismissible fade show bg-info text-center">Payment Status : {{ $trainerSessionPayments->sum('value') < ($trainerSession->package_price+ $trainerSession->admin_price)? "UNPAID" : "PAID" }}</span>
</div>
<div class="row">
    @if ($trainerSessionPayments->sum('value') < ($trainerSession->package_price+ $trainerSession->admin_price))     
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
                    @foreach($trainerSessionPayments as $trainerSessionPayment)
                        <tr>
                            <td>{{ $loop->iteration }}</td>
                            <td>{{ DateFormat($trainerSessionPayment->created_at, "DD MMMM YY H:m:s") }}</td>
                            <td>{{ formatRupiah($trainerSessionPayment->value) }}</td>
                            <td>{{ $trainerSessionPayment->methodPayment->name }}</td>                                 
                            <td>{{ $trainerSessionPayment->note }}</td>
                            <td>{{ $trainerSessionPayment->user->full_name }}</td>
                            <td>
                                @if (Auth::user()->role == 'ADMIN')
                                    <form action="{{ route('trainer-session-payment.destroy', $trainerSessionPayment->id) }}"
                                        method="POST">
                                        @method('delete')
                                        @csrf
                                        <button type="submit"
                                            class="btn light btn-danger btn-xs btn-block mb-1"
                                            onclick="return confirm('Delete {{ $trainerSessionPayment->value }} payment ?')">Delete</button>
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
                                <input type="text"  placeholder="0"  class="form-control" value="{{   formatRupiah(($trainerSession->package_price+ $trainerSession->admin_price) - $trainerSessionPayments->sum('value')) }}"
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
                                <input type="hidden" name="price" value="{{  $trainerSession->package_price+ $trainerSession->admin_price}}">
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