@extends('layouts.admin')
@section('content')
    <div class="row">
        <div class="col-md-6">
            <h3 class="breadcrumb page-breadcrumb">Branch Code</h3>
        </div>
        @if(Auth::user()->can('Category Create'))
            <div class="col-md-6">
                <div class="text-lg-right">
                    <button class="btn btn-success btn-sm mr-1" data-toggle="modal" data-target="#branch-code-create" type="button"><span><i class="fal fa-plus mr-1"></i> Add New</span></button>
                </div>
            </div>
        @endif
    </div>
    
    <div id="panel-1" class="panel">
        <div class="panel-hdr">
            <h2>
                Branch Code
            </h2>
        </div>
        <div class="panel-container show">
            <div class="panel-content">
                <div class="table-responsive">
                    <table id="btn_category" class="table table-bordered table-hover table-striped">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Code</th>
                                <th>Abbreviation</th>
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
    <!-- Modal Create New Branch Code -->
    <div class="modal custom-modal fade" id="branch-code-create" role="dialog" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Add New Branch Code</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true"><i class="fal fa-times"></i></span>
                    </button>
                </div>
                <div class="modal-body">
                    <form action="{{url('admin/configuration/branch-code')}}" method="POST" enctype="multipart/form-data" class="needs-validation" novalidate>
                        @csrf
                        <div class="form-group">
                            <label class="form-label">Code <span class="text-danger">*</span></label>
                            <input type="text" class="form-control @error('code') is-invalid @enderror" name="code" value="{{ old('code') }}">
                            @error('code')
                                <div class="invalid-feedback">
                                    {{ $message }}
                                </div>
                            @enderror
                        </div>
                        <div class="form-group">
                            <label class="form-label">Abbreviations <span class="text-danger">*</span></label>
                            <input type="text" class="form-control @error('abbreviations') is-invalid @enderror" name="abbreviations" value="{{ old('abbreviations') }}">
                            @error('abbreviations')
                                <div class="invalid-feedback">
                                    {{ $message }}
                                </div>
                            @enderror
                        </div>
                        <div class="form-group">
                            <label class="form-label">Name <span class="text-danger">*</span></label>
                            <input type="text" class="form-control @error('name') is-invalid @enderror" name="name" value="{{ old('name') }}" required>
                            @error('name')
                                <div class="invalid-feedback">
                                    {{ $message }}
                                </div>
                            @enderror
                        </div>
                        <div class="float-lg-right">
                            <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                            <button type="submit" class="btn btn-primary">Save</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="editBranchCode" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Edit Branch Code</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true"><i class="fal fa-times"></i></span>
                    </button>
                </div>
                <div class="modal-body">
                    <form action="{{url('admin/configuration/branch-code/update')}}" method="POST" enctype="multipart/form-data" class="needs-validation" novalidate>
                        @csrf
                        @method('PUT')
                        <input type="hidden" name="id" class="e_id" id="e_id" value="">
                        <div class="form-group">
                            <label class="form-label">Code <span class="text-danger">*</span></label>
                            <input type="text" class="form-control @error('code') is-invalid @enderror" id="e_code" name="code" value="{{ old('code') }}">
                            @error('code')
                                <div class="invalid-feedback">
                                    {{ $message }}
                                </div>
                            @enderror
                        </div>
                        <div class="form-group">
                            <label class="form-label">Abbreviations <span class="text-danger">*</span></label>
                            <input type="text" class="form-control @error('abbreviations') is-invalid @enderror" id="e_abbreviations" name="abbreviations" value="{{ old('abbreviations') }}">
                            @error('abbreviations')
                                <div class="invalid-feedback">
                                    {{ $message }}
                                </div>
                            @enderror
                        </div>
                        <div class="form-group">
                            <label class="form-label">Name <span class="text-danger">*</span></label>
                            <input type="text" class="form-control @error('name') is-invalid @enderror" id="e_name" name="name" value="{{ old('name') }}" required>
                            @error('name')
                                <div class="invalid-feedback">
                                    {{ $message }}
                                </div>
                            @enderror
                        </div>
                        <div class="float-lg-right">
                            <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                            <button type="submit" class="btn btn-primary">Save</button>
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
                        <form action="{{url('admin/configuration/branch-code/delete')}}" method="POST" enctype="multipart/form-data">
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
        var edit = @json(Auth::user()->can('Category Edit'));
        var catDelete = @json(Auth::user()->can('Category Delete'));
        $(function(){
            $(document).on('click','.btnDelete',function(){
                $('.e_id').val($(this).data("id"));
            });
            $(document).on('click','.btnEdit',function(){
                let id = $(this).data("id");
                $.ajax({
                    type: "GET",
                    url: "{{url('admin/configuration/branch-code')}}/" + id + '/edit',
                    data: {
                        id : id
                    },
                    dataType: "JSON",
                    success: function (response) {
                        if (response.success) {
                            $('#e_id').val(response.success.id);
                            $('#e_code').val(response.success.code);
                            $('#e_abbreviations').val(response.success.abbreviations);
                            $('#e_name').val(response.success.name);
                            $('#editBranchCode').modal('show');
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
                    url: '{{ URL("admin/configuration/branch-code") }}',
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
                        data: 'code', 
                        name: 'code',
                        className: 'stuck-scroll-3',
                        orderable: true,
                        searchable: true,
                    },
                    { 
                        data: 'abbreviations', 
                        name: 'abbreviations',
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
                            if (catDelete) {
                                actionButtons += `<a href="javascript:void(0);" class="btn btn-sm btn-outline-danger btn-icon btn-inline-block mr-2 btnDelete" data-toggle="modal" data-target="#delete_category" title="Delete Record" data-id="${row.id}"><i class="fal fa-times"></i></a>`;
                            }
                            if(edit){
                                actionButtons += `<a href="javascript:void(0);" class="btn btn-sm btn-outline-success btn-icon btn-inline-block mr-2 btnEdit" data-id="${row.id}"><i class="fal fa-edit"></i></a>`;
                            }
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