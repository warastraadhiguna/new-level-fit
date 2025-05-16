{{-- Row bawah ini untuk tabel members --}}
<div class="row" id="memberForm">
    <div class="card">
        <div class="card-body">
            <form action="{{ route('member-second-store') }}" method="POST" enctype="multipart/form-data"
                id="addMemberForm">
                @csrf
                <h3>Create Member 1 Day Visit</h3>
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
                        <input type="hidden" name="member_id" id="memberIdInput">
                        <label for="exampleFormControlInput1" class="form-label">Full Name</label>
                        <div class="input-group mb-3">
                            <input type="text" name="full_name" id="fullNameInput" value="{{ old('full_name') }}"
                                class="form-control" aria-label="Recipient's username" aria-describedby="basic-addon2">
                            <i class="fas fa-search input-group-text bg-info text-white" onclick="openMembers()"
                                style="cursor: pointer;"></i>
                        </div>
                    </div>
                    <div class="col-xl-6">
                        <div class="mb-3">
                            <label for="exampleFormControlInput1" class="form-label">Phone Number</label>
                            <input type="text" id="phoneInput" name="phone_number" value="{{ old('phone_number') }}"
                                class="form-control" id="exampleFormControlInput1" autocomplete="off" required>
                        </div>
                    </div>
                    <div class="col-xl-6" id="member_package">
                        <div class="mb-3">
                            <label for="exampleFormControlInput1" class="form-label">Member Package</label>
                            <select id="single-select3" name="member_package_id" class="form-control">
                                <option>
                                    <- Choose ->
                                </option>
                                @foreach ($memberPackage as $item)
                                    <option value="{{ $item->id }}">{{ $item->package_name }}</option>
                                @endforeach
                            </select>
                            {{-- <select id="single-select2" name="member_package_id" class="form-control" required>
                                <option value="{{ $memberPackage->id }}">{{ $memberPackage->package_name }} |
                                    {{ $memberPackage->days }} Days |
                                    {{ formatRupiah($memberPackage->package_price) }} |
                                    {{ formatRupiah($memberPackage->admin_price) }}</option>
                            </select> --}}
                        </div>
                    </div>
                    <div class="col-xl-6" id="method_payment">
                        <div class="mb-3">
                            <label for="exampleFormControlInput1" class="form-label">Method Payment</label>
                            <select id="single-select3" name="method_payment_id" class="form-control">
                                <option>
                                    <- Choose ->
                                </option>
                                @foreach ($methodPayment as $item)
                                    <option value="{{ $item->id }}">{{ $item->name }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <div class="col-xl-6" id="description">
                        <div class="mb-3">
                            <label for="exampleFormControlTextarea1" class="form-label text-primary">
                                Description
                            </label>
                            <textarea class="form-control" name="description" id="exampleFormControlTextarea1" rows="6"
                                placeholder="Enter Description">{{ old('description') }}</textarea>
                        </div>
                    </div>
                    <input class="form-check-input" type="hidden" name="status" value="one_day_visit">
                    <div class="d-flex justify-content-between">
                        <button type="submit" id="btnSubmit" class="btn btn-primary">Save</button>
                    </div>
                </div>
        </div>
        </form>
    </div>
</div>
</div>

<div class="modal fade" id="memberModal" tabindex="-1" aria-labelledby="memberModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="memberModalLabel">Member 1 Day Visit</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <input type="text" id="searchInput" class="form-control" placeholder="Search..."
                        onkeyup="filterMembers()">
                </div>
                <table class="table" id="memberTable" border="1">
                    <thead>
                        <tr>
                            <th scope="col">Full Name</th>
                            <th scope="col">Phone Number</th>
                            <th scope="col">Action</th>
                        </tr>
                    </thead>
                    <tbody id="memberTableBody">
                    </tbody>
                </table>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<script>
    document.getElementById('submitButton').addEventListener('click', function() {
        document.getElementById('addMemberForm').submit();
    });
</script>

<script>
    function openMembers(source) {
        $.ajax({
            url: "{{ route('openMembers') }}",
            type: "GET",
            dataType: "json",
            success: function(data) {
                $("#memberTableBody").empty();
                data.forEach(function(member) {
                    var memberRow = $('<tr>' +
                        '<td>' + member.full_name + '</td>' +
                        '<td>' + member.phone_number + '</td>' +
                        '<td>' +
                        '<button class="btn btn-primary member-button btn-xxs" data-id="' +
                        member.id + '" data-name="' +
                        member.full_name +
                        '" data-phone="' +
                        member.phone_number +
                        '" data-source="' + source +
                        '" onclick="selectMember(this)">Select</button>' +
                        '</td>' +
                        '</tr>');
                    $("#memberTableBody").append(memberRow);
                });
                $("#memberModal").modal("show");
            },
            error: function(xhr, status, error) {
                console.error(xhr.responseText);
            },
        });
    }

    const selectMember = (singleData) => {
        document.getElementById("memberIdInput").value = singleData.getAttribute("data-id")
        document.getElementById("fullNameInput").value = singleData.getAttribute("data-name")
    };

    function filterMembers() {
        var input, filter, table, tr, td, i, txtValue;
        input = document.getElementById("searchInput");
        filter = input.value.toUpperCase();
        table = document.getElementById("memberTable");
        tr = table.getElementsByTagName("tr");
        for (i = 0; i < tr.length; i++) {
            td = tr[i].getElementsByTagName("td")[0]; // Change index if you want to search other columns
            if (td) {
                txtValue = td.textContent || td.innerText;
                if (txtValue.toUpperCase().indexOf(filter) > -1) {
                    tr[i].style.display = "";
                } else {
                    tr[i].style.display = "none";
                }
            }
        }
    }
</script>
