<div class="row">
    <div class="col-xl-12">
        <div class="row">
            <div class="col-xl-12">
                <div class="page-title flex-wrap justify-content-between">
                    <button type="button" class="btn btn-info" data-bs-toggle="modal" data-bs-target="#ptFreeExcelModal">Download Excel</button>
                </div>
            </div>
            <div class="col-xl-12 wow fadeInUp" data-wow-delay="1.5s">
                <div class="table-responsive full-data">
                    <table class="table-responsive-lg table display dataTablesCard student-tab dataTable no-footer" id="myTable">
                        <thead>
                            <tr><th>No</th><th>Member Data</th><th>Session</th><th>Payment</th><th>Trainer</th><th>Action</th></tr>
                        </thead>
                        <tbody>
                            @foreach ($trainerSessions as $item)
                                <tr>
                                    <td>{{ $loop->iteration }}</td>
                                    <td>
                                        <h6>{{ $item->member_name }},</h6>
                                        <h6>{{ $item->member_code }}</h6><br>
                                        <h6>{{ $item->branch_store_name }}</h6>
                                    </td>
                                    <td>
                                        <span class="badge badge-primary d-inline-block">{{ $item->package_name }}</span>
                                        <h6>Session Total : {{ $item->ts_number_of_session }}</h6>
                                    </td>
                                    <td><span class="badge badge-primary badge-lg">Free</span></td>
                                    <td><h6>{{ $item->trainer_name ?: '-' }}</h6></td>
                                    <td>
                                        <div class="btn-group dropstart" role="group">
                                            <button type="button" class="btn btn-primary btn-xs dropdown-toggle" style="width: 100px" data-bs-toggle="dropdown">Action</button>
                                            <ul class="dropdown-menu">
                                                @if (Auth::user()->isAdmin())
                                                    <li><a href="{{ route('pt-free.edit', $item->id) }}" class="btn light btn-warning btn-xs mb-1 btn-block">Edit</a></li>
                                                @endif
                                                <li><a href="{{ route('pt-free.show', $item->id) }}" class="btn light btn-info btn-xs mb-1 btn-block">Detail</a></li>
                                                @if (Auth::user()->isAdmin())
                                                    <li>
                                                        <form action="{{ route('pt-free.destroy', $item->id) }}" method="POST" onsubmit="return confirm('Delete Data ?')">
                                                            @csrf @method('DELETE')
                                                            <button type="submit" class="btn light btn-danger btn-xs mb-1 btn-block">Delete</button>
                                                        </form>
                                                    </li>
                                                @endif
                                            </ul>
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
</div>

@include('admin.pt-free.partials.excel-modal')
