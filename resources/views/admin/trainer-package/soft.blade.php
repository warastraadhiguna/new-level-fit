<div class="row">
    <div class="col-xl-12">
        <div class="row">
            <div class="col-xl-12">
                <div class="page-title flex-wrap">
                    <div class="d-flex justify-content-around">
                        <a href="{{ route('trainer-package.index') }}" class="btn btn-primary">Kembali</a>
                    </div>
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
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($trainerPackages as $item)
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
                                    <td>
                                        <a href="{{ route('restore-trainer-package-data', $item->id) }}" onclick="return confirm('Restore data?')" class="btn light btn-warning btn-xs btn-block">Restore</a>
                                        <form action="{{ route('trainer-packages-force-delete', $item->id) }}"
                                            onclick="return confirm('Permanently delete? ')"
                                            method="POST">
                                            @method('delete')
                                            @csrf
                                            <button type="submit"
                                                class="btn light btn-danger btn-xs btn-block">Force Delete</button>
                                        </form>
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
