<!-- Modal Add -->
<div class="modal fade bd-example-modal-lg" id="modalAddClassInstructor" tabindex="-1" aria-labelledby="exampleModalLabel"
    aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <form action="{{ route('class-instructor.store') }}" method="POST">
                @csrf
                <input type="hidden" name="page" value="ci">
                <div class="modal-header">
                    <h1 class="modal-title fs-5" id="exampleModalLabel">Create Class Instructor</h1>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="row">
                        <div class="col-xl-6">
                            <div class="mb-3">
                                <label for="exampleFormControlInput1" class="form-label">Full Name</label>
                                <input type="text" name="full_name" value="{{ old('full_name') }}"
                                    class="form-control" id="exampleFormControlInput1" autocomplete="off" required>
                            </div>
                        </div>
                        <div class="col-xl-6">
                            <div class="mb-3">
                                <label for="exampleFormControlInput1" class="form-label">Gender</label>
                                <select name="gender" class="form-control" aria-label="Default select example" required
                                    autocomplete="off">
                                    <option disabled selected value>
                                        <- Choose ->
                                    </option>
                                    <option value="Male">Male</option>
                                    <option value="Female">Female</option>
                                </select>
                            </div>
                        </div>
                        <div class="col-xl-6">
                            <div class="mb-3">
                                <label for="exampleFormControlInput1" class="form-label">Phone Number</label>
                                <input type="text" name="phone_number" value="{{ old('phone_number') }}"
                                    class="form-control" id="exampleFormControlInput1" autocomplete="off" required>
                            </div>
                        </div>

                        <div class="col-xl-6">
                            <div class="mb-3">
                                <label for="exampleFormControlTextarea1" class="form-label text-primary">
                                    Description
                                </label>
                                <textarea class="form-control" name="description" id="exampleFormControlTextarea1" rows="6"
                                    placeholder="Enter Description">{{ old('description') }}</textarea>
                            </div>
                        </div>

                    </div>
                </div>
                <div class="modal-footer">
                    <button type="submit" class="btn btn-primary">Save</button>
                    <button type="button" class="btn btn-danger light" data-bs-dismiss="modal">Close</button>
                </div>
            </form>
        </div>
    </div>

<!-- Modal Edit -->
@foreach ($classInstructors as $item)
    <div class="modal fade bd-example-modal-lg" id="modalEditClassInstructor{{ $item->id }}" tabindex="-1"
        aria-labelledby="exampleModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <form action="{{ route('class-instructor.update', $item->id) }}" method="POST">
                    @method('PUT')
                    @csrf
                    <input type="hidden" name="page" value="ci">
                    <div class="modal-header">
                        <h1 class="modal-title fs-5" id="exampleModalLabel">Edit Class Instructor</h1>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <div class="row">
                            <div class="col-xl-6">
                                <div class="mb-3">
                                    <label for="exampleFormControlInput1" class="form-label">Full Name</label>
                                    <input type="text" name="full_name"
                                        value="{{ old('full_name', $item->full_name) }}" class="form-control"
                                        id="exampleFormControlInput1" required autocomplete="off">
                                </div>
                            </div>
                            <div class="col-xl-6">
                                <div class="mb-3">
                                    <label for="exampleFormControlInput1" class="form-label">Gender</label>
                                    <select name="gender" class="form-control" aria-label="Default select example">
                                        <option value="{{ $item->gender }}" selected>
                                            {{ old('gender', $item->gender) }}
                                        </option>
                                        <option value="Male">Male</option>
                                        <option value="Female">Female</option>
                                    </select>
                                </div>
                            </div>
                            <div class="col-xl-6">
                                <div class="mb-3">
                                    <label for="exampleFormControlInput1" class="form-label">Phone Number</label>
                                    <input type="text" name="phone_number"
                                        value="{{ old('phone_number', $item->phone_number) }}" class="form-control"
                                        id="exampleFormControlInput1" autocomplete="off" required>
                                </div>
                            </div>
                            <div class="col-xl-6">
                                <div class="mb-3">
                                    <label for="exampleFormControlTextarea1" class="form-label text-primary">
                                        Description
                                    </label>
                                    <textarea class="form-control" name="description" id="exampleFormControlTextarea1" rows="6"
                                        placeholder="Enter Description">{{ old('description', $item->description) }}</textarea>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="submit" class="btn btn-primary">Update</button>
                        <button type="button" class="btn btn-danger light" data-bs-dismiss="modal">Close</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endforeach



<div class="tab-pane fade {{ $page == 'ci' ? 'show active' : '' }}" id="classInstructor" role="tabpanel">
    <div class="card">
        <div class="card-body">
            <div class="col-xl-12">
                <h4>Class Instructor List</h4>
            </div>
        </div>
    </div>
    <div class="row">
        <div class="col-xl-12">
            <div class="row">
                @if ($pageType == "new")                     
                <div class="col-xl-12">
                    <div class="page-title flex-wrap">
                        <div>                            
                            <button type="button" class="btn btn-primary" data-bs-toggle="modal"
                                data-bs-target="#modalAddClassInstructor">
                                + New Class Instructor
                            </button>
                        </div>
                    </div>
                </div>
                @endif 
                </div>                
                <!--column-->
                <div class="col-xl-12 wow fadeInUp" data-wow-delay="1.5s">
                    <div class="table-responsive full-data">
                        <table class="table-responsive-lg table display dataTablesCard student-tab dataTable no-footer"
                            id="myTable">
                            <thead>
                                <tr>
                                    <th>No</th>                                  
                                    <th>Full Name</th>
                                    <th>Phone Number</th>
                                    <th>Gender</th>
                                    <th>Description</th>
                                    @if (Auth::user()->isAdmin())
                                        <th>Action</th>
                                    @endif
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($classInstructors as $item)
                                    <tr>                                        
                                        <td>
                                            <h6>{{ $loop->iteration }}</h6>
                                        </td>                                     
                                        <td>
                                            <h6>{{ $item->full_name }}</h6>
                                        </td>
                                        <td>
                                            <h6>{{ $item->phone_number }}</h6>
                                        </td>
                                        <td>
                                            <h6>{{ $item->gender }}</h6>
                                        </td>
                                        <td>
                                            <h6>{{ $item->description }}</h6>
                                        </td>
                                        @if (Auth::user()->isAdmin())
                                            <td>
                                                <div>
                                                @if ($pageType == "new")                                                          
                                                    <button type="button"
                                                        class="btn light btn-warning btn-xs mb-1 btn-block"
                                                        data-bs-toggle="modal"
                                                        data-bs-target="#modalEditClassInstructor{{ $item->id }}">
                                                        Edit
                                                    </button>
                                                    <form action="{{ route('class-instructor.destroy', $item->id) }}"
                                                        onclick="return confirm('Hapus Data {{ $item->full_name }}? ')"
                                                        method="POST">
                                                        @method('delete')
                                                        @csrf
                                                        <input type="hidden" name="page" value="ci">
                                                        <button type="submit"
                                                            class="btn light btn-danger btn-xs btn-block">Delete</button>
                                                    </form>
                                                @else
                                                    <a href="{{ route('restore-class-instructor', $item->id) }}" onclick="return confirm('Restore Data?')" class="btn light btn-warning btn-xs btn-block">Restore</a>
                                                    <form action="{{ route('class-instructor-force-delete', $item->id) }}"
                                                        onclick="return confirm('Permanently delete? ')"
                                                        method="POST">
                                                        @method('delete')
                                                        @csrf
                                                        <button type="submit"
                                                            class="btn light btn-danger btn-xs btn-block">Force Delete</button>
                                                    </form>                                                    
                                                @endif                                                       
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
</div>
