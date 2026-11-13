@php
    $staffAccess=app(\App\Support\RoleFeatureAccess::class);
    $staffUser=Auth::user();
    $staffListVisible=$staffAccess->allowsRoute($staffUser,'staff.index');
    $attendanceMenuActive=request()->routeIs('employees.*','employee-schedules.*','employee-leaves.*','employee-attendance.*','face-enrollment.*');
    $attendanceSettingActive=request()->routeIs('attendance-device.*','employee-shifts.*','employee-positions.*');
    $staffMenuActive=$attendanceMenuActive || $attendanceSettingActive || request()->routeIs('staff.*');
    $attendanceLinks=collect([
        'employees.index'=>'Employees',
        'employee-schedules.index'=>'Schedules',
        'employee-leaves.index'=>'Day Off',
        'employee-attendance.report'=>'Attendance Report',
    ])->filter(fn($label,$route)=>$staffAccess->allowsRoute($staffUser,$route));
    $attendanceSettingLinks=collect([
        'attendance-device.index'=>'Perangkat absensi',
        'employee-shifts.index'=>'Shift template',
        'employee-positions.index'=>'Kategori Jabatan',
    ])->filter(fn($label,$route)=>$staffAccess->allowsRoute($staffUser,$route)
        && ($route!=='attendance-device.index' || ($staffUser->isOwner() && optional($staffUser->branchStore)->face_enrollment_enabled)));
@endphp
@if($staffListVisible || $attendanceLinks->isNotEmpty() || $attendanceSettingLinks->isNotEmpty())
<li class="staff-navigation {{ $staffMenuActive ? 'mm-active':'' }}">
    <a class="has-arrow" href="javascript:void(0);" aria-expanded="{{ $staffMenuActive ? 'true':'false' }}"><i class="material-symbols-outlined">person</i><span class="nav-text">Staff</span></a>
    <ul class="{{ $staffMenuActive ? 'mm-show':'' }}" aria-expanded="{{ $staffMenuActive ? 'true':'false' }}">
        @if($staffListVisible)<li><a href="{{ route('staff.index',['page'=>'cs']) }}">Staff List</a></li>@endif
        @if($attendanceLinks->isNotEmpty())
        <li class="{{ $attendanceMenuActive ? 'mm-active':'' }}"><a class="has-arrow" href="javascript:void(0);" aria-expanded="{{ $attendanceMenuActive ? 'true':'false' }}">Attendance</a>
            <ul class="{{ $attendanceMenuActive ? 'mm-show':'' }}" aria-expanded="{{ $attendanceMenuActive ? 'true':'false' }}">
                @foreach($attendanceLinks as $route=>$label)<li><a href="{{ route($route) }}">{{ $label }}</a></li>@endforeach
            </ul>
        </li>
        @endif
        @if($attendanceSettingLinks->isNotEmpty())
        <li class="{{ $attendanceSettingActive ? 'mm-active':'' }}"><a class="has-arrow" href="javascript:void(0);" aria-expanded="{{ $attendanceSettingActive ? 'true':'false' }}">Attendance Setting</a>
            <ul class="{{ $attendanceSettingActive ? 'mm-show':'' }}" aria-expanded="{{ $attendanceSettingActive ? 'true':'false' }}">
                @foreach($attendanceSettingLinks as $route=>$label)<li><a href="{{ route($route) }}">{{ $label }}</a></li>@endforeach
            </ul>
        </li>
        @endif
    </ul>
</li>
@endif
