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
                @php
                    $AccessBranch = Auth::user()->AccessBranch;
                    $branches = preg_split('/\s+/', trim($AccessBranch));
                @endphp
                @if ($branches[0] == "HQ")
                    <div class="col-sm-3 col-md-3">
                        <div class="form-group">
                            <select data-filter="branch" class="select2 form-control btn-filter filter-branch" id="branch_id" data-select2-id="select2-data-2-c0n2" name="branch_id">
                                <option value="">All Branch</option>
                                @foreach ($branchs as $item)
                                    <option value="{{ $item->ID }}">
                                        {{ $item->ID }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                @endif
                
                <div class="col-sm-3 col-md-3">
                    <div class="form-group">
                        <select data-filter="currency" class="select2 form-control btn-filter" id="currency" data-select2-id="select2-data-2-c0n4" name="currency">
                            <option value="" selected>Original Currency</option>
                            <option value="KHR" >In KHR</option>
                            <option value="USD">In USD</option>
                        </select>
                    </div>
                </div>
                <div class="col-sm-3 col-md-3">
                    <div class="float-right">
                        @if(Auth::user()->can('GL Balance Export'))
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
                GL Balance Reports
            </h2>
        </div>
         <div class="panel-container show">
            <div class="panel-content">
                <div class="table-responsive">
                    <table id="tbl-TB" class="table table-bordered table-hover table-striped">
                        <thead>
                            <tr>
                                <th>GL Line</th>
                                <th>Description</th>
                                <th>Currency</th>
                                <th>FCYBalance</th>
                                <th>LCYBalance</th>
                                <th>Pre Month Balance</th>
                                <th>Pre Year Balance</th>
                                <th>Year to Date Balance</th>
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
                
                // 1. Get Table Headers
                let headers = [];
                $('#tbl-TB thead tr th').each(function() {
                    headers.push($(this).text().trim());
                });

                // 2. Loop through Table Body Rows
                $('#tbl-TB tbody tr').each(function() {
                    let rowData = {};
                    $(this).find('td').each(function(index) {
                        let cellValue = $(this).text().trim();
                        
                        // Convert financial values (Index 3 and above: FCYBalance, LCYBalance, etc.) to numbers
                        if (index >= 3) {
                            cellValue = cellValue.replace(' $', '').replace(/,/g, ''); 
                            cellValue = parseFloat(cellValue) || 0;
                        }
                        
                        if (headers[index]) {
                            rowData[headers[index]] = cellValue;
                        }
                    });
                    excelData.push(rowData);
                });

                // 3. Create Worksheet from JSON Data
                var ws = XLSX.utils.json_to_sheet(excelData);

                // 4. Set Column Widths
                ws['!cols'] = [
                    { wpx: 80 },  // GL Line
                    { wpx: 250 }, // Description
                    { wpx: 70 },  // Currency
                    { wpx: 110 }, // FCYBalance
                    { wpx: 110 }, // LCYBalance
                    { wpx: 110 }, // Pre Month Balance
                    { wpx: 110 }, // Pre Year Balance
                    { wpx: 110 }  // Year to Date Balance
                ];

                // 5. Create Workbook and Export File
                var wb = XLSX.utils.book_new();
                XLSX.utils.book_append_sheet(wb, ws, "GL_Balance_Report");

                var d = new Date();
                var dateString = d.getFullYear() + "-" + ("0" + (d.getMonth() + 1)).slice(-2) + "-" + ("0" + d.getDate()).slice(-2);
                XLSX.writeFile(wb, "GL_Balance_Report_" + dateString + ".xlsx");

                // Reset button state
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
                scrollCollapse: true,
                order: [[1, 'asc']],
                lengthMenu: [ 
                    [20, 25, 50, 100, -1],
                    [20, 25, 50, 100, "All"]
                ],
                ajax: {
                    url: '{{ URL("admin/mkt-report/gl-balance") }}',
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
                        data: 'ID', 
                        name: 'bl.ID',
                        render: function (data, type, row) {
                            if (!data) return ''; 

                            return `<a href="/admin/mkt-report/gl-detail/${data}" class="text-decoration-none">${data}</a>`;
                        }
                    },
                    { 
                        data: 'Description', 
                        name: 'mp.Description', 
                        width: "250px", 
                        className: "text-wrap",
                        render: function (data, type, row) {
                            if (!data) return '';
                            return `<a href="/admin/mkt-report/gl-detail/${row.ID}" class="text-decoration-none">${data}</a>`;
                        }
                    },
                    { 
                        data: 'Currency', 
                        name: 'bl.Currency'
                    },
                    { 
                        data: 'Balance', 
                        name: 'bl.Balance',
                        className: 'text-right',
                        render: $.fn.dataTable.render.number(',', '.', 2, '')
                    },
                    { 
                        data: 'LCYBalance', 
                        name: 'bl.LCYBalance',
                        className: 'text-right',
                        render: $.fn.dataTable.render.number(',', '.', 2, '')
                    },
                    { 
                        data: 'LCYPrevMonthBal', 
                        name: 'bl.LCYPrevMonthBal',
                        className: 'text-right',
                        render: $.fn.dataTable.render.number(',', '.', 2, '')
                    },
                    { 
                        data: 'LCYPrevYearBal', 
                        name: 'bl.LCYPrevYearBal',
                        className: 'text-right',
                        render: $.fn.dataTable.render.number(',', '.', 2, '')
                    },
                     { 
                        data: 'LCYYTDBal', 
                        name: 'bl.LCYYTDBal',
                        className: 'text-right',
                        render: $.fn.dataTable.render.number(',', '.', 2, '')
                    },
                ],
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