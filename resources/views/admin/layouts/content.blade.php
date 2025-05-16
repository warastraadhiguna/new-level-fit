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
            @if ($content)
                @include($content)
            @endif
        </div>
    </div>
</div>

<!--**********************************
            Content body end
        ***********************************-->
