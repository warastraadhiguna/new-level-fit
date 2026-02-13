<div class="row">
    <div class="col-xl-12">

        @if (isset($fromDate))
            <div class="row align-items-center g-2 mb-3">

                <!-- FROM - TO -->
                <div class="col-md-4 col-12">
                    <div class="d-flex flex-nowrap align-items-center">
                        <input type="date" id="fromDate" class="form-control" value="{{ $fromDate }}">
                        <span class="mx-2">to</span>
                        <input type="date" id="toDate" class="form-control" value="{{ $toDate }}">
                    </div>
                </div>

                <!-- MEMBER -->
                <div class="col-md-3 col-12">
                    <select id="memberId" class="form-control single-select w-100">
                        <option value="">All Member</option>
                        @foreach ($members as $item)
                            <option value="{{ $item->id }}" {{ $item->id == $memberId ? 'selected' : '' }}>
                                {{ $item->full_name }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <!-- BUTTONS -->
                <div class="col-md-5 col-12 d-flex gap-2">
                    <button type="button" onclick="reloadPage()" class="btn btn-info">
                        Filter
                    </button>
                    <button type="button" onclick="reloadPage(1)" class="btn btn-outline-info">
                        Download Excel
                    </button>
                </div>

            </div>
        @endif

        <!-- tabel kamu lanjutkan seperti biasa -->
        <div class="row">
            <div class="col-xl-12 wow fadeInUp" data-wow-delay="1.5s">
                <div class="table-responsive full-data">
                    <table class="table table-bordered" border="1" style="text-align: center;" height="2px" width="100%">
                        <thead>
                            <tr>
                                <th>No</th>
                                <th>Member Name</th>
                                <th>Branch</th>
                                <th>Check In Time</th>
                                <th>Check Out Time</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($results as $item)
                                <tr>
                                    <td>{{ ($results->currentPage() - 1) * $results->perPage() + $loop->iteration }}</td>
                                    <td>{{ $item->member_name }}</td>
                                    <td>{{ $item->branch_store_name }}</td>
                                    <td>{{ DateFormat($item->check_in_time, 'DD MMMM YYYY, HH:mm:ss') }}</td>
                                    <td>{{ $item->check_out_time? DateFormat($item->check_out_time, 'DD MMMM YYYY, HH:mm:ss') : "-" }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>

                    {{ $results->appends(request()->query())->links('pagination::bootstrap-4') }}
                </div>
            </div>
        </div>

    </div>
</div>


<script>
    function reloadPage(excel = 0) {
        var fromDate = document.getElementById("fromDate").value;
        var toDate = document.getElementById("toDate").value;
        var memberId = document.getElementById("memberId").value;
        // alert(window.location.host );
        window.open(window.location.pathname + '?fromDate=' + fromDate + '&toDate=' + toDate + '&memberId=' + memberId +
            '&excel=' + excel +
            "&date=" + new Date().toISOString(), '_self');
    }
</script>
