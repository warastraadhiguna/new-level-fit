<!-- Modal Add -->
<div class="modal fade bd-example-modal-lg" id="modalAdd" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <form action="{{ route('class-schedule.store') }}" method="POST">
                @csrf
                <div class="modal-header">
                    <h1 class="modal-title fs-5" id="exampleModalLabel">Create Class Schedule</h1>
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
                                <label for="exampleFormControlInput1" class="form-label">Class Session Master</label>
                                <select id="classSessionSelect" name="class_session_id" class="form-control" aria-label="Default select example"
                                    required>
                                    <option value="">-- Choose Class Session --</option>
                                    @foreach($classSessions as $classSession)                                        
                                        <option value="{{ $classSession->id }}" 
                                            data-name="{{ $classSession->name }}"
                                            data-capacity="{{ $classSession->capacity }}"
                                            data-price="{{ $classSession->price }}"
                                            data-instructor="{{ $classSession->class_instructor_id }}"
                                            data-note="{{ $classSession->note }}">{{ $classSession->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div> 
                        <div class="col-xl-6">
                            <div class="mb-3">
                                <label for="exampleFormControlInput1" class="form-label">Name</label>
                                <input type="text" id="classSessionName" name="name" value="{{ old('name') }}"
                                    class="form-control"  autocomplete="off" required>
                            </div>
                        </div>           
                        <div class="col-xl-6">
                            <div class="mb-3">
                                <label for="exampleFormControlInput1" class="form-label">Capacity</label>
                                <input type="number" id="classSessionCapacity" name="capacity" value="{{ old('capacity') }}" class="form-control"
                                    autocomplete="off" required>
                            </div>
                        </div>                        
                        <div class="col-xl-6">
                            <div class="mb-3">
                                <label for="exampleFormControlInput1" class="form-label">Price</label>
                                <input type="text" name="price" id="classSessionPrice" value="{{ old('price') }}"
                                    class="form-control rupiah"  autocomplete="off"
                                    required>
                            </div>
                        </div>
                        <div class="col-xl-6">
                            <div class="mb-3">
                                <label for="exampleFormControlInput1" class="form-label">Instructor</label>
                                <select name="class_instructor_id" id="classSessionInstructor" class="form-control" aria-label="Default select example"
                                    required>
                                    <option value="">-- Choose Instructor --</option>
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
                                <textarea class="form-control" id="classSessionNote" name="note" rows="6"
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

<script>
    document.getElementById('classSessionSelect').addEventListener('change', function () {
        const selectedOption = this.options[this.selectedIndex];
        const className = selectedOption.getAttribute('data-name') || '';
        const classCapacity = selectedOption.getAttribute('data-capacity') || '0';
        const classPrice = selectedOption.getAttribute('data-price') || '0';
        const classNote = selectedOption.getAttribute('data-note') || '';
        const classInstructor = selectedOption.getAttribute('data-instructor') || '';
        const instructorSelect = document.getElementById('classSessionInstructor');

        document.getElementById('classSessionName').value = className;
        document.getElementById('classSessionCapacity').value = classCapacity;        
        document.getElementById('classSessionPrice').value = classPrice;    
        document.getElementById('classSessionNote').value = classNote;           

        instructorSelect.value = classInstructor;

        // kalau bootstrap-select terpasang
        if (window.jQuery && jQuery.fn.selectpicker && $('#classSessionInstructor').parent().hasClass('bootstrap-select')) {
            $('#classSessionInstructor').selectpicker('refresh');
        }
        });
</script>
