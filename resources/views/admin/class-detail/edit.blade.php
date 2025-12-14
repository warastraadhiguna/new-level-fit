    <div class="modal fade" id="modalEdit{{ $item->id }}" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <form action="{{ route('class-detail.update', $item->id) }}" method="POST">
                    @method('PUT')
                    @csrf
                    <div class="modal-header">
                        <h1 class="modal-title fs-5" id="exampleModalLabel">Edit Class Detail</h1>
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
                                    <input type="text" name="name" value="{{ $item->name . ($item->member? "(". $item->member->member_code .")" : "") }}"
                                        class="form-control"  autocomplete="off" readonly>
                                </div>
                            </div>           
                            <div class="col-xl-6">
                                <div class="mb-3">
                                    <label for="exampleFormControlInput1" class="form-label">Phone</label>
                                    <input type="text"  name="phone" value="{{ $item->phone }}" class="form-control"
                                        autocomplete="off" readonly>
                                </div>
                            </div>                        
                            <div class="col-xl-6">
                                <div class="mb-3">
                                    <label for="exampleFormControlInput1" class="form-label">Email</label>
                                    <input type="text" name="email" value="{{ $item->email }}"
                                        class="form-control"  autocomplete="off" readonly>
                                </div>
                            </div>          
                        <div class="col-xl-6">
                            <div class="mb-3">
                                <label for="exampleFormControlInput1" class="form-label">Status</label>
                                <select id="status" name="status" class="form-control" aria-label="Default select example">
                                    <option value="1" >Continue</option>
                                    <option value="0" >Cancel</option>                                    
                                </select>
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