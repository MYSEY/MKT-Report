@extends('layouts.admin')
@section('content')
    <div class="row mb-2">
        <div class="col-md-6">
            <h3 class="">Veryfy Repayment Agent Report</h3>
        </div>
        <div class="col-md-6" style="text-align: right;">
            <a type="button" id="btn-import" href="#" data-toggle="modal" data-target="#modal-import" class="btn btn-danger btn-sm mr-1">Veryfy</a>
        </div>
    </div>
    {!! Toastr::message() !!}
    <div id="panel-1" class="panel">
        <div class="panel-hdr">
            <h2>
                Veryfy Repayment Agent Report
            </h2>
        </div>
        <div class="panel-container show">
            <div class="panel-content">
                <div class="table-responsive">
                    <table id="tbl-tmg-reports" class="table table-bordered table-hover table-striped">
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
            $(".btn_excel").on("click", function() {
                let query = {
                    branch_id: ""
                };
                var url = "{{URL::to('admin/hr-report/tmg/download')}}?" + $.param(query)
                window.location = url;
            });
            // $(".upload_file_data").on("click", function() {
            //     if ($('#result_file').val() == "") {
            //         $("#thanLess").text("Please select a xls,xlsx and csv file and size less then 1MB").css("color", "red");
            //         $(".thanLess").show();
            //         return false;
            //     }
            //     var file_data = $('#result_file').prop('files')[0];
            //     var fileName = file_data['name'];
            //     var form_data = new FormData();
            //     var fileExtension = fileName.split('.').pop();
            //     var fileSize = file_data['size'];
            //     form_data.append('file', file_data);
            //     form_data.append('exchange_rate', exchange_rate);
            //     form_data.append('date', date);
            //     form_data.append('_token', "{{ csrf_token() }}");
            //     if (fileExtension == "xls" || fileExtension == "xlsx" || fileExtension == "csv" && fileSize < 1048576) {

            //         $(".upload_file_data").prop('disabled', true);
            //         $(".btn-hidden-show").hide();
            //         $(".btn-impot-loading").css('display', 'block');

            //         $("#modal-import").modal("show");
            //         $.ajax({
            //             type: 'POST',
            //             url: "{{ url('admin/mkt-report/veryfy/repayment/agent/import') }}",
            //             data: form_data,
            //             contentType: false,
            //             cache: false,
            //             processData: false,
            //             success: function(data) {
            //                 if (data.mg == 'success') {
            //                     $("#modal-import").modal("hide");
            //                     toastr.success('Data has been save success');
            //                     window.location.replace("{{ URL('admin/asset') }}");
            //                 }
            //             },error: function(xhr, status, error) {
            //                 Swal.fire("Error!", "An error occurred while processing your request. Please try again.","error");
            //             },
            //         });
            //     }else{
            //         $("#thanLess").text("Please select a xls,xlsx and csv file and size less then 1MB").css("color", "red");
            //         $(".thanLess").show();
            //     }
            // });


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
                if ((fileExtension == "xls" || fileExtension == "xlsx" || fileExtension == "csv") && fileSize < 1048576
                ) {
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
                            if (data.mg == 'success') {
                                $("#modal-import").modal("hide");
                                toastr.success('Verify completed successfully');
                                window.location.reload();
                            }
                        },
                        error: function (xhr, status, error) {
                            $("#modal-import").modal("hide");
                            $(".upload_file_data").prop('disabled', false);
                            $(".btn-hidden-show").show();
                            $(".btn-impot-loading").hide();
                            Swal.fire(
                                "Error!",
                                "An error occurred while processing your request.",
                                "error"
                            );
                        }
                    });
                } else {
                    $("#thanLess").text("Please select xls, xlsx or csv file and size less than 1MB").css("color", "red");
                    $(".thanLess").show();
                }
            });

            // Initialize only once
            // dataTables();
        });

        // function dataTables() {
        //     $('#loading-overlay').show();
        //     // Check if DataTable instance exists, then destroy it
        //     if ($.fn.DataTable.isDataTable('#tbl-tmg-reports')) {
        //         $('#tbl-tmg-reports').DataTable().clear().destroy();
        //     }
        //     $('#tbl-tmg-reports').DataTable({
        //         pageLength: 20,
        //         destroy: true,
        //         processing: true,
        //         serverSide: true,
        //         // scrollX: true,
        //         scrollY: '350px',
        //         scroller: false,
        //         order: [[1, 'asc']], // តម្រៀបតាមឈ្មោះខេត្ត ដើម្បីងាយស្រួល Merge
        //         lengthMenu: [ [20, 25, 50, 100], [10, 25, 50, 100] ],
        //         ajax: {
        //             url: '{{ URL("admin/hr-report/tmg") }}',
        //             type: 'GET',
        //             dataSrc: function (json) {
        //                 return json.data;
        //             }
        //         },
        //         columns: [
        //             {
        //                 data: null,
        //                 name: 'id',
        //                 orderable: false,
        //                 searchable: false,
        //                 className: 'text-center',
        //                 render: function (data, type, row, meta) {
        //                     return meta.row + meta.settings._iDisplayStart + 1;
        //                 }
        //             },
        //             { data: 'employee_name_kh', name: 'employee_name_kh' },
        //             { data: 'position_name_kh', name: 'position_name_kh' },
        //             { data: 'branch_name_kh', name: 'branch_name_kh' },
        //         ],
                
        //     });
        //     $('#tbl-tmg-reports').on('processing.dt', function (e, settings, processing) {
        //         if (processing) {
        //             $('#loading-overlay').show();
        //         } else {
        //             $('#loading-overlay').hide();
        //         }
        //     });
        // }
    </script>
@endsection