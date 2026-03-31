@extends('layouts.admin')
@section('content')
    <div class="row">
        <div class="col-md-6">
            <h3 class="breadcrumb page-breadcrumb">Rols</h3>
        </div>
    </div>
    
    <div id="panel-1" class="panel">
        <div class="panel-hdr">
            <h2>
                Rols
            </h2>
        </div>
        <div class="panel-container show">
            <div class="panel-content">
                <div class="table-responsive">
                    <table id="tbl_role" class="table table-bordered table-hover table-striped">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Role</th>
                                <th>Action</th>
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
        var edit = @json(Auth::user()->can('Role Edit'));
        $(function(){
            $('.btn-search').on('click', function() {
                $('#loading-overlay').hide();
                branch_id = $('#branch_id').val();
                $('#tbl_role').DataTable().ajax.reload(null, false);
            });
            $(".reset-btn").on("click", function() {
                $(this).prop('disabled', true);
                $(".btn-text-reset").hide();
                $("#btn-text-loading").css('display', 'block');
                window.location.replace("{{ URL('admin/s/category') }}");
            });
            dataTables();
        });

        function dataTables() {
            $('#loading-overlay').show();
            $('#tbl_role').DataTable({
                pageLength: 10,
                destroy: true,
                processing: true,
                serverSide: true,
                order: [[0, 'desc']],
                lengthMenu: [ [10, 25, 50, 100], [10, 25, 50, 100] ],
                ajax: {
                    url: '{{ URL("admin/setting/role") }}',
                    type: 'GET',
                },
                columns: [
                    { 
                        data: 'ID', 
                        name: 'ID',
                        className: 'stuck-scroll-3',
                        orderable: true,
                        searchable: true,
                    },
                    { 
                        data: 'Description', 
                        name: 'Description',
                        className: 'stuck-scroll-3',
                        orderable: true,
                        searchable: true,
                    },
                    {
                        data: null,
                        name: 'action',
                        render: function(data, type, row) {
                            if (edit) {
                                let url = "{{ url('admin/setting/role') }}/" + row.ID + "/edit";
                                return `<a href="${url}" class="btn btn-sm btn-outline-success btn-icon btn-inline-block mr-2" data-id="${row.ID}"><i class="fal fa-edit"></i></a>`;   
                            }else{
                                return '';
                            }
                        },
                        orderable: false,
                        searchable: false
                    }
                ],
                initComplete: function() {
                    $('#loading-overlay').hide();
                }
            });
            $('#tbl_role').on('processing.dt', function (e, settings, processing) {
                if (processing) {
                    $('#loading-overlay').show();
                } else {
                    $('#loading-overlay').hide();
                }
            });
        }
    </script>
@endsection