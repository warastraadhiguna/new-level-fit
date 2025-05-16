<div class="row">
    <div class="col-xl-12">
        @if (isset($fromDate))
            <div class="col-xl-12">
                <div class="page-title flex-wrap justify-content-start">
                    <div class="col-3 d-flex flex-nowrap align-items-center">
                        <input type="date" id="fromDate" class="form-control" value="{{ $fromDate }}">
                        <span class="mx-1">to</span>
                        <input type="date" id="toDate" class="form-control" value="{{ $toDate }}">
                        <select name="trainerName" id="trainerName" class="form-control mx-1">
                            @foreach ($personalTrainers as $item)
                                <option value="{{ $item->id }}" {{ $personalTrainer == $item->id? " selected" : ""  }}>{{ $item->full_name }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <button type="button" onclick="reloadPage()" class="btn btn-info mx-1" data-bs-toggle="modal">
                        Filter
                    </button>
                    <button type="button" onclick="reloadPage(1)" class="btn btn-outline-info" data-bs-toggle="modal">
                        PDF
                    </button>
                </div>
            </div>
        @endif
        <div class="row">
            @if (!isset($fromDate))
            <h4 align="center">Personal Trainer Detail Report</h4>
            @endif
            <!--column-->
            <div class="col-xl-12 wow fadeInUp" data-wow-delay="1.5s">
                <div class="table-responsive full-data">
                    <table class="table table-bordered" border="1" style="text-align: center;" height="2px" width="100%">
                        <thead>
                            <tr>
                                <th>No</th>
                                <th>Trainer Name</th>
                                <th>Member Name</th>
                                <th>Package Name</th>
                                <th>Check In Time</th>
                                <th>Check Out Time</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($result as $item)
                                <tr>
                                    <td>{{ $loop->iteration }}</td>
                                    <td>
                                        {{ $item->trainer_name }}
                                    </td>
                                    <td>
                                        {{ $item->member_name }}
                                    </td>
                                    <td>
                                       {{ $item->package_name }}
                                    </td>
                                    <td>
                                        {{ DateFormat($item->check_in_time, 'DD MMMM YYYY, H:mm:ss') }}
                                     </td>
                                     <td>
                                        {{ DateFormat($item->check_out_time, 'DD MMMM YYYY, H:mm:ss') }}
                                     </td>
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


<script>
    function reloadPage(pdf = 0) {
        var fromDate = document.getElementById("fromDate").value;
        var toDate = document.getElementById("toDate").value;
        var trainerName = document.getElementById("trainerName").value;
        window.open(window.location.pathname + '?fromDate=' + fromDate + '&toDate=' + toDate + '&trainerName=' + trainerName + '&pdf=' + pdf +
            "&date=" + new Date().toISOString(), '_self');
    }
</script>
