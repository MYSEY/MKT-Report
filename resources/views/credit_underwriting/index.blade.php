@extends('layouts.admin')
@section('content')
    <div class="row mb-2">
        <div class="col-md-6">
            <h3 class="">Credit Underwriting Report</h3>
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
                Credit Underwriting Report
            </h2>
        </div>
        <div class="panel-container show">
            <div class="panel-content">
                <div class="table-responsive">
                    <table id="tbl_co_report" class="table table-bordered table-hover table-striped">
                        <thead>
                            <tr>
                                <th>Branch</th>
                                <th>LoanID</th>
                                <th>Customer Name</th>
                                <th>Currency</th>
                                <th>Income</th>
                                <th>DSCR</th>
                                <th>Credit Bureau Checks</th>
                                <th>Number Of Other Lenders</th>
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
                var url = "{{URL::to('admin/mkt-report/credit-underwriting/download')}}?" + $.param(query)
                window.location = url;
            });
            dataTables();
        });

        function dataTables() {
            $('#loading-overlay').show();
            // Check if DataTable instance exists, then destroy it
            if ($.fn.DataTable.isDataTable('#tbl_co_report')) {
                $('#tbl_co_report').DataTable().clear().destroy();
            }
            $('#tbl_co_report').DataTable({
                pageLength: 10,
                destroy: true,
                processing: true,
                serverSide: true,
                order: [[0, 'desc']],
                lengthMenu: [ [10, 25, 50, 100], [10, 25, 50, 100] ],
                ajax: {
                    url: '{{ URL("admin/mkt-report/credit-underwriting") }}',
                    type: 'GET',
                },
                columns: [
                    { data: 'branch' },
                    { data: 'loan_id' },
                    { data: 'customer_name' },
                    { data: 'income_currency' },
                    { data: 'monthly_income', render: $.fn.dataTable.render.number(',', '.', 2) },
                    {
                        data: 'dsc_ratio',
                        render: function (data, type) {
                            if (data === null || data === '') return '';
                            // keep raw value for sorting/searching, show % only on screen
                            return type === 'display' ? parseFloat(data).toFixed(2) + '%' : data;
                        }
                    },
                    { data: 'number_of_applicants' },
                    { data: 'number_of_other_lenders' },
                ],
            });
        }
    </script>
@endsection