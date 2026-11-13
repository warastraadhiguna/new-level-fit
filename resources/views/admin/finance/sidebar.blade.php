<li>@if(app(\App\Support\RoleFeatureAccess::class)->allowsRoute(Auth::user(),'finance.home'))<a href="{{ route('finance.home') }}"><i class="material-symbols-outlined">home</i><span class="nav-text">Finance</span></a>@endif</li>
@if(optional(Auth::user()->branchStore)->canRoleViewRevenueReport(Auth::user()->role))
<li>@if(app(\App\Support\RoleFeatureAccess::class)->allowsRoute(Auth::user(),'revenue-report.index'))<a href="{{ route('revenue-report.index') }}"><i class="material-icons">app_registration</i><span class="nav-text">Revenue</span></a>@endif</li>
@endif

@include('admin.includes.staff-sidebar')
