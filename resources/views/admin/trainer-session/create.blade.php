<div class="row mt-4" id="trainerSessionForm">
    <div class="card">
        <div class="card-body">
            <form action="{{ route('trainer-session.store') }}" method="POST" enctype="multipart/form-data">
                @csrf
                <h3>Create Trainer Session</h3>
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
                            <select id="single-select5" name="member_id" class="form-control">
                                <option disabled selected value>
                                    <- Choose ->
                                </option>
                                @foreach ($members as $item)
                                    <option value="{{ $item->id }}">
                                        {{ $item->full_name }} | {{ $item->member_code ?? 'No member code' }} |
                                        {{ $item->phone_number }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <div class="col-xl-6">
                        <div class="mb-3">
                            <label for="exampleFormControlInput1" class="form-label">Trainer Name</label>
                            <select id="single-select6" name="trainer_id" class="form-control">
                                <option disabled selected value>
                                    <- Choose ->
                                </option>
                                @foreach ($personalTrainers as $item)
                                    <option value="{{ $item->id }}">
                                        {{ $item->full_name }} | {{ $item->phone_number }} | {{ $item->gender }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <div class="col-xl-6">
                        <div class="mb-3">
                            <label for="exampleFormControlInput1" class="form-label">Trainer Package</label>
                            <select id="single-select2" name="trainer_package_id" class="form-control">
                                <option disabled selected value>
                                    <- Choose ->
                                </option>
                                @foreach ($trainerPackages as $item)
                                    <option value="{{ $item->id }}" data-session="{{ $item->number_of_session }}">
                                        {{ $item->package_name }} |
                                        {{ formatRupiah($item->package_price) }} |
                                        {{ $item->number_of_session }} Sessions | {{ $item->status == 'LGT' ? 'LGT' : 'Non LGT' }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <div class="col-xl-6">
                        <div class="mb-3">
                            <label class="form-label">Start Date</label>
                            <input type="text" name="start_date" value="{{ old('start_date') }}"
                                class="form-control editDate mdate-custom3" placeholder="Choose start date" required>
                        </div>
                    </div>
                    <div class="col-xl-6" id="method_payment">
                        <div class="mb-3">
                            <label for="exampleFormControlInput1" class="form-label">Method Payment</label>
                            <select id="single-select3" name="method_payment_id" class="form-control" required>
                                <option>
                                    <- Choose ->
                                </option>
                                @foreach ($methodPayment as $item)
                                    <option value="{{ $item->id }}">{{ $item->name }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <div class="col-xl-6" id="first_payment">
                        <div class="mb-3">
                            <label class="form-label">First Payment</label>
                            <input type="text" name="first_payment" value="{{ old('first_payment') }}"
                                class="form-control" placeholder="First Payment" required>
                        </div>
                    </div>                       
                    @if (Auth::user()->role == 'CS' || Auth::user()->role == 'ADMIN')
                        <div class="col-xl-6" id="fitness_consultant">
                            <div class="mb-3">
                                <label for="exampleFormControlInput1" class="form-label">Fitness Consultant</label>
                                <select id="single-select4" name="fc_id" class="form-control" required>
                                    <option>
                                        <- Choose ->
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
                                placeholder="Enter Description">{{ old('description') }}</textarea>
                        </div>
                    </div>


                </div>
                <div class="d-flex justify-content-between">
                    <button type="submit" class="btn btn-primary">Save</button>
                    <a href="{{ route('trainer-session.index') }}" class="btn btn-info text-right">Trainer Session
                        List</a>
                </div>
            </form>
        </div>
    </div>
</div>
<script>
  const input = document.getElementById('first_payment');

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