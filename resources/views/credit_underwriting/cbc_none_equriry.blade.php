@extends('layouts.admin')
@section('content')
    <div class="row mb-2">
        <div class="col-md-6">
            <h3 class="">CBC None Equriry Report</h3>
        </div>
        <div class="col-md-6" style="text-align: right;">
            <button type="button" class="btn btn-sm btn-info waves-effect waves-themed btn_excel mr-1" id="icon-search-download-reload">
                <span class="btn-text-excel"><i class="fal fa-arrow-circle-down"></i></span>
                Excel
                <span id="btn-text-loading-excel" style="display: none"><i class="fa fa-spinner fa-spin"></i></span>
            </button>
        </div>
    </div>
    <div id="panel-1" class="panel">
        <div class="panel-hdr">
            <h2>
                CBC None Equriry Report
            </h2>
        </div>
        <div class="panel-container show">
            <div class="panel-content">
                <div class="table-responsive">
                    <table id="tbl_cbc_none_equriry" class="table table-bordered table-hover table-striped">
                        <thead>
                            <tr>
                                <th>Branch</th>
                                <th>LoanID</th>
                                <th>CustomerName</th>
                                <th>ApplicationType</th>
                                <th>Currency</th>
                                <th>DisbursedDate</th>
                                <th>DisbursedAmount</th>
                                <th>Outstanding</th>
                                <th>Reference</th>
                            </tr>
                        </thead>
                        <tbody>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
@endsection
@section('script')
    <script>
        $(function(){
            $(".btn_excel").on("click", function() {
                let query = {
                    branch_id: $("#branch_id").val()
                };
                var url = "{{URL::to('admin/mkt-report/cbc-none-equriry/download')}}?" + $.param(query)
                window.location = url;
            });
            dataTables();
        });

        function dataTables() {
            $('#loading-overlay').show();
            // Check if DataTable instance exists, then destroy it
            if ($.fn.DataTable.isDataTable('#tbl_cbc_none_equriry')) {
                $('#tbl_cbc_none_equriry').DataTable().clear().destroy();
            }
            $('#tbl_cbc_none_equriry').DataTable({
                pageLength: 10,
                destroy: true,
                processing: true,
                serverSide: true,
                order: [[0, 'desc']],
                lengthMenu: [ [10, 25, 50, 100], [10, 25, 50, 100] ],
                ajax: {
                    url: '{{ URL("admin/mkt-report/cbc-none-equriry") }}',
                    type: 'GET',
                },
                columns: [
                    { data: 'branch' },
                    { data: 'loan_id' },
                    { data: 'customer_name' },
                    { data: 'applicant_type' },
                    { data: 'currency' },
                    { data: 'value_date' },
                    { data: 'disbursed', render: $.fn.dataTable.render.number(',', '.', 2) },
                    { data: 'outstanding_amount', render: $.fn.dataTable.render.number(',', '.', 2) },
                    { data: 'member_reference' },
                ],
            });
        }
    </script>
@endsection