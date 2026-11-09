<div class="col-lg-8"><div class="card"><div class="card-body"><h3>Finance</h3><p>Cabang: {{ optional(Auth::user()->branchStore)->name ?: 'Belum ditentukan' }}</p>
@if(Auth::user()->branch_store_id && optional(Auth::user()->branchStore)->canRoleViewRevenueReport(Auth::user()->role))
<p>Lihat ringkasan dan rincian pendapatan cabang login.</p><a class="btn btn-primary" href="{{ route('revenue-report.index') }}">Laporan Revenue</a>
@else<p>Laporan Revenue belum tersedia untuk akun ini. Hubungi Owner untuk menentukan cabang dan mengatur izin Revenue.</p>@endif
</div></div></div>
