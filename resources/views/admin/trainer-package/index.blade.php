<div class="row">
    <div class="col-xl-12">
        <div class="row">
            <div class="col-xl-12">
                <div class="page-title flex-wrap justify-content-between">
                    <div>
                        <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#modalAdd">
                            + New Trainer Package
                        </button>
                    </div>
                    <a href="{{ route('trainer-package-data-soft') }}" class="btn btn-secondary">Old Trainer Package</a>
                </div>
            </div>
            <!--column-->
            <div class="col-xl-12 wow fadeInUp" data-wow-delay="1.5s">
                <div class="table-responsive full-data">
                    <table class="table-responsive-lg table display dataTablesCard student-tab dataTable no-footer"
                        id="myTable">
                        <thead>
                            <tr>
                                <th>Package Name</th>
                                <th>Branch</th>                                  
                                <th>Session / Days</th>
                                <th>Price / Admin</th>
                                <th>Package Type</th>
                                <th>Description</th>
                                <th>Staff</th>
                                @if (Auth::user()->role == 'ADMIN')
                                    <th>Action</th>
                                @endif
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($trainerPackage as $item)
                                <tr>
                                    <td>
                                        <h6>{{ $item->package_name }}</h6>
                                    </td>
                                    <td>
                                        <h6>{{ $item->branchStore->name }}</h6>
                                    </td>                                       
                                    <td>
                                        <h6 class="mb-1">Session: {{ $item->number_of_session }}</h6>
                                        <h6 class="mb-0">Days: {{ $item->days }}</h6>
                                    </td>
                                    <td>
                                        <h6 class="mb-1">Package: {{ formatRupiah($item->package_price) }}</h6>
                                        <h6 class="mb-0">Admin: {{ formatRupiah($item->admin_price) }}</h6>
                                    </td>
                                    <td>
                                        <h6>{{ $item->status == 'LGT' ? 'LGT' : 'Non LGT' }}</h6>
                                    </td>
                                    <td>
                                        <h6>{{ $item->description }}</h6>
                                    </td>
                                    <td>
                                        <h6>{{ $item->users->full_name }}</h6>
                                    </td>
                                    @if (Auth::user()->role == 'ADMIN')
                                        <td>
                                            <div>
                                                <button type="button"
                                                    class="btn light btn-warning btn-xs mb-1 btn-block"
                                                    data-bs-toggle="modal"
                                                    data-bs-target="#modalEdit{{ $item->id }}">
                                                    Edit
                                                </button>
                                                <form action="{{ route('trainer-package.destroy', $item->id) }}"
                                                    onclick="return confirm('Delete Trainer Package Data ? ')"
                                                    method="POST">
                                                    @method('delete')
                                                    @csrf
                                                    <button type="submit"
                                                        class="btn light btn-danger btn-xs btn-block">Delete</button>
                                                </form>
                                            </div>
                                        </td>
                                    @endif
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
@include('admin.trainer-package.create')
@include('admin.trainer-package.edit')
@include('admin.partials.package-submit-guard')
