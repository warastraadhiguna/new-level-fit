<!--**********************************
            Sidebar start
        ***********************************-->
<div class="dlabnav">
    <div class="dlabnav-scroll">
        <ul class="metismenu" id="menu">
            @if (app(\App\Support\RoleFeatureAccess::class)->allows(Auth::user(),'tip_tap_transfer') && optional(Auth::user()->branchStore)->tip_tap_transfer_enabled)
                <li>
                    @if(app(\App\Support\RoleFeatureAccess::class)->allowsRoute(Auth::user(),'tip-tap-transfer.index'))<a href="{{ route('tip-tap-transfer.index') }}" aria-expanded="false">
                        <i class="material-icons">archive</i>
                        <span class="nav-text">Export Tip-Tap</span>
                    </a>@endif
                </li>
            @endif
            @if(Auth::user()->isHrd())
                @include('admin.hrd.sidebar')
            @elseif(Auth::user()->isFinance())
                @include('admin.finance.sidebar')
            @else

            @if (Auth::user()->isAdmin() || Auth::user()->role == 'CS' || Auth::user()->role == 'FC' || Auth::user()->isPt())
                <li>
                    @if(app(\App\Support\RoleFeatureAccess::class)->allowsRoute(Auth::user(),'dashboard'))<a href="{{ route('dashboard') }}" aria-expanded="false">
                        <i class="material-symbols-outlined">home</i>
                        <span class="nav-text">Dashboard</span>
                    </a>@endif
                </li>
            @endif

            @if (!Auth::user()->isPt())
                <li @unless(app(\App\Support\RoleFeatureAccess::class)->allows(Auth::user(),'leads')) style="display:none" @endunless>
                    <a class="has-arrow " href="javascript:void(0);" aria-expanded="false">
                        <i class="material-icons">article</i>
                        <span class="nav-text">Lead</span>
                    </a>
                    <ul aria-expanded="false">
                        <li>@if(app(\App\Support\RoleFeatureAccess::class)->allowsRoute(Auth::user(),'add-data'))<a href="{{ route('add-data') }}">General Lead</a>@endif</li>
                        <li>@if(app(\App\Support\RoleFeatureAccess::class)->allowsRoute(Auth::user(),'one-day-visit-lead'))<a href="{{ route('one-day-visit-lead') }}">1 Day Visit Lead</a>@endif</li>
                    </ul>
                </li>
            @endif

            @if (Auth::user()->isAdmin() || Auth::user()->role == 'CS' || Auth::user()->role == 'FC')
                <li @unless(app(\App\Support\RoleFeatureAccess::class)->allows(Auth::user(),'members')) style="display:none" @endunless>
                    <a class="has-arrow " href="javascript:void(0);" aria-expanded="false">
                        <i class="material-symbols-outlined">diversity_3</i>
                        <span class="nav-text">Members</span>
                    </a>
                    <ul aria-expanded="false">
                        <li>@if(app(\App\Support\RoleFeatureAccess::class)->allowsRoute(Auth::user(),'members.index'))<a href="{{ route('members.index') }}">Member List</a>@endif</li>
                        <li>@if(app(\App\Support\RoleFeatureAccess::class)->allowsRoute(Auth::user(),'missed-guest.index'))<a href="{{ route('missed-guest.index') }}">Missed Guest</a>@endif</li>
                        {{-- <li>@if(app(\App\Support\RoleFeatureAccess::class)->allowsRoute(Auth::user(),'appointmentSchedule'))<a href="{{ route('appointmentSchedule') }}">Appointment Schedule</a>@endif</li> --}}
                        <li>@if(app(\App\Support\RoleFeatureAccess::class)->allowsRoute(Auth::user(),'oneDayVisit'))<a href="{{ route('oneDayVisit') }}">1 Day Visit</a>@endif</li>
                    </ul>
                </li>
            @endif

            @if (Auth::user()->isAdmin() || Auth::user()->role == 'CS')
                <li @unless(app(\App\Support\RoleFeatureAccess::class)->allows(Auth::user(),'membership')) style="display:none" @endunless>
                    <a class="has-arrow " href="javascript:void(0);" aria-expanded="false">
                        <i class="material-symbols-outlined">person_3</i>
                        <span class="nav-text">Membership</span>
                    </a>
                    <ul aria-expanded="false">
                        <li>@if(app(\App\Support\RoleFeatureAccess::class)->allowsRoute(Auth::user(),'member-check-in.index'))<a href="{{ route('member-check-in.index') }}">Check In/Out</a>@endif</li>
                        <li>@if(app(\App\Support\RoleFeatureAccess::class)->allowsRoute(Auth::user(),'member-active.index'))<a href="{{ route('member-active.index') }}">Member Active</a>@endif</li>
                        <li>@if(app(\App\Support\RoleFeatureAccess::class)->allowsRoute(Auth::user(),'member-pending'))<a href="{{ route('member-pending') }}">Member Pending</a>@endif</li>
                        <li>@if(app(\App\Support\RoleFeatureAccess::class)->allowsRoute(Auth::user(),'member-expired.index'))<a href="{{ route('member-expired.index') }}">Member Expired</a>@endif
                        <li>@if(app(\App\Support\RoleFeatureAccess::class)->allowsRoute(Auth::user(),'member-unpaid'))<a href="{{ route('member-unpaid') }}">Unpaid Member</a>@endif
                        @if (Auth::user()->isAdmin() && optional(Auth::user()->branchStore)->member_approval_enabled)
                            <li>@if(app(\App\Support\RoleFeatureAccess::class)->allowsRoute(Auth::user(),'member-approval.index'))<a href="{{ route('member-approval.index') }}">Member Approval</a>@endif</li>
                        @endif
                        <li>@if(app(\App\Support\RoleFeatureAccess::class)->allowsRoute(Auth::user(),'history-member-registration'))<a href="{{ route('history-member-registration') }}">History</a>@endif
                        </li>
                    </ul>
                </li>
            @endif

            <li @unless(app(\App\Support\RoleFeatureAccess::class)->allows(Auth::user(),'pt')) style="display:none" @endunless>
                <a class="has-arrow " href="javascript:void(0);" aria-expanded="false">
                    <i class="material-symbols-outlined">group</i>
                    <span class="nav-text">PT</span>
                </a>
                <ul aria-expanded="false">
                    @if (!Auth::user()->isPt())
                        <li>@if(app(\App\Support\RoleFeatureAccess::class)->allowsRoute(Auth::user(),'trainer-session.create'))<a href="{{ route('trainer-session.create') }}">PT Registration</a>@endif</li>
                    @endif
                    @if (Auth::user()->isAdmin() || Auth::user()->role == 'CS' || Auth::user()->isPt())
                        @if (!Auth::user()->isPt())
                            <li>@if(app(\App\Support\RoleFeatureAccess::class)->allowsRoute(Auth::user(),'trainer-session-check-in.index'))<a href="{{ route('trainer-session-check-in.index') }}">Check In/Out</a>@endif</li>
                        @endif
                        <li>@if(app(\App\Support\RoleFeatureAccess::class)->allowsRoute(Auth::user(),'trainer-session.index'))<a href="{{ route('trainer-session.index') }}">PT Active</a>@endif</li>
                        <li>@if(app(\App\Support\RoleFeatureAccess::class)->allowsRoute(Auth::user(),'trainer-session-pending'))<a href="{{ route('trainer-session-pending') }}">PT Pending</a>@endif</li>
                        <li>@if(app(\App\Support\RoleFeatureAccess::class)->allowsRoute(Auth::user(),'trainer-session-unpaid'))<a href="{{ route('trainer-session-unpaid') }}">PT Unpaid</a>@endif</li>
                        <li>@if(app(\App\Support\RoleFeatureAccess::class)->allowsRoute(Auth::user(),'trainer-session-over.index'))<a href="{{ route('trainer-session-over.index') }}">PT Expired</a>@endif</li>
                        <li>@if(app(\App\Support\RoleFeatureAccess::class)->allowsRoute(Auth::user(),'trainer-session-waiting-list'))<a href="{{ route('trainer-session-waiting-list') }}">PT Waiting List</a>@endif</li>
                        @if (!Auth::user()->isPt())
                            <li>@if(app(\App\Support\RoleFeatureAccess::class)->allowsRoute(Auth::user(),'lgt'))<a href="{{ route('lgt') }}">LGT</a>@endif</li>
                            @if (Auth::user()->isAdmin() && optional(Auth::user()->branchStore)->trainer_approval_enabled)
                                <li>@if(app(\App\Support\RoleFeatureAccess::class)->allowsRoute(Auth::user(),'trainer-approval.index'))<a href="{{ route('trainer-approval.index') }}">PT Approval</a>@endif</li>
                            @endif
                            <li>@if(app(\App\Support\RoleFeatureAccess::class)->allowsRoute(Auth::user(),'pt-history'))<a href="{{ route('pt-history') }}">History</a>@endif</li>
                        @endif
                    @endif
                </ul>
            </li>

            @if (optional(Auth::user()->branchStore)->pt_free_enabled)
                <li @unless(app(\App\Support\RoleFeatureAccess::class)->allows(Auth::user(),'pt_free')) style="display:none" @endunless>
                    <a class="has-arrow" href="javascript:void(0);" aria-expanded="false">
                        <i class="material-symbols-outlined">group</i>
                        <span class="nav-text">PT Free</span>
                    </a>
                    <ul aria-expanded="false">
                        @if (Auth::user()->isAdmin())
                            <li>@if(app(\App\Support\RoleFeatureAccess::class)->allowsRoute(Auth::user(),'pt-free.create'))<a href="{{ route('pt-free.create') }}">PT Registration</a>@endif</li>
                        @endif
                        @if (Auth::user()->isAdmin() || Auth::user()->role == 'CS' || Auth::user()->isPt())
                            @if (!Auth::user()->isPt())
                                <li>@if(app(\App\Support\RoleFeatureAccess::class)->allowsRoute(Auth::user(),'pt-free.check-in.index'))<a href="{{ route('pt-free.check-in.index') }}">Check In/Out</a>@endif</li>
                            @endif
                            <li>@if(app(\App\Support\RoleFeatureAccess::class)->allowsRoute(Auth::user(),'pt-free.active'))<a href="{{ route('pt-free.active') }}">PT Active</a>@endif</li>
                            <li>@if(app(\App\Support\RoleFeatureAccess::class)->allowsRoute(Auth::user(),'pt-free.pending'))<a href="{{ route('pt-free.pending') }}">PT Pending</a>@endif</li>
                            <li>@if(app(\App\Support\RoleFeatureAccess::class)->allowsRoute(Auth::user(),'pt-free.expired'))<a href="{{ route('pt-free.expired') }}">PT Expired</a>@endif</li>
                            <li>@if(app(\App\Support\RoleFeatureAccess::class)->allowsRoute(Auth::user(),'pt-free.waiting-list'))<a href="{{ route('pt-free.waiting-list') }}">PT Waiting List</a>@endif</li>
                            @if (!Auth::user()->isPt())
                                <li>@if(app(\App\Support\RoleFeatureAccess::class)->allowsRoute(Auth::user(),'pt-free.history'))<a href="{{ route('pt-free.history') }}">History</a>@endif</li>
                            @endif
                        @endif
                    </ul>
                </li>
            @endif

            @if (Auth::user()->isAdmin())
                <li @unless(app(\App\Support\RoleFeatureAccess::class)->allows(Auth::user(),'staff') || app(\App\Support\RoleFeatureAccess::class)->allows(Auth::user(),'employees') || app(\App\Support\RoleFeatureAccess::class)->allows(Auth::user(),'enrollment')) style="display:none" @endunless>
                    <a class="has-arrow" href="javascript:void(0);" aria-expanded="false">
                        <i class="material-symbols-outlined">person</i>
                        <span class="nav-text">Staff</span>
                    </a>
                    <ul aria-expanded="false">
                        <li>@if(app(\App\Support\RoleFeatureAccess::class)->allowsRoute(Auth::user(),'staff.index'))<a href="{{ route('staff.index', ['page' => 'cs']) }}">Staff List</a>@endif</li>
                        @if (optional(Auth::user()->branchStore)->face_enrollment_enabled)
                            <li>@if(app(\App\Support\RoleFeatureAccess::class)->allowsRoute(Auth::user(),'employees.index'))<a href="{{ route('employees.index') }}">Data Pegawai</a>@endif</li>
                            <li>@if(app(\App\Support\RoleFeatureAccess::class)->allowsRoute(Auth::user(),'face-enrollment.index'))<a href="{{ route('face-enrollment.index') }}">Pendaftaran Wajah</a>@endif</li>
                        @endif
                        @if (Auth::user()->isOwner() && optional(Auth::user()->branchStore)->face_enrollment_enabled)
                            <li>@if(app(\App\Support\RoleFeatureAccess::class)->allowsRoute(Auth::user(),'attendance-device.index'))<a href="{{ route('attendance-device.index') }}">Perangkat Absensi</a>@endif</li>
                        @endif
                    </ul>
                </li>
            @endif

            @if (in_array(strtoupper((string) Auth::user()->role), ['OWNER', 'ADMIN', 'CS', 'CSPOS'], true) && optional(Auth::user()->branchStore)->pos_inventory_enabled)
                <li @unless(app(\App\Support\RoleFeatureAccess::class)->allows(Auth::user(),'pos_sales') || app(\App\Support\RoleFeatureAccess::class)->allows(Auth::user(),'pos_inventory')) style="display:none" @endunless>
                    <a class="has-arrow" href="javascript:void(0);" aria-expanded="false">
                        <i class="material-symbols-outlined">point_of_sale</i>
                        <span class="nav-text">POS</span>
                    </a>
                    <ul aria-expanded="false">
                        <li>@if(app(\App\Support\RoleFeatureAccess::class)->allowsRoute(Auth::user(),'pos.index'))<a href="{{ route('pos.index') }}">Kasir</a>@endif</li>
                        <li>@if(app(\App\Support\RoleFeatureAccess::class)->allowsRoute(Auth::user(),'pos-sales.index'))<a href="{{ route('pos-sales.index') }}">Riwayat Penjualan</a>@endif</li>
                        @if (Auth::user()->isAdmin())
                            <li>@if(app(\App\Support\RoleFeatureAccess::class)->allowsRoute(Auth::user(),'pos-products.index'))<a href="{{ route('pos-products.index') }}">Produk & Stok</a>@endif</li>
                            <li>@if(app(\App\Support\RoleFeatureAccess::class)->allowsRoute(Auth::user(),'pos-purchases.index'))<a href="{{ route('pos-purchases.index') }}">Pembelian</a>@endif</li>
                            <li>@if(app(\App\Support\RoleFeatureAccess::class)->allowsRoute(Auth::user(),'pos-suppliers.index'))<a href="{{ route('pos-suppliers.index') }}">Supplier</a>@endif</li>
                            <li>@if(app(\App\Support\RoleFeatureAccess::class)->allowsRoute(Auth::user(),'pos-stock.index'))<a href="{{ route('pos-stock.index') }}">Kartu & Penyesuaian Stok</a>@endif</li>
                        @endif
                    </ul>
                </li>
            @endif

            @php
                $canViewRevenueReport = optional(Auth::user()->branchStore)
                    ->canRoleViewRevenueReport(Auth::user()->role);
            @endphp
                <li @unless(app(\App\Support\RoleFeatureAccess::class)->allows(Auth::user(),'reports') || app(\App\Support\RoleFeatureAccess::class)->allows(Auth::user(),'revenue')) style="display:none" @endunless><a class="has-arrow " href="javascript:void(0);" aria-expanded="false">
                        <i class="material-icons"> app_registration </i>
                        <span class="nav-text">Report</span>
                    </a>
                    <ul aria-expanded="false">
                    @if ($canViewRevenueReport)
                        <li>@if(app(\App\Support\RoleFeatureAccess::class)->allowsRoute(Auth::user(),'revenue-report.index'))<a href="{{ route('revenue-report.index') }}">Revenue</a>@endif</li>
                    @endif
                    @if (Auth::user()->isPt())
                    <li>
                        <a class="has-arrow" href="javascript:void(0);" aria-expanded="false">PT</a>
                        <ul aria-expanded="false">
                            <li style="margin-left: 10px">@if(app(\App\Support\RoleFeatureAccess::class)->allowsRoute(Auth::user(),'report-member-pt-checkin'))<a href="{{ route('report-member-pt-checkin') }}">Check In</a>@endif</li>
                        </ul>
                    </li>
                    @endif
                    @if (!Auth::user()->isPt())
                    {{-- <li><a class="has-arrow" href="javascript:void(0);" aria-expanded="false">PT</a>
                        <ul aria-expanded="false">
                            <li style="margin-left: 10px">@if(app(\App\Support\RoleFeatureAccess::class)->allowsRoute(Auth::user(),'pt-total-report'))<a href="{{ route('pt-total-report') }}">PT Total</a>@endif
                            </li>
                            <li style="margin-left: 10px">@if(app(\App\Support\RoleFeatureAccess::class)->allowsRoute(Auth::user(),'pt-detail-report'))<a href="{{ route('pt-detail-report') }}">PT Detail</a>@endif
                            </li>
                        </ul>
                    </li> --}}
                    <li>@if(app(\App\Support\RoleFeatureAccess::class)->allowsRoute(Auth::user(),'lo-report'))<a href="{{ route('lo-report') }}">LO</a>@endif</li>
                    {{-- <li>@if(app(\App\Support\RoleFeatureAccess::class)->allowsRoute(Auth::user(),'one-visit-report'))<a href="{{ route('one-visit-report') }}">1 Visit</a>@endif</li> --}}
                    <li>@if(app(\App\Support\RoleFeatureAccess::class)->allowsRoute(Auth::user(),'report-member-checkin'))<a href="{{ route('report-member-checkin') }}">Member Check In</a>@endif</li>
                    <li>
                        <a class="has-arrow" href="javascript:void(0);" aria-expanded="false">PT</a>
                        <ul aria-expanded="false">
                            <li style="margin-left: 10px">@if(app(\App\Support\RoleFeatureAccess::class)->allowsRoute(Auth::user(),'report-member-pt-checkin'))<a href="{{ route('report-member-pt-checkin') }}">Check In</a>@endif</li>
                            @if (optional(Auth::user()->branchStore)->pt_free_enabled)
                                <li style="margin-left: 10px">@if(app(\App\Support\RoleFeatureAccess::class)->allowsRoute(Auth::user(),'pt-free.report'))<a href="{{ route('pt-free.report') }}">Free Check In</a>@endif</li>
                            @endif
                        </ul>
                    </li>
                    <li>
                        <a class="has-arrow" href="javascript:void(0);" aria-expanded="false">Lead</a>
                        <ul aria-expanded="false">
                            <li style="margin-left: 10px">
                                <a class="has-arrow" href="javascript:void(0);" aria-expanded="false">General</a>
                                <ul aria-expanded="false">
                                    <li style="margin-left: 10px">@if(app(\App\Support\RoleFeatureAccess::class)->allowsRoute(Auth::user(),'fc-total-report-member-checkin'))<a
                                            href="{{ route('fc-total-report-member-checkin') }}">Total</a>@endif</li>
                                    <li style="margin-left: 10px">@if(app(\App\Support\RoleFeatureAccess::class)->allowsRoute(Auth::user(),'fc-detail-report-member-checkin'))<a
                                            href="{{ route('fc-detail-report-member-checkin') }}">Detail</a>@endif</li>
                                </ul>
                            </li>
                            <li style="margin-left: 10px">
                                <a class="has-arrow" href="javascript:void(0);" aria-expanded="false">PT Selling</a>
                                <ul aria-expanded="false">
                                    <li style="margin-left: 10px">@if(app(\App\Support\RoleFeatureAccess::class)->allowsRoute(Auth::user(),'fc-total-report-pt'))<a href="{{ route('fc-total-report-pt') }}">Total</a>@endif
                                    </li>
                                    <li style="margin-left: 10px">@if(app(\App\Support\RoleFeatureAccess::class)->allowsRoute(Auth::user(),'fc-detail-report-pt'))<a
                                            href="{{ route('fc-detail-report-pt') }}">Detail</a>@endif</li>
                                </ul>
                            </li>
                        </ul>
                    </li>
                    @endif
                    </ul>
                </li>

            @if (Auth::user()->isAdmin())
                {{-- <li>
                    <a class="has-arrow " href="javascript:void(0);" aria-expanded="false">
                        <i class="material-icons"> flight_class </i>
                        <span class="nav-text">Class</span>
                    </a>
                    <ul aria-expanded="false">
                        <li>@if(app(\App\Support\RoleFeatureAccess::class)->allowsRoute(Auth::user(),'class-session.index'))<a href="{{ route('class-session.index') }}">Class Session</a>@endif</li>
                        <li>@if(app(\App\Support\RoleFeatureAccess::class)->allowsRoute(Auth::user(),'class-schedule.index'))<a href="{{ route('class-schedule.index') }}">Class Schedule</a>@endif</li>
                    </ul>
                </li>             --}}
                <li @unless(app(\App\Support\RoleFeatureAccess::class)->allows(Auth::user(),'packages')) style="display:none" @endunless>
                    <a class="has-arrow " href="javascript:void(0);" aria-expanded="false">
                        <i class="material-icons"> insert_drive_file </i>
                        <span class="nav-text">Setting</span>
                    </a>
                    <ul aria-expanded="false">
                        <li>@if(app(\App\Support\RoleFeatureAccess::class)->allowsRoute(Auth::user(),'member-package.index'))<a href="{{ route('member-package.index') }}">Member Package</a>@endif</li>
                        <li>@if(app(\App\Support\RoleFeatureAccess::class)->allowsRoute(Auth::user(),'payment-method.index'))<a href="{{ route('payment-method.index') }}">Payment Method</a>@endif</li>
                        <li>@if(app(\App\Support\RoleFeatureAccess::class)->allowsRoute(Auth::user(),'trainer-package.index'))<a href="{{ route('trainer-package.index') }}">Trainer Package</a>@endif</li>
                        @if(Auth::user()->isOwner())
                            <li><a href="{{ route('role-feature-access.index') }}">Pengaturan Akses Role</a></li>
                        @endif
                    </ul>
                </li>
            @endif
            @endif
        </ul>
        <div class="copyright">
            <p>Hi, <b>{{ Auth::user()->full_name }}</b></p>
        </div>
    </div>
</div>
<!--**********************************
            Sidebar end
        ***********************************-->
