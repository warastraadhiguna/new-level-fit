<!-- Modal Add -->
<div class="modal fade bd-example-modal-lg" id="modalAdd" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <form action="{{ route('class-session.store') }}" method="POST">
                @csrf
                <div class="modal-header">
                    <h1 class="modal-title fs-5" id="exampleModalLabel">Create Class Session</h1>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    @if ($errors->any())
                        <div class="alert alert-danger">
                            <ul>
                                @foreach ($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif
                    <div class="row">
                        <div class="col-xl-6">
                            <div class="mb-3">
                                <label for="exampleFormControlInput1" class="form-label">Name</label>
                                <input type="text" name="name" value="{{ old('name') }}"
                                    class="form-control" id="exampleFormControlInput1" autocomplete="off" required>
                            </div>
                        </div>           
                        <div class="col-xl-6">
                            <div class="mb-3">
                                <label for="exampleFormControlInput1" class="form-label">Capacity</label>
                                <input type="number" name="capacity" value="{{ old('capacity') }}" class="form-control"
                                    id="exampleFormControlInput1" autocomplete="off" required>
                            </div>
                        </div>                        
                        <div class="col-xl-6">
                            <div class="mb-3">
                                <label for="exampleFormControlInput1" class="form-label">Price</label>
                                <input type="text" name="price" value="{{ old('price') }}"
                                    class="form-control rupiah" id="exampleFormControlInput1" autocomplete="off"
                                    required>
                            </div>
                        </div>
                        <div class="col-xl-6">
                            <div class="mb-3">
                                <label for="exampleFormControlInput1" class="form-label">Instructor</label>
                                <select name="class_instructor_id" class="form-control" aria-label="Default select example"
                                    required>
                                    @foreach($classInstructors as $classInstructor)                                        
                                        <option value="{{ $classInstructor->id }}">{{ $classInstructor->full_name }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>                           
                        <div class="col-xl-6">
                            <div class="mb-3">
                                <label for="exampleFormControlTextarea1" class="form-label text-primary">
                                    Description
                                </label>
                                <textarea class="form-control" name="note" id="exampleFormControlTextarea1" rows="6"
                                    placeholder="Enter Description">{{ old('note') }}</textarea>
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
</div>
