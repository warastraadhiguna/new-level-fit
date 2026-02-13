<div class="row mb-5">
    <div class="col-xl-12">
        <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#checkIn2"
                        id="checkInButton">
            Input Card Number
        </button>
    </div>
</div>

<div class="modal fade bd-example-modal-sm" id="checkIn2" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-sm">
        <div class="modal-content">
            <form action="{{ route('member-check-in.store') }}" method="POST" enctype="multipart/form-data" autocomplete="off">
                @csrf
                <div class="modal-header">
                    <h1 class="modal-title fs-5" id="exampleModalLabel">Input Card Number</h1>
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
                        <input type="hidden" name="card_number">
                        <div class="col-xl-12">
                            <div class="mb-3">
                                <p>Card Number</p>
                                <input type="text" name="card_number" id="memberCode" class="form-control" autofocus>
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

<div class="row">
    <div class="col-xl-12 wow fadeInUp" data-wow-delay="1.5s">
        <div class="table-responsive full-data">
            <table class="table table-bordered" border="1" style="text-align: center;" height="2px" width="100%">
                <thead>
                    <tr>
                        <th>No</th>
                        <th>Member Name</th>
                        <th>Branch</th>
                        <th>Check In Time</th>
                        <th>Check Out Time</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($results as $item)
                        <tr>
                            <td>{{  $loop->iteration }}</td>
                            <td>{{ $item->member_name }}</td>
                            <td>{{ $item->branch_store_name }}</td>
                            <td>{{ DateFormat($item->check_in_time, 'DD MMMM YYYY, HH:mm:ss') }}</td>
                            <td>{{ $item->check_out_time? DateFormat($item->check_out_time, 'DD MMMM YYYY, HH:mm:ss') : "-" }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>