@extends('layouts.admin')
@section('content')
    <div class="row">
        <div class="col-md-4">
            <h3 class="">Sale Record Console</h3>
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
                <div class="col-sm-4 col-md-4">
                    <div class="form-group">
                        <select class="select2 form-control btn-filter filter-branch" id="branch_id" data-select2-id="select2-data-2-c0n2" name="branch_id">
                            <option value="" data-select2-id="select2-data-2-c0n2">All Branch</option>
                            @foreach ($branchs as $item)
                                <option value="{{ $item->ID }}">
                                    {{ $item->ID }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                </div>
                <div class="col-sm-4 col-md-4">
                    <div class="float-right">
                        @if(Auth::user()->can('Sale Record Console Export'))
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
                Sale Record Console
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
                        <tfoot>
                            <tr style="background-color: #f8f9fa; font-weight: bold;">
                                <th colspan="5" style="text-align: right;">Total:</th>
                                <th id="sum_khr" class="text-right"></th>
                                <th id="sum_usd" class="text-right"></th>
                                <th id="sum_total_khr" class="text-right"></th>
                                <th id="sum_tax" class="text-right"></th>
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
                    format: "yyyy-mm",
                    viewMode: "months",
                    minViewMode: "months",
                    autoclose: true,
                    todayHighlight: true
                });
            });
            $(".btn_excel").on("click", function() {
                let query = {
                    branch_id: $('select[name="branch_id"]').val(),
                    date: $('input[name="sale_date"]').val(),
                    type: "1"
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
                lengthMenu: [ 
                    [20, 25, 50, 100, -1],
                    [20, 25, 50, 100, "All"]
                ],
                ajax: {
                    url: '{{ URL("admin/mkt-report/sale-record-console") }}',
                    type: 'GET',
                    data: function (d) {
                        d.branch_id = $('select[name="branch_id"]').val();
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
                        render: function (data, type, row) {
                            return data+ ' ៛';
                        }
                    },
                    { 
                        data: 'AmountUSD', 
                        className: 'text-right',
                        render: function (data, type, row) {
                            return data+ ' $';
                        }
                    },
                    { 
                        data: 'TotalAmountKHR', 
                        className: 'text-right',
                        render: function (data, type, row) {
                            return data+ ' ៛';
                        }
                    },
                    { 
                        data: 'Exemption1Percent', 
                        className: 'text-right',
                        render: function (data, type, row) {
                            return data+ ' ៛';
                        }
                    }
                ],
                footerCallback: function (row, data, start, end, display) {
                    var api = this.api();

                    // បង្កើត function សម្រាប់សម្អាត data ឱ្យទៅជាលេខសម្រាប់បូក (លុបសញ្ញា $ ឬ ៛ ចេញ)
                    var intVal = function (i) {
                        return typeof i === 'string' ?
                            i.replace(/[\$,៛]/g, '') * 1 :
                            typeof i === 'number' ? i : 0;
                    };

                    // ១. សរុប AmountKHR (Column index 5)
                    var totalKHR = api.column(5, { page: 'current' }).data().reduce(function (a, b) {
                        return intVal(a) + intVal(b);
                    }, 0);

                    // ២. សរុប AmountUSD (Column index 6)
                    var totalUSD = api.column(6, { page: 'current' }).data().reduce(function (a, b) {
                        return intVal(a) + intVal(b);
                    }, 0);

                    // ៣. សរុប TotalAmountKHR (Column index 7)
                    var totalAmountKHR = api.column(7, { page: 'current' }).data().reduce(function (a, b) {
                        return intVal(a) + intVal(b);
                    }, 0);

                    // ៤. សរុប Exemption1Percent (Column index 8)
                    var totalTax = api.column(8, { page: 'current' }).data().reduce(function (a, b) {
                        return intVal(a) + intVal(b);
                    }, 0);

                    // បោះតម្លៃដែលបូករួចទៅកាន់ HTML (<tfoot>)
                    $(api.column(5).footer()).html(Number(totalKHR).toLocaleString() + ' ៛');
                    $(api.column(6).footer()).html('$ ' + Number(totalUSD).toLocaleString(undefined, {minimumFractionDigits: 2}));
                    $(api.column(7).footer()).html(Number(totalAmountKHR).toLocaleString() + ' ៛');
                    $(api.column(8).footer()).html(Number(totalTax).toLocaleString() + ' ៛');
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