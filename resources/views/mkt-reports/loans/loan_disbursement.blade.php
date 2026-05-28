@extends('layouts.admin')
@section('content')
<style>

    /* កំណត់ Width ពិសេសសម្រាប់ Column LoanProduct */
    .w-150 {
        width: 150px !important;
        min-width: 150px !important;
    }

    /* ជួយឱ្យអត្ថបទវែងៗចុះបន្ទាត់ */
    .text-wrap {
        white-space: normal !important;
        word-wrap: break-word;
    }
</style>
    {!! Toastr::message() !!}
    <div class="card mb-2">
        <div class="card-body">
            <div class="row filter-btn">
                <div class="col-sm-3 col-md-3">
                    <div class="form-group">
                        <input type="text" 
                            class="form-control datepicker_month btn-filter" 
                            name="from_date" 
                            id="from_date" 
                            value="{{ \Carbon\Carbon::now()->startOfMonth()->format('d-m-Y') }}"
                            placeholder="From Date" 
                            readonly 
                            style="background-color: #fff;">
                    </div>
                </div>
                <div class="col-sm-3 col-md-3">
                    <div class="form-group">
                        <input type="text" 
                            class="form-control datepicker_month btn-filter" 
                            name="to_date" 
                            id="to_date" 
                            value="{{ \Carbon\Carbon::now()->endOfMonth()->format('d-m-Y') }}"
                            placeholder="To Date " 
                            readonly 
                            style="background-color: #fff;">
                    </div>
                </div>
                 <div class="col-sm-2 col-md-2">
                    <div class="form-group">
                        <select class="select2 form-control btn-filter filter-branch" name="branch_id" id="branch_id" data-select2-id="select2-data-2-c0n2" >
                            <option value="all" data-select2-id="select2-data-2-c0n2">All Branch</option>
                            @foreach ($branchs as $item)
                                <option value="{{ $item->ID }}">
                                    {{ $item->ID }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                </div>
                <div class="col-sm-2 col-md-2">
                    <div class="form-group">
                        <select class="select2 form-control btn-filter" id="currency" data-select2-id="select2-data-2-c0n4" name="currency">
                            <option value="all" selected>All Currency</option>
                            <option value="KHR" >KHR</option>
                            <option value="USD">USD</option>
                        </select>
                    </div>
                </div>
                <div class="col-sm-2 col-md-2">
                    <div class="float-right">
                        @if(Auth::user()->can('Loan Disbursement Export'))
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
                Loan Disbursement
            </h2>
        </div>
         <div class="panel-container show">
            <div class="panel-content">
                <div class="table-responsive">
                    <table id="tbl-loan-disbursement" class="table table-bordered table-hover table-striped">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Branch </th>
                                <th>Currency </th>
                                <th>TransactionDate </th>
                                <th>Reference </th>
                                <th>Amount </th>
                                <th>AmountAS </th>
                                <th>Transaction </th>
                                <th>LoanType </th>
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
<script src="{{asset('admins/js/export_xlsx.bundle.js')}}"></script>
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
            $(document).on('click', '.btn_excel', function (e) {
                e.preventDefault();
                
                // បង្ហាញ Loading
                $('.btn-text-excel').hide();
                $('#btn-text-loading-excel-1').show();

                let excelData = [];
                
                // ១. ទាញយក Header ពីក្បាលតារាង
                let headers = [];
                $('#tbl-loan-disbursement thead tr th').each(function() {
                    headers.push($(this).text().trim());
                });

                // ២. Loop ទាញទិន្នន័យពី tbody 
                $('#tbl-loan-disbursement tbody tr').each(function() {
                    let rowData = {};
                    $(this).find('td').each(function(index) {
                        let cellValue = $(this).text().trim();
                        if (index === 5 || index === 6) {
                            cellValue = cellValue.replace(/,/g, ''); // លុបសញ្ញាក្បៀសខណ្ឌខ្ទង់ពាន់
                            cellValue = parseFloat(cellValue) || 0;  // បម្លែងជាលេខទសភាគ
                        }
                        
                        // ការពារក្រែងលោចំនួន Column របស់ Header និង Body មិនស្មើគ្នា
                        if (headers[index]) {
                            rowData[headers[index]] = cellValue;
                        }
                    });
                    excelData.push(rowData);
                });

                // ៣. បង្កើត Worksheet ពី Array JSON
                var ws = XLSX.utils.json_to_sheet(excelData);

                // ៤. កំណត់ទទឹង Column ឱ្យត្រូវតាមចំនួន Column ទាំង ៩ របស់អ្នក
                ws['!cols'] = [
                    {wpx: 40},  // លំដាប់ (No.)
                    {wpx: 80},  // Branch
                    {wpx: 80},  // Currency
                    {wpx: 120}, // TransactionDate
                    {wpx: 120}, // Reference
                    {wpx: 120}, // Amount (លេខ)
                    {wpx: 120}, // AmountAS (លេខ)
                    {wpx: 100}, // Transaction
                    {wpx: 80}   // Column ទទេចុងក្រោយ
                ];

                // ៥. បង្កើត Workbook និង Export ឯកសារ
                var wb = XLSX.utils.book_new();
                XLSX.utils.book_append_sheet(wb, ws, "Loan_Disbursement");
                
                var d = new Date();
                var dateString = d.getFullYear() + "-" + ("0" + (d.getMonth() + 1)).slice(-2) + "-" + ("0" + d.getDate()).slice(-2);
                
                XLSX.writeFile(wb, "loan_disbursement_" + dateString + ".xlsx");

                // លាក់ Loading វិញ
                setTimeout(function() {
                    $('.btn-text-excel').show();
                    $('#btn-text-loading-excel-1').hide();
                }, 500);
            });
            // Reload only (DON'T destroy/reinit)
            $('.btn-filter').on('change', function() {
                $('#loading-overlay').hide();
                $('#tbl-loan-disbursement').DataTable().ajax.reload(null, false);
                $(".currency_month").text($('input[name="sale_date"]').val());
            });
            
        });

        function dataTables() {
            $('#loading-overlay').show();
            var dynamicHeight = $(window).height() - 350;
            if (dynamicHeight < 200) dynamicHeight = 200;
            if ($.fn.DataTable.isDataTable('#tbl-loan-disbursement')) {
                $('#tbl-loan-disbursement').DataTable().clear().destroy();
            }
           $('#tbl-loan-disbursement').DataTable({
                pageLength: 20,
                destroy: true,
                processing: true,
                serverSide: true,
                autoWidth: false,
                scrollX: true,
                scrollY: dynamicHeight + 'px',
                scroller: false,
                order: [[1, 'asc']],
                lengthMenu: [ 
                    [20, 25, 50, 100, -1],
                    [20, 25, 50, 100, "All"]
                ],
                ajax: {
                    url: '{{ URL("admin/mkt-report/loan-disbursement") }}',
                    type: 'GET',
                    data: function (d) {
                        d.branch_id = $('select[name="branch_id"]').val();
                        d.from_date = $('input[name="from_date"]').val();
                        d.to_date = $('input[name="to_date"]').val();
                        d.currency = $('select[name="currency"]').val();
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
                    { data: 'Branch', name: 'Branch' },
                    { data: "Currency", name: "Currency" },
                    { data: 'TransactionDate', name: 'TransactionDate' },
                    { data: "Reference", name: "Reference" },
                    { 
                        data: "Amount", 
                        className: 'text-right',
                        render: function (data) {
                            return data ? new Intl.NumberFormat('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 }).format(data) : '0.00';
                        }
                     },
                    { 
                        data: "AmountAS", 
                        className: 'text-right',
                        render: function (data) {
                            return data ? new Intl.NumberFormat('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 }).format(data) : '0.00';
                        }
                    },
                    { data: "Transaction", name: "Transaction" },
                    { 
                        data: null,
                        render: function () { return ""; }
                    },
                ],
                
            });

            $('#tbl-loan-disbursement').on('processing.dt', function (e, settings, processing) {
                if (processing) {
                    $('#loading-overlay').show();
                } else {
                    $('#loading-overlay').hide();
                }
            });
        }
    </script>
@endsection