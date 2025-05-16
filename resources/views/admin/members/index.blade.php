<div class="row">
    <div class="col-xl-12">
        <div class="row">
            <div class="col-xl-12">
                <div class="page-title flex-wrap justify-content-between">
                    <button type="button" class="btn btn-info" data-bs-toggle="modal" data-bs-target="#exampleModal">
                        Download Excel
                    </button>
                </div>
            </div>
            <!--column-->
            <div class="col-xl-12 wow fadeInUp" data-wow-delay="1.5s">
                <div class="table-responsive full-data">
                    <table class="table-responsive-lg table display dataTablesCard student-tab dataTable no-footer"
                        id="myTable">
                        <thead>
                            <tr>
                                <th>No</th>
                                <th>Image</th>
                                <th>Full Name</th>
                                <th>Phone Number</th>
                                <th>No Member</th>
                                <th>Date of Birth</th>
                                <th>Created At</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($members as $item)
                                <tr>
                                    <td>{{ $loop->iteration }}</td>
                                    <td>
                                        <div class="trans-list">
                                            @if ($item->photos)
                                                <img src="{{ Storage::url($item->photos ?? '') }}" class="lazyload"
                                                    width="100" alt="image">
                                            @else
                                                <img src="{{ asset('default.png') }}" width="100" class="img-fluid"
                                                    alt="">
                                            @endif
                                        </div>
                                    </td>
                                    <td>
                                        <h6>{{ $item->full_name }}</h6>
                                    </td>
                                    <td>
                                        <h6>{{ $item->phone_number ?? 'No Data' }}</h6>
                                    </td>
                                    <td>
                                        <h6>{{ $item->member_code ?? 'No Data' }}</h6>
                                    </td>
                                    <td>
                                        <h6>{{ DateFormat($item->born, 'DD MMMM YYYY') ?? 'No Data' }}</h6>
                                    </td>
                                    <td>
                                        <h6>{{ DateFormat($item->created_at, 'DD MMMM YYYY') ?? 'No Data' }}</h6>
                                    </td>
                                    <td>
                                        <div>
                                            <a href="{{ route('edit-member-sell', $item->id) }}"
                                                class="btn light btn-warning btn-xs btn-block mb-1">Edit
                                                Member</a>
                                            <a href="{{ route('members.show', $item->id) }}"
                                                class="btn light btn-info btn-xs btn-block mb-1">Detail Member</a>
                                            {{-- @if ($item->lo_status == 'Running' && $item->lo_is_used == 0) --}}
                                            @if (   $item->lo_is_used == 0)
                                                <a href="{{ route('useLayoutOrientation', $item->id) }}"
                                                    class="btn btn-dark btn-xs mb-1 btn-block">LO</a>
                                            @else
                                                @if (!$item->lo_end)
                                                    <form action="{{ route("stopLayoutOrientation", $item->id) }}" method="POST">
                                                        @csrf
                                                        <button type="submit" class="btn btn-dark btn-xs mb-1 btn-block">Stop LO(Running)</button>
                                                    </form>
                                                @else
                                                    <button type="button" class="btn btn-dark btn-xs mb-1 btn-block"
                                                        data-bs-toggle="popover" data-bs-title="Check In tanpa kartu"
                                                        data-bs-content="Member ini sudah menggunakan Layout Orientation">
                                                        <span class="text-danger">X</span> LO is used<span
                                                            class="text-danger">X</span>
                                                    </button>
                                                @endif
                                            @endif
                                            @if (Auth::user()->role == 'ADMIN')
                                                <form action="{{ route('member.destroy', $item->id) }}"
                                                    onclick="return confirm('Delete Data ?')" method="POST">
                                                    @method('delete')
                                                    @csrf
                                                    <button type="submit"
                                                        class="btn light btn-danger btn-xs btn-block mb-1">Delete</button>
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
            <!--/column-->
        </div>
    </div>
</div>

{{-- MODAL --}}
<div class="modal fade" id="exampleModal" tabindex="-1" aria-labelledby="exampleModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h1 class="modal-title fs-5" id="exampleModalLabel">Download Excel by Date</h1>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="row">
                    <div class="col-6">
                        <div class="mb-3">
                            <label class="form-label">From Date</label>
                            <input type="date" id="fromDate" class="form-control">
                        </div>
                    </div>
                    <div class="col-6">
                        <div class="mb-3">
                            <label class="form-label">To Date</label>
                            <input type="date" id="toDate" class="form-control">
                        </div>
                    </div>
                </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" onclick="reloadPage()" class="btn btn-primary">Download</button>
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>


<script>
    function reloadPage() {
        var fromDate = document.getElementById("fromDate").value;
        var toDate = document.getElementById("toDate").value;

        window.open(window.location.href + '?excel=1&fromDate=' + fromDate + '&toDate=' + toDate, '_self');
    }
</script>
