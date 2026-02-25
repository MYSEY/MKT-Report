@extends('layouts.admin')
@section('content')
    <div class="row">
        <div class="col-md-6">
            <h3 class="breadcrumb page-breadcrumb">Category</h3>
        </div>
        <div class="col-md-6">
            <div class="text-lg-right">
                <button class="btn btn-success btn-sm mr-1" data-toggle="modal" data-target="#category-create" type="button"><span><i class="fal fa-plus mr-1"></i> Add New</span></button>
            </div>
        </div>
    </div>
    
    <div id="panel-1" class="panel">
        <div class="panel-hdr">
            <h2>
                Category
            </h2>
        </div>
        <div class="panel-container show">
            <div class="panel-content">
                <div class="table-responsive">
                    <table id="btn_category" class="table table-bordered table-hover table-striped">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Name</th>
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
    <!-- Modal Create New Category -->
    <div class="modal custom-modal fade" id="category-create" role="dialog" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Add New Category</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true"><i class="fal fa-times"></i></span>
                    </button>
                </div>
                <div class="modal-body">
                    <form action="{{url('admin/setting/category')}}" method="POST" enctype="multipart/form-data" class="needs-validation" novalidate>
                        @csrf
                        <div class="form-group">
                            <label class="form-label">Name <span class="text-danger">*</span></label>
                            <input type="text" class="form-control @error('name') is-invalid @enderror" name="name" required>
                        </div>
                        <div class="float-lg-right">
                            <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                            <button type="submit" class="btn btn-primary">Save changes</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="editCategory" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Edit Category</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true"><i class="fal fa-times"></i></span>
                    </button>
                </div>
                <div class="modal-body">
                    <form action="{{url('admin/setting/category/update')}}" method="POST" enctype="multipart/form-data" class="needs-validation" novalidate>
                        @csrf
                        @method('PUT')
                        <input type="hidden" name="id" class="e_id" id="e_id" value="">
                        <div class="form-group">
                            <label class="form-label">Name <span class="text-danger">*</span></label>
                            <input type="text" id="e_name" class="form-control @error('name') is-invalid @enderror" name="name">
                        </div>
                        <div class="float-lg-right">
                            <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                            <button type="submit" class="btn btn-primary">Save changes</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
    <div class="modal custom-modal fade" id="delete_category" role="dialog">
        <div class="modal-dialog modal-sm modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-body">
                    <div class="form-header">
                        <h5 class="modal-title">Delete</h5>
                        <p>Are you sure want to delete?</p>
                    </div>
                    <div class="modal-btn delete-action">
                        <form action="{{url('admin/setting/category/delete')}}" method="POST" enctype="multipart/form-data">
                            @csrf
                            @method("DELETE")
                            <input type="hidden" name="id" class="e_id" id="e_id" value="">
                            <div class="float-lg-right">
                                <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                                <button type="submit" class="btn btn-danger waves-effect waves-themed">Delete</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
@section('script')
    <script>
        $(function(){
            $('.btn-search').on('click', function() {
                $('#loading-overlay').hide();
                branch_id = $('#branch_id').val();
                $('#btn_category').DataTable().ajax.reload(null, false);
            });
            $(".reset-btn").on("click", function() {
                $(this).prop('disabled', true);
                $(".btn-text-reset").hide();
                $("#btn-text-loading").css('display', 'block');
                window.location.replace("{{ URL('admin/s/category') }}");
            });
            $(document).on('click','.btnDelete',function(){
                $('.e_id').val($(this).data("id"));
            });
            $(document).on('click','.btnEdit',function(){
                let id = $(this).data("id");
                $.ajax({
                    type: "GET",
                    url: "{{url('admin/setting/category')}}/" + id + '/edit',
                    data: {
                        id : id
                    },
                    dataType: "JSON",
                    success: function (response) {
                        if (response.success) {
                            $('#e_id').val(response.success.id);
                            $('#e_name').val(response.success.name);
                            $('#editCategory').modal('show');
                        }
                    }
                });
            });
            dataTables();
        });

        function dataTables() {
            $('#loading-overlay').show();
            $('#btn_category').DataTable({
                pageLength: 10,
                destroy: true,
                processing: true,
                serverSide: true,
                order: [[0, 'desc']],
                lengthMenu: [ [10, 25, 50, 100], [10, 25, 50, 100] ],
                ajax: {
                    url: '{{ URL("admin/setting/category") }}',
                    type: 'GET',
                },
                columns: [
                    { 
                        data: 'id', 
                        name: 'id',
                        className: 'stuck-scroll-3',
                        orderable: true,
                        searchable: true,
                    },
                    { 
                        data: 'name', 
                        name: 'name',
                        className: 'stuck-scroll-3',
                        orderable: true,
                        searchable: true,
                    },
                    {
                        data: '',
                        name: 'action',
                        render: function(data, type, row) {
                            let actionButtons = '';
                            actionButtons += `<a href="javascript:void(0);" class="btn btn-sm btn-outline-danger btn-icon btn-inline-block mr-2 btnDelete" data-toggle="modal" data-target="#delete_category" title="Delete Record" data-id="${row.id}"><i class="fal fa-times"></i></a>`;
                            actionButtons += `<a href="javascript:void(0);" class="btn btn-sm btn-outline-success btn-icon btn-inline-block mr-2 btnEdit" data-id="${row.id}"><i class="fal fa-edit"></i></a>`;
                            return actionButtons;
                        },
                        orderable: false,
                        searchable: false
                    }
                ],
                initComplete: function() {
                    $('#loading-overlay').hide();
                }
            });
            $('#btn_category').on('processing.dt', function (e, settings, processing) {
                if (processing) {
                    $('#loading-overlay').show();
                } else {
                    $('#loading-overlay').hide();
                }
            });
        }
    </script>
@endsection