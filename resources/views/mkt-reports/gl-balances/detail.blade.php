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
                            value="{{ \Carbon\Carbon::now()->format('d-m-Y') }}"
                            placeholder="To Date" 
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
                @else
                 <div class="col-sm-3 col-md-3"></div>
                @endif
                <div class="col-sm-3 col-md-3">
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
            // $(document).ready(function() {
            //     $('.datepicker_month').datepicker({
            //         format: "yyyy-mm",
            //         viewMode: "months",
            //         minViewMode: "months",
            //         autoclose: true,
            //         todayHighlight: true
            //     });
            // });
            $(document).ready(function() {
                $('.datepicker_month').datepicker({
                    format: "dd-mm-yyyy",
                    autoclose: true,
                    todayHighlight: true
                });
            });
            $(document).on('click', '.btn_excel', function (e) {
                e.preventDefault();
                
                let $btnText = $('.btn-text-excel');
                let $btnLoading = $('#btn-text-loading-excel-1');

                $btnText.hide();
                $btnLoading.show();

                function loadXlsxStyleScript(callback) {
                    if (window.XLSX && window.XLSX.utils && window.XLSX.utils.json_to_sheet) {
                        callback();
                        return;
                    }

                    let script = document.createElement('script');
                    script.id = 'xlsx-style-script-dynamic';
                    script.src = "{{ asset('admins/js/export_xlsx.bundle.js') }}";
                    script.onload = function() {
                        callback();
                    };
                    document.head.appendChild(script);
                }

                loadXlsxStyleScript(function() {
                    try {
                        let headers = [
                            "Branch", "Date", "Transaction", "Reference", 
                            "Description", "Debit", "Credit", "Balance", "SortCut"
                        ];

                        let excelData = [];
                        let merges = []; 
                        let summaryRows = []; 

                        $('#tbl-TB tbody tr').each(function(rowIndex) {
                            let $row = $(this);
                            let $tds = $row.find('td');

                            let isSummary = $tds.text().includes('000 - Beginning Balance') || 
                                            $tds.text().includes('*** - Ending Balance');

                            let rowData = {};
                            let excelRowIndex = rowIndex + 1; // +1 ព្រោះ Row 0 ជា Header

                            if (isSummary) {
                                // ទាញ Data តាម Structure ថ្មី៖
                                // td[0] = Branch
                                // td[2] = Title (000 / ***)
                                // td[5] = Balance (ព្រោះ td[2] មាន colspan=3 នាំឱ្យ Balance ធ្លាក់មក td[5])
                                let branchName   = $tds.eq(0).text().trim(); 
                                let summaryTitle = $tds.eq(2).text().trim(); 
                                let rawBalance   = $tds.eq(5).text().trim().replace('$', '').replace(/,/g, ''); 
                                let balanceVal   = parseFloat(rawBalance) || 0;

                                // Merge តែ Columns 2..4 (Transaction, Reference, Description) សម្រាប់ Title
                                merges.push({ s: { r: excelRowIndex, c: 2 }, e: { r: excelRowIndex, c: 4 } });

                                summaryRows.push(excelRowIndex);

                                // រៀបចំ Structural Data សម្រាប់ Excel Row (Balance ស្ថិតនៅ Col 7 ត្រឹមត្រូវ)
                                rowData["Branch"]      = branchName;
                                rowData["Date"]        = "";
                                rowData["Transaction"] = summaryTitle; // លោតទៅ Col 2 (Merged Col 2..4)
                                rowData["Reference"]   = "";
                                rowData["Description"] = "";
                                rowData["Debit"]       = "";
                                rowData["Credit"]      = "";
                                rowData["Balance"]     = balanceVal;   // ចំ Col 7 (Balance Column)
                                rowData["SortCut"]     = "";
                            } else {
                                // Normal Rows
                                if ($tds.length >= 8) {
                                    let parseNum = (val) => parseFloat(val.replace('$', '').replace(/,/g, '').trim()) || 0;

                                    rowData["Branch"]      = $tds.eq(0).text().trim();
                                    rowData["Date"]        = $tds.eq(1).text().trim();
                                    rowData["Transaction"] = $tds.eq(2).text().trim();
                                    rowData["Reference"]   = $tds.eq(3).text().trim();
                                    rowData["Description"] = $tds.eq(4).text().trim();
                                    rowData["Debit"]       = parseNum($tds.eq(5).text());
                                    rowData["Credit"]      = parseNum($tds.eq(6).text());
                                    rowData["Balance"]     = parseNum($tds.eq(7).text());
                                    rowData["SortCut"]     = $tds.eq(8).text().trim();
                                }
                            }

                            if (Object.keys(rowData).length > 0) {
                                excelData.push(rowData);
                            }
                        });

                        var ws = XLSX.utils.json_to_sheet(excelData, { header: headers });
                        ws['!merges'] = merges;

                        // --- APPLY STYLES ---
                        const range = XLSX.utils.decode_range(ws['!ref']);

                        for (let R = range.s.r; R <= range.e.r; ++R) {
                            let isSummaryRow = summaryRows.includes(R);

                            for (let C = range.s.c; C <= range.e.c; ++C) {
                                const cell_address = { c: C, r: R };
                                const cell_ref = XLSX.utils.encode_cell(cell_address);

                                if (!ws[cell_ref]) continue;

                                if (R === 0) {
                                    // Header Style
                                    ws[cell_ref].s = {
                                        font: { bold: true, color: { rgb: "FFFFFF" } },
                                        fill: { fgColor: { rgb: "343A40" } },
                                        alignment: { horizontal: "left", vertical: "left" }
                                    };
                                } else if (isSummaryRow) {
                                    // Summary Row Style
                                    let cellStyle = {
                                        font: { bold: true, color: { rgb: "000000" } },
                                        fill: { fgColor: { rgb: "E9ECEF" } }, // Background ពណ៌ប្រផេះស្រាល
                                        alignment: { vertical: "left" }
                                    };

                                    if (C === 0) {
                                        cellStyle.alignment.horizontal = "left";   // Branch
                                    } else if (C >= 2 && C <= 4) {
                                        cellStyle.alignment.horizontal = "left"; // Merged Title
                                    } else if (C === 7) {
                                        cellStyle.alignment.horizontal = "right";  // Balance Column (Col 7)
                                        if (typeof ws[cell_ref].v === 'number') {
                                            ws[cell_ref].z = '#,##0.00';
                                        }
                                    }
                                    ws[cell_ref].s = cellStyle;
                                } else {
                                    // Regular Rows Format Number
                                    if ([5, 6, 7].includes(C) && typeof ws[cell_ref].v === 'number') {
                                        ws[cell_ref].z = '#,##0.00';
                                    }
                                }
                            }
                        }

                        ws['!cols'] = [
                            { wpx: 120 }, { wpx: 110 }, { wpx: 160 }, 
                            { wpx: 180 }, { wpx: 250 }, { wpx: 110 }, 
                            { wpx: 110 }, { wpx: 120 }, { wpx: 80 }
                        ];

                        var wb = XLSX.utils.book_new();
                        XLSX.utils.book_append_sheet(wb, ws, "Journal_Report");

                        var d = new Date();
                        var dateString = d.getFullYear() + "-" + ("0" + (d.getMonth() + 1)).slice(-2) + "-" + ("0" + d.getDate()).slice(-2);
                        XLSX.writeFile(wb, "Journal_Report_" + dateString + ".xlsx");

                    } catch (err) {
                        console.error("Export error:", err);
                    } finally {
                        $btnText.show();
                        $btnLoading.hide();
                    }
                });
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
                        d.from_date = $('input[name="from_date"]').val();
                        d.to_date = $('input[name="to_date"]').val();
                    },
                    dataSrc: function (json) {
                        return json.data;
                    }
                },
                columns: [
                    { 
                        data: 'Branch', 
                        name: 'jn.Branch',
                        render: function (data) { return data ? data : ''; } // មិនបង្ហាញ null លើជួរ Summary
                    },
                    { 
                        data: 'TransactionDate', 
                        name: 'jn.TransactionDate',
                        render: function (data) { return data ? data : ''; }
                    },
                    { 
                        data: 'Transaction',
                        render: function (data) { return data ? data : ''; }
                    },
                    { 
                        data: 'Reference', 
                        name: 'jn.Reference',
                        render: function (data) { return data ? data : ''; }
                    },
                    { 
                        data: 'Description', 
                        name: 'jn.Description',
                        render: function (data) { return data ? data : ''; }
                    },
                    { 
                        data: 'Debit',
                        className: 'text-end', // ឬ 'text-right' អាស្រ័យលើ Bootstrap Version (BS5 = text-end)
                        render: function (data, type, row) {
                            // ប្រសិនបើជាជួរ Summary មិនបាច់បង្ហាញ 0.00 ទេ
                            if (row.is_summary_row || !data) return '';
                            return $.fn.dataTable.render.number(',', '.', 2, '').display(data);
                        }
                    },
                    { 
                        data: 'Credit',
                        className: 'text-end',
                        render: function (data, type, row) {
                            if (row.is_summary_row || !data) return '';
                            return $.fn.dataTable.render.number(',', '.', 2, '').display(data);
                        }
                    },
                    { 
                        data: 'balance', 
                        className: 'text-end',
                        render: function (data, type, row) {
                            if (data === null || data === undefined) return '';
                            return $.fn.dataTable.render.number(',', '.', 2, '').display(data);
                        }
                    },
                    { 
                        data: 'SortCut',
                        render: function (data) { return data ? data : ''; }
                    },
                ],
                createdRow: function(row, data, dataIndex) {
                    if (data.is_summary_row || data.Reference === '000 - Beginning Balance' || data.Reference === '*** - Ending Balance') {
                        
                        // 1. Set background-color and text color directly
                        $(row).css({
                            'background-color': '#e9ecef', // Light gray (or #fff3cd for soft yellow)
                            'color': '#000000',            // Black text
                            'font-weight': 'bold'
                        });

                        let titleText = data.Reference;
                        let branch = data.Branch;
                        let balanceValue = (data.balance !== null && data.balance !== undefined) 
                            ? $.fn.dataTable.render.number(',', '.', 2, '').display(data.balance) 
                            : '';

                        // 2. Re-render merged HTML (apply inline style to cells if needed)
                        let mergedHtml = `
                            <td>${branch}</td>
                            <td></td>
                            <td colspan="3" class="fw-bold">${titleText}</td>
                            <td></td>
                            <td></td>
                            <td class="text-end text-right fw-bold">${balanceValue}</td>
                            <td></td>
                        `;

                        $(row).html(mergedHtml);
                    }
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