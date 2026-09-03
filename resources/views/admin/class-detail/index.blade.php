<div class="row">
    <div class="col-xl-12">
        <div class="row">
            <div class="col-xl-12">
                <div class="page-title flex-wrap justify-content-between">
                    <div class="col-xl-12">
                        <div class="mb-5">
                            <h1>Class Schedule</h1>
                        </div>
                    </div>           
                    
                    <div class="col-xl-6">
                        <div class="mb-3 mr-2">
                            <label for="exampleFormControlInput1" class="form-label">Name</label>
                            <input type="text" name="name" value="{{ old('name', $classSchedule->name) }}"
                                class="form-control" id="exampleFormControlInput1" autocomplete="off" readonly>
                        </div>
                    </div>                          
                    <div class="col-xl-6">
                        <div class="mb-3">
                            <label for="exampleFormControlInput1" class="form-label">Instructor</label>
                            <input type="text" name="price" value="{{ old('price', $classSchedule->classInstructor->full_name) }}"
                                class="form-control" id="exampleFormControlInput1" autocomplete="off"
                                readonly>       
                        </div>
                    </div>                        
                    <div class="col-xl-6">
                        <div class="mb-3">
                            <label for="exampleFormControlInput1" class="form-label">Capacity</label>
                            <input type="number" name="capacity" value="{{ old('capacity', $classSchedule->capacity) }}" class="form-control"
                                id="exampleFormControlInput1" autocomplete="off" readonly>
                        </div>
                    </div>                        
                    <div class="col-xl-6">
                        <div class="mb-3">
                            <label for="exampleFormControlInput1" class="form-label">Price</label>
                            <input type="text" name="price" value="{{ old('price', $classSchedule->price) }}"
                                class="form-control rupiah" id="exampleFormControlInput1" autocomplete="off"
                                readonly>
                        </div>
                    </div>                              
                    <div class="col-xl-6">
                        <div class="mb-3">
                            <label for="exampleFormControlTextarea1" class="form-label text-primary">
                                Description
                            </label>
                            <textarea class="form-control" name="note" id="exampleFormControlTextarea1" rows="3"
                                placeholder="Enter Description" readonly>{{ old('note', $classSchedule->note) }}</textarea>
                        </div>
                    </div>                     
                </div>
            </div>        
        </div>
        <div class="row">
            <div class="col-xl-12">
                <div class="page-title flex-wrap justify-content-between">
                    <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#modalAdd">
                        + New Class Detail
                    </button>
                    {{-- <a href="{{ route('dataSoft') }}" class="btn btn-secondary">Old Class Detail</a> --}}
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
                                <th>Phone</th>
                                <th>Email</th>
                                <th>User</th>          
                                <th>Status</th>                                                                                                     
                                @if (Auth::user()->isAdmin())
                                    <th>Action</th>
                                @endif
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($classDetails as $item)
                                <tr>
                                    <td>
                                        <h6>{{ $item->name . ($item->member? "(". $item->member->member_code .")" : "") }}</h6>
                                    </td>                             
                                    <td>
                                        <h6>{{ $item->phone }}</h6>
                                    </td>
                                    <td>
                                        <h6>{{ $item->email }}</h6>
                                    </td>
                                    <td>
                                        <h6>{{ $item->user->full_name }}</h6>
                                    </td>    
                                    <td>
                                        <h6>{{ $item->status == "1"? "Continue" : "Canceled" }}</h6>
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

                                                <form action="{{ route('class-detail.destroy', $item->id) }}"
                                                    onclick="return confirm('Delete Class Detail Data ? ')"
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

@include('admin.class-detail.create')
@foreach ($classDetails as $item)
    @include('admin.class-detail.edit')    
@endforeach