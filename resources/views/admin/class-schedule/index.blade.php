<div class="row">
    <div class="col-xl-12">
        <div class="row">
            <div class="col-xl-12">
                <div class="page-title flex-wrap justify-content-between">
                    <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#modalAdd">
                        + New Class Schedule
                    </button>
                    {{-- <a href="{{ route('dataSoft') }}" class="btn btn-secondary">Old Class Schedule</a> --}}
                </div>
            </div>
            <!--column-->
            <div class="col-xl-12 wow fadeInUp" data-wow-delay="1.5s">
                <div class="table-responsive full-data">
                    <table class="table-responsive-lg table display dataTablesCard student-tab dataTable no-footer"
                        id="myTable">
                        <thead>
                            <tr>
                                <th>Name</th>           
                                <th>Capacity</th>
                                <th>Price</th>
                                <th>Description</th>
                                <th>Instructor</th>        
                                <th>Real Capacity</th>        
                                <th>Status</th>                                                                                                     
                                @if (Auth::user()->isAdmin())
                                    <th>Action</th>
                                @endif
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($classSchedules as $item)
                                <tr>
                                    <td>
                                        <h6>{{ $item->name }}</h6>
                                    </td>                             
                                    <td>
                                        <h6>{{ $item->capacity }}</h6>
                                    </td>
                                    <td>
                                        <h6>{{ formatRupiah($item->price) }}</h6>
                                    </td>
                                    <td>
                                        <h6>{{ $item->note }}</h6>
                                    </td>
                                    <td>
                                        <h6>{{ $item->classInstructor->full_name }}</h6>
                                    </td>          
                                    <td>
                                        <h6>{{ $item->real_capacity }}</h6>
                                    </td>
                                    <td>
                                        <h6>{{ $item->is_active == "1"? "Active" : "Inactive" }}</h6>
                                    </td>                                                                                                  
                                    @if (Auth::user()->isAdmin())
                                        <td>
                                            <div>
                                                <a href="{{ route("class-detail.index", "class-schedule=" . $item->id) }}" class="btn light btn-primary btn-xs mb-1 btn-block">Detail</a>
                                                <button type="button"
                                                    class="btn light btn-warning btn-xs mb-1 btn-block"
                                                    data-bs-toggle="modal"
                                                    data-bs-target="#modalEdit{{ $item->id }}">
                                                    Edit
                                                </button>

                                                <form action="{{ route('class-schedule.destroy', $item->id) }}"
                                                    onclick="return confirm('Delete Class Schedule Data ? ')"
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

@include('admin.class-schedule.create')
@foreach ($classSchedules as $item)
    @include('admin.class-schedule.edit')    
@endforeach