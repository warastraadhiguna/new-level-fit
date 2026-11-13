<li>@if(app(\App\Support\RoleFeatureAccess::class)->allowsRoute(Auth::user(),'hrd.home'))<a href="{{ route('hrd.home') }}"><i class="material-symbols-outlined">home</i><span class="nav-text">HRD</span></a>@endif</li>
@include('admin.includes.staff-sidebar')

@if(app(\App\Support\RoleFeatureAccess::class)->allows(Auth::user(),'revenue') && optional(Auth::user()->branchStore)->canRoleViewRevenueReport(Auth::user()->role))
<li><a href="{{ route('revenue-report.index') }}"><i class="material-icons">app_registration</i><span class="nav-text">Revenue</span></a></li>
@endif
