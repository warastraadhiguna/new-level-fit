<div class="row">
    <div class="col-xl-12">
        <div class="row">
            <div class="col-xl-12">
                <div class="page-title flex-wrap">
                    <div class="d-flex justify-content-around">
                        <a href="{{ route('member-package.index') }}" class="btn btn-primary">Kembali</a>
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
                                <th>Number Of Months</th>
                                <th>Package Price</th>
                                <th>Admin Price</th>
                                <th>Description</th>
                                <th>Staff</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($memberPackages as $item)
                                <tr>
                                    <td>
                                        <h6>{{ $item->package_name }}</h6>
                                    </td>
                                    <td>
                                        <h6>{{ $item->days }}</h6>
                                    </td>
                                    <td>
                                        <h6>{{ formatRupiah($item->package_price) }}</h6>
                                    </td>
                                    <td>
                                        <h6>{{ formatRupiah($item->admin_price) }}</h6>
                                    </td>
                                    <td>
                                        <h6>{{ $item->description }}</h6>
                                    </td>
                                    <td>
                                        <h6>{{ $item->users->full_name }}</h6>
                                    </td>
                                    <td>
                                        <a href="{{ route('restore-member-package-data', $item->id) }}" onclick="return confirm('Delete Data ?')" class="btn light btn-warning btn-xs btn-block">Restore</a>
                                        <form action="{{ route('member-packages-force-delete', $item->id) }}"
                                            onclick="return confirm('Hapus Permanen Data ? ')"
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