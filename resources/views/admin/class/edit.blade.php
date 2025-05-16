<div class="row">
    <div class="card">
        <div class="card-body">
            <form action="{{ route('class.update', $class->id) }}" method="POST" enctype="multipart/form-data">
                @method('PUT')
                @csrf
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
                            <label for="exampleFormControlInput1" class="form-label">Date & Time</label>
                            <input type="text" name="date_time" value="{{ old('date_time', $class->date_time) }}"
                                id="date-format" class="form-control" required>
                        </div>
                    </div>
                    <div class="col-xl-6">
                        <div class="mb-3">
                            <label for="exampleFormControlInput1" class="form-label">Class Name</label>
                            <input class="form-control" type="text" name="class_name"
                                value="{{ old('class_name', $class->class_name) }}" autocomplete="off" required>
                        </div>
                    </div>
                    <div class="col-xl-6">
                        <div class="mb-3">
                            <label for="exampleFormControlInput1" class="form-label">Instructor Name</label>
                            <select id="single-select" name="class_instructor_id" class="form-control">
                                <option value="{{ $class->class_instructor_id }}" selected>
                                    {{ old('class_instructor_id', $class->classInstructor->full_name) }}
                                </option>
                                @foreach ($classInstructor as $item)
                                    <option value="{{ $item->id }}">
                                        {{ $item->full_name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <div class="col-xl-6">
                        <div class="mb-3">
                            <label for="exampleFormControlInput1" class="form-label">Member Total</label>
                            <input class="form-control" type="number" name="member_total"
                                value="{{ old('member_total', $class->member_total) }}" autocomplete="off" required>
                        </div>
                    </div>
                    <div class="col-xl-6">
                        <div class="mb-3">
                            <label for="exampleFormControlInput1" class="form-label">Class Price</label>
                            <input type="text" name="class_price"
                                value="{{ old('class_price', $class->class_price) }}" class="form-control rupiah"
                                id="exampleFormControlInput1" autocomplete="off" required>
                        </div>
                    </div>
                </div>
                <button type="submit" class="btn btn-primary">Update</button>
                <a href="{{ route('class.index') }}" class="btn btn-danger">Back</a>
            </form>
        </div>
    </div>
</div>
