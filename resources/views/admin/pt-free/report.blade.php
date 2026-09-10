<div class="row">
    <div class="col-xl-12">
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-body">
                <form action="{{ route('pt-free.report') }}" method="GET">
                    <div class="row align-items-end g-3">
                        <div class="col-md-3">
                            <label class="form-label">From Date</label>
                            <input type="date" name="from_date" value="{{ $fromDate }}" class="form-control" required>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">To Date</label>
                            <input type="date" name="to_date" value="{{ $toDate }}" class="form-control" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Search</label>
                            <input type="text" name="search" value="{{ $search }}" class="form-control"
                                placeholder="Member, kode, paket, atau trainer">
                        </div>
                        <div class="col-md-2 d-flex gap-2">
                            <button type="submit" class="btn btn-primary">Tampilkan</button>
                            <a href="{{ route('pt-free.report') }}" class="btn btn-light">Reset</a>
                        </div>
                    </div>
                    <div class="d-flex align-items-center gap-2 mt-3">
                        <span class="text-muted">Show</span>
                        <select name="per_page" class="form-control" style="width: 80px;" onchange="this.form.submit()">
                            @foreach ([10, 25, 50, 100] as $option)
                                <option value="{{ $option }}" {{ $perPage == $option ? 'selected' : '' }}>{{ $option }}</option>
                            @endforeach
                        </select>
                        <span class="text-muted">entries</span>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div class="col-xl-12 wow fadeInUp" data-wow-delay="1.5s">
        <div class="table-responsive full-data">
            <table class="table-responsive-lg table display dataTablesCard student-tab dataTable no-footer">
                <thead>
                    <tr>
                        <th>No</th>
                        <th>Member</th>
                        <th>Package</th>
                        <th>Trainer</th>
                        <th>Check In</th>
                        <th>Check Out</th>
                        <th>Duration</th>
                        <th>Staff</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($results as $item)
                        <tr>
                            <td>{{ $results->firstItem() + $loop->index }}</td>
                            <td><h6>{{ $item->member_name }}</h6><small>{{ $item->member_code }}</small></td>
                            <td>{{ $item->package_name }}</td>
                            <td>{{ $item->trainer_name }}</td>
                            <td class="text-nowrap">{{ DateFormat($item->check_in_time, 'DD MMMM YYYY, HH:mm:ss') }}</td>
                            <td class="text-nowrap">
                                {{ $item->check_out_time ? DateFormat($item->check_out_time, 'DD MMMM YYYY, HH:mm:ss') : '-' }}
                            </td>
                            <td>
                                {{ $item->check_out_time ? Carbon\Carbon::parse($item->check_in_time)->diffForHumans(Carbon\Carbon::parse($item->check_out_time), true) : '-' }}
                            </td>
                            <td>{{ $item->staff_name }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="8" class="text-center">Tidak ada data PT Free pada periode ini.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="mt-3 d-flex flex-wrap justify-content-between align-items-center">
            <div class="text-muted mb-2">
                Showing {{ $results->firstItem() ?? 0 }} to {{ $results->lastItem() ?? 0 }} of {{ $results->total() }} data
            </div>
            <div class="mb-2">{{ $results->links('pagination::bootstrap-4') }}</div>
        </div>
    </div>
</div>
