<!-- Modal Add -->
<div class="modal fade bd-example-modal-lg" id="modalAdd" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <form action="{{ route('class-detail.store') }}" method="POST">
                @csrf
                <input type="hidden" name="class_schedule_id" value="{{ $classSchedule->id }}">
                <div class="modal-header">
                    <h1 class="modal-title fs-5" id="exampleModalLabel">Create Class Detail</h1>
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
                                <label for="exampleFormControlInput1" class="form-label">Member Master</label>
                                <select id="memberSelect" name="member_id" class="form-control" aria-label="Default select example">
                                    <option value="" data-name=""
                                            data-phone=""
                                            data-email="">-- Choose Member (Null Allowed) --</option>
                                    @foreach($members as $member)                                        
                                        <option value="{{ $member->id }}" 
                                            data-name="{{ $member->full_name }}"
                                            data-phone="{{ $member->phone_number }}"
                                            data-email="{{ $member->email }}">{{ $member->full_name }} ({{ $member->member_code }})</option>
                                    @endforeach
                                </select>
                            </div>
                        </div> 
                        <div class="col-xl-6">
                            <div class="mb-3">
                                <label for="exampleFormControlInput1" class="form-label">Name</label>
                                <input type="text" id="memberName" name="name" value="{{ old('name') }}"
                                    class="form-control"  autocomplete="off" required>
                            </div>
                        </div>           
                        <div class="col-xl-6">
                            <div class="mb-3">
                                <label for="exampleFormControlInput1" class="form-label">Phone</label>
                                <input type="text" id="memberPhone" name="phone" value="{{ old('phone') }}" class="form-control"
                                    autocomplete="off" required>
                            </div>
                        </div>                        
                        <div class="col-xl-6">
                            <div class="mb-3">
                                <label for="exampleFormControlInput1" class="form-label">Email</label>
                                <input type="text" name="email" id="memberEmail" value="{{ old('email') }}"
                                    class="form-control"  autocomplete="off">
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
    document.getElementById('memberSelect').addEventListener('change', function () {
        const selectedOption = this.options[this.selectedIndex];
        const className = selectedOption.getAttribute('data-name') || '';
        const classPhone = selectedOption.getAttribute('data-phone') || '';
        const classEmail = selectedOption.getAttribute('data-email') || '';

        document.getElementById('memberName').value = className;
        document.getElementById('memberPhone').value = classPhone;        
        document.getElementById('memberEmail').value = classEmail;      
    });
</script>
