{{-- Row bawah ini untuk tabel members --}}
<div class="row" id="memberForm">
    <div class="card">
        <div class="card-body">
            <form action="{{ route('member-second-store') }}" method="POST" enctype="multipart/form-data"
                id="addMemberForm">
                @csrf
                <h3>Create Member</h3>
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
                            <label for="exampleFormControlInput1" class="form-label">Full Name</label>
                            <input type="text" name="full_name" value="{{ old('full_name') }}" class="form-control"
                                id="exampleFormControlInput1" autocomplete="off" required>
                        </div>
                    </div>
                    <div class="col-xl-6">
                        <div class="mb-3">
                            <label for="exampleFormControlInput1" class="form-label">Phone Number</label>
                            <input type="text" name="phone_number" value="{{ old('phone_number') }}"
                                class="form-control" id="exampleFormControlInput1" autocomplete="off" required>
                        </div>
                    </div>
                    @if (Auth::user()->role == 'CS' || Auth::user()->role == 'ADMIN')
                        <div class="col-xl-6" id="candidateFC">
                            <div class="mb-3">
                                <label for="exampleFormControlInput1" class="form-label">Candidate Fitness Consultant</label>
                                <select id="single-select4" name="fc_candidate_id" class="form-control">
                                    <option value="">
                                        <- Choose ->
                                    </option>
                                    @foreach ($fitnessConsultant as $item)
                                        <option value="{{ $item->id }}" {{ old('fc_candidate_id') == $item->id ? 'selected' : '' }}>{{ $item->full_name }}
                                    @endforeach
                                </select>
                            </div>
                        </div>
                    @endif
                    <div class="col-xl-6" id="cancellation-note">
                        <div class="mb-3">
                            <label for="exampleFormControlTextarea1" class="form-label text-primary">
                                Cancellation Note
                            </label>
                            <textarea class="form-control" name="cancellation_note" id="exampleFormControlTextarea1" rows="6"
                                placeholder="Enter Cancellation Note">{{ old('cancellation_note') }}</textarea>
                        </div>
                    </div>
                    <div class="col-xl-6">
                        <div class="mb-3">
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="status" id="sell"
                                    value="missed_guest"   {{ old('status')? (old('status') == 'missed_guest' ? 'checked' : ''): 'checked' }}>
                                <label class="form-check-label" for="sell">
                                    Missed Guest
                                </label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="status" id="missed"
                                    value="sell"  {{ old('status') == 'sell' ? 'checked' : '' }}>
                                <label class="form-check-label" for="missed">
                                    Sell
                                </label>
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-xl-6" id="nickname">
                            <div class="mb-3">
                                <label for="exampleFormControlInput1" class="form-label">Nick Name</label>
                                <input type="text" name="nickname" value="{{ old('nickname') }}" class="form-control"
                                    id="exampleFormControlInput1" autocomplete="off">
                            </div>
                        </div>
                        <div class="col-xl-6" id="born">
                            <div class="mb-3">
                                <label class="form-label">Date of Birth</label>
                                <input type="text" name="born" value="{{ old('born') }}"
                                    class="form-control mdate-custom" placeholder="Choose born date">
                            </div>
                        </div>
                        <div class="col-xl-6" id="member_code">
                            <div class="mb-3">
                                <label for="exampleFormControlInput1" class="form-label">Member Number</label>
                                <div class="d-flex">
                                    <input type="text" name="member_code" value="{{ old('member_code') }}"
                                        class="form-control" id="exampleFormControlInput1" autocomplete="off">
                                </div>
                            </div>
                        </div>
                        <div class="col-xl-6" id="card_number">
                            <div class="mb-3">
                                <label for="exampleFormControlInput1" class="form-label">Card Number</label>
                                <div class="d-flex">
                                    <input type="text" name="card_number" value="{{ old('card_number') }}"
                                        class="form-control" id="exampleFormControlInput1" autocomplete="off">
                                </div>
                            </div>
                        </div>
                        <div class="col-xl-6" id="email">
                            <div class="mb-3">
                                <label for="exampleFormControlInput1" class="form-label">Email</label>
                                <input type="text" name="email" value="{{ old('email') }}" class="form-control"
                                    id="exampleFormControlInput1" autocomplete="off">
                            </div>
                        </div>
                        <div class="col-xl-6" id="ig">
                            <div class="mb-3">
                                <label for="exampleFormControlInput1" class="form-label">Instagram</label>
                                <input type="text" name="ig" value="{{ old('ig') }}"
                                    class="form-control" id="exampleFormControlInput1" autocomplete="off">
                            </div>
                        </div>
                        <div class="col-xl-6" id="emergency_contact">
                            <div class="mb-3">
                                <label for="exampleFormControlInput1" class="form-label">Emergency Contact</label>
                                <input type="text" name="emergency_contact"
                                    value="{{ old('emergency_contact') }}" class="form-control"
                                    id="exampleFormControlInput1" autocomplete="off">
                            </div>
                        </div>
                        <div class="col-xl-6" id="ec_name">
                            <div class="mb-3">
                                <label for="exampleFormControlInput1" class="form-label">Emergency Contact
                                    Name</label>
                                <input type="text" name="ec_name" value="{{ old('ec_name') }}"
                                    class="form-control" id="exampleFormControlInput1" autocomplete="off">
                            </div>
                        </div>
                        <div class="col-xl-6" id="gender">
                            <div class="mb-3">
                                <label for="exampleFormControlInput1" class="form-label">Gender</label>
                                <select name="gender" class="form-control" aria-label="Default select example">
                                    <option disabled selected value>
                                        <- Choose ->
                                    </option>
                                    <option value="Male" {{ old('gender') == 'Male' ? 'selected' : '' }}>Male</option>
                                    <option value="Female"  {{ old('gender') == 'Female' ? 'selected' : '' }}>>Female</option>
                                </select>
                            </div>
                        </div>
                        <div class="col-xl-6" id="formFile">
                            <div class="mb-3">
                                <label for="formFile" class="form-label">Photo</label>
                                <input class="form-control" type="file" name="photos" onchange="loadFile(event)"
                                    id="formFile">
                            </div>
                            <img id="output" class="img-fluid mt-2 mb-4" width="100" />
                        </div>
                        <div class="col-xl-6" id="address">
                            <div class="mb-3">
                                <label for="exampleFormControlTextarea1" class="form-label text-primary">
                                    Address
                                </label>
                                <textarea class="form-control" name="address" id="exampleFormControlTextarea1" rows="6"
                                    placeholder="Enter Address">{{ old('address') }}</textarea>
                            </div>
                        </div>
                    </div>
                </div>
                {{-- Row bawah ini untuk tabel member_registrations --}}
                <div class="row mt-4">
                    <div class="col-xl-6" id="member_package">
                        <div class="mb-3">
                            <label for="exampleFormControlInput1" class="form-label">Member Package</label>
                            <select id="single-select2" name="member_package_id" class="form-control">
                                <option value="">
                                    <- Choose ->
                                </option>
                                @foreach ($memberPackage as $item)
                                    <option value="{{ $item->id }}" {{ old('member_package_id') == $item->id ? 'selected' : '' }}>{{ $item->package_name }} |
                                        {{ $item->days }} Days |
                                        {{ formatRupiah($item->package_price) }} |
                                        {{ formatRupiah($item->admin_price) }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <div class="col-xl-6" id="start_date">
                        <div class="mb-3">
                            <label class="form-label">Start Date</label>
                            <input type="text" name="start_date" value="{{ old('start_date') }}"
                                class="form-control editDate mdate-custom3" placeholder="Choose start date">
                        </div>
                    </div>
                    <div class="col-xl-6" id="method_payment">
                        <div class="mb-3">
                            <label for="exampleFormControlInput1" class="form-label">Method Payment</label>
                            <select id="single-select3" name="method_payment_id" class="form-control">
                                <option value="">
                                    <- Choose ->
                                </option>
                                @foreach ($methodPayment as $item)
                                    <option value="{{ $item->id }}" {{ old('method_payment_id') == $item->id ? 'selected' : '' }}>{{ $item->name }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <div class="col-xl-6" id="first_payment">
                        <div class="mb-3">
                            <label class="form-label">First Payment</label>
                            <input type="text" name="first_payment" value="{{ old('first_payment') }}"
                                class="form-control" placeholder="First Payment">
                        </div>
                    </div>                    
                    @if (Auth::user()->role == 'CS' || Auth::user()->role == 'ADMIN')
                        <div class="col-xl-6" id="fitness_consultant">
                            <div class="mb-3">
                                <label for="exampleFormControlInput1" class="form-label">Fitness Consultant</label>
                                <select id="single-select4" name="fc_id" class="form-control">
                                    <option value="">
                                        <- Choose ->
                                    </option>
                                    @foreach ($fitnessConsultant as $item)
                                        <option value="{{ $item->id }}" {{ old('fc_id') == $item->id ? 'selected' : '' }}>{{ $item->full_name }}
                                    @endforeach
                                </select>
                            </div>
                        </div>
                    @endif
                    <div class="col-xl-6" id="description">
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
                </div>
            </form>
        </div>
    </div>
</div>

{{-- <script>
    document.getElementById('submitButton').addEventListener('click', function() {
        document.getElementById('addMemberForm').submit();
    });
</script> --}}
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