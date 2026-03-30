@extends('layouts.admin')
@section('content')
    <div class="row">
        <div class="col-xl-12">
            <div id="panel-1" class="panel">
                <div class="panel-hdr">
                    <h2>
                        User List
                    </h2>
                </div>
                <div class="panel-container show">
                    <div class="panel-content">
                        <!-- datatable start -->
                        <table id="tbl_suer" class="table table-bordered table-hover table-striped w-100">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>LogInName</th>
                                    <th>DisplayName</th>
                                    <th>Role</th>
                                    <th>Branch</th>
                                    <th>AccessBranch</th>
                                    <th>RestrictBranch</th>
                                    <th>Officer</th>
                                    <th>Active</th>
                                </tr>
                            </thead>
                            <tbody>
                                
                            </tbody>
                        </table>
                        <!-- datatable end -->
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
@section('script')
    <script>
        $(function(){
            // Initialize only once
            dataTables();
        });

        function dataTables() {
            $('#loading-overlay').show();
            // Check if DataTable instance exists, then destroy it
            if ($.fn.DataTable.isDataTable('#tbl_suer')) {
                $('#tbl_suer').DataTable().clear().destroy();
            }
            $('#tbl_suer').DataTable({
                pageLength: 10,
                destroy: true,
                processing: true,
                serverSide: true,
                order: [[0, 'desc']],
                lengthMenu: [ [10, 25, 50, 100], [10, 25, 50, 100] ],
                ajax: {
                    url: '{{ URL("admin/setting/user") }}',
                    type: 'GET',
                    data: function (d) {
                        d.branch_id = $('select[name="branch_id"]').val();
                    },
                    dataSrc: function (json) {
                        window.mktSub = json.subtotal;
                        return json.data;
                    }
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
                        data: 'LogInName', 
                        name: 'LogInName',
                        className: 'stuck-scroll-3',
                        orderable: true,
                        searchable: true,
                    },
                    {
                        data: 'DisplayName', 
                        name: 'DisplayName',
                        className: 'stuck-scroll-3',
                        orderable: true,
                        searchable: true,
                    },
                    {
                        data: 'Role', 
                        name: 'Role',
                        orderable: true,
                        searchable: true,
                    },
                    {
                        data: 'Branch', 
                        name: 'Branch',
                        orderable: true,
                        searchable: true,
                    },
                    {
                        data: 'AccessBranch', 
                        name: 'AccessBranch',
                        orderable: true,
                        searchable: true,
                    },
                    {
                        data: 'RestrictBranch', 
                        name: 'RestrictBranch',
                        orderable: true,
                        searchable: true,
                    },
                    {
                        data: 'Officer', 
                        name: 'Officer',
                        orderable: true,
                        searchable: true,
                    },
                    {
                        data: 'Active', 
                        name: 'Active',
                        orderable: true,
                        searchable: true,
                    },
                ],
                initComplete: function() {
                    $('#loading-overlay').hide();
                },
            });
            $('#tbl_suer').on('processing.dt', function (e, settings, processing) {
                if (processing) {
                    $('#loading-overlay').show();
                } else {
                    $('#loading-overlay').hide();
                }
            });
        }
    </script>
@endsection