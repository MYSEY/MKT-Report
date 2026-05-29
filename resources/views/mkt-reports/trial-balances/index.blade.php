@extends('layouts.admin')
@section('content')
<style>
    .w-200 {
        width: 200px !important;
        min-width: 200px !important;
    }
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
                            name="tb_date" 
                            id="tb_date" data-filter="date"
                            value="{{ date('Y-m') }}"
                            placeholder="mm/yyyy" 
                            readonly 
                            style="background-color: #fff;">
                    </div>
                </div>
                <div class="col-sm-3 col-md-3">
                    <div class="form-group">
                        <select data-filter="period" class="select2 form-control btn-filter" id="period" data-select2-id="select2-data-2-c0n3" name="period">
                            {{-- <option value="">Period</option> --}}
                            <option value=""></option>
                            <option value="current_month" selected>Current Month</option>
                            {{-- <option value="current_previous_month">Current Previous Month</option> --}}
                            <option value="current_previous_year">Current Previous Year</option>
                            <option value="current_year">Current Year</option>
                        </select>
                    </div>
                </div>
                @php
                    $AccessBranch = Auth::user()->AccessBranch;
                    $branches = preg_split('/\s+/', trim($AccessBranch));
                @endphp
                @if ($branches[0] == "HQ")
                    <div class="col-sm-2 col-md-2">
                        <div class="form-group">
                            <select data-filter="branch" class="select2 form-control btn-filter filter-branch" id="branch_id" data-select2-id="select2-data-2-c0n2" name="branch_id">
                                <option value="">All Branch</option>
                                @foreach ($branchs as $item)
                                    {{-- <option value="{{ $item->ID }}" {{ $item->ID == 'HQ' ? 'selected' : '' }}> --}}
                                    <option value="{{ $item->ID }}">
                                        {{ $item->ID }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                @endif
                
                <div class="col-sm-2 col-md-2">
                    <div class="form-group">
                        <select data-filter="currency" class="select2 form-control btn-filter" id="currency" data-select2-id="select2-data-2-c0n4" name="currency">
                            <option value="" selected>Original Currency</option>
                            <option value="KHR" >In KHR</option>
                            <option value="USD">In USD</option>
                        </select>
                    </div>
                </div>
                <div class="col-sm-2 col-md-2">
                    <div class="float-right">
                        @if(Auth::user()->can('Trial Balance Export'))
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
                Trial Balance Reports
            </h2>
        </div>
         <div class="panel-container show">
            <div class="panel-content">
                <div class="table-responsive">
                    <table id="tbl-TB" class="table table-bordered table-hover table-striped">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>AccNo</th>
                                <th>AccName</th>
                                <th>Beginning Balance</th>
                                <th>Movement Balance</th>
                                <th>Ending Balance</th>
                            </tr>
                        </thead>
                        <tbody>
                        </tbody>
                        <tfoot>
                            <tr style="background-color: #f8f9fa; font-weight: bold;">
                                <th colspan="3" style="text-align: right;">Total Balance:</th>
                                <th id="sum_BeginningBalance" class="text-right"></th>
                                <th id="sum_MovmenBalance" class="text-right"></th>
                                <th id="sum_EndingBalance" class="text-right"></th>
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
            $(document).on('click', '.btn_excel', function (e) {
                e.preventDefault();
                
                $('.btn-text-excel').hide();
                $('#btn-text-loading-excel-1').show();

                let excelData = [];
                
                // ១. ទាញយក Header ធម្មតា
                let headers = [];
                $('#tbl-TB thead tr th').each(function() {
                    headers.push($(this).text().trim());
                });

                // ២. Loop ទាញទិន្នន័យពី tbody ធម្មតា
                $('#tbl-TB tbody tr').each(function() {
                    let rowData = {};
                    $(this).find('td').each(function(index) {
                        let cellValue = $(this).text().trim();
                        if (index >= 3) {
                            cellValue = cellValue.replace(' $', '').replace(/,/g, ''); 
                            cellValue = parseFloat(cellValue) || 0;
                        }
                        rowData[headers[index]] = cellValue;
                    });
                    excelData.push(rowData);
                });

                // ៣. បង្កើត Worksheet ពីទិន្នន័យ tbody ជាមុនសិន
                var ws = XLSX.utils.json_to_sheet(excelData);
                let totalBeg = $('#sum_BeginningBalance').text().replace(' $', '').replace(/,/g, '');
                let totalMov = $('#sum_MovmenBalance').text().replace(' $', '').replace(/,/g, '');
                let totalEnd = $('#sum_EndingBalance').text().replace(' $', '').replace(/,/g, '');

                let footerRow = [
                    "Total Balance:", "", "", 
                    parseFloat(totalBeg) || 0,
                    parseFloat(totalMov) || 0,
                    parseFloat(totalEnd) || 0 
                ];

                let lastRowIndex = excelData.length + 1; 

                // បញ្ចូលជួរដេក Footer ទៅក្នុង Worksheet
                XLSX.utils.sheet_add_aoa(ws, [footerRow], { origin: -1 });
                if(!ws['!merges']) ws['!merges'] = [];
                ws['!merges'].push({
                    s: { r: lastRowIndex, c: 0 },
                    e: { r: lastRowIndex, c: 2 } 
                });

                // ៦. កំណត់ទទឹង Column
                ws['!cols'] = [
                    {wpx: 40}, {wpx: 80}, {wpx: 250}, {wpx: 120}, {wpx: 120}, {wpx: 120}
                ];

                // ៧. បង្កើត Workbook និង Export ឯកសារ
                var wb = XLSX.utils.book_new();
                XLSX.utils.book_append_sheet(wb, ws, "Trial_Balance");
                var d = new Date();
                var dateString = d.getFullYear() + "-" + ("0" + (d.getMonth() + 1)).slice(-2) + "-" + ("0" + d.getDate()).slice(-2);
                XLSX.writeFile(wb, "Trial_Balance_" + dateString + ".xlsx");

                setTimeout(function() {
                    $('.btn-text-excel').show();
                    $('#btn-text-loading-excel-1').hide();
                }, 500);
            });
            // Reload only (DON'T destroy/reinit)
            $('.btn-filter').on('change', function() {
                let current_filter = $(this).data("filter");
                if (current_filter == "date") {
                    $("#period").val("");
                }
                if(current_filter == "period"){
                    $("#tb_date").val("");
                }
                $('#loading-overlay').hide();
                $('#tbl-TB').DataTable().ajax.reload(null, false);
            });
            // Initialize only once
            dataTables();
        });

        function dataTables() {
            $('#loading-overlay').show();
            
            // ប្រើ DataTable() (D ធំ) សម្រាប់ API access
            if ($.fn.DataTable.isDataTable('#tbl-TB')) {
                $('#tbl-TB').DataTable().destroy();
                $('#tbl-TB tbody').empty(); // សម្អាតទិន្នន័យចាស់ចេញពី DOM
                $('#tbl-TB tfoot').empty(); 
            }

            var dynamicHeight = $(window).height() - 350;
            if (dynamicHeight < 200) dynamicHeight = 200;

            var table = $('#tbl-TB').DataTable({
                pageLength: 20,
                processing: true,
                serverSide: true,
                autoWidth: false,
                scrollX: true,
                scrollY: dynamicHeight + 'px',
                scrollCollapse: true, // ជួយឱ្យ Table រួញតាមទិន្នន័យតិចឬច្រើន
                order: [[1, 'asc']],
                lengthMenu: [ 
                    [20, 25, 50, 100, -1],
                    [20, 25, 50, 100, "All"]
                ],
                ajax: {
                    url: '{{ URL("admin/mkt-report/trial-balance") }}',
                    type: 'GET',
                    data: function (d) {
                        d.branch_id = $('select[name="branch_id"]').val();
                        d.period = $('select[name="period"]').val();
                        d.date = $('input[name="tb_date"]').val();
                        d.currency = $('select[name="currency"]').val();
                    },
                    dataSrc: function (json) {
                        return json.data;
                    }
                },
                columns: [
                    {
                        data: null,
                        orderable: false,
                        searchable: false,
                        className: 'text-center',
                        render: function (data, type, row, meta) {
                            return meta.row + meta.settings._iDisplayStart + 1;
                        }
                    },
                    { data: 'ID', name: 'bl.ID' }, // ផ្ទៀងផ្ទាត់ឈ្មោះ Column ក្នុង SQL (bl.ID)
                    { 
                        data: 'Description', 
                        name: 'mp.Description', 
                        width: "250px", 
                        className: "text-wrap" 
                    },
                    { 
                        data: 'BeginningBalance', 
                        className: 'text-right',
                        render: $.fn.dataTable.render.number(',', '.', 2, '')
                    },
                     { 
                        data: 'movementBalance', 
                        className: 'text-right',
                        render: $.fn.dataTable.render.number(',', '.', 2, '')
                    },
                    { 
                        data: 'EndingBalance', 
                        className: 'text-right',
                        render: $.fn.dataTable.render.number(',', '.', 2, '')
                    }
                ],
                footerCallback: function (row, data, start, end, display) {
                    var api = this.api();

                    // បង្កើត Function សម្រាប់ប្តូរតម្លៃទៅជាលេខទសភាគ
                    var intVal = function (i) {
                        return typeof i === 'string' ?
                            i.replace(/[\$,]/g, '') * 1 :
                            typeof i === 'number' ? i : 0;
                    };

                    // 1. គណនា Sum នៃ BeginningBalance
                    var totalBeginning = api.column(3).data().reduce(function (a, b) {
                        return intVal(a) + intVal(b);
                    }, 0);

                    // 2. គណនា Sum នៃ movementBalance
                    var totalMovement = api.column(4).data().reduce(function (a, b) {
                        return intVal(a) + intVal(b);
                    }, 0);

                    // 3. គណនា Sum នៃ EndingBalance
                    var totalEnding = api.column(5).data().reduce(function (a, b) {
                        return intVal(a) + intVal(b);
                    }, 0);

                    // បង្កើត Helper សម្រាប់ Format លេខ
                    var formatNumber = function(num) {
                        return new Intl.NumberFormat('en-US', { 
                            minimumFractionDigits: 2, 
                            maximumFractionDigits: 2 
                        }).format(num);
                    };

                    $(api.column(3).footer()).html(formatNumber(totalBeginning));
                    $(api.column(4).footer()).html(formatNumber(totalMovement));
                    $(api.column(5).footer()).html(formatNumber(totalEnding));

                    // បង្ហាញលទ្ធផលចូលទៅក្នុង ID នៃតារាង Footer របស់អ្នក
                    // $('#sum_BeginningBalance').html(formatNumber(totalBeginning));
                    // $('#sum_MovmenBalance').html(formatNumber(totalMovement));
                    // $('#sum_EndingBalance').html(formatNumber(totalEnding));
                }
            });
            $('#tbl-TB').on('processing.dt', function (e, settings, processing) {
                if (processing) {
                    $('#loading-overlay').show();
                } else {
                    $('#loading-overlay').hide();
                }
            });
        }
    </script>
@endsection