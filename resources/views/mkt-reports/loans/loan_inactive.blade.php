@extends('layouts.admin')
@section('content')
    {!! Toastr::message() !!}
    <div class="card mb-2">
        <div class="card-body">
            <div class="row filter-btn">
                <div class="col-sm-3 col-md-3">
                    <div class="form-group">
                        <input type="text" 
                            class="form-control datepicker_month btn-filter" 
                            name="from_closedDate" 
                            id="from_closedDate" 
                            value="{{ \Carbon\Carbon::now()->format('d-m-Y') }}"
                            placeholder="From ClosedDate" 
                            readonly 
                            style="background-color: #fff;">
                    </div>
                </div>
                <div class="col-sm-3 col-md-3">
                    <div class="form-group">
                        <input type="text" 
                            class="form-control datepicker_month btn-filter" 
                            name="to_closedDate" 
                            id="to_closedDate" 
                            value="{{ \Carbon\Carbon::now()->addMonth()->format('d-m-Y') }}"
                            placeholder="To ClosedDate " 
                            readonly 
                            style="background-color: #fff;">
                    </div>
                </div>
                 <div class="col-sm-3 col-md-3">
                    <div class="form-group">
                        <select class="select2 form-control btn-filter filter-branch" name="branch_id" id="branch_id" data-select2-id="select2-data-2-c0n2" >
                            <option value="" data-select2-id="select2-data-2-c0n2">All Branch</option>
                            @foreach ($branchs as $item)
                                <option value="{{ $item->ID }}">
                                    {{ $item->ID }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                </div>
                <div class="col-sm-3 col-md-3">
                    <div class="float-right">
                        @if(Auth::user()->can('Loan Inactive Export'))
                           <button type="button" class="btn btn-sm btn-info waves-effect waves-themed btn_excel mr-1">
                                <span class="btn-text-excel"><i class="fal fa-arrow-circle-down"></i></span>
                                <span id="btn-text-loading-excel-1" style="display: none"><i class="fa fa-spinner fa-spin"></i></span>
                                Excel
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
                Loan Inactive Monitoring
            </h2>
        </div>
         <div class="panel-container show">
            <div class="panel-content">
                <div class="table-responsive">
                    <table id="tbl-loan-inactive" class="table table-bordered table-hover table-striped">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>LoanStatus </th>
                                <th>Branch </th>
                                <th>ID </th>
                                <th>ContractCustomerID </th>
                                <th>CustomerName </th>
                                {{-- <th>Account </th> --}}
                                <th>Currency </th>
                                <th>DisburseDate </th>
                                <th>ClosedDate </th>
                                <th>Disbursed </th>
                                <th>InterestRate </th>
                                <th>Term </th>
                                <th>MaturityDate </th>
                                <th>LoanProduct </th>
                                <th>Sector </th>
                                <th>Category </th>
                                <th>ContractOfficerID </th>
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
            dataTables();
            $(document).ready(function() {
                $('.datepicker_month').datepicker({
                    format: "dd-mm-yyyy",
                    autoclose: true,
                    todayHighlight: true
                });
            });
            $(".btn_excel").on("click", function() {
                let query = {
                    branch_id: $('select[name="branch_id"]').val(),
                    from_closedDate: $('input[name="from_closedDate"]').val(),
                    to_closedDate: $('input[name="to_closedDate"]').val(),
                };
                var url = "{{URL::to('admin/mkt-report/loan-inactive/download')}}?" + $.param(query)
                window.location = url;
            });
            // Reload only (DON'T destroy/reinit)
            $('.btn-filter').on('change', function() {
                $('#loading-overlay').hide();
                $('#tbl-loan-inactive').DataTable().ajax.reload(null, false);
                $(".currency_month").text($('input[name="sale_date"]').val());
            });
            // Initialize only once
            
        });

        function dataTables() {
            $('#loading-overlay').show();
            if ($.fn.DataTable.isDataTable('#tbl-loan-inactive')) {
                $('#tbl-loan-inactive').DataTable().clear().destroy();
            }
           $('#tbl-loan-inactive').DataTable({
                pageLength: 20,
                destroy: true,
                processing: true,
                serverSide: true,
                autoWidth: false, // បន្ថែមចំណុចនេះ ដើម្បីកុំឱ្យវាគណនា Style column លឿនពេក
                scrollX: true,
                // scrollY: '350px',
                scroller: false,
                order: [[1, 'asc']],
                lengthMenu: [ 
                    [20, 25, 50, 100, -1],
                    [20, 25, 50, 100, "All"]
                ],
                ajax: {
                    url: '{{ URL("admin/mkt-report/loan-inactive") }}',
                    type: 'GET',
                    data: function (d) {
                        d.branch_id = $('select[name="branch_id"]').val();
                        d.from_closedDate = $('input[name="from_closedDate"]').val();
                        d.to_closedDate = $('input[name="to_closedDate"]').val();
                    },
                    dataSrc: function (json) {
                        return json.data;
                    }
                },
                columns: [
                    {
                        data: null,
                        className: 'text-center',
                        render: function (data, type, row, meta) {
                            return meta.row + meta.settings._iDisplayStart + 1;
                        }
                    },
                    { 
                        data: "LoanStatus", 
                        name: "LoanStatus",
                        className: 'text-center',
                        render: function (data, type, row) {
                            let badgeClass = 'secondary'; // ពណ៌ប្រផេះសម្រាប់ Status ទូទៅ
                            let statusText = data ? data : 'N/A';

                            // ឆែកលក្ខខណ្ឌបើ status ស្មើ "Active"
                            if (data === "Active") {
                                badgeClass = 'success'; // ពណ៌បៃតង (Bootstrap class)
                            } else{
                                badgeClass = 'danger'; // ពណ៌ក្រហម
                            }

                            return '<span class="badge badge-' + badgeClass + '" style="padding: 5px 10px; border-radius: 4px;">' + statusText + '</span>';
                        }
                    },
                    // { data: "LoanStatus", name: "LoanStatus" },
                    { data: 'Branch', name: 'Branch' },
                    { data: 'ID', name: 'ID' },
                    { data: "ContractCustomerID", name: "ContractCustomerID" },
                    { data: "EnName", name: "EnName" },
                    { data: 'Currency', name: 'Currency' },
                    { data: "ValueDate", name: "ValueDate" },
                    { data: "ClosedDate", name: "ClosedDate" },
                    { 
                        data: "Disbursed", 
                        className: 'text-right',
                        render: function (data) {
                            return data ? new Intl.NumberFormat('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 }).format(data) : '0.00';
                        }
                    },
                    { data: "InterestRate", name: "InterestRate" },
                    { data: "Term", name: "Term" },
                    { data: "MaturityDate", name: "MaturityDate" },
                    { data: "ProdName", name: "ProdName" },
                    { data: "Sector", name: "Sector" },
                    { data: "Category", name: "Category" },
                    { data: "ContractOfficerID", name: "ContractOfficerID" },
                ],
                drawCallback: function (settings) {
                    var api = this.api();
                    var rows = api.rows({ page: 'current' }).nodes();
                    var last = null;

                    api.column(4, { page: 'current' })
                        .data()
                        .each(function (group, i) {
                            if (last !== group) {
                                // ទាញយកឈ្មោះអតិថិជនពី Row នោះមកបង្ហាញក្នុង Header តែម្តង
                                var rowData = api.row(i).data();
                                var customerName = rowData ? rowData.EnName : '';

                                $(rows).eq(i).before(
                                    '<tr class="group" style="background-color: #e9ecef; font-weight: bold; border-left: 4px solid #6f42c1;">' +
                                    '<td colspan="17" style="padding-left: 15px;">' + 
                                    '<i class="fa fa-user"></i> ID: ' + group + ' | ' + customerName + 
                                    '</td></tr>'
                                );
                                last = group;
                            }
                        });
                }
            });

            $('#tbl-loan-inactive').on('processing.dt', function (e, settings, processing) {
                if (processing) {
                    $('#loading-overlay').show();
                } else {
                    $('#loading-overlay').hide();
                }
            });
        }
    </script>
@endsection