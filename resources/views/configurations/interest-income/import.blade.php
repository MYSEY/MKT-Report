<div id="importModal" class="modal custom-modal fade" role="dialog">
    <div class="modal-dialog modal-dialog-centered" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Import Interest Income</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true"><i class="fal fa-times"></i></span>
                </button>
            </div>
            <div class="modal-body">
                <h4 class="card-title mb-0">Import excel/ XLS XLSX or CSV</h4>
                <div class="card">
                    <div class="card-body">
                        <div class="form-group">
                            <div class="col-md-12 alert thanLess" style="display:none;background-color:#F7D7DA">
                                <span id="thanLess"></span>
                            </div>
                            <div class="col-md-12" style="padding-left: 2%;">
                                <input type="file" id="result_file">
                            </div>
                        </div>
                    </div>
                </div><br>
                <div class="float-lg-right">
                    <a href="javascript:" class="btn btn-primary submit-btn upload_file_data">
                        <span class="btn-text-submit">Submit</span>
                        <span id="btn-loading" style="display: none"><i class="fa fa-spinner fa-spin"></i> Loading</span>
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>
