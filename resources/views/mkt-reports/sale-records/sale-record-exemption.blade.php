@extends('layouts.admin')
@section('content')
    <div class="row">
        <div class="col-md-4">
            <h3 class="">Sale Record Exemptions</h3>
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
                        @if(Auth::user()->can('Sale Record Exemption Export'))
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
                Sale Record Exemptions
            </h2>
        </div>
         <div class="panel-container show">
            <div class="panel-content">
                <div class="table-responsive">
                    <table id="tbl-sale-record" class="table table-bordered table-hover table-striped">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Transaction_Date</th>
                                <th>Inv_No</th>
                                <th>Account_Number</th>
                                <th>Account_Name</th>
                                <th>Amount_KHR</th>
                                <th>Amount_USD</th>
                                <th>Total_Amount_KHR</th>
                                <th>Income_Tax_Rate_1%</th>
                            </tr>
                        </thead>
                        <tbody>
                        </tbody>
                        {{-- <tfoot>
                            <tr style="background-color: #f8f9fa; font-weight: bold;">
                                <th colspan="8" style="text-align: right;">Total:</th>
                                <th id="sum_khr" class="text-right"></th>
                                <th id="sum_usd" class="text-right"></th>
                                <th id="sum_total_khr" class="text-right"></th>
                                <th id="sum_tax" class="text-right"></th>
                                <th colspan="2"></th>
                            </tr>
                        </tfoot> --}}
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
            $(".btn_excel").on("click", function() {
                let query = {
                    date: $('input[name="sale_date"]').val(),
                    type: "2"
                };
                var url = "{{URL::to('admin/mkt-report/sale-record-excs/download')}}?" + $.param(query)
                window.location = url;
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
                    url: '{{ URL("admin/mkt-report/sale-record-exemption") }}',
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
                        name: 'no',
                        orderable: false,
                        searchable: false,
                        className: 'text-center',
                        render: function (data, type, row, meta) {
                            return meta.row + meta.settings._iDisplayStart + 1;
                        }
                    },
                    {   
                        data: null, 
                        name: 'date_display',
                        orderable: false, 
                        searchable: false,
                        render: function (data, type, row) {
                            // បញ្ជាក់៖ ត្រូវប្រាកដថាបានទាញ Column ទាំង ៣ នេះចេញពី Backend មកដែរ
                            if (row.GLYear && row.GLMonth && row.GLDay) {
                                return row.GLDay +'-' +row.GLMonth + '-'+ row.GLYear;
                            }
                            return '-';
                        }
                    },
                    { 
                        data: null,
                        render: function () { return 11111; }
                    },
                    {   
                        data: 'ID', 
                        name: 'Bl.ID', 
                    },
                    {   
                        data: 'Description', 
                        name: 'Mp.Description',
                        defaultContent: 'No Description'
                    },
                    { 
                        data: 'AmountKHR', 
                        className: 'text-right',
                        render: d => d > 0 ? Number(d).toLocaleString() + ' ៛' : '-' 
                    },
                    { 
                        data: 'AmountUSD', 
                        className: 'text-right',
                        render: d => d > 0 ? '$ ' + Number(d).toLocaleString(undefined, {minimumFractionDigits: 2}) : '-' 
                    },
                    { 
                        data: 'TotalAmountKHR', 
                        className: 'text-right',
                        render: d => Number(d).toLocaleString() + ' ៛'
                    },
                    { 
                        data: 'Exemption1Percent', 
                        className: 'text-right',
                        render: d => Math.round(d).toLocaleString() + ' ៛'
                    }
                ],
                // footerCallback: function (row, data, start, end, display) {
                //     var api = this.api();

                //     // បង្កើត Function ជំនួយសម្រាប់បម្លែង string ទៅជាលេខ
                //     var intVal = function (i) {
                //         return typeof i === 'string' ? i.replace(/[\$,៛]/g, '') * 1 : typeof i === 'number' ? i : 0;
                //     };

                //     // ១. សរុប Amount_KHR (បូកតែជួរដែលមាន Currency == "KHR")
                //     var totalKHR = api.rows().data().toArray().reduce(function (a, b) {
                //         return b.Currency === "KHR" ? a + intVal(b.Amount) : a;
                //     }, 0);

                //     // ២. សរុប Amount_USD (បូកតែជួរដែលមាន Currency == "USD")
                //     var totalUSD = api.rows().data().toArray().reduce(function (a, b) {
                //         return b.Currency === "USD" ? a + intVal(b.Amount) : a;
                //     }, 0);

                //     // ៣. សរុប Total_Amount_KHR (បូកគ្រប់ជួរ)
                //     var totalAllKHR = api.column(10).data().reduce(function (a, b) {
                //         return intVal(a) + intVal(b);
                //     }, 0);

                //     // ៤. សរុប Income_Tax_Rate_1% (បូកគ្រប់ជួរ)
                //     var totalTax = api.column(11).data().reduce(function (a, b) {
                //         return intVal(a) + intVal(b);
                //     }, 0);

                //     // បង្ហាញលទ្ធផលចូលក្នុង Footer
                //     $(api.column(8).footer()).html(totalKHR.toLocaleString() + ' ៛');
                //     $(api.column(9).footer()).html(totalUSD.toLocaleString(undefined, {minimumFractionDigits: 2})+ ' $');
                //     $(api.column(10).footer()).html(totalAllKHR.toLocaleString() + ' ៛');
                //     $(api.column(11).footer()).html(Math.round(totalTax).toLocaleString() + ' ៛');
                // }
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