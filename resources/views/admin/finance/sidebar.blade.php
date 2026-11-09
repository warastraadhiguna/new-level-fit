<li><a href="{{ route('finance.home') }}"><i class="material-symbols-outlined">home</i><span class="nav-text">Finance</span></a></li>
@if(optional(Auth::user()->branchStore)->canRoleViewRevenueReport(Auth::user()->role))
<li><a href="{{ route('revenue-report.index') }}"><i class="material-icons">app_registration</i><span class="nav-text">Revenue</span></a></li>
@endif
