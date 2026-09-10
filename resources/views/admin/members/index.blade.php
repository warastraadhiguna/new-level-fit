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
                                <th>Small Photo</th>
                                <th>
                                    <a href="{{ $sortLink('full_name') }}" class="text-primary">
                                        Member <i class="fa {{ $sortIcon('full_name') }}"></i>
                                    </a>
                                </th>
                                <th>Info</th>
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
                                        @if ($item->photos)
                                            @if ($item->small_photos)
                                                <a href="javascript:void(0)" class="text-success small-photo-link"
                                                    data-bs-toggle="modal" data-bs-target="#smallPhotoModal"
                                                    data-member-name="{{ $item->full_name }}"
                                                    data-photo-url="{{ Storage::url($item->photos) }}"
                                                    data-small-photo-url="{{ Storage::url($item->small_photos) }}"
                                                    data-update-url="{{ route('members.small-photo.update', $item->id) }}">
                                                    Available
                                                </a>
                                                <div class="mt-2">
                                                    <img src="{{ Storage::url($item->small_photos) }}" width="64" height="64"
                                                        style="object-fit: cover;" alt="small photo">
                                                </div>
                                            @else
                                                <a href="javascript:void(0)" class="text-danger small-photo-link"
                                                    data-bs-toggle="modal" data-bs-target="#smallPhotoModal"
                                                    data-member-name="{{ $item->full_name }}"
                                                    data-photo-url="{{ Storage::url($item->photos) }}"
                                                    data-small-photo-url=""
                                                    data-update-url="{{ route('members.small-photo.update', $item->id) }}">
                                                    Not Available
                                                </a>
                                            @endif
                                        @else
                                            <span class="text-muted">Main Photo Not Available</span>
                                        @endif
                                    </td>
                                    <td>
                                        <h6>{{ $item->full_name }}</h6>
                                        <span class="text-muted">{{ $item->member_code ?? 'No Member Code' }}</span>
                                        <div class="mt-2">
                                            <span class="badge badge-primary">{{ $item->branch_store_name }}</span>
                                        </div>
                                    </td>
                                    <td>
                                        <small class="text-muted d-block">Phone Number</small>
                                        <h6 class="mb-2">{{ $item->phone_number ?? 'No Data' }}</h6>
                                        <small class="text-muted d-block">Date of Birth</small>
                                        <h6 class="mb-2">{{ DateFormat($item->born, 'DD MMMM YYYY') ?? 'No Data' }}</h6>
                                        <small class="text-muted d-block">Created At</small>
                                        <h6 class="mb-0">{{ DateFormat($item->created_at, 'DD MMMM YYYY') ?? 'No Data' }}</h6>
                                    </td>
                                    <td>
                                        <div class="btn-group dropstart" role="group">
                                            <button type="button" class="btn btn-primary btn-xs dropdown-toggle"
                                                style="width: 100px" data-bs-toggle="dropdown" aria-expanded="false">
                                                Action
                                            </button>
                                            <ul class="dropdown-menu">
                                                <li>
                                                    <a href="{{ route('edit-member-sell', $item->id) }}"
                                                        class="btn light btn-warning btn-xs btn-block mb-1">Edit Member</a>
                                                </li>
                                                <li>
                                                    <a href="{{ route('members.show', $item->id) }}"
                                                        class="btn light btn-info btn-xs btn-block mb-1">Detail Member</a>
                                                </li>
                                                <li>
                                                    <a href="{{ route('members.membership-history', $item->id) }}"
                                                        class="btn light btn-secondary btn-xs btn-block mb-1">Membership History</a>
                                                </li>
                                                <li>
                                                    <a href="{{ route('members.pt-history', $item->id) }}"
                                                        class="btn light btn-dark btn-xs btn-block mb-1">PT History</a>
                                                </li>
                                                @if (Auth::user()->isAdmin())
                                                    <li>
                                                        <a href="{{ route('members.create-membership', $item->id) }}"
                                                            class="btn light btn-primary btn-xs btn-block mb-1">Create Membership</a>
                                                    </li>
                                                    @if ($ptFreeEnabled)
                                                        <li>
                                                            <button type="button"
                                                                class="btn light btn-success btn-xs btn-block mb-1 js-give-pt-free"
                                                                data-bs-toggle="modal" data-bs-target="#ptFreeModal"
                                                                data-member-id="{{ $item->id }}"
                                                                data-member-name="{{ $item->full_name }}"
                                                                data-store-url="{{ route('pt-free.store', $item->id) }}">
                                                                Give PT Free
                                                            </button>
                                                        </li>
                                                    @endif
                                                @endif
                                                <li>
                                                    @if ($item->lo_is_used == 0)
                                                        <a href="{{ route('useLayoutOrientation', $item->id) }}"
                                                            class="btn btn-dark btn-xs mb-1 btn-block">LO</a>
                                                    @elseif (!$item->lo_end)
                                                        <form action="{{ route('stopLayoutOrientation', $item->id) }}" method="POST">
                                                            @csrf
                                                            <button type="submit" class="btn btn-dark btn-xs mb-1 btn-block">Stop LO(Running)</button>
                                                        </form>
                                                    @else
                                                        <button type="button" class="btn btn-dark btn-xs mb-1 btn-block"
                                                            data-bs-toggle="popover" data-bs-title="Check In tanpa kartu"
                                                            data-bs-content="Member ini sudah menggunakan Layout Orientation">
                                                            <span class="text-danger">X</span> LO is used<span class="text-danger">X</span>
                                                        </button>
                                                    @endif
                                                </li>
                                                @if (Auth::user()->isAdmin())
                                                    <li>
                                                        <form action="{{ route('member.destroy', $item->id) }}"
                                                            onsubmit="return confirm('Delete Data ?')" method="POST">
                                                            @method('delete')
                                                            @csrf
                                                            <button type="submit"
                                                                class="btn light btn-danger btn-xs btn-block mb-1">Delete</button>
                                                        </form>
                                                    </li>
                                                @endif
                                            </ul>
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                            @if ($members->count() == 0)
                                <tr>
                                    <td colspan="6" class="text-center">No data found</td>
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

@if ($ptFreeEnabled && Auth::user()->isAdmin())
    <div class="modal fade" id="ptFreeModal" tabindex="-1" aria-labelledby="ptFreeModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <form method="POST" id="ptFreeForm"
                    action="{{ old('pt_free_member_id') ? route('pt-free.store', old('pt_free_member_id')) : '#' }}">
                    @csrf
                    <input type="hidden" name="_submission_token" value="{{ (string) \Illuminate\Support\Str::uuid() }}">
                    <input type="hidden" name="pt_free_member_id" id="ptFreeMemberId" value="{{ old('pt_free_member_id') }}">
                    <div class="modal-header">
                        <div>
                            <h5 class="modal-title" id="ptFreeModalLabel">Give PT Free</h5>
                            <small class="text-muted" id="ptFreeMemberName"></small>
                        </div>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        @if ($errors->any() && old('pt_free_member_id'))
                            <div class="alert alert-danger">{{ $errors->first() }}</div>
                        @endif
                        @if ($ptFreePackages->isEmpty())
                            <div class="alert alert-warning">
                                Belum ada Trainer Package dengan harga paket dan admin Rp0 pada cabang ini.
                            </div>
                        @endif
                        <div class="mb-3">
                            <label class="form-label">PT Free Package</label>
                            <select name="trainer_package_id" class="form-control" required
                                {{ $ptFreePackages->isEmpty() ? 'disabled' : '' }}>
                                <option value="">-- Choose Package --</option>
                                @foreach ($ptFreePackages as $package)
                                    <option value="{{ $package->id }}" {{ (string) old('trainer_package_id') === (string) $package->id ? 'selected' : '' }}>
                                        {{ $package->package_name }} · {{ $package->number_of_session }} sesi · {{ $package->days }} hari
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Personal Trainer</label>
                            <select name="trainer_id" class="form-control" required>
                                <option value="">-- Choose Trainer --</option>
                                @foreach ($ptFreeTrainers as $trainer)
                                    <option value="{{ $trainer->id }}" {{ (string) old('trainer_id') === (string) $trainer->id ? 'selected' : '' }}>
                                        {{ $trainer->full_name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Start Date</label>
                            <input type="date" name="start_date" class="form-control"
                                value="{{ old('start_date', now()->format('Y-m-d')) }}" required>
                        </div>
                        <div>
                            <label class="form-label">Description / Gift Reason</label>
                            <textarea name="description" rows="4" class="form-control" required>{{ old('description') }}</textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-light" data-bs-dismiss="modal">Close</button>
                        <button type="submit" class="btn btn-success" {{ $ptFreePackages->isEmpty() ? 'disabled' : '' }}>
                            Give PT Free
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            var form = document.getElementById('ptFreeForm');
            var memberId = document.getElementById('ptFreeMemberId');
            var memberName = document.getElementById('ptFreeMemberName');

            document.querySelectorAll('.js-give-pt-free').forEach(function (button) {
                button.addEventListener('click', function () {
                    form.action = button.dataset.storeUrl;
                    memberId.value = button.dataset.memberId;
                    memberName.textContent = button.dataset.memberName;
                });
            });

            form.addEventListener('submit', function () {
                var submitButton = form.querySelector('button[type="submit"]');
                submitButton.disabled = true;
                submitButton.textContent = 'Saving...';
            });

            @if ($errors->any() && old('pt_free_member_id'))
                var previousButton = document.querySelector('.js-give-pt-free[data-member-id="{{ old('pt_free_member_id') }}"]');
                if (previousButton) {
                    memberName.textContent = previousButton.dataset.memberName;
                }
                if (window.bootstrap) {
                    bootstrap.Modal.getOrCreateInstance(document.getElementById('ptFreeModal')).show();
                }
            @endif
        });
    </script>
@endif

<style>
    .small-photo-crop-stage {
        width: 320px;
        max-width: 100%;
        margin: 0 auto;
    }

    #smallPhotoCanvas {
        width: 100%;
        aspect-ratio: 1 / 1;
        cursor: move;
        border: 1px solid #e5e7eb;
        background: #f8f9fa;
    }
</style>

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

<div class="modal fade" id="smallPhotoModal" tabindex="-1" aria-labelledby="smallPhotoModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content">
            <form method="POST" id="smallPhotoForm">
                @csrf
                <input type="hidden" name="small_photo_data" id="smallPhotoData">
                <div class="modal-header">
                    <h1 class="modal-title fs-5" id="smallPhotoModalLabel">Update Small Photo</h1>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="row align-items-start">
                        <div class="col-lg-7">
                            <div class="small-photo-crop-stage">
                                <canvas id="smallPhotoCanvas" width="320" height="320"></canvas>
                            </div>
                            <div class="mt-3">
                                <label class="form-label">Zoom</label>
                                <input type="range" class="form-range" id="smallPhotoZoom" min="1" max="3" step="0.01" value="1">
                            </div>
                        </div>
                        <div class="col-lg-5">
                            <p class="mb-2"><strong id="smallPhotoMemberName"></strong></p>
                            <div id="currentSmallPhotoWrap" class="mb-3 d-none">
                                <label class="form-label">Current Small Photo</label>
                                <div>
                                    <img src="" id="currentSmallPhoto" width="120" height="120" style="object-fit: cover;" alt="current small photo">
                                </div>
                            </div>
                            <p class="text-muted mb-0">Geser gambar di kotak crop, atur zoom, lalu simpan.</p>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="submit" class="btn btn-primary">Save Small Photo</button>
                    <button type="button" class="btn btn-danger light" data-bs-dismiss="modal">Close</button>
                </div>
            </form>
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

        const cropLinks = document.querySelectorAll('.small-photo-link');
        const cropForm = document.getElementById('smallPhotoForm');
        const cropCanvas = document.getElementById('smallPhotoCanvas');
        const cropData = document.getElementById('smallPhotoData');
        const zoomInput = document.getElementById('smallPhotoZoom');
        const memberName = document.getElementById('smallPhotoMemberName');
        const currentSmallPhotoWrap = document.getElementById('currentSmallPhotoWrap');
        const currentSmallPhoto = document.getElementById('currentSmallPhoto');
        const cropContext = cropCanvas ? cropCanvas.getContext('2d') : null;
        const cropImage = new Image();
        let cropScale = 1;
        let baseScale = 1;
        let offsetX = 0;
        let offsetY = 0;
        let isDragging = false;
        let dragStartX = 0;
        let dragStartY = 0;

        const drawCrop = function() {
            if (!cropContext || !cropImage.complete || cropImage.naturalWidth === 0) {
                return;
            }

            const canvasSize = cropCanvas.width;
            const drawWidth = cropImage.naturalWidth * baseScale * cropScale;
            const drawHeight = cropImage.naturalHeight * baseScale * cropScale;
            const minX = canvasSize - drawWidth;
            const minY = canvasSize - drawHeight;

            offsetX = Math.min(0, Math.max(minX, offsetX));
            offsetY = Math.min(0, Math.max(minY, offsetY));

            cropContext.clearRect(0, 0, canvasSize, canvasSize);
            cropContext.drawImage(cropImage, offsetX, offsetY, drawWidth, drawHeight);
        };

        const canvasPoint = function(event) {
            const rect = cropCanvas.getBoundingClientRect();
            const touch = event.touches ? event.touches[0] : event;

            return {
                x: (touch.clientX - rect.left) * (cropCanvas.width / rect.width),
                y: (touch.clientY - rect.top) * (cropCanvas.height / rect.height),
            };
        };

        cropLinks.forEach(function(link) {
            link.addEventListener('click', function() {
                const photoUrl = link.dataset.photoUrl;
                const smallPhotoUrl = link.dataset.smallPhotoUrl;

                cropForm.action = link.dataset.updateUrl;
                memberName.textContent = link.dataset.memberName;
                cropData.value = '';
                zoomInput.value = '1';
                cropScale = 1;

                if (smallPhotoUrl) {
                    currentSmallPhoto.src = smallPhotoUrl;
                    currentSmallPhotoWrap.classList.remove('d-none');
                } else {
                    currentSmallPhoto.src = '';
                    currentSmallPhotoWrap.classList.add('d-none');
                }

                cropImage.onload = function() {
                    baseScale = Math.max(cropCanvas.width / cropImage.naturalWidth, cropCanvas.height / cropImage.naturalHeight);
                    offsetX = (cropCanvas.width - cropImage.naturalWidth * baseScale) / 2;
                    offsetY = (cropCanvas.height - cropImage.naturalHeight * baseScale) / 2;
                    drawCrop();
                };

                cropImage.src = photoUrl;
            });
        });

        if (zoomInput) {
            zoomInput.addEventListener('input', function() {
                cropScale = parseFloat(zoomInput.value);
                drawCrop();
            });
        }

        if (cropCanvas) {
            cropCanvas.addEventListener('mousedown', function(event) {
                isDragging = true;
                const point = canvasPoint(event);
                dragStartX = point.x - offsetX;
                dragStartY = point.y - offsetY;
            });

            cropCanvas.addEventListener('mousemove', function(event) {
                if (!isDragging) {
                    return;
                }

                const point = canvasPoint(event);
                offsetX = point.x - dragStartX;
                offsetY = point.y - dragStartY;
                drawCrop();
            });

            window.addEventListener('mouseup', function() {
                isDragging = false;
            });

            cropCanvas.addEventListener('touchstart', function(event) {
                isDragging = true;
                const point = canvasPoint(event);
                dragStartX = point.x - offsetX;
                dragStartY = point.y - offsetY;
            }, { passive: true });

            cropCanvas.addEventListener('touchmove', function(event) {
                if (!isDragging) {
                    return;
                }

                const point = canvasPoint(event);
                offsetX = point.x - dragStartX;
                offsetY = point.y - dragStartY;
                drawCrop();
            }, { passive: true });

            window.addEventListener('touchend', function() {
                isDragging = false;
            });
        }

        if (cropForm) {
            cropForm.addEventListener('submit', function(event) {
                drawCrop();
                cropData.value = cropCanvas.toDataURL('image/jpeg', 0.72);

                if (!cropData.value) {
                    event.preventDefault();
                }
            });
        }
    });
</script>
