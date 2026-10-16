@extends('layouts.admin')
@section('content')
    <div class="row mb-2">
        <div class="col-md-6">
            <h3 class="">CBC None Equriry Report</h3>
        </div>
        {{-- <div class="col-md-6" style="text-align: right;">
            <button type="button" class="btn btn-sm btn-info waves-effect waves-themed btn_excel mr-1" id="icon-search-download-reload">
                <span class="btn-text-excel"><i class="fal fa-arrow-circle-down"></i></span>
                Excel
                <span id="btn-text-loading-excel" style="display: none"><i class="fa fa-spinner fa-spin"></i></span>
            </button>
        </div> --}}
    </div>
    <div class="card mb-2">
        <div class="card-body">
            <div class="row filter-btn">
                <div class="col-sm-3 col-md-3">
                    <div class="form-group">
                        <input type="text" class="form-control datepicker btn-filter"  name="from_date" id="from_date" value="{{ \Carbon\Carbon::now()->startOfMonth()->format('d-m-Y') }}" placeholder="From Date" readonly style="background-color: #fff;">
                    </div>
                </div>
                <div class="col-sm-3 col-md-3">
                    <div class="form-group">
                        <input type="text" class="form-control datepicker btn-filter" name="to_date" id="to_date" value="{{ \Carbon\Carbon::now()->endOfMonth()->format('d-m-Y') }}" placeholder="To Date" readonly style="background-color: #fff;">
                    </div>
                </div>
                <div class="col-sm-3 col-md-3 col-lg-3 col-xl-3">
                    <div class="form-group">
                        <select class="select2 form-control" id="branch_id" data-select2-id="select2-data-2-c0n2" name="branch_id">
                            <option value="" data-select2-id="select2-data-2-c0n2">All Branch</option>
                            @foreach ($branch as $item)
                                <option value="{{ $item->ID }}">
                                    {{ Helper::getLang() == 'en' ? $item->Description : $item->LocalDescription }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                </div>
                <div class="col-sm-3 col-md-3 col-lg-3 col-xl-3">
                    <div class="float-right">
                        <button type="button" class="btn btn-sm btn-outline-secondary btn-search mr-1" data-dismiss="modal" id="icon-search-download-reload">
                            <span class="btn-txt"><i class="fal fa-search"></i></span>
                            Search
                            <span class="loading-icon" style="display: none"><i class="fa fa-spinner fa-spin"></i></span>
                        </button>
                        @if(Auth::user()->can('Loan Detail Export'))
                            <button type="button" class="btn btn-sm btn-info waves-effect waves-themed btn_excel mr-1" id="icon-search-download-reload">
                                <span class="btn-text-excel"><i class="fal fa-arrow-circle-down"></i></span>
                                Excel
                                <span id="btn-text-loading-excel" style="display: none"><i class="fa fa-spinner fa-spin"></i></span>
                            </button>
                        @endif
                    </div>
                </div>
            </div>
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
        const toIso = s => s ? s.split('-').reverse().join('-') : '';   // 01-10-2026 -> 2026-10-01
        let branch_id = $('#branch_id').val();
        let from_date = toIso($('#from_date').val());
        let to_date   = toIso($('#to_date').val());
        $(function(){
            $('.datepicker_month').datepicker({
                format: "dd-mm-yyyy",
                autoclose: true,
                todayHighlight: true
            });
            $(".btn_excel").on("click", function() {
                let query = {
                    branch_id: $("#branch_id").val()
                };
                var url = "{{URL::to('admin/mkt-report/cbc-none-equriry/download')}}?" + $.param(query)
                window.location = url;
            });
            $('.btn-search').on('click', function () {
                $('#loading-overlay').hide();
                branch_id = $('#branch_id').val();
                from_date = toIso($('#from_date').val());
                to_date   = toIso($('#to_date').val());
                $('#tbl_cbc_none_equriry').DataTable().ajax.reload(null, false);
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
                    data: function (d) {
                        d.branch_id = branch_id;
                        d.from_date = from_date;
                        d.to_date   = to_date;
                    }
                },
                columns: [
                    { data: 'branch' },
                    { data: 'loan_application_id' },
                    { data: 'customer_name' },
                    { data: 'customer_type' },
                    { data: 'currency' },
                    { data: 'app_date' },
                    { data: 'amount', render: $.fn.dataTable.render.number(',', '.', 2) },
                    { data: 'amount', render: $.fn.dataTable.render.number(',', '.', 2) },
                    // { data: 'outstanding_amount', render: $.fn.dataTable.render.number(',', '.', 2) },
                    { data: 'CBC_Ref' },
                ],
            });
        }
    </script>
@endsection