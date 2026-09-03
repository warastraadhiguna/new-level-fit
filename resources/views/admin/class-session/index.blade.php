<div class="row">
    <div class="col-xl-12">
        <div class="row">
            <div class="col-xl-12">
                <div class="page-title flex-wrap justify-content-between">
                    <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#modalAdd">
                        + New Class Session
                    </button>
                    {{-- <a href="{{ route('dataSoft') }}" class="btn btn-secondary">Old Class Session</a> --}}
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
                                @if (Auth::user()->isAdmin())
                                    <th>Action</th>
                                @endif
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($classSessions as $item)
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
                                    @if (Auth::user()->isAdmin())
                                        <td>
                                            <div>
                                                <button type="button"
                                                    class="btn light btn-warning btn-xs mb-1 btn-block"
                                                    data-bs-toggle="modal"
                                                    data-bs-target="#modalEdit{{ $item->id }}">
                                                    Edit
                                                </button>
                                             
                                                <form action="{{ route('class-session.destroy', $item->id) }}"
                                                    onclick="return confirm('Delete Class Session Data ? ')"
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

@include('admin.class-session.create')
@foreach ($classSessions as $item)
    @include('admin.class-session.edit')    
@endforeach