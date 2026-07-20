@extends('layouts.admin')
@section('content')
    <div class="row mb-2">
        <div class="col-md-6">
            <h3 class="">Verify Repayment Agent Report</h3>
        </div>
        <div class="col-md-6" style="text-align: right;">
            @if(Auth::user()->can('Veryfy Repayment Agent Report Import'))
                <a type="button" id="btn-import" href="#" data-toggle="modal" data-target="#modal-import" class="btn btn-danger btn-sm mr-1">Upload Verify</a>
            @endif
            @if(Auth::user()->can('Veryfy Repayment Agent Report Export'))
                <a type="button" class="btn btn-sucess btn-sm" id="downloadToMorakot">Download To Morakot</a>
                <a type="button" class="btn btn-sucess btn-sm" id="downloadToBranch">Download To Branch</a>
            @endif
        </div>
    </div>
    {!! Toastr::message() !!}
    <div id="panel-1" class="panel">
        <div class="panel-hdr">
            <h2>
                Verify Repayment Agent Report
            </h2>
        </div>
        <div class="panel-container show">
            <div class="panel-content">
                <div class="table-responsive">
                    <table id="tbl_veryfy_repayment_agent" class="table table-bordered table-hover table-striped">
                        <thead>
                            <tr>
                                <th class="text-center">Branch</th>
                                <th class="text-center">DrAccount</th>
                                <th class="text-center">DrCategory</th>
                                <th class="text-center">DrCurrency</th>
                                <th class="text-center">CrAccount</th>
                                <th class="text-center">CrCategory</th>
                                <th class="text-center">CrCurrency</th>
                                <th class="text-center">Amount</th>
                                <th class="text-center">LCYAmount</th>
                                <th class="text-center">ExchangeRate</th>
                                <th class="text-center">Transaction</th>
                                <th class="text-center">TranDate</th>
                                <th class="text-center">Reference</th>
                                <th class="text-center">Note</th>
                                <th class="text-center">DrGLKey</th>
                                <th class="text-center">CrGLKey</th>
                                <th class="text-center">Module</th>
                                <th class="text-center">Officer</th>
                                <th class="text-center">DisbursementList</th>
                                <th class="text-center">TargetBranch</th>
                                <th class="text-center">TargetBranchDrCr</th>
                            </tr>
                        </thead>
                        <tbody>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
    @include('verify_repayment_agent.import')
@endsection
@section('script')
    <script>
        $(function(){
            $("#downloadToMorakot").on("click", function () {
                window.location = "{{ URL::to('admin/mkt-report/veryfy/repayment/agent/download/morakot') }}";
            });
            $("#downloadToBranch").on("click", function () {
                window.location = "{{ URL::to('admin/mkt-report/veryfy/repayment/agent/download/branch') }}";
            });

            $(".upload_file_data").on("click", function () {
                if ($('#result_file').val() == "") {
                    $("#thanLess").text("Please select xls, xlsx or csv file and size less than 1MB").css("color", "red");
                    $(".thanLess").show();
                    return false;
                }
                var file_data = $('#result_file').prop('files')[0];
                var fileName = file_data.name;
                var form_data = new FormData();
                var fileExtension = fileName.split('.').pop().toLowerCase();
                var fileSize = file_data.size;
                form_data.append('file', file_data);
                form_data.append('exchange_rate',$('#exchange_rate').val());
                form_data.append('date',$('#date').val());
                form_data.append('_token', "{{ csrf_token() }}");

                // FIX CONDITION
                if ((fileExtension == "xls" || fileExtension == "xlsx" || fileExtension == "csv") && fileSize < 1048576) {
                    $(".upload_file_data").prop('disabled', true);
                    $(".btn-hidden-show").hide();
                    $(".btn-impot-loading").show();
                    $("#modal-import").modal("show");
                    $.ajax({
                        type: 'POST',
                        url: "{{ url('admin/mkt-report/veryfy/repayment/agent/import') }}",
                        data: form_data,
                        contentType: false,
                        cache: false,
                        processData: false,
                        success: function (data) {
                            $("#modal-import").modal("hide");
                            $(".upload_file_data").prop('disabled', false);
                            $(".btn-hidden-show").show();
                            $(".btn-impot-loading").hide();
                            toastr.success('Verify completed successfully');
                            dataTables();
                        },
                        error: function (xhr, status, error) {
                            $("#modal-import").modal("hide");
                            $(".upload_file_data").prop('disabled', false);
                            $(".btn-hidden-show").show();
                            $(".btn-impot-loading").hide();
                            Swal.fire("Error!","An error occurred while processing your request.","error");
                        }
                    });
                } else {
                    $("#thanLess").text("Please select xls, xlsx or csv file and size less than 1MB").css("color", "red");
                    $(".thanLess").show();
                }
            });
        });

        function dataTables() {
            if ($.fn.DataTable.isDataTable('#tbl_veryfy_repayment_agent')) {
                $('#tbl_veryfy_repayment_agent').DataTable().destroy();
            }
            $('#tbl_veryfy_repayment_agent').DataTable({
                pageLength: 10,
                destroy: true,
                processing: true,
                serverSide: true,
                order: [[0, 'desc']],
                lengthMenu: [ [10, 25, 50, 100], [10, 25, 50, 100] ],
                ajax: {
                    url: '{{ URL("admin/mkt-report/veryfy/repayment/agent") }}',
                    type: 'GET',
                    data: function (d) {
                        d.date = $('#date').val();
                    },
                },
                columns: [
                    {
                        data: 'Branch',
                        name: 'Branch',
                        orderable: false,
                        searchable: false,
                    },
                    { 
                        data: 'DrAccount', 
                        name: 'DrAccount',
                    },
                    { 
                        data: 'DrCategory', 
                        name: 'DrCategory',
                    },
                    { 
                        data: 'DrCurrency', 
                        name: 'DrCurrency',
                    },
                    { 
                        data: 'CrAccount', 
                        name: 'CrAccount',
                    },
                    { 
                        data: 'CrCategory', 
                        name: 'CrCategory',
                    },
                    { 
                        data: 'CrCurrency', 
                        name: 'CrCurrency',
                    },
                    { 
                        data: 'Amount', 
                        name: 'Amount',
                    },
                    { 
                        data: 'LCYAmount', 
                        name: 'LCYAmount',
                    },
                    { 
                        data: 'ExchangeRate', 
                        name: 'ExchangeRate',
                    },
                    { 
                        data: 'Transaction', 
                        name: 'Transaction',
                    },
                    { 
                        data: 'TranDate', 
                        name: 'TranDate',
                    },
                    { 
                        data: 'Reference', 
                        name: 'Reference',
                    },
                    { 
                        data: 'Note', 
                        name: 'Note',
                    },
                    {
                        data: 'DrGLKey', 
                        name: 'DrGLKey',
                    },
                    { 
                        data: 'CrGLKey', 
                        name: 'CrGLKey',
                    },
                    { 
                        data: 'Module', 
                        name: 'Module',
                    },
                    { 
                        data: 'Officer', 
                        name: 'Officer',
                    },
                    { 
                        data: 'DisbursementList', 
                        name: 'DisbursementList',
                    },
                    { 
                        data: 'TargetBranch', 
                        name: 'TargetBranch',
                    },
                    { 
                        data: 'TargetBranchDrCr', 
                        name: 'TargetBranchDrCr',
                    },
                ],
            });
            $('#tbl_veryfy_repayment_agent').on('processing.dt', function (e, settings, processing) {
                if (processing) {
                    $('#loading-overlay').show();
                } else {
                    $('#loading-overlay').hide();
                }
            });
        }
    </script>
@endsection