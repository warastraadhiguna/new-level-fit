<div class="row">
    @php
        $sortLink = function ($column) use ($sort, $direction, $search, $perPage) {
            $nextDirection = $sort === $column && $direction === 'asc' ? 'desc' : 'asc';

            return route('members.index', array_filter([
                'search' => $search,
                'per_page' => $perPage,
                'sort' => $column,
                'direction' => $nextDirection,
            ], fn ($value) => $value !== null && $value !== ''));
        };

        $sortIcon = function ($column) use ($sort, $direction) {
            if ($sort !== $column) {
                return 'fa-sort';
            }

            return $direction === 'asc' ? 'fa-sort-up' : 'fa-sort-down';
        };
    @endphp
    <div class="col-xl-12">
        <div class="row">
            <div class="col-xl-12">
                <div class="card border-0 shadow-sm mb-4">
                    <div class="card-body">
                        <form action="{{ route('members.index') }}" method="GET" id="memberSearchForm">
                            <div class="d-flex justify-content-between align-items-center mb-4">
                                <button type="button" class="btn btn-info" data-bs-toggle="modal" data-bs-target="#exampleModal">
                                    Download Excel
                                </button>
                                @if ($search)
                                    <a href="{{ route('members.index') }}" class="btn btn-danger light">
                                        Reset Search
                                    </a>
                                @endif
                            </div>
                            <div class="d-flex flex-wrap justify-content-between align-items-center gap-3">
                                <div class="d-flex align-items-center gap-2">
                                    <span class="text-muted">Show</span>
                                    <select name="per_page" class="form-control" style="width: 80px;"
                                        onchange="this.form.submit()">
                                        @foreach ([10, 25, 50, 100] as $option)
                                            <option value="{{ $option }}" {{ $perPage == $option ? 'selected' : '' }}>
                                                {{ $option }}
                                            </option>
                                        @endforeach
                                    </select>
                                    <span class="text-muted">entries</span>
                                </div>
                                <div class="d-flex align-items-center gap-2">
                                    <span class="text-muted">Search:</span>
                                    <input type="text" name="search" id="memberSearchInput" class="form-control" style="width: 280px;"
                                        value="{{ $search }}" placeholder="Press Enter to search">
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
            <!--column-->
            <div class="col-xl-12 wow fadeInUp" data-wow-delay="1.5s">
                <div class="table-responsive full-data">
                    <table class="table-responsive-lg table display dataTablesCard student-tab dataTable no-footer"
                        id="membersTable">
                        <thead>
                            <tr>
                                <th>No</th>
                                <th>Image</th>
                                <th>
                                    <a href="{{ $sortLink('full_name') }}" class="text-primary">
                                        Member <i class="fa {{ $sortIcon('full_name') }}"></i>
                                    </a>
                                </th>
                                <th>
                                    <a href="{{ $sortLink('branch') }}" class="text-primary">
                                        Branch <i class="fa {{ $sortIcon('branch') }}"></i>
                                    </a>
                                </th>
                                <th>
                                    <a href="{{ $sortLink('phone_number') }}" class="text-primary">
                                        Phone Number <i class="fa {{ $sortIcon('phone_number') }}"></i>
                                    </a>
                                </th>
                                <th>
                                    <a href="{{ $sortLink('born') }}" class="text-primary">
                                        Date of Birth <i class="fa {{ $sortIcon('born') }}"></i>
                                    </a>
                                </th>
                                <th>
                                    <a href="{{ $sortLink('created_at') }}" class="text-primary">
                                        Created At <i class="fa {{ $sortIcon('created_at') }}"></i>
                                    </a>
                                </th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($members as $item)
                                <tr>
                                    <td>{{ $members->firstItem() + $loop->index }}</td>
                                    <td>
                                        <div class="trans-list">
                                            @if ($item->photos)
                                                <img src="{{ Storage::url($item->photos ?? '') }}" class="lazyload"
                                                    width="100" alt="image">
                                            @else
                                                <img src="{{ asset('default.png') }}" width="100" class="img-fluid"
                                                    alt="">
                                            @endif
                                        </div>
                                    </td>
                                    <td>
                                        <h6>{{ $item->full_name }}</h6>
                                        <span class="text-muted">{{ $item->member_code ?? 'No Member Code' }}</span>
                                    </td>
                                    <td>
                                        <h6>{{ $item->branch_store_name }}</h6></td>
                                    <td>
                                        <h6>{{ $item->phone_number ?? 'No Data' }}</h6>
                                    </td>
                                    <td>
                                        <h6>{{ DateFormat($item->born, 'DD MMMM YYYY') ?? 'No Data' }}</h6>
                                    </td>
                                    <td>
                                        <h6>{{ DateFormat($item->created_at, 'DD MMMM YYYY') ?? 'No Data' }}</h6>
                                    </td>
                                    <td>
                                        <div>
                                            <a href="{{ route('edit-member-sell', $item->id) }}"
                                                class="btn light btn-warning btn-xs btn-block mb-1">Edit
                                                Member</a>
                                            <a href="{{ route('members.show', $item->id) }}"
                                                class="btn light btn-info btn-xs btn-block mb-1">Detail Member</a>
                                            @if (Auth::user()->role == 'ADMIN')
                                                <a href="{{ route('members.create-membership', $item->id) }}"
                                                    class="btn light btn-primary btn-xs btn-block mb-1">Create Membership</a>
                                            @endif
                                            {{-- @if ($item->lo_status == 'Running' && $item->lo_is_used == 0) --}}
                                            @if (   $item->lo_is_used == 0)
                                                <a href="{{ route('useLayoutOrientation', $item->id) }}"
                                                    class="btn btn-dark btn-xs mb-1 btn-block">LO</a>
                                            @else
                                                @if (!$item->lo_end)
                                                    <form action="{{ route("stopLayoutOrientation", $item->id) }}" method="POST">
                                                        @csrf
                                                        <button type="submit" class="btn btn-dark btn-xs mb-1 btn-block">Stop LO(Running)</button>
                                                    </form>
                                                @else
                                                    <button type="button" class="btn btn-dark btn-xs mb-1 btn-block"
                                                        data-bs-toggle="popover" data-bs-title="Check In tanpa kartu"
                                                        data-bs-content="Member ini sudah menggunakan Layout Orientation">
                                                        <span class="text-danger">X</span> LO is used<span
                                                            class="text-danger">X</span>
                                                    </button>
                                                @endif
                                            @endif
                                            @if (Auth::user()->role == 'ADMIN')
                                                <form action="{{ route('member.destroy', $item->id) }}"
                                                    onclick="return confirm('Delete Data ?')" method="POST">
                                                    @method('delete')
                                                    @csrf
                                                    <button type="submit"
                                                        class="btn light btn-danger btn-xs btn-block mb-1">Delete</button>
                                                </form>
                                            @endif
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                            @if ($members->count() == 0)
                                <tr>
                                    <td colspan="8" class="text-center">No data found</td>
                                </tr>
                            @endif
                        </tbody>
                    </table>
                </div>
                <div class="mt-3 d-flex flex-wrap justify-content-between align-items-center">
                    <div class="text-muted mb-2">
                        Showing {{ $members->firstItem() ?? 0 }} to {{ $members->lastItem() ?? 0 }} of {{ $members->total() }} data
                    </div>
                    <div class="mb-2">
                        {{ $members->links('pagination::bootstrap-4') }}
                    </div>
                </div>
            </div>
            <!--/column-->
        </div>
    </div>
</div>

{{-- MODAL --}}
<div class="modal fade" id="exampleModal" tabindex="-1" aria-labelledby="exampleModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h1 class="modal-title fs-5" id="exampleModalLabel">Download Excel by Date</h1>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="row">
                    <div class="col-6">
                        <div class="mb-3">
                            <label class="form-label">From Date</label>
                            <input type="date" id="fromDate" class="form-control">
                        </div>
                    </div>
                    <div class="col-6">
                        <div class="mb-3">
                            <label class="form-label">To Date</label>
                            <input type="date" id="toDate" class="form-control">
                        </div>
                    </div>
                </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" onclick="reloadPage()" class="btn btn-primary">Download</button>
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>


<script>
    function reloadPage() {
        var fromDate = document.getElementById("fromDate").value;
        var toDate = document.getElementById("toDate").value;

        window.open(window.location.href + '?excel=1&fromDate=' + fromDate + '&toDate=' + toDate, '_self');
    }

    document.addEventListener('DOMContentLoaded', function() {
        const searchForm = document.getElementById('memberSearchForm');
        const searchInput = document.getElementById('memberSearchInput');
        let searchTimer;

        if (!searchForm || !searchInput) {
            return;
        }

        searchInput.addEventListener('input', function() {
            clearTimeout(searchTimer);
            searchTimer = setTimeout(function() {
                searchForm.submit();
            }, 1500);
        });
    });
</script>
