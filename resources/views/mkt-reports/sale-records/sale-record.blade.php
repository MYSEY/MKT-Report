@extends('layouts.admin')
@section('content')
    <div class="row">
        <div class="col-md-4">
            <h3 class="">Sale Records</h3>
            <h5 class=""><strong>As of month:</strong> <span class="currency_month">{{ date('Y-m') }}</span></h5>
            <h6><strong>Currency:</strong> <span class="currency_rate"></span></h6>
        </div>
    </div>
    {!! Toastr::message() !!}
    <div class="card mb-2">
        <div class="card-body">
            <div class="row filter-btn">
                <div class="col-sm-3 col-md-3">
                    <div class="form-group">
                        <input type="text" 
                            class="form-control datepicker_month" 
                            name="sale_date" 
                            id="sale_date" 
                            value=""
                            placeholder="mm/yyyy" 
                            readonly 
                            style="background-color: #fff;">
                    </div>
                </div>
                <div class="col-sm-3 col-md-3">
                    <div class="form-group">
                        <input type="text"  placeholder="GL Code" name="gl_code" class="form-control gl_code">
                    </div>
                </div>
                <div class="col-sm-3 col-md-3">
                    <div class="form-group">
                        <input type="text"  placeholder="Full Name" name="full_name"  class="form-control full_name">
                    </div>
                </div>
                <div class="col-sm-3 col-md-3">
                    <div class="float-right">
                        <button type="button" class="btn btn-sm btn-outline-secondary btn-search mr-1" id="icon-search-download-reload">
                            <span class="btn-txt"><i class="fal fa-search"></i></span>
                            Search
                            <span class="loading-icon" style="display: none"><i class="fa fa-spinner fa-spin"></i></span>
                        </button>
                        @if(Auth::user()->can('Sale Record Export'))
                           <button type="button" class="btn btn-sm btn-info waves-effect waves-themed btn_excel mr-1">
                                <span class="btn-text-excel"><i class="fal fa-arrow-circle-down"></i></span>
                                <span id="btn-text-loading-excel-1" style="display: none"><i class="fa fa-spinner fa-spin"></i></span>
                                Excel Summary
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
                Sale Records
            </h2>
        </div>
         <div class="panel-container show">
            <div class="panel-content">
                <div class="table-responsive">
                    <table id="tbl-sale-record" class="table table-bordered table-hover table-striped">
                        <thead>
                            <tr>
                                <th rowspan="2">#</th>
                                <th rowspan="2">Transaction_Date</th>
                                <th rowspan="2">Inv_No</th>
                                <th rowspan="2">GL_Code</th>
                                <th rowspan="2">Currency</th>
                                <th colspan="4" class="text-center">Buyer</th> 
                                <th rowspan="2">Type_of_Supply</th>
                                <th rowspan="2">Amount_KHR</th>
                                <th rowspan="2">Amount_USD</th>
                                <th rowspan="2">Total_Amount_KHR</th>
                                <th rowspan="2">Income_Tax_Rate_1%</th>
                                <th rowspan="2">Description</th>
                                <th rowspan="2">Acc_Method*</th>
                            </tr>
                            <tr>
                                <th>Type</th>
                                <th>ID</th>
                                <th>Name_KH</th>
                                <th>Name_EN</th>
                            </tr>
                        </thead>
                        <tbody>
                        </tbody>
                        <tfoot>
                            <tr style="background-color: #f8f9fa; font-weight: bold;">
                                <th colspan="10" style="text-align: right;">Total:</th>
                                <th id="sum_khr" class="text-right"></th>
                                <th id="sum_usd" class="text-right"></th>
                                <th id="sum_total_khr" class="text-right"></th>
                                <th id="sum_tax" class="text-right"></th>
                                <th colspan="2"></th>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>
        </div>
    </div>
@endsection
@section('script')
    <script>
        $(function(){
            $(document).ready(function() {
                $('.datepicker_month').datepicker({
                    format: "yyyy-mm",     // កំណត់ Format បង្ហាញ
                    viewMode: "months",    // បង្ហាញផ្ទាំងខែពេលបើកដំបូង
                    minViewMode: "months", // កំណត់ឱ្យរើសបានត្រឹមខែ (ចុចលើខែហើយបិទតែម្ដង)
                    autoclose: true,
                    todayHighlight: true
                });
            });
            $(document).ready(function() {
                // បង្កើត Function រួមសម្រាប់ Handle ការ Export
                function handleExport(btnElement, urlPath) {
                    let $thisBtn = $(btnElement);
                    let $allButtons = $('.btn_excel, .btn_excel_all');
                    
                    // ១. Disable button ទាំងពីរ និងបង្ហាញ Loading
                    $allButtons.prop('disabled', true).addClass('disabled');
                    
                    // លាក់ icon ដើម និងបង្ហាញ icon spinner (ប្រើ selector ឱ្យចំ span ដែលអ្នកមាន)
                    $allButtons.find('.btn-text-excel').hide();
                    $allButtons.find('span[id^="btn-text-loading-excel"]').show();

                    // ២. រៀបចំ Query String
                    let query = {
                        date: $('input[name="sale_date"]').val(),
                        gl_code: $('input[name="gl_code"]').val(),
                        full_name: $('input[name="full_name"]').val(),
                        // អ្នកអាចបន្ថែម search: $('input[type="search"]').val() បើចង់បាន
                    };

                    // ៣. បញ្ជូនទៅកាន់ URL
                    let url = "{{ url('/') }}/" + urlPath + "?" + $.param(query);
                    window.location = url;

                    // ៤. កំណត់ឱ្យ Buttons ដើរវិញក្រោយពេល ១០ វិនាទី (ព្រោះ window.location គ្មាន callback ទេ)
                    setTimeout(function() {
                        $allButtons.prop('disabled', false).removeClass('disabled');
                        $allButtons.find('.btn-text-excel').show();
                        $allButtons.find('span[id^="btn-text-loading-excel"]').hide();
                    }, 10000); 
                }

                // ចាប់ Event ពេលចុចលើ "Excel to Template"
                $(".btn_excel").on("click", function() {
                    handleExport(this, 'admin/mkt-report/sale-record/download');
                });

                // ចាប់ Event ពេលចុចលើ "Excel Not Template"
                $(".btn_excel_all").on("click", function() {
                    handleExport(this, 'admin/mkt-report/sale-record/downloads');
                });
            });
            // Reload only (DON'T destroy/reinit)
            $('.btn-search').on('click', function() {
                dataTables();
            });
            // Initialize only once
            // dataTables();
        });

        function dataTables() {
            $('#loading-overlay').show();
            // Check if DataTable instance exists, then destroy it
            if ($.fn.DataTable.isDataTable('#tbl-sale-record')) {
                $('#tbl-sale-record').DataTable().clear().destroy();
            }
           $('#tbl-sale-record').DataTable({
                pageLength: 20,
                searching: false,
                destroy: true,
                processing: true,
                serverSide: true,
                scrollX: true, // បើកវិញប្រសិនបើ Column ច្រើនពេកហៀរចេញក្រៅ
                scrollY: '350px',
                scroller: false,
                order: [[1, 'desc']],
                lengthMenu: [ [20, 25, 50, 100], [10, 25, 50, 100] ],
                ajax: {
                    url: '{{ URL("admin/mkt-report/sale-record") }}',
                    type: 'GET',
                    data: function (d) {
                        d.date = $('input[name="sale_date"]').val();
                        d.gl_code = $('input[name="gl_code"]').val();
                        d.full_name = $('input[name="full_name"]').val();
                    },
                    dataSrc: function (json) {
                        let currency = json.currency;
                        $(".currency_rate").text(currency+"៛");
                        return json.data;
                    }
                },
                columns: [
                    {
                        data: null,
                        name: 'no',
                        orderable: false,
                        searchable: false,
                        className: 'text-center',
                        render: (data, type, row, meta) => meta.row + meta.settings._iDisplayStart + 1
                    },
                    { data: 'TransactionMonth', name: 'combined.TransactionMonth', className: 'text-center' },
                    { data: null, render: () => 11111, className: 'text-center', orderable: false, searchable: false },
                    { data: 'GLAcc', name: 'combined.GLAcc'},
                    { data: 'Currency', name: 'combined.Currency' },
                    { data: null, render: () => 2, className: 'text-center', orderable: false, searchable: false },
                    { data: 'Reference', name: 'combined.Reference' },
                    
                    // 💡 កែសម្រួលត្រង់នេះ៖ ប្តូរពី CUST.LastNameKh មកជា cust.LastNameKh (អក្សរតូចដូចក្នុង Backend)
                    { data: 'KhName', name: 'cust.LastNameKh' },
                    { data: 'EnName', name: 'cust.LastNameEn' },
                    
                    { data: null, render: () => 3, className: 'text-center', orderable: false, searchable: false },

                    // Amount KHR
                    { 
                        data: 'Amount_KHR', 
                        name: 'Amount_KHR', 
                        className: 'text-right',
                        render: d => d != 0 ? Number(d).toLocaleString() + ' ៛' : '-'
                    },
                    // Amount USD
                    { 
                        data: 'Amount_USD', 
                        name: 'Amount_USD', 
                        className: 'text-right',
                        render: d => d != 0 ? '$ ' + Number(d).toLocaleString(undefined, {minimumFractionDigits: 2}) : '-'
                    },
                    // Total Amount KHR
                    { 
                        data: 'Total_Amount_KHR', 
                        name: 'Total_Amount_KHR', 
                        className: 'text-right font-weight-bold',
                        render: d => Number(d).toLocaleString() + ' ៛'
                    },
                    // Income Tax 1%
                    { 
                        data: 'Income_Tax', 
                        name: 'Income_Tax', 
                        className: 'text-right',
                        render: d => Math.round(d).toLocaleString() + ' ៛'
                    },
                    { data: null, render: () => 'Loan Repayment', orderable: false, searchable: false },
                    { data: null, render: () => 0, className: 'text-center', orderable: false, searchable: false },
                ],

                footerCallback: function (row, data, start, end, display) {
                    const api = this.api();

                    // Function ជំនួយសម្រាប់ដកក្បៀស និងប្តូរជាលេខ
                    const parseNum = i => typeof i === 'string' ? i.replace(/[\$,៛,]/g, '') * 1 : typeof i === 'number' ? i : 0;

                    // បូកសរុបតាម Column (ប្រើ index 8, 9, 10, 11)
                    const totalKHR = api.column(11, { page: 'current' }).data().reduce((a, b) => parseNum(a) + parseNum(b), 0);
                    const totalUSD = api.column(12, { page: 'current' }).data().reduce((a, b) => parseNum(a) + parseNum(b), 0);
                    const totalAll = api.column(13, { page: 'current' }).data().reduce((a, b) => parseNum(a) + parseNum(b), 0);
                    const totalTax = api.column(14, { page: 'current' }).data().reduce((a, b) => parseNum(a) + parseNum(b), 0);

                    // បង្ហាញលទ្ធផលក្នុង Footer
                    $(api.column(11).footer()).html(totalKHR != 0 ? totalKHR.toLocaleString() + ' ៛' : '-');
                    $(api.column(12).footer()).html(totalUSD != 0 ? '$ ' + totalUSD.toLocaleString(undefined, {minimumFractionDigits: 2}) : '-');
                    $(api.column(13).footer()).html(totalAll.toLocaleString() + ' ៛');
                    $(api.column(14).footer()).html(Math.round(totalTax).toLocaleString() + ' ៛');
                }
            });

            $('#tbl-sale-record').on('processing.dt', function (e, settings, processing) {
                if (processing) {
                    $('#loading-overlay').show();
                } else {
                    $('#loading-overlay').hide();
                }
            });
        }
    </script>
@endsection