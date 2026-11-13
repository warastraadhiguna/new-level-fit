@php
    $featureAccess=app(\App\Support\RoleFeatureAccess::class);
    $navigation=[
        'dashboard'=>'Dashboard','add-data'=>'Lead & Appointment','members.index'=>'Data Member',
        'history-member-registration'=>'Leading History',
        'member-active.index'=>'Membership','member-approval.index'=>'Approval Membership',
        'member-check-in.index'=>'Check-in Membership','member-payment.index'=>'Pembayaran Membership',
        'pt-history'=>'PT Leading History',
        'trainer-session.index'=>'PT','trainer-approval.index'=>'Approval PT',
        'trainer-session-check-in.index'=>'Check-in PT','pt-free.active'=>'PT Free',
        'pos.index'=>'POS Kasir',
        'pos-sales.index'=>'Riwayat Penjualan','pos-products.index'=>'POS Produk & Stok',
        'pos-purchases.index'=>'POS Pembelian','pos-suppliers.index'=>'POS Supplier',
        'revenue-report.index'=>'Laporan Revenue','report-member-pt-checkin'=>'Laporan Check-in',
        'member-package.index'=>'Paket Membership','trainer-package.index'=>'Paket PT',
        'payment-method.index'=>'Metode Pembayaran','class-session.index'=>'Kelas',
        'class-schedule.index'=>'Jadwal Kelas','studio.index'=>'Studio',
    ];
@endphp
@if(Auth::user()->isHrd())<li><a href="{{ route('hrd.home') }}">HRD</a></li>@endif
@if(Auth::user()->isFinance())<li><a href="{{ route('finance.home') }}">Finance</a></li>@endif
@foreach($navigation as $routeName=>$label)
    @if($featureAccess->allowsRoute(Auth::user(),$routeName))
        <li><a href="{{ route($routeName) }}"><span class="nav-text">{{ $label }}</span></a></li>
    @endif
@endforeach

@include('admin.includes.staff-sidebar')
