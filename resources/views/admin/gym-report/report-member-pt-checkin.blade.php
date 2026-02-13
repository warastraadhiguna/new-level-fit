<div class="row">
    <div class="col-xl-12">

        {{-- FILTER BAR --}}
        <div class="row align-items-center g-2 mb-3">

            <div class="col-auto d-flex flex-nowrap align-items-center">
                <input type="date" id="fromDate" class="form-control" value="{{ $fromDate }}">
                <span class="mx-1">to</span>
                <input type="date" id="toDate" class="form-control" value="{{ $toDate }}">
            </div>

            <div class="col-auto">
                <select id="memberId" class="form-control single-select">
                    <option value="">All Member</option>
                    @foreach ($members as $item)
                        <option value="{{ $item->id }}" {{ $item->id == $memberId ? 'selected' : '' }}>
                            {{ $item->full_name }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div class="col-auto">
                <select id="ptId" class="form-control single-select">
                    <option value="">All Trainners</option>
                    @foreach ($trainers as $item)
                        <option value="{{ $item->id }}" {{ $item->id == $ptId ? 'selected' : '' }}>
                            {{ $item->full_name }}
                        </option>
                    @endforeach
                </select>
            </div>            

            <div class="col-auto">
                <button type="button" onclick="reloadPage(0)" class="btn btn-info">
                    Filter
                </button>
            </div>

            <div class="col-auto">
                <button type="button" onclick="reloadPage(1)" class="btn btn-outline-info">
                    Download Excel
                </button>
            </div>

        </div>

        {{-- TABLE --}}
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
                                <th>Check In Time</th>
                                <th>Check Out Time</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($results as $item)
                                <tr>
                                    <td>{{ ($results->currentPage() - 1) * $results->perPage() + $loop->iteration }}</td>
                                    <td>{{ $item->member_code }}</td>
                                    <td>{{ $item->member_name }}</td>
                                    <td><h6>{{ $item->package_name }}</h6></td>
                                    <td>{{ $item->trainer_name }}</td>
                                    <td>{{ DateFormat($item->check_in_time, 'DD MMMM YYYY, HH:mm:ss') }}</td>
                                    <td>{{ $item->check_out_time? DateFormat($item->check_out_time, 'DD MMMM YYYY, HH:mm:ss') : "-" }}</td>                                    
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="7">No data found</td>
                                </tr>
                            @endforelse
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
    const fromDateEl = document.getElementById("fromDate");
    const toDateEl = document.getElementById("toDate");
    const memberIdEl = document.getElementById("memberId");
    const ptIdEl = document.getElementById("ptId");    

    let fromDate = fromDateEl ? fromDateEl.value : "";
    let toDate   = toDateEl ? toDateEl.value : "";
    let memberId = memberIdEl ? memberIdEl.value : "";
    let ptId = ptIdEl ? ptIdEl.value : "";

    // ✅ Guard: kalau kosong, isi default "hari ini" supaya query tidak kosong semua
    // Kamu boleh ubah default ini sesuai kebutuhan (misal last 7 days).
    const today = new Date().toISOString().slice(0, 10); // YYYY-MM-DD

    if (!fromDate) fromDate = today;
    if (!toDate) toDate = today;

    const params = new URLSearchParams();
    params.set('fromDate', fromDate);
    params.set('toDate', toDate);
    if (memberId) params.set('memberId', memberId);
    if (ptId) params.set('ptId', ptId);    
    params.set('excel', excel);

    // optional cache-buster (boleh dihapus kalau tidak perlu)
    params.set('date', new Date().toISOString());

    window.location.href = window.location.pathname + '?' + params.toString();
}
</script>
