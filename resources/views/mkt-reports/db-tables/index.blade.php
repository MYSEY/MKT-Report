@extends('layouts.admin')
@section('content')
<style>
    .w-200 { width: 200px !important; min-width: 200px !important; }
    .text-wrap { white-space: normal !important; word-wrap: break-word; }
</style>
    {!! Toastr::message() !!}

    {{-- Filters --}}
    <div class="card mb-2">
        <div class="card-body">
            <div class="row filter-btn">
                <div class="col-sm-3 col-md-3">
                    <div class="form-group">
                        <input type="text" class="form-control datepicker_month btn-filter"
                            name="from_date" id="from_date"
                            value="{{ \Carbon\Carbon::now()->startOfMonth()->format('d-m-Y') }}"
                            placeholder="From Date" readonly style="background-color: #fff;">
                    </div>
                </div>
                <div class="col-sm-3 col-md-3">
                    <div class="form-group">
                        <input type="text" class="form-control datepicker_month btn-filter"
                            name="to_date" id="to_date"
                            value="{{ \Carbon\Carbon::now()->endOfMonth()->format('d-m-Y') }}"
                            placeholder="To Date" readonly style="background-color: #fff;">
                    </div>
                </div>

                <div class="col-sm-3 col-md-3">
                    <div class="form-group">
                        <select data-filter="table_name" class="select2 form-control btn-filter" id="table_name" name="table_name">
                            <option value="" selected>-- Select Table --</option>
                            @foreach($tables as $t)
                                <option value="{{ $t->table_name }}" data-date-col="{{ $t->date_column }}">{{ $t->display_name }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>

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

    {{-- Column picker --}}
    <div class="card mb-2" id="column-card" style="display:none">
        <div class="card-header d-flex justify-content-between align-items-center">
            <div>
                <strong>Check Columns</strong>
                <span class="badge badge-primary ml-2" id="col-count">0</span>
            </div>
            <div class="custom-control custom-checkbox">
                <input type="checkbox" class="custom-control-input" id="check_all" checked>
                <label class="custom-control-label" for="check_all">Check All</label>
            </div>
        </div>
        <div class="card-body">
            <input type="text" id="col_search" class="form-control form-control-sm mb-3" placeholder="Search column...">

            <div class="row mb-3" hidden>
                <div class="col-md-4">
                    <input type="text" id="col_index" class="form-control form-control-sm"
                           placeholder="Column index e.g. 1,2,5-8">
                </div>
                <div class="col-md-8">
                    <small class="text-muted">ទុកទទេ = ប្រើ checkbox | លេខចាប់ពី 1 តាមលំដាប់ក្នុង table</small>
                </div>
            </div>

            <div class="row" id="column-list" style="max-height:260px; overflow-y:auto;"></div>

            <div class="mt-3">
                <strong>Value filters</strong>
                <button type="button" class="btn btn-xs btn-outline-primary ml-2" id="btn_add_filter">+ Add filter</button>
                <div id="filter-list" class="mt-2"></div>
            </div>
        </div>
        <div class="card-footer text-right">
            <button type="button" class="btn btn-sm btn-primary mr-1" id="btn_show">
                <i class="fal fa-table"></i> Show Data
            </button>
            @if(Auth::user()->can('MKT DB Table Export'))
                <button type="button" class="btn btn-sm btn-success btn_export mr-1" data-format="csv">
                    <i class="fal fa-file-csv"></i> Download CSV
                </button>
                <button type="button" class="btn btn-sm btn-info btn_export" data-format="xlsx">
                    <i class="fal fa-file-excel"></i> Download Excel
                </button>
            @endif
        </div>
    </div>

    {{-- Data table --}}
    <div id="panel-1" class="panel">
        <div class="panel-hdr">
            <h2>Table name: <span id="panel-title" class="ml-1 text-primary"></span></h2>
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
        var tableUrl       = '{{ URL("admin/mkt-report/db-tables") }}';
        var columnsUrl     = tableUrl + '/columns';
        var exportCheckUrl = tableUrl + '/export-check';
        var exportCsvUrl   = tableUrl + '/export';
        var csrf           = '{{ csrf_token() }}';

        var defaultFrom = '{{ \Carbon\Carbon::now()->startOfMonth()->format("d-m-Y") }}';
        var defaultTo   = '{{ \Carbon\Carbon::now()->endOfMonth()->format("d-m-Y") }}';

        function esc(s) {
            return $('<div>').text(s).html();
        }

        // column ដែលត្រូវប្រើ: តាម index (បើវាយ) ឬតាម checkbox
        function selectedCols() {
            var expr = ($('#col_index').val() || '').trim();

            if (expr) {
                var all = $('#column-list .col-check').map(function () { return this.value; }).get();
                var out = [];

                expr.split(',').forEach(function (p) {
                    p = p.trim();
                    var m = p.match(/^(\d+)\s*-\s*(\d+)$/);
                    if (m) {
                        var a = Math.min(+m[1], +m[2]), b = Math.max(+m[1], +m[2]);
                        for (var i = a; i <= Math.min(b, all.length); i++) {
                            if (all[i - 1] && out.indexOf(all[i - 1]) === -1) out.push(all[i - 1]);
                        }
                    } else if (/^\d+$/.test(p) && all[+p - 1] && out.indexOf(all[+p - 1]) === -1) {
                        out.push(all[+p - 1]);
                    }
                });

                if (out.length) return out;
            }

            return $('#column-list .col-check:checked').map(function () { return this.value; }).get();
        }

        // value filters
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

        // Step 1: select table -> load columns
        function loadColumns() {
            var tableName = $('#table_name').val();
            $('#panel-title').text(tableName);
            destroyTable();
            $('#col_search').val('');
            $('#col_index').val('');
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
                                    '<label class="custom-control-label" for="col_' + i + '">' + (i + 1) + '. ' + esc(c) + '</label>' +
                                '</div>' +
                            '</div>';

                    var sel = (c.toLowerCase() === 'createdon') ? ' selected' : '';
                    dateOpts += '<option value="' + esc(c) + '"' + sel + '>' + esc(c) + '</option>';
                });

                $('#column-list').html(html);
                $('#date_col').html(dateOpts);
                $('#column-card').show();
                updateCount();
            }).fail(function () {
                $('#column-list').html('');
                $('#date_col').html('');
                $('#column-card').hide();
                updateCount();
                Swal.fire(" ", "Failed to load columns", "error");
            });
        }

        // Step 2: Show Data
        function showData() {
            var cols = selectedCols();

            if (!cols.length) {
                Swal.fire(" ", "Please select at least one column", "error");
                return;
            }

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
                searching: false,          // បិទ global search (យឺតលើ table ធំ)
                autoWidth: false,
                scrollX: true,
                scrollY: dynamicHeight + 'px',
                scrollCollapse: true,
                order: [],                 // មិន sort ដំបូង (លឿនជាង)
                lengthMenu: [[20, 25, 50, 100], [20, 25, 50, 100]],
                ajax: {
                    url: tableUrl,
                    type: 'POST',
                    data: function (d) {
                        delete d.columns;          // ធ្វើឲ្យ request តូច
                        $.extend(d, filters());
                        d._token = csrf;
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

        // Download: ឆែកចំនួនមុន រួច submit form ឲ្យ browser download
        function download(format) {
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

            $.post(exportCheckUrl, $.extend({}, f, { _token: csrf }), function (res) {

                if (res.status === 'too_large') {
                    Swal.fire({ icon: 'error', title: 'Download limit exceeded', text: res.message });
                    return;
                }
                if (res.status === 'empty') {
                    Swal.fire({ icon: 'info', title: 'No data', text: 'No data to export' });
                    return;
                }
                if (res.status === 'error') {
                    Swal.fire({ icon: 'error', title: 'Export failed', text: res.message });
                    return;
                }

                var form = $('<form method="POST" style="display:none">').attr('action', exportCsvUrl);
                form.append($('<input type="hidden" name="_token">').val(csrf));
                form.append($('<input type="hidden" name="format">').val(format));
                form.append($('<input type="hidden" name="table_name">').val(f.table_name));
                form.append($('<input type="hidden" name="from_date">').val(f.from_date));
                form.append($('<input type="hidden" name="to_date">').val(f.to_date));
                form.append($('<input type="hidden" name="date_col">').val(f.date_col));

                f.select_cols.forEach(function (c) {
                    form.append($('<input type="hidden" name="select_cols[]">').val(c));
                });
                f.col_filters.forEach(function (cf, i) {
                    form.append($('<input type="hidden">').attr('name', 'col_filters[' + i + '][col]').val(cf.col));
                    form.append($('<input type="hidden">').attr('name', 'col_filters[' + i + '][op]').val(cf.op));
                    form.append($('<input type="hidden">').attr('name', 'col_filters[' + i + '][val]').val(cf.val));
                });

                $('body').append(form);
                form.submit();
                form.remove();

                Swal.fire({
                    icon: 'success',
                    title: 'Download started',
                    text: res.total.toLocaleString() + ' rows កំពុងត្រូវបានទាញយក។',
                    timer: 3000,
                    showConfirmButton: false
                });

            }).fail(function (xhr) {
                var msg = (xhr.responseJSON && xhr.responseJSON.message) ? xhr.responseJSON.message : 'Export failed (' + xhr.status + ')';
                Swal.fire({ icon: 'error', title: 'Export failed', text: msg });
            }).always(function () {
                $('.btn-text-excel').show();
                $('#btn-text-loading-excel-1').hide();
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

            $('#check_all').on('change', function () {
                $('#column-list .col-item:visible .col-check').prop('checked', this.checked);
                updateCount();
            });

            $(document).on('change', '.col-check', updateCount);

            // checkbox តាមដាន index ដែលវាយ
            $('#col_index').on('input', function () {
                var expr = $(this).val().trim();
                if (!expr) return;

                var all = $('#column-list .col-check').map(function () { return this.value; }).get();
                var picked = [];

                expr.split(',').forEach(function (p) {
                    p = p.trim();
                    var m = p.match(/^(\d+)\s*-\s*(\d+)$/);
                    if (m) {
                        var a = Math.min(+m[1], +m[2]), b = Math.min(Math.max(+m[1], +m[2]), all.length);
                        for (var i = a; i <= b; i++) { if (all[i - 1]) picked.push(all[i - 1]); }
                    } else if (/^\d+$/.test(p) && all[+p - 1]) {
                        picked.push(all[+p - 1]);
                    }
                });

                if (!picked.length) return;

                $('#column-list .col-check').each(function () {
                    $(this).prop('checked', picked.indexOf(this.value) !== -1);
                });
                updateCount();
            });

            // reload ពេលប្តូរថ្ងៃ ឬ date column (បើបានបង្ហាញ data រួច)
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

            $(document).on('click', '.btn_export', function (e) {
                e.preventDefault();
                download($(this).data('format'));
            });
        });
    </script>
@endsection