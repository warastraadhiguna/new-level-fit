<!--**********************************
            Content body start
        ***********************************-->
<!--**********************************
            Content body start
        ***********************************-->
@include('sweetalert::alert')
<div class="content-body">
    <!-- row -->
    <div class="container-fluid">
        <!-- Row -->
        <div class="row">
            @if (session('payment_receipt_url'))
                <div class="col-12">
                    <div class="alert alert-success d-flex justify-content-between align-items-center flex-wrap gap-2">
                        <span>Pembayaran berhasil disimpan.</span>
                        <a href="{{ session('payment_receipt_url') }}" target="_blank" rel="noopener" class="btn btn-sm btn-primary">Cetak Struk</a>
                    </div>
                </div>
            @endif
            @if ($content)
                @include($content)
            @endif
        </div>
    </div>
</div>

<!--**********************************
            Content body end
        ***********************************-->
