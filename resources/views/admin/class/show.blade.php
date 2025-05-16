<div class="'col-xl-12">
    <div class="card">
        <div class="card-body">
            <div class="teacher-deatails">
                <h3 class="heading">Trainer Session Detail:</h3>
                <ul>
                    <li>
                        <h6 class="mb-1">Active Period</h6>
                        <span>{{ $trainerSession->active_period }}</span>
                        <h6 class="mb-1">Session Total</h6>
                        <span>{{ $trainerSession->session_total }}</span>
                        <h6 class="mb-1">Remaining Session</h6>
                        <span>{{ $trainerSession->remaining_session }}</span>
                        <h6 class="mb-1">Status</h6>
                        <span>{{ $trainerSession->status }}</span>
                    </li>
                </ul>
            </div>
        </div>
    </div>
</div>

<div class="'col-xl-12">
    <div class="card">
        <div class="card-body">
            <div class="teacher-deatails">
                <h3 class="heading">Member:</h3>
                <ul>
                    <li>
                        <h6 class="mb-1">First Name</h6>
                        <span>{{ $trainerSession->members->first_name }}</span>
                        <h6 class="mb-1">Last Name</h6>
                        <span>{{ $trainerSession->members->last_name }}</span>
                        <h6 class="mb-1">Member Code</h6>
                        <span>{{ $trainerSession->members->member_code }}</span>
                        <h6 class="mb-1">Phone Number</h6>
                        <span>{{ $trainerSession->members->phone_number }}</span>
                        <h6 class="mb-1">Member Status</h6>
                        <span>{{ $trainerSession->members->status }}</span>
                    </li>
                </ul>
                <h3 class="heading mt-4">Trainer:</h3>
                <ul>
                    <li>
                        <h6 class="mb-1">trainer Name</h6>
                        <span>{{ $trainerSession->trainers->trainer_name }}</span>
                    </li>
                </ul>
            </div>
        </div>
    </div>
    <a href="{{ route('trainer-session.index') }}" class="btn btn-primary">Back</a>
</div>
