<div class="modal fade" id="ptFreeExcelModal" tabindex="-1" aria-labelledby="ptFreeExcelModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h1 class="modal-title fs-5" id="ptFreeExcelModalLabel">Download Excel by Date</h1>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="row">
                    <div class="col-6">
                        <div class="mb-3">
                            <label class="form-label">From Date</label>
                            <input type="date" id="excelFromDate" class="form-control" value="{{ $fromDate }}">
                        </div>
                    </div>
                    <div class="col-6">
                        <div class="mb-3">
                            <label class="form-label">To Date</label>
                            <input type="date" id="excelToDate" class="form-control" value="{{ $toDate }}">
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" onclick="downloadPtFreeExcel()" class="btn btn-primary">Download</button>
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<script>
    function downloadPtFreeExcel() {
        var params = new URLSearchParams({
            excel: '1',
            fromDate: document.getElementById('excelFromDate').value,
            toDate: document.getElementById('excelToDate').value
        });
        window.location.href = window.location.pathname + '?' + params.toString();
    }
</script>
