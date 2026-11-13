<li>@if(app(\App\Support\RoleFeatureAccess::class)->allowsRoute(Auth::user(),'hrd.home'))<a href="{{ route('hrd.home') }}"><i class="material-symbols-outlined">home</i><span class="nav-text">HRD</span></a>@endif</li>
@if(optional(Auth::user()->branchStore)->face_enrollment_enabled && (app(\App\Support\RoleFeatureAccess::class)->allows(Auth::user(),'employees') || app(\App\Support\RoleFeatureAccess::class)->allows(Auth::user(),'enrollment')))
<li><a class="has-arrow" href="javascript:void(0);" aria-expanded="false"><i class="material-symbols-outlined">person</i><span class="nav-text">Staff</span></a><ul aria-expanded="false">
<li>@if(app(\App\Support\RoleFeatureAccess::class)->allowsRoute(Auth::user(),'employees.index'))<a href="{{ route('employees.index') }}">Data Pegawai</a>@endif</li>
<li>@if(app(\App\Support\RoleFeatureAccess::class)->allowsRoute(Auth::user(),'face-enrollment.index'))<a href="{{ route('face-enrollment.index') }}">Pendaftaran Wajah</a>@endif</li>
</ul></li>
@endif

@if(app(\App\Support\RoleFeatureAccess::class)->allows(Auth::user(),'revenue') && optional(Auth::user()->branchStore)->canRoleViewRevenueReport(Auth::user()->role))
<li><a href="{{ route('revenue-report.index') }}"><i class="material-icons">app_registration</i><span class="nav-text">Revenue</span></a></li>
@endif

@include('admin.employee-attendance.sidebar')
