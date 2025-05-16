<!--**********************************
   Footer start
  ***********************************-->
<div class="footer footer-outer">
    <div class="copyright">
        <p>Copyright © Developed by <a href="https://dexignlab.com/" target="_blank">Warastra Adhiguna</a> 2023
        </p>
    </div>
</div>

</div>


<!--**********************************
        Main wrapper end
    ***********************************-->

<!--***********************************-->
<div class="modal fade" id="exampleModal" tabindex="-1" aria-labelledby="exampleModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-center">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="exampleModalLabel">Recent Student title</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="row">
                    <div class="col-xl-6">
                        <div class="mb-3">
                            <label for="exampleFormControlInput1" class="form-label mb-2">Student Name</label>
                            <input type="text" class="form-control" id="exampleFormControlInput1"
                                placeholder="James">
                        </div>
                        <div class="mb-3">
                            <label for="exampleFormControlInput2" class="form-label mb-2">Email</label>
                            <input type="email" class="form-control" id="exampleFormControlInput2"
                                placeholder="hello@example.com">
                        </div>
                        <div class="mb-3">
                            <label class="form-label mb-2">Gender</label>
                            <select class="default-select wide" aria-label="Default select example">
                                <option selected>Select Option</option>
                                <option value="1">Male</option>
                                <option value="2">Women</option>
                                <option value="3">Other</option>
                            </select>
                        </div>
                    </div>
                    <div class="col-xl-6">
                        <div class="mb-3">
                            <label for="exampleFormControlInput4" class="form-label mb-2">Entery Year</label>
                            <input type="number" class="form-control" id="exampleFormControlInput4"
                                placeholder="EX: 2023">
                        </div>
                        <div class="mb-3">
                            <label for="exampleFormControlInput5" class="form-label mb-2">Student ID</label>
                            <input type="number" class="form-control" id="exampleFormControlInput5"
                                placeholder="14EMHEE092">
                        </div>
                        <div class="mb-3">
                            <label for="exampleFormControlInput6" class="form-label mb-2">Phone Number</label>
                            <input type="number" class="form-control" id="exampleFormControlInput6"
                                placeholder="+123456">
                        </div>
                    </div>
                </div>

            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-danger light" data-bs-dismiss="modal">Close</button>
                <button type="button" class="btn btn-primary">Save changes</button>
            </div>
        </div>
    </div>
</div>


<!--**********************************
  Modal
 ***********************************-->
<!--**********************************
        Scripts
    ***********************************-->
<!-- Required vendors -->
<script src="{{ asset('admingym/vendor/global/global.min.js') }}"></script>
<script src="{{ asset('admingym/vendor/chart.js/Chart.bundle.min.js') }}"></script>
<script src="{{ asset('admingym/vendor/bootstrap-select/dist/js/bootstrap-select.min.js') }}"></script>
<!-- Apex Chart -->
{{-- <script src="{{ asset('admingym/vendor/apexchart/apexchart.js') }}"></script> --}}
<!-- Chart piety plugin files -->
<script src="{{ asset('admingym/vendor/peity/jquery.peity.min.js') }}"></script>
<script src="{{ asset('admingym/vendor/jquery-nice-select/js/jquery.nice-select.min.js') }}"></script>
<!--swiper-slider-->
<script src="{{ asset('admingym/vendor/swiper/js/swiper-bundle.min.js') }}"></script>


<!-- Datatable -->
<script src="{{ asset('admingym/vendor/datatables/js/jquery.dataTables.min.js') }}"></script>
<script src="{{ asset('admingym/js/plugins-init/datatables.init.js') }}"></script>

<!-- Dashboard 1 -->
<script src="{{ asset('admingym/js/dashboard/dashboard-1.js') }}"></script>
<script src="{{ asset('admingym/vendor/wow-master/dist/wow.min.js') }}"></script>
<script src="{{ asset('admingym/vendor/bootstrap-datetimepicker/js/moment.js') }}"></script>
<script src="{{ asset('admingym/vendor/datepicker/js/bootstrap-datepicker.min.js') }}"></script>
<script src="{{ asset('admingym/vendor/bootstrap-select-country/js/bootstrap-select-country.min.js') }}"></script>

<script src="{{ asset('admingym/js/dlabnav-init.js') }}"></script>
<script src="{{ asset('admingym/js/custom.min.js') }}"></script>
<script src="{{ asset('custom/js/jquery.mask.min.js') }}"></script>

<!-- Required vendors -->
<script src="{{ asset('admingym/vendor/jquery-nice-select/js/jquery.nice-select.min.js') }}"></script>
<script src="{{ asset('admingym/vendor/bootstrap-select/dist/js/bootstrap-select.min.js') }}"></script>
<script src="{{ asset('admingym/vendor/select2/js/select2.full.min.js') }}"></script>
<script src="{{ asset('admingym/js/plugins-init/select2-init.js') }}"></script>
<script src="{{ asset('admingym/js/dlabnav-init.js') }}"></script>

<!-- Daterangepicker -->
<!-- momment js is must -->
<script src="{{ asset('admingym/vendor/moment/moment.min.js') }}"></script>
<script src="{{ asset('admingym/vendor/bootstrap-daterangepicker/daterangepicker.js') }}"></script>
<!-- clockpicker -->
<script src="{{ asset('admingym/vendor/clockpicker/js/bootstrap-clockpicker.min.js') }}"></script>
<!-- asColorPicker -->
<script src="{{ asset('admingym/vendor/jquery-asColor/jquery-asColor.min.js') }}"></script>
<script src="{{ asset('admingym/vendor/jquery-asGradient/jquery-asGradient.min.js') }}"></script>
<script src="{{ asset('admingym/vendor/jquery-asColorPicker/js/jquery-asColorPicker.min.js') }}"></script>
<!-- Material color picker -->
<script src="{{ asset('admingym/vendor/bootstrap-material-datetimepicker/js/bootstrap-material-datetimepicker.js') }}">
</script>
<!-- pickdate -->
<script src="{{ asset('admingym/vendor/pickadate/picker.js') }}"></script>
<script src="{{ asset('admingym/vendor/pickadate/picker.time.js') }}"></script>
<script src="{{ asset('admingym/vendor/pickadate/picker.date.js') }}"></script>



<!-- Daterangepicker -->
<script src="{{ asset('admingym/js/plugins-init/bs-daterange-picker-init.js') }}"></script>
<!-- Clockpicker init -->
<script src="{{ asset('admingym/js/plugins-init/clock-picker-init.js') }}"></script>
<!-- asColorPicker init -->
<script src="{{ asset('admingym/js/plugins-init/jquery-asColorPicker.init.js') }}"></script>
<!-- Material color picker init -->
<script src="{{ asset('admingym/js/plugins-init/material-date-picker-init.js') }}"></script>
<!-- Material color picker -->
<script src="{{ asset('admingym/vendor/bootstrap-material-datetimepicker/js/bootstrap-material-datetimepicker.js') }}">
</script>
<!-- Pickdate -->
<script src="{{ asset('admingym/js/plugins-init/pickadate-init.js') }}"></script>
<script src="{{ asset('admingym/vendor/bootstrap-select/dist/js/bootstrap-select.min.js') }}"></script>
<script src="{{ asset('admingym/vendor/jquery-nice-select/js/jquery.nice-select.min.js') }}"></script>
<!-- clockpicker -->
<script src="{{ asset('admingym/vendor/clockpicker/js/bootstrap-clockpicker.min.js') }}"></script>


<script>
    @if (Session::has('success'))
        toastr.success("{{ Session::get('success') }}")
    @endif
</script>

<script>
    $(document).ready(function() {
        $('.rupiah').mask("#,##0", {
            reverse: true
        });
    });
</script>

<script src="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.js"
    integrity="sha512-VEd+nq25CkR676O+pLBnDW09R7VQX9Mdiij052gVCp5yVH3jGtH70Ho/UUv4mJDsEdTvqRCFZg0NKGiojGnUCw=="
    crossorigin="anonymous" referrerpolicy="no-referrer"></script>

<script>
    var loadFile = function(event) {
        var output = document.getElementById('output');
        output.src = URL.createObjectURL(event.target.files[0]);
    };
</script>

<script>
    var loadFile = function(event) {
        var output = document.getElementById('outputEdit');
        output.src = URL.createObjectURL(event.target.files[0]);
    };
</script>

@if (Session::has('message'))
    <script>
        toastr.options = {
            "progressBar": true,
        }
        toastr.success("{{ Session::get('message') }}");
    </script>
@endif


</body>

</html>
