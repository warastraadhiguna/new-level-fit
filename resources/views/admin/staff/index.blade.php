<div class="col-xl-12">
    <div class="page-title flex-wrap justify-content-end">
        <a href="{{ route('ptReportExcel') }}" class="btn btn-info">Download Excel</a>
    </div>
</div>

<div class="tab-content" id="myTabContent-1">
    <div class="tab-pane fade show active" id="#" role="tabpanel" aria-labelledby="home-tab">
        <div class="card-body pt-0">
            <!-- Nav tabs -->
            <div class="custom-tab-1">
                <ul class="nav nav-tabs">
                    <li class="nav-item active">
                        <a class="nav-link {{ !$page || $page == 'cs' ? 'show active' : '' }}" data-bs-toggle="tab"
                            href="#customerService">
                            Customer Service
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link {{ $page == 'fc' ? 'show active' : '' }}" data-bs-toggle="tab"
                            href="#fitnessConsultant">
                            Fitness Consultant
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link {{ $page == 'pt' ? 'show active' : '' }}" data-bs-toggle="tab"
                            href="#personalTrainer">
                            Personal Trainer
                        </a>
                    </li>
                    @if (Auth::user()->role == 'ADMIN')
                        <li class="nav-item">
                            <a class="nav-link {{ $page == 'admin' ? 'show active' : '' }}" data-bs-toggle="tab"
                                href="#administrator">
                                Adminstrator
                            </a>
                        </li>
                    @endif
                </ul>
                <div class="tab-content">
                    @include('admin.staff.customer-service.index')
                    @include('admin.staff.fitness-consultant.index')
                    @include('admin.staff.personal-trainer.index')
                    @include('admin.staff.administrator.index')
                </div>
            </div>
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
</script>
