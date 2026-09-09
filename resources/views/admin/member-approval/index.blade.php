<div class="row">
    <div class="col-xl-12">
        <div class="row">
            <div class="col-xl-12">
                <div class="card border-0 shadow-sm mb-4">
                    <div class="card-body">
                        <form method="GET" action="{{ route('member-approval.index') }}" id="memberApprovalSearchForm">
                            <div class="d-flex justify-content-end align-items-center mb-4">
                                @if ($search)
                                    <a href="{{ route('member-approval.index') }}" class="btn btn-danger light">
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
                                    <input type="text" name="search" class="form-control" style="width: 280px;"
                                        value="{{ $search }}" placeholder="Press Enter to search">
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

            <div class="col-xl-12 wow fadeInUp" data-wow-delay="1.5s">
                <div class="table-responsive full-data">
                    <table class="table-responsive-lg table display dataTablesCard student-tab dataTable no-footer"
                        id="memberApprovalTable">
                        <thead>
                            <tr>
                                <th>No</th>
                                <th>Member</th>
                                <th>Package</th>
                                <th>Periode</th>
                                <th style="min-width: 190px">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($memberRegistrations as $item)
                                <tr>
                                    <td>{{ $memberRegistrations->firstItem() + $loop->index }}</td>
                                    <td>
                                        <h6>{{ optional($item->members)->full_name }}</h6>
                                        <h6>{{ optional($item->members)->member_code }}</h6>
                                    </td>
                                    <td>{{ optional($item->memberPackage)->package_name }}</td>
                                    <td class="text-nowrap">
                                        {{ DateFormat($item->start_date, 'DD MMM YYYY') }}<br>
                                        <small>{{ (int) $item->days }} hari</small>
                                    </td>
                                    <td>
                                        <div class="d-flex flex-wrap gap-2">
                                            <button type="button" class="btn btn-primary btn-sm" data-bs-toggle="modal"
                                                data-bs-target="#approvalModal{{ $item->id }}">
                                                Approval
                                            </button>
                                            <a href="{{ route('member-active.edit', $item->id) }}"
                                                class="btn btn-light btn-sm">Edit Member</a>
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="text-center">Tidak ada membership yang perlu disetujui.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                @foreach ($memberRegistrations as $item)
                    @php
                        $hasApprovalError = (string) old('member_registration_id') === (string) $item->id
                            && $errors->any();
                    @endphp
                    <div class="modal fade" id="approvalModal{{ $item->id }}" tabindex="-1"
                        aria-labelledby="approvalModalLabel{{ $item->id }}" aria-hidden="true">
                        <div class="modal-dialog modal-dialog-centered">
                            <div class="modal-content">
                                <form action="{{ route('member-approval.update', $item->id) }}" method="POST">
                                    @csrf
                                    @method('PUT')
                                    <input type="hidden" name="member_registration_id" value="{{ $item->id }}">
                                    <input type="hidden" name="is_approved" value="1">

                                    <div class="modal-header">
                                        <h5 class="modal-title" id="approvalModalLabel{{ $item->id }}">
                                            Approval Membership
                                        </h5>
                                        <button type="button" class="btn-close" data-bs-dismiss="modal"
                                            aria-label="Close"></button>
                                    </div>
                                    <div class="modal-body">
                                        @if ($hasApprovalError)
                                            <div class="alert alert-danger">{{ $errors->first() }}</div>
                                        @endif

                                        <div class="mb-3">
                                            <strong>{{ optional($item->members)->full_name }}</strong>
                                            <span class="d-block text-muted">
                                                {{ optional($item->members)->member_code }} ·
                                                {{ optional($item->memberPackage)->package_name }}
                                            </span>
                                        </div>
                                        <div>
                                            <label class="form-label">Description</label>
                                            <textarea name="description" rows="5" class="form-control" required
                                                placeholder="Description wajib diubah">{{ $hasApprovalError ? old('description') : $item->description }}</textarea>
                                            <small class="text-muted">Description wajib diubah sebelum membership disetujui.</small>
                                        </div>
                                    </div>
                                    <div class="modal-footer">
                                        <button type="button" class="btn btn-light" data-bs-dismiss="modal">Batal</button>
                                        <button type="submit" class="btn btn-primary">Approve Membership</button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                @endforeach

                <div class="mt-3 d-flex flex-wrap justify-content-between align-items-center">
                    <div class="text-muted mb-2">
                        Showing {{ $memberRegistrations->firstItem() ?? 0 }} to
                        {{ $memberRegistrations->lastItem() ?? 0 }} of
                        {{ $memberRegistrations->total() }} data
                    </div>
                    <div class="mb-2">
                        {{ $memberRegistrations->links('pagination::bootstrap-4') }}
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

@if (old('member_registration_id') && $errors->any())
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            var approvalModal = document.getElementById(@json('approvalModal' . old('member_registration_id')));
            if (approvalModal && window.bootstrap) {
                new bootstrap.Modal(approvalModal).show();
            }
        });
    </script>
@endif
