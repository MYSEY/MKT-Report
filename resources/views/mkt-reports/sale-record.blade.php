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
                <div class="col-sm-4 col-md-4">
                    <div class="form-group">
                        <input type="text" 
                            class="form-control datepicker_month btn-filter" 
                            name="sale_date" 
                            id="sale_date" 
                            value="{{ date('Y-m') }}"
                            placeholder="mm/yyyy" 
                            readonly 
                            style="background-color: #fff;">
                    </div>
                </div>
                <div class="col-sm-8 col-md-8">
                    <div class="float-right">
                        @if(Auth::user()->can('Sale Record Export'))
                           <button type="button" class="btn btn-sm btn-info waves-effect waves-themed btn_excel mr-1">
                                <span class="btn-text-excel"><i class="fal fa-arrow-circle-down"></i></span>
                                <span id="btn-text-loading-excel-1" style="display: none"><i class="fa fa-spinner fa-spin"></i></span>
                                Excel Summary
                            </button>

                            <button type="button" class="btn btn-sm btn-success waves-effect waves-themed btn_excel_all mr-1">
                                <span class="btn-text-excel"><i class="fal fa-arrow-circle-down"></i></span>
                                <span id="btn-text-loading-excel-2" style="display: none"><i class="fa fa-spinner fa-spin"></i></span>
                                Excel Details
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
            $('.btn-filter').on('change', function() {
                $(".currency_rate").text("");
                $('#loading-overlay').hide();
                $('#tbl-sale-record').DataTable().ajax.reload(null, false);
                $(".currency_month").text($('input[name="sale_date"]').val());
            });
            // Initialize only once
            dataTables();
        });

        function dataTables() {
            $('#loading-overlay').show();
            // Check if DataTable instance exists, then destroy it
            if ($.fn.DataTable.isDataTable('#tbl-sale-record')) {
                $('#tbl-sale-record').DataTable().clear().destroy();
            }
           $('#tbl-sale-record').DataTable({
                pageLength: 20,
                destroy: true,
                processing: true,
                serverSide: true,
                scrollX: true, // បើកវិញប្រសិនបើ Column ច្រើនពេកហៀរចេញក្រៅ
                scrollY: '350px',
                scroller: false,
                order: [[1, 'asc']],
                lengthMenu: [ [20, 25, 50, 100], [10, 25, 50, 100] ],
                ajax: {
                    url: '{{ URL("admin/mkt-report/sale-record") }}',
                    type: 'GET',
                    data: function (d) {
                        d.date = $('input[name="sale_date"]').val();
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
                        name: 'id',
                        orderable: false,
                        searchable: false,
                        className: 'text-center',
                        render: function (data, type, row, meta) {
                            return meta.row + meta.settings._iDisplayStart + 1;
                        }
                    },
                    {   
                        data: 'TransactionDate', 
                        name: 'TransactionDate',
                    },
                    { 
                        data: null,
                        render: function () {
                            return 11111;
                        }
                    },
                    { 
                        data: null,
                        className: 'text-center',
                        render: function () {
                            return 2;
                        }
                    },
                    { 
                        data: 'Reference', 
                        name: 'Reference',
                    },
                    { 
                        data: 'KhName', 
                        name: 'KhName',
                    },
                    { 
                        data: 'EnName', 
                        name: 'EnName',
                    },
                    { 
                        data: null,
                        className: 'text-center',
                        render: function () {
                            return 3;
                        }
                    },
                   
                    // ✅ KHR amount
                    { 
                        className: 'text-right',
                        data: 'Amount',
                        name: 'Amount',
                        render: function (data, type, row) {
                            if (row.Currency === "KHR") {
                                return Number(data) + '៛';
                            } else {
                                return '- ៛';
                            }
                        }
                    },
                     // ✅ USD amount
                    { 
                        className: 'text-right',
                        data: 'Amount',
                        name: 'Amount',
                        render: function (data, type, row) {
                            if (row.Currency === "USD") {
                                return Number(data) + '$';
                            } else {
                                return '- $';
                            }
                        }
                    },
                    {
                        className: 'text-right',
                        data: 'TotalKHR', // ប្រើឈ្មោះដែលយើងបានប្ដូរនៅ Backend
                        name: 'TotalKHR',
                        render: function (data) {
                            return Number(data).toLocaleString() + ' ៛';
                        }
                    },

                    // ✅ Column ពន្ធ ១%
                    { 
                        className: 'text-right',
                        data: 'Tax1Percent', // ប្រើឈ្មោះដែលយើងបានប្ដូរនៅ Backend
                        name: 'Tax1Percent',
                        render: function (data) {
                            return Math.round(data).toLocaleString() + ' ៛';
                        }
                    },
                    { 
                        data: null,
                        render: function () {
                            return 'Loan Repayment';
                        }
                    },
                    { 
                        data: null,
                        render: function () {
                            return 0;
                        }
                    },
                ],
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