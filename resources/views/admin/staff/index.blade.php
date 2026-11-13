<div class="col-xl-12">
    <div class="page-title flex-wrap justify-content-end">
        {{-- <a href="{{ route('ptReportExcel') }}" class="btn btn-info">Download Excel</a> --}}
        @if ($pageType == "new")
            <a href="{{ route('old-staff', ['page' => $page ?: 'cs']) }}" id="staffDataSwitch" class="btn btn-secondary ms-3">Old Data</a>
        @else
            <a href="{{ route('staff.index', ['page' => $page ?: 'cs']) }}" id="staffDataSwitch" class="btn btn-primary ms-3">Current Data</a>
        @endif
                
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
                    @if ((Auth::user()->isAdmin() || Auth::user()->hasCurrentFeatureGrant()))
                        <li class="nav-item">
                            <a class="nav-link {{ $page == 'pt-user' ? 'show active' : '' }}" data-bs-toggle="tab"
                                href="#ptUser">
                                PT User
                            </a>
                        </li>
                    @endif
                    {{-- <li class="nav-item">
                        <a class="nav-link {{ $page == 'ci' ? 'show active' : '' }}" data-bs-toggle="tab"
                            href="#classInstructor">
                            Class Instructor
                        </a>
                    </li>                     --}}
                    @if ((Auth::user()->isAdmin() || Auth::user()->hasCurrentFeatureGrant()))
                        <li class="nav-item">
                            <a class="nav-link {{ $page == 'admin' ? 'show active' : '' }}" data-bs-toggle="tab"
                                href="#administrator">
                                Adminstrator
                            </a>
                        </li>
                    @endif
                    @if((Auth::user()->isAdmin() || Auth::user()->hasCurrentFeatureGrant()))
                        <li class="nav-item"><a class="nav-link {{ $page==='hrd' ? 'show active' : '' }}" data-bs-toggle="tab" href="#hrdAccounts">HRD</a></li>
                        <li class="nav-item"><a class="nav-link {{ $page==='finance' ? 'show active' : '' }}" data-bs-toggle="tab" href="#financeAccounts">Finance</a></li>
                    @endif
                </ul>
                <div class="tab-content">
                    @include('admin.staff.customer-service.index')
                    @include('admin.staff.fitness-consultant.index')
                    @include('admin.staff.personal-trainer.index')
                    @include('admin.staff.pt-user.index')
                    @include('admin.staff.administrator.index')
                    @include('admin.staff.class-instructor.index')
                    @if((Auth::user()->isAdmin() || Auth::user()->hasCurrentFeatureGrant()))
                        @include('admin.staff.role-accounts.index',['accountRole'=>'HRD','rolePrefix'=>'hrd','roleLabel'=>'HRD','tabId'=>'hrdAccounts','accounts'=>$roleAccounts->get('HRD',collect())])
                        @include('admin.staff.role-accounts.index',['accountRole'=>'FINANCE','rolePrefix'=>'finance','roleLabel'=>'Finance','tabId'=>'financeAccounts','accounts'=>$roleAccounts->get('FINANCE',collect())])
                    @endif
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

<script>
document.addEventListener('DOMContentLoaded',function(){
    const roleTables=[];
    if(window.DataTable){
        document.querySelectorAll('#staff-hrd-table, #staff-finance-table').forEach(function(table){
            roleTables.push(new DataTable(table,{autoWidth:false,order:[[1,'asc']],columnDefs:[{targets:6,orderable:false}]}));
        });
    }
    const pages={customerService:'cs',fitnessConsultant:'fc',personalTrainer:'pt',ptUser:'pt-user',administrator:'admin',hrdAccounts:'hrd',financeAccounts:'finance'};
    document.querySelectorAll('.custom-tab-1 > .nav-tabs a[data-bs-toggle="tab"]').forEach(function(tab){
        tab.addEventListener('shown.bs.tab',function(){
            roleTables.forEach(function(table){table.columns.adjust();});
            const page=pages[tab.getAttribute('href').slice(1)];if(!page)return;
            const url=new URL(window.location.href);url.searchParams.set('page',page);url.searchParams.delete('edit_account');window.history.replaceState(null,'',url);
            const switchLink=document.getElementById('staffDataSwitch');
            if(switchLink){const target=new URL(switchLink.href);target.searchParams.set('page',page);switchLink.href=target.toString();}
        });
    });
});
</script>
