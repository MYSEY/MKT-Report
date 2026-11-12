@extends('layouts.admin')
@section('content')
    {!! Toastr::message() !!}
    <div class="row">
        <div class="col-md-6">
            <h3 class="breadcrumb page-breadcrumb">MKT Table Setup</h3>
        </div>
        @if(Auth::user()->can('MKT Table Setup Create'))
            <div class="col-md-6">
                <div class="text-lg-right">
                   <button type="button" class="btn btn-sm btn-primary" id="btn_add">
                        <i class="fal fa-plus"></i> Add Table
                    </button>
                </div>
            </div>
        @endif
    </div>

    <div id="panel-1" class="panel">
        <div class="panel-container show">
            <div class="panel-content">
                <div class="table-responsive">
                    <table id="setup-tbl" class="table table-bordered table-hover table-striped w-100">
                        <thead>
                            <tr>
                                <th style="width:50px">#</th>
                                <th>Table Name</th>
                                <th>Display Name</th>
                                <th>Date Column</th>
                                <th style="width:80px">Order</th>
                                <th style="width:90px">Status</th>
                                <th style="width:140px">Action</th>
                            </tr>
                        </thead>
                        <tbody></tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    {{-- Modal Create / Edit --}}
    <div class="modal fade" id="setup-modal" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="modal-title">Add Table</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <input type="hidden" id="f_id">

                    <div class="form-group">
                        <label>Table Name (Postgres) <span class="text-danger">*</span></label>
                        <input type="text" id="f_table_name" class="form-control" placeholder="e.g. MKT_JOURNAL" maxlength="100" autocomplete="off">
                        <small class="text-muted">អក្សរ A-Z, លេខ និង _ ប៉ុណ្ណោះ</small><br>
                        <small class="text-danger err" id="err_table_name"></small>
                    </div>

                    <div class="form-group">
                        <label>Display Name <span class="text-danger">*</span></label>
                        <input type="text" id="f_display_name" class="form-control" maxlength="150">
                        <small class="text-danger err" id="err_display_name"></small>
                    </div>

                    <div class="form-group">
                        <label>Default Date Column</label>
                        <input type="text" id="f_date_column" class="form-control" placeholder="e.g. CreatedOn" maxlength="100">
                        <small class="text-danger err" id="err_date_column"></small>
                    </div>

                    <div class="form-row">
                        <div class="form-group col-md-6">
                            <label>Sort Order</label>
                            <input type="number" id="f_sort_order" class="form-control" min="0" value="0">
                            <small class="text-danger err" id="err_sort_order"></small>
                        </div>
                        <div class="form-group col-md-6 d-flex align-items-end">
                            <div class="custom-control custom-checkbox mb-2">
                                <input type="checkbox" class="custom-control-input" id="f_is_active" checked>
                                <label class="custom-control-label" for="f_is_active">Active</label>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-primary" id="btn_save">
                        <span class="save-text">Save</span>
                        <span class="save-loading" style="display:none"><i class="fa fa-spinner fa-spin"></i></span>
                    </button>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('script')
    <script>
        var baseUrl    = '{{ URL("admin/configuration/mkt-table-setup") }}';
        var csrf       = '{{ csrf_token() }}';
        var canEdit    = {{ Auth::user()->can('MKT Table Setup Edit') ? 'true' : 'false' }};
        var canDelete  = {{ Auth::user()->can('MKT Table Setup Delete') ? 'true' : 'false' }};
        var setupTable = null;

        function ajaxMsg(xhr) {
            if (xhr.responseJSON && xhr.responseJSON.errors) {
                return Object.values(xhr.responseJSON.errors).map(function (a) { return a[0]; }).join('\n');
            }
            return (xhr.responseJSON && xhr.responseJSON.message) ? xhr.responseJSON.message : 'Error (' + xhr.status + ')';
        }

        function initTable() {
            setupTable = $('#setup-tbl').DataTable({
                ajax: { url: baseUrl + '/data', dataSrc: 'data' },
                pageLength: 25,
                order: [[4, 'asc']],
                columns: [
                    { data: null, orderable: false, render: function (d, t, r, m) { return m.row + 1; } },
                    { data: 'table_name' },
                    { data: 'display_name' },
                    { data: 'date_column', defaultContent: '' },
                    { data: 'sort_order' },
                    {
                        data: 'is_active',
                        render: function (v) {
                            return v ? '<span class="badge badge-success">Active</span>'
                                     : '<span class="badge badge-secondary">Inactive</span>';
                        }
                    },
                    {
                        data: null, orderable: false,
                        render: function (d, t, r) {
                            var h = '';
                            if (canEdit) {
                                h += '<button class="btn btn-xs btn-outline-primary btn-edit mr-1" data-id="' + r.id + '"><i class="fal fa-edit"></i></button>';
                                h += '<button class="btn btn-xs btn-outline-warning btn-toggle mr-1" data-id="' + r.id + '" title="Toggle"><i class="fal fa-power-off"></i></button>';
                            }
                            if (canDelete) {
                                h += '<button class="btn btn-xs btn-outline-danger btn-delete" data-id="' + r.id + '"><i class="fal fa-trash"></i></button>';
                            }
                            return h;
                        }
                    }
                ]
            });
        }

        function resetForm() {
            $('#f_id').val('');
            $('#f_table_name').val('');
            $('#f_display_name').val('');
            $('#f_date_column').val('CreatedOn');
            $('#f_sort_order').val(0);
            $('#f_is_active').prop('checked', true);
            $('.err').text('');
        }

        function openCreate() {
            resetForm();
            $('#modal-title').text('Add Table');
            $('#setup-modal').modal('show');
        }

        function openEdit(id) {
            var row = setupTable.rows().data().toArray().find(function (r) { return r.id == id; });
            if (!row) return;

            resetForm();
            $('#modal-title').text('Edit Table');
            $('#f_id').val(row.id);
            $('#f_table_name').val(row.table_name);
            $('#f_display_name').val(row.display_name);
            $('#f_date_column').val(row.date_column || '');
            $('#f_sort_order').val(row.sort_order);
            $('#f_is_active').prop('checked', !!row.is_active);
            $('#setup-modal').modal('show');
        }

        function save() {
            var id = $('#f_id').val();
            var payload = {
                _token:       csrf,
                table_name:   $('#f_table_name').val(),
                display_name: $('#f_display_name').val(),
                date_column:  $('#f_date_column').val(),
                sort_order:   $('#f_sort_order').val(),
                is_active:    $('#f_is_active').is(':checked') ? 1 : 0
            };
            if (id) payload._method = 'PUT';

            $('.err').text('');
            $('.save-text').hide();
            $('.save-loading').show();

            $.ajax({
                url: id ? baseUrl + '/' + id : baseUrl,
                type: 'POST',
                data: payload
            }).done(function (res) {
                $('#setup-modal').modal('hide');
                setupTable.ajax.reload(null, false);
                Swal.fire({ icon: 'success', title: res.message, timer: 1500, showConfirmButton: false });
            }).fail(function (xhr) {
                if (xhr.status === 422 && xhr.responseJSON && xhr.responseJSON.errors) {
                    $.each(xhr.responseJSON.errors, function (k, v) { $('#err_' + k).text(v[0]); });
                } else {
                    Swal.fire(' ', ajaxMsg(xhr), 'error');
                }
            }).always(function () {
                $('.save-text').show();
                $('.save-loading').hide();
            });
        }

        function removeRow(id) {
            Swal.fire({
                title: 'Delete this table setup?',
                text: 'ទិន្នន័យក្នុង Postgres មិនត្រូវបានលុបទេ គ្រាន់តែដកចេញពីបញ្ជី report។',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonText: 'Delete'
            }).then(function (r) {
                var confirmed = (r && (r.isConfirmed === true || r.value === true || r === true));
                if (!confirmed) return;

                $.ajax({
                    url: baseUrl + '/' + id,
                    type: 'POST',
                    data: { _token: csrf, _method: 'DELETE' }
                }).done(function (res) {
                    setupTable.ajax.reload(null, false);
                    Swal.fire({ icon: 'success', title: res.message, timer: 1500, showConfirmButton: false });
                }).fail(function (xhr) {
                    Swal.fire(' ', ajaxMsg(xhr), 'error');
                });
            });
        }

        $(function () {
            initTable();

            $('#btn_add').on('click', openCreate);
            $('#btn_save').on('click', save);

            // វាយឈ្មោះ table រួច: បម្លែងជាអក្សរធំ និងបំពេញ Display Name បើនៅទទេ
            $('#f_table_name').on('blur', function () {
                var v = $(this).val().trim().toUpperCase();
                $(this).val(v);
                if (!$('#f_display_name').val()) $('#f_display_name').val(v);
            });

            $(document).on('click', '.btn-edit',   function () { openEdit($(this).data('id')); });
            $(document).on('click', '.btn-delete', function () { removeRow($(this).data('id')); });
            $(document).on('click', '.btn-toggle', function () {
                $.post(baseUrl + '/' + $(this).data('id') + '/toggle', { _token: csrf }, function () {
                    setupTable.ajax.reload(null, false);
                }).fail(function (xhr) { Swal.fire(' ', ajaxMsg(xhr), 'error'); });
            });
        });
    </script>
@endsection