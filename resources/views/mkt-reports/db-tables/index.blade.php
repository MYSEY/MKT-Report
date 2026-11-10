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

    {{-- Filters --}}
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
                            placeholder="To Date"
                            readonly
                            style="background-color: #fff;">
                    </div>
                </div>

                <div class="col-sm-3 col-md-3">
                    <div class="form-group">
                        <select data-filter="table_name" class="select2 form-control btn-filter" id="table_name" name="table_name">
                            <option value="" selected>-- Select Table --</option>
                            <option value="MKT_PD_DATE">PD DATE</option>
                            <option value="MKT_LOAN_CONTRACT">LOAN CONTRACT</option>
                            <option value="MKT_REP_SCHEDULE">REP SCHEDULE</option>
                            <option value="MKT_REP_SCHEDULE_AS">REP SCHEDULE AS</option>
                            <option value="MKT_GL_BALANCE_BACKUP">GL BALANCE BACKUP</option>
                            <option value="MKT_JOURNAL">MKT_JOURNAL</option>
                            <option value="MKT_COLLATERAL">MKT_COLLATERAL</option>
                            <option value="MKT_BORROWING_REP_SCHEDULE_AS">BORROWING REP SCHEDULE AS</option>
                        </select>
                    </div>
                </div>

                {{-- NEW: choose which column the From/To date applies to --}}
                <div class="col-sm-3 col-md-3">
                    <div class="form-group">
                        <select id="date_col" class="form-control">
                            <option value="">-- No date filter --</option>
                        </select>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Column picker (filled by JS after selecting a table) --}}
    <div class="card mb-2" id="column-card" style="display:none">
        <div class="card-header d-flex justify-content-between align-items-center">
            <div>
                <strong>Select Columns</strong>
                <span class="badge badge-primary ml-2" id="col-count">0</span>
            </div>
            <div class="custom-control custom-checkbox">
                <input type="checkbox" class="custom-control-input" id="check_all" checked>
                <label class="custom-control-label" for="check_all">Select All</label>
            </div>
        </div>
        <div class="card-body">
            <input type="text" id="col_search" class="form-control form-control-sm mb-3" placeholder="Search column...">
            <div class="row" id="column-list" style="max-height:260px; overflow-y:auto;"></div>

            {{-- NEW: value filters --}}
            <div class="mt-3">
                <strong>Value filters</strong>
                <button type="button" class="btn btn-xs btn-outline-primary ml-2" id="btn_add_filter">+ Add filter</button>
                <div id="filter-list" class="mt-2"></div>
            </div>
        </div>
        <div class="card-footer text-right">
            <button type="button" class="btn btn-sm btn-primary" id="btn_show">
                <i class="fal fa-table"></i> Show Data
            </button>
            @if(Auth::user()->can('MKT DB Table Export'))
                <button type="button" class="btn btn-sm btn-info waves-effect waves-themed btn_excel mr-1">
                    <span class="btn-text-excel"><i class="fal fa-arrow-circle-down"></i></span>
                    <span id="btn-text-loading-excel-1" style="display: none"><i class="fa fa-spinner fa-spin"></i></span>
                    Download To Excel
                </button>
            @endif
        </div>
    </div>

    {{-- Data table (header and body built by JS) --}}
    <div id="panel-1" class="panel">
        <div class="panel-hdr">
            <h2>
                Table name: <span id="panel-title" class="ml-1 text-primary"></span>
            </h2>
        </div>
        <div class="panel-container show">
            <div class="panel-content">
                <div class="table-responsive">
                    <table id="db-tbl" class="table table-bordered table-hover table-striped"></table>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('script')
    <script>
        var tableUrl   = '{{ URL("admin/mkt-report/db-tables") }}';
        var columnsUrl = tableUrl + '/columns';
        var exportUrl  = tableUrl + '/export';

        var defaultFrom = '{{ \Carbon\Carbon::now()->startOfMonth()->format("d-m-Y") }}';
        var defaultTo   = '{{ \Carbon\Carbon::now()->endOfMonth()->format("d-m-Y") }}';

        function esc(s) {
            return $('<div>').text(s).html();
        }

        function selectedCols() {
            return $('#column-list .col-check:checked').map(function () {
                return this.value;
            }).get();
        }

        // NEW: collect value filters
        function colFilters() {
            var list = [];
            $('#filter-list .filter-row').each(function () {
                var col = $(this).find('.f-col').val();
                var op  = $(this).find('.f-op').val();
                var val = $(this).find('.f-val').val();
                if (col && val !== '') list.push({ col: col, op: op, val: val });
            });
            return list;
        }

        // NEW: add one filter row
        function addFilterRow() {
            var cols = $('#column-list .col-check').map(function () { return this.value; }).get();
            if (!cols.length) return;

            var opts = cols.map(function (c) {
                return '<option value="' + esc(c) + '">' + esc(c) + '</option>';
            }).join('');

            $('#filter-list').append(
                '<div class="form-row mb-2 filter-row">' +
                    '<div class="col-md-3"><select class="form-control form-control-sm f-col">' + opts + '</select></div>' +
                    '<div class="col-md-2"><select class="form-control form-control-sm f-op">' +
                        '<option value="contains">contains</option>' +
                        '<option value="=">=</option>' +
                        '<option value="!=">!=</option>' +
                        '<option value="&gt;">&gt;</option>' +
                        '<option value="&lt;">&lt;</option>' +
                    '</select></div>' +
                    '<div class="col-md-4"><input type="text" class="form-control form-control-sm f-val" placeholder="Value"></div>' +
                    '<div class="col-md-1"><button type="button" class="btn btn-sm btn-outline-danger btn-del-filter">&times;</button></div>' +
                '</div>'
            );
        }

        function filters() {
            return {
                from_date:   $('#from_date').val() || defaultFrom,
                to_date:     $('#to_date').val()   || defaultTo,
                date_col:    $('#date_col').val(),
                table_name:  $('#table_name').val(),
                select_cols: selectedCols(),
                col_filters: colFilters()
            };
        }

        function updateCount() {
            var total   = $('#column-list .col-check').length;
            var checked = $('#column-list .col-check:checked').length;
            $('#col-count').text(checked + ' / ' + total);
            $('#check_all').prop('checked', total > 0 && total === checked);
        }

        function destroyTable() {
            if ($.fn.DataTable.isDataTable('#db-tbl')) {
                $('#db-tbl').DataTable().destroy();
            }
            $('#db-tbl').empty();
        }

        // Step 1: table selected -> load column names as checkboxes
        function loadColumns() {
            var tableName = $('#table_name').val();
            $('#panel-title').text(tableName);
            destroyTable();
            $('#col_search').val('');
            $('#filter-list').empty();
            $('#date_col').html('<option value="">-- No date filter --</option>');

            if (!tableName) {
                $('#column-card').hide();
                return;
            }

            $.get(columnsUrl, { table_name: tableName }, function (res) {
                var html = '';
                var dateOpts = '<option value="">-- No date filter --</option>';

                res.columns.forEach(function (c, i) {
                    html += '<div class="col-sm-6 col-md-3 col-item" data-name="' + esc(c.toLowerCase()) + '">' +
                                '<div class="custom-control custom-checkbox mb-2">' +
                                    '<input type="checkbox" class="custom-control-input col-check" id="col_' + i + '" value="' + esc(c) + '" checked>' +
                                    '<label class="custom-control-label" for="col_' + i + '">' + esc(c) + '</label>' +
                                '</div>' +
                            '</div>';

                    // default date filter column = CreatedOn (if the table has it)
                    var sel = (c.toLowerCase() === 'createdon') ? ' selected' : '';
                    dateOpts += '<option value="' + esc(c) + '"' + sel + '>' + esc(c) + '</option>';
                });

                $('#column-list').html(html);
                $('#date_col').html(dateOpts);
                $('#column-card').show();
                updateCount();
            }).fail(function () {
                Swal.fire(" ", "Failed to load columns", "error");
            });
        }

        // Step 2: click Show Data -> build header + load body for checked columns
        function showData() {
            var cols = selectedCols();

            if (!cols.length) {
                Swal.fire(" ", "Please select at least one column", "error");
                return;
            }

            // if date inputs are empty, put the defaults back
            if (!$('#from_date').val()) $('#from_date').val(defaultFrom);
            if (!$('#to_date').val())   $('#to_date').val(defaultTo);

            destroyTable();

            var head   = '<thead><tr>';
            var dtCols = [];
            cols.forEach(function (c) {
                head += '<th>' + esc(c) + '</th>';
                dtCols.push({ data: c, name: c, defaultContent: '' });
            });
            head += '</tr></thead><tbody></tbody>';
            $('#db-tbl').html(head);

            var dynamicHeight = Math.max($(window).height() - 350, 200);

            $('#db-tbl').DataTable({
                pageLength: 20,
                processing: true,
                serverSide: true,
                autoWidth: false,
                scrollX: true,
                scrollY: dynamicHeight + 'px',
                scrollCollapse: true,
                order: [[0, 'asc']],
                lengthMenu: [[20, 25, 50, 100], [20, 25, 50, 100]],
                ajax: {
                    url: tableUrl,
                    type: 'POST',
                    data: function (d) {
                        delete d.columns;          // keep the request small
                        $.extend(d, filters());
                        d._token = '{{ csrf_token() }}';
                    },
                    error: function (xhr) {
                        console.error(xhr.status, xhr.responseText.substring(0, 1000));
                        var msg = (xhr.responseJSON && xhr.responseJSON.error) ? xhr.responseJSON.error : xhr.status;
                        Swal.fire(" ", "Load failed: " + msg, "error");
                    }
                },
                columns: dtCols
            });

            $('#db-tbl').off('processing.dt').on('processing.dt', function (e, settings, processing) {
                $('#loading-overlay').toggle(processing);
            });
        }

        $(function () {
            $('.datepicker_month').datepicker({
                format: "dd-mm-yyyy",
                autoclose: true,
                todayHighlight: true
            });

            $('#table_name').on('change', loadColumns);
            $('#btn_show').on('click', showData);
            $('#btn_add_filter').on('click', addFilterRow);
            $(document).on('click', '.btn-del-filter', function () {
                $(this).closest('.filter-row').remove();
            });

            // Select All (only visible columns, so it works with the search box)
            $('#check_all').on('change', function () {
                $('#column-list .col-item:visible .col-check').prop('checked', this.checked);
                updateCount();
            });

            $(document).on('change', '.col-check', updateCount);

            // reload when date or date column changes (only if data is already shown)
            $(document).on('change changeDate dp.change', '#from_date, #to_date, #date_col', function () {
                if ($.fn.DataTable.isDataTable('#db-tbl')) {
                    $('#db-tbl').DataTable().ajax.reload();
                }
            });

            // Search column name
            $('#col_search').on('keyup', function () {
                var q = $(this).val().toLowerCase();
                $('#column-list .col-item').each(function () {
                    $(this).toggle($(this).data('name').indexOf(q) !== -1);
                });
            });

            // Export to Excel
            $(document).on('click', '.btn_excel', function (e) {
                e.preventDefault();

                var f = filters();
                if (!f.table_name) {
                    Swal.fire(" ", "Please select a table first", "error");
                    return;
                }
                if (!f.select_cols.length) {
                    Swal.fire(" ", "Please select at least one column", "error");
                    return;
                }

                $('.btn-text-excel').hide();
                $('#btn-text-loading-excel-1').show();

                $.post(exportUrl, $.extend({}, f, { _token: '{{ csrf_token() }}' }), function (res) {
                    if (res.status === 'too_large') {
                        Swal.fire({ icon: 'error', title: 'Download limit exceeded', text: res.message });
                        return;
                    }

                    if (!res.rows.length) {
                        Swal.fire({ icon: 'info', title: 'No data', text: 'No data to export' });
                        return;
                    }

                    var ws = XLSX.utils.json_to_sheet(res.rows, { header: f.select_cols });

                    ws['!cols'] = f.select_cols.map(function (k) {
                        var max = k.length;
                        res.rows.slice(0, 100).forEach(function (r) {
                            var len = (r[k] === null || r[k] === undefined ? '' : String(r[k])).length;
                            if (len > max) max = len;
                        });
                        return { wch: Math.min(max + 2, 50) };
                    });

                    var wb = XLSX.utils.book_new();
                    XLSX.utils.book_append_sheet(wb, ws, res.table.substring(0, 31));

                    var d  = new Date();
                    var ds = d.getFullYear() + '-' + ('0' + (d.getMonth() + 1)).slice(-2) + '-' + ('0' + d.getDate()).slice(-2);
                    XLSX.writeFile(wb, res.table + '_' + ds + '.xlsx');
                }).fail(function (xhr) {
                    var msg = (xhr.responseJSON && xhr.responseJSON.message) ? xhr.responseJSON.message : 'Export failed (' + xhr.status + ')';
                    Swal.fire({ icon: 'error', title: 'Export failed', text: msg });
                }).always(function () {
                    $('.btn-text-excel').show();
                    $('#btn-text-loading-excel-1').hide();
                });
            });
        });
    </script>
@endsection