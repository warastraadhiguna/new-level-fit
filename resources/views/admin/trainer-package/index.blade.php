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
                                <th data-orderable="false">Branch & Staff</th>
                                <th data-orderable="false">Session & Duration</th>
                                <th data-orderable="false">Price</th>
                                @if (Auth::user()->isAdmin())
                                    <th data-orderable="false">Action</th>
                                @endif
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($trainerPackage as $item)
                                <tr>
                                    <td>
                                        <h6>{{ $item->package_name }}</h6>
                                        @if ($item->is_free)
                                            <span class="badge badge-success">PT Free</span>
                                        @elseif ($item->status == 'LGT')
                                            <span class="badge badge-info">LGT</span>
                                        @else
                                            <span class="badge badge-primary">Non LGT</span>
                                        @endif
                                        @if ($item->description)
                                            <small class="text-muted d-block mt-1">{{ $item->description }}</small>
                                        @endif
                                    </td>
                                    <td>
                                        <h6 class="mb-1">{{ $item->branchStore->name }}</h6>
                                        <small class="text-muted">Staff: {{ $item->users->full_name }}</small>
                                    </td>
                                    <td>
                                        <h6 class="mb-1">Session: {{ $item->number_of_session }}</h6>
                                        <small class="text-muted">Days: {{ $item->days }}</small>
                                    </td>
                                    <td>
                                        <h6 class="mb-1">Package: {{ formatRupiah($item->package_price) }}</h6>
                                        <small class="text-muted">Admin: {{ formatRupiah($item->admin_price) }}</small>
                                    </td>
                                    @if (Auth::user()->isAdmin())
                                        <td>
                                            <div>
                                                <button type="button"
                                                    class="btn light btn-warning btn-xs mb-1 btn-block"
                                                    data-bs-toggle="modal"
                                                    data-bs-target="#modalEdit{{ $item->id }}">
                                                    Edit
                                                </button>
                                                <form action="{{ route('trainer-package.destroy', $item->id) }}"
                                                    data-delete-detail="{{ $item->package_name }} | {{ $item->branchStore->name }} | {{ $item->number_of_session }} sessions | {{ $item->days }} days"
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
