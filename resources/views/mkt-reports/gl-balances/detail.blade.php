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
                
                {{-- <div class="col-sm-3 col-md-3">
                    <div class="form-group">
                        <select data-filter="currency" class="select2 form-control btn-filter" id="currency" data-select2-id="select2-data-2-c0n4" name="currency">
                            <option value="" selected>Original Currency</option>
                            <option value="KHR" >In KHR</option>
                            <option value="USD">In USD</option>
                        </select>
                    </div>
                </div> --}}
                <div class="col-sm-6 col-md-6">
                    <div class="float-right">
                        @if(Auth::user()->can('GL Balance Export'))
                           <button type="button" class="btn btn-sm btn-info waves-effect waves-themed btn_excel mr-1">
                                <span class="btn-text-excel"><i class="fal fa-arrow-circle-down"></i></span>
                                <span id="btn-text-loading-excel-1" style="display: none"><i class="fa fa-spinner fa-spin"></i></span>
                                Export to Excel
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
                                <th>Branch</th>
                                <th>Date</th>
                                <th>Transaction</th>
                                <th>Reference</th>
                                <th>Note</th>
                                <th>Debit</th>
                                <th>Credit</th>
                                <th>Balance</th>
                                <th>SortCut</th>
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
                        
                        // Indexes 5 (Debit), 6 (Credit), 7 (balance) are financial numbers
                        if (index >= 5 && index <= 7) {
                            // Clean formatted string (e.g. "1,250.00 $" -> 1250.00)
                            cellValue = cellValue.replace('$', '').replace(/,/g, '').trim(); 
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

                // 4. Apply Excel Number Format (#,##0.00) to Debit, Credit, Balance columns
                const range = XLSX.utils.decode_range(ws['!ref']);
                for (let R = range.s.r + 1; R <= range.e.r; ++R) { // Skip header row
                    [5, 6, 7].forEach(C => { // Col 5: Debit, Col 6: Credit, Col 7: Balance
                        const cell_address = { c: C, r: R };
                        const cell_ref = XLSX.utils.encode_cell(cell_address);
                        if (ws[cell_ref] && typeof ws[cell_ref].v === 'number') {
                            ws[cell_ref].z = '#,##0.00'; // Standard 2-decimal format
                        }
                    });
                }

                // 5. Set Column Widths for all 9 columns
                ws['!cols'] = [
                    { wpx: 80 },  // 0. Branch
                    { wpx: 110 }, // 1. Transaction Date
                    { wpx: 160 }, // 2. Transaction (Transaction + Description)
                    { wpx: 110 }, // 3. Reference
                    { wpx: 250 }, // 4. Description
                    { wpx: 110 }, // 5. Debit
                    { wpx: 110 }, // 6. Credit
                    { wpx: 120 }, // 7. Balance
                    { wpx: 80 }   // 8. SortCut
                ];

                // 6. Create Workbook and Export File
                var wb = XLSX.utils.book_new();
                XLSX.utils.book_append_sheet(wb, ws, "Journal_Report");

                var d = new Date();
                var dateString = d.getFullYear() + "-" + ("0" + (d.getMonth() + 1)).slice(-2) + "-" + ("0" + d.getDate()).slice(-2);
                XLSX.writeFile(wb, "Journal_Report_" + dateString + ".xlsx");

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
            let path = window.location.pathname; 
            let glId = path.split('/').pop();
            dataTables(glId);
        });

        function dataTables(glId) {
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
                    url: "{{ url('admin/mkt-report/gl-detail') }}/" + glId,
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
                    { data: 'Branch', name: 'jn.Branch' },
                    { data: 'TransactionDate', name: 'jn.TransactionDate' },
                    { 
                        data: 'Transaction',
                    },
                    { 
                        data: 'Reference', 
                        name: 'jn.Reference'
                    },
                    { 
                        data: 'Description', 
                        name: 'jn.Description'
                    },
                    { 
                        data: 'Debit',
                        className: 'text-right',
                        render: $.fn.dataTable.render.number(',', '.', 2, '')
                    },
                    { 
                        data: 'Credit',
                        className: 'text-right',
                        render: $.fn.dataTable.render.number(',', '.', 2, '')
                    },
                    { 
                        data: 'balance', 
                        className: 'text-right',
                        render: $.fn.dataTable.render.number(',', '.', 2, '')
                    },
                    { 
                        data: 'SortCut',
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