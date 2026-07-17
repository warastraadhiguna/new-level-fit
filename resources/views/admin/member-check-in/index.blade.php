<div class="row mb-5">
    <div class="col-xl-12">
        <form action="{{ route('member-check-in.toggle-by-card-number') }}" method="POST" enctype="multipart/form-data" autocomplete="off" id="member-check-in-form">
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
                <div class="col-xl-4 col-md-6">
                    <div class="mb-0">
                        <p>Card Number</p>
                        <input type="text" name="card_number" id="memberCardNumberInput" class="form-control" autofocus autocomplete="off">
                    </div>
                </div>
            </div>
        </form>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        const form = document.getElementById('member-check-in-form');
        const cardInput = document.getElementById('memberCardNumberInput');

        if (!form) {
            return;
        }

        let isSubmitting = false;

        const focusCardInput = function() {
            if (!cardInput || cardInput.readOnly) {
                return;
            }

            cardInput.focus();
        };

        const resetSubmitState = function() {
            isSubmitting = false;

            if (cardInput) {
                cardInput.readOnly = false;
            }

            focusCardInput();
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
        });

        document.addEventListener('visibilitychange', function() {
            if (!document.hidden) {
                focusCardInput();
            }
        });

        window.addEventListener('focus', focusCardInput);
        window.addEventListener('pageshow', resetSubmitState);
        document.addEventListener('click', function(event) {
            if (!event.target.closest('a, button, input, select, textarea, label')) {
                focusCardInput();
            }
        });

        focusCardInput();
    });
</script>

<div class="row">
    <div class="col-xl-12 wow fadeInUp" data-wow-delay="1.5s">
        <div class="table-responsive full-data">
            <table class="table table-bordered" border="1" style="text-align: center;" height="2px" width="100%">
                <thead>
                    <tr>
                        <th>No</th>
                        <th>Member Code</th>
                        <th>Member Name</th>
                        <th>Branch</th>
                        <th>Check In Time</th>
                        <th>Check Out Time</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($results as $item)
                        <tr>
                            <td>{{  $loop->iteration }}</td>
                            <td>{{ $item->member_code ?? '-' }}</td>
                            <td>{{ $item->member_name }}</td>
                            <td>{{ $item->branch_store_name }}</td>
                            <td>{{ DateFormat($item->check_in_time, 'DD MMMM YYYY, HH:mm:ss') }}</td>
                            <td>{{ $item->check_out_time? DateFormat($item->check_out_time, 'DD MMMM YYYY, HH:mm:ss') : "-" }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>
