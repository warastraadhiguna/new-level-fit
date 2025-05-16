<div class="row">
    <div class="col-xl-12">
        <div class="row">
            <div class="col-xl-12">
                <div class="page-title flex-wrap">
                    <div>
                        <a href="{{ route('class.create') }}" class="btn btn-primary"><i class="fa fa-plus"></i> New
                            Class</a>
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
                                <th>Date & Time</th>
                                <th>Class Name</th>
                                <th>Instructor Name</th>
                                <th>Member Total</th>
                                <th>Class Price</th>
                                <th>Staff Name</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($class as $item)
                                <tr>
                                    <td>{{ $item->date_time }}</td>
                                    <td>{{ $item->class_name }}</td>
                                    <td>
                                        {{ !empty($item->classInstructor->full_name) ? $item->classInstructor->full_name : 'Class Instructor has  been deleted' }}
                                    </td>
                                    <td>{{ $item->member_total }}</td>
                                    <td>{{ formatRupiah($item->class_price) }}</td>
                                    <td>
                                        {{ !empty($item->users->full_name) ? $item->users->full_name : 'Staff has  been deleted' }}
                                    </td>
                                    <td>
                                        <a href="{{ route('class.edit', $item->id) }}"
                                            class="btn light btn-warning btn-xs mb-1 btn-block">Edit</a>
                                        <form action="{{ route('class.destroy', $item->id) }}"
                                            onclick="return confirm('Delete Data ?')" method="POST">
                                            @method('delete')
                                            @csrf
                                            <button type="submit"
                                                class="btn light btn-danger btn-xs btn-block">Delete</button>
                                        </form>
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
