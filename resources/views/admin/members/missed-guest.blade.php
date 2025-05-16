<div class="row">
    <div class="col-xl-12">
        <div class="page-title flex-wrap justify-content-between">
            <button type="button" class="btn btn-info" data-bs-toggle="modal" data-bs-target="#exampleModal">
                Download Excel
            </button>
        </div>
    </div>
    <div class="col-xl-12">
        <div class="row">
            @csrf
            @method('delete')
            <!--column-->
            <div class="col-xl-12 wow fadeInUp" data-wow-delay="1.5s">
                <div class="table-responsive full-data">
                    <table class="table-responsive-lg table display dataTablesCard student-tab dataTable no-footer"
                        id="myTable">

                        <thead>
                            <tr>
                                <th>No</th>
                                <th>Member Name</th>
                                <th>Phone Number</th>
                                <th>FC Name</th>
                                <th>Cancellation Note</th>
                                <th>Created At</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($members as $item)
                                <tr>
                                    <td>{{ $loop->iteration }}</td>
                                    <td>
                                        <h6>{{ $item->full_name }}</h6>
                                    </td>
                                    <td>
                                        <h6>{{ $item->phone_number }}</h6>
                                    </td>
                                    <td>
                                        <h6>{{ isset($item->fitnessConsultant->full_name) ? $item->fitnessConsultant->full_name : '-' }}
                                        </h6>
                                    </td>
                                    <td>
                                        <h6>{{ isset($item->cancellation_note) ? $item->cancellation_note : '-' }}
                                        </h6>
                                    </td>
                                    <td>
                                        <h6>{{ DateFormat($item->created_at, 'DD MMMM YYYY') }}</h6>
                                    </td>
                                    <td>
                                        <div>
                                            {{-- <a href="{{ route('appointment', $item->id) }}"
                                                class="btn light btn-info btn-xs btn-block mb-1">Appointment</a>
                                                <a href="{{ route('appointment', $item->id) }}"
                                                    class="btn light btn-primary btn-xs btn-block mb-1">Appointment Schedule</a> --}}
                                            <a href="{{ route('members.edit', $item->id) }}"
                                                class="btn light btn-warning btn-xs btn-block mb-1">Edit</a>
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
