<div class="row mb-5">
    <div class="col-xl-12">
            <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#checkIn2"
                        id="checkInButton">
                Input Member Code
            </button>      
    </div>
</div>
<div class="modal fade bd-example-modal-sm" id="checkIn2" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-sm">
        <div class="modal-content">
            <form action="{{ route('trainer-session-check-in.store') }}" method="POST" enctype="multipart/form-data" id="trainer-session-check-in-form" autocomplete="off">
                @csrf
                <div class="modal-header">
                    <h1 class="modal-title fs-5" id="exampleModalLabel">Check In Trainer Session</h1>
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
                        <div class="col-xl-12">
                            <div class="mb-0">
                                <p>Card Number</p>
                                <input type="text" name="card_number" id="trainerSessionCardNumberInput" class="form-control"
                                    autofocus>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="submit" class="btn btn-primary" id="trainerSessionCheckInSubmitButton">Submit</button>
                    <button type="button" class="btn btn-danger light" data-bs-dismiss="modal">Close</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        const form = document.getElementById('trainer-session-check-in-form');
        const cardInput = document.getElementById('trainerSessionCardNumberInput');
        const submitButton = document.getElementById('trainerSessionCheckInSubmitButton');

        if (!form) {
            return;
        }

        let isSubmitting = false;

        const resetSubmitState = function() {
            isSubmitting = false;

            if (cardInput) {
                cardInput.readOnly = false;
            }

            if (submitButton) {
                submitButton.disabled = false;
                submitButton.textContent = 'Submit';
            }
        };

        form.addEventListener('submit', function(event) {
            if (isSubmitting) {
                event.preventDefault();
                return;
            }

            isSubmitting = true;

            if (cardInput) {
                cardInput.value = cardInput.value.trim();
                cardInput.readOnly = true;
            }

            if (submitButton) {
                submitButton.disabled = true;
                submitButton.textContent = 'Processing...';
            }
        });

        window.addEventListener('pageshow', resetSubmitState);
    });
</script>


<div class="row">
    <div class="col-xl-12 wow fadeInUp" data-wow-delay="1.5s">
        <div class="table-responsive full-data">
            <table class="table table-bordered" border="1" style="text-align: center;" width="100%">
                <thead>
                    <tr>
                        <th>No</th>
                        <th>Member Code</th>
                        <th>Member Name</th>
                        <th>Package Name</th>
                        <th>Trainer Name</th>
                        <th>Branch</th>
                        <th>Check In Time</th>
                        <th>Check Out Time</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($results as $item)
                        <tr>
                            <td>{{ $loop->iteration }}</td>
                            <td>{{ $item->member_code }}</td>
                            <td>{{ $item->member_name }}</td>
                            <td><h6>{{ $item->package_name }}</h6></td>
                            <td>{{ $item->trainer_name }}</td>
                            <td>{{ $item->branch_store_name ?? '-' }}</td>
                            <td>{{ DateFormat($item->check_in_time, 'DD MMMM YYYY, HH:mm:ss') }}</td>
                            <td>{{ $item->check_out_time? DateFormat($item->check_out_time, 'DD MMMM YYYY, HH:mm:ss') : "-" }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8">No data found</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

