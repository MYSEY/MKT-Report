{{-- import datas --}}
<div class="modal fade show" id="modal-import" role="dialog" aria-modal="true">
    <div class="modal-dialog modal-dialog-centered" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h3 class="modal-title"><strong>Agent</strong></h3>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true"><i class="fal fa-times"></i></span>
                </button>
            </div>
            <div class="modal-body">
                <div class="form-group">
                    <div class="col-md-12 alert thanLess" style="display:none;background-color:#F7D7DA">
                        <span id="thanLess"></span>
                    </div>

                    <div class="row mb-2">
                        <div class="col-md-3">
                            <label for="Exchange Rate">Exchange Rate :</label>
                        </div>
                        <div class="col-md-9">
                            <input type="number" class="form-control" id="exchange_rate">
                            <small>16 decimal</small><br>
                            <small>0.0000000000000000</small>
                        </div>
                    </div>
                    <div class="row mb-2">
                        <div class="col-md-3">Date :</div>
                        <div class="col-md-9">
                            <input type="date" class="form-control" id="date">
                            <small>YYYY-MM-DD</small>
                        </div>
                    </div>
                    <div class="row mb-2">
                        <div class="col-md-3">
                            <label for="">Import :</label>
                        </div>
                        <div class="col-md-9">
                            <input type="file" class="form-control" name="result_file" id="result_file">
                        </div>
                    </div>
                </div>
                <div class="text-end float-right">
                    <div class="btn-hidden-show">
                        <button class="btn btn-primary waves-effect waves-themed upload_file_data" type="button">Submit</button>
                    </div>
                    <div class="btn-impot-loading mt-3" style="display: none">
                        <button  class="btn btn-danger waves-effect waves-themed" type="button" disabled="">
                            <span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span>
                            Loading...
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script>
    $(document).ready(function () {
        $("#result_file").prop('disabled', true);
        // Listen for changes on exchange_rate input
        $("#exchange_rate").on('input keyup change', function () {
            var value = $(this).val().trim();
            var parts = value.split('.');
            var decimalPart = parts[1] || ''; // digits after the decimal point

            if (decimalPart.length === 18) {
                $("#result_file").prop('disabled', false);
            } else {
                $("#result_file").prop('disabled', true);
            }
        });

        let today = new Date();
        let year = today.getFullYear();
        let month = String(today.getMonth() + 1).padStart(2, '0');
        let day = String(today.getDate()).padStart(2, '0');
        let formatDate = year + '-' + month + '-' + day;
        $('#date').val(formatDate);
    });
</script>