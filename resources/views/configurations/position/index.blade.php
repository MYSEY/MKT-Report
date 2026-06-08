@extends('layouts.admin')
@section('content')
    <style>
        /* បង្កើន z-index របស់ Select2 Dropdown ឱ្យខ្ពស់ជាង Bootstrap Modal (1050) */
        .select2-container--open {
            z-index: 9999999 !important;
        }
    </style>
    <div class="row">
        <div class="col-md-6">
            <h3 class="breadcrumb page-breadcrumb">Position</h3>
        </div>
        @if(Auth::user()->can('Position Create'))
            <div class="col-md-6">
                <div class="text-lg-right">
                    <button class="btn btn-success btn-sm mr-1" data-toggle="modal" data-target="#interest-income-create" type="button"><span><i class="fal fa-plus mr-1"></i> Add New</span></button>
                </div>
            </div>
        @endif
    </div>
    
    <div id="panel-1" class="panel">
        <div class="panel-hdr">
            <h2>
                Position
            </h2>
        </div>
        <div class="panel-container show">
            <div class="panel-content">
                <div class="table-responsive">
                    <table id="tbl-position" class="table table-bordered table-hover table-striped">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Type</th>
                                <th>Position Name</th>
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
    <!-- Modal Create New -->
    <div class="modal custom-modal fade" id="interest-income-create" role="dialog" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Add New Position</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true"><i class="fal fa-times"></i></span>
                    </button>
                </div>
                <div class="modal-body">
                    <form id="form-create" action="{{url('admin/configuration/position')}}" method="POST" enctype="multipart/form-data" class="needs-validation" novalidate>
                        @csrf
                        <div class="form-group">
                            <label class="form-label">Type <span class="text-danger">*</span></label>
                            <select name="type" id="" class="form-control" required>
                                <option value=""> </option>
                                <option value="1">Network Employee</option>
                                <option value="2">TMG</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Position Name <span class="text-danger">*</span></label>
                            <select class="form-control select2" id="position_id" name="position_id" style="width: 100%;">
                                <option value="">-- Select Position --</option>
                                @foreach ($positions as $item)
                                    <option value="{{ $item->id }}">
                                        {{ $item->name_english }}
                                    </option>
                                @endforeach
                            </select>
                            <span id="position_error" class="text-danger" style="display:none; font-size: 12px;">Please select a position!</span>
                        </div>
                        <div class="float-lg-right">
                            <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                            <button type="submit" class="btn btn-danger">Save changes</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="editPosition" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Edit Position</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true"><i class="fal fa-times"></i></span>
                    </button>
                </div>
                <div class="modal-body">
                    <form action="{{url('admin/configuration/position/update')}}" method="POST" enctype="multipart/form-data" class="needs-validation" novalidate>
                        @csrf
                        @method('PUT')
                        <input type="hidden" name="id" class="e_id" id="e_id" value="">
                        <div class="form-group">
                            <label class="form-label">Type <span class="text-danger">*</span></label>
                            <select name="type" id="e_type" class="form-control" required>
                            </select>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Position Name <span class="text-danger">*</span></label>
                            <select class="form-control select2" id="e_position_id" name="position_id" style="width: 100%;">
                                <option value="">-- Select Position --</option>
                            </select>
                        </div>
                        <div class="float-lg-right">
                            <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                            <button type="submit" class="btn btn-danger">Save changes</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
    <div class="modal custom-modal fade" id="delete_position" role="dialog">
        <div class="modal-dialog modal-sm modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-body">
                    <div class="form-header">
                        <h5 class="modal-title">Delete</h5>
                        <p>Are you sure want to delete?</p>
                    </div>
                    <div class="modal-btn delete-action">
                        <form id="delete_position_form" action="{{ url('admin/configuration/position/delete') }}" method="POST">
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
        var edit = @json(Auth::user()->can('Position Edit'));
        var catDelete = @json(Auth::user()->can('Position Delete'));
        $(function(){
            dataTables();
            $(document).ready(function() {
                // ចាប់ព្រឹត្តិការណ៍នៅពេល Form ត្រូវ Submit
                $('#form-create').on('submit', function(e) {
                    var positionValue = $('#position_id').val();

                    // 💡 បើតម្លៃនៅក្នុង Select2 នៅទទេ (មិនទាន់រើស)
                    if (positionValue === "" || positionValue === null) {
                        e.preventDefault(); // ឃាត់មិនឱ្យ Form ផ្ញើទៅ Server (មិនឱ្យ Save)
                        
                        $('#position_error').show(); // បង្ហាញអក្សរក្រហមព្រមាន
                        
                        // បន្ថែមស៊ុមក្រហមទៅលើប្រអប់ Select2 ឱ្យអ្នកប្រើប្រាស់ដឹង
                        $('.select2-selection').css('border-color', '#ff0000'); 
                        return false;
                    } else {
                        $('#position_error').hide(); // លាក់ការព្រមានបើមានទិន្នន័យ
                        $('.select2-selection').css('border-color', '#aaa');
                    }
                });

                // បាត់ស៊ុមក្រហមវិញ នៅពេលប្តូរមកជ្រើសរើសទិន្នន័យ
                $('#position_id').on('change', function() {
                    if ($(this).val() !== "") {
                        $('#position_error').hide();
                        $('.select2-selection').css('border-color', '#aaa');
                    }
                });
            });
           
            $(document).on('click','.btnDelete',function(){
                $('.e_id').val($(this).data("id"));
            });
            $(document).on('click','.btnEdit',function(){
                let id = $(this).data("id");
                $.ajax({
                    type: "GET",
                    url: "{{url('admin/configuration/position')}}/" + id + '/edit',
                    data: {
                        id : id
                    },
                    dataType: "JSON",
                    success: function (response) {
                        if (response.success) {
                            $('#e_id').val(response.success.id);
                            if (response.success.type == "2") {
                                $("#e_type").append('<option selected value="2">TMG</option><option value="1">Network Employee</option>');
                            } else {
                                $("#e_type").append('<option value=" "> </option><option selected value="1">Network Employee</option> <option value="2">TMG</option>');   
                            }
                            $("#dub_department").val(response.success.department_id);
                            $('#e_department').html( '<option value=""> -- Select Position  --</option>');
                            $.each(response.positions, function(i, item) {
                                $('#e_position_id').append($('<option>', {
                                    value: item.id,
                                    text: item.name_english,
                                    selected: item.id == response.success.position_id
                                }));
                            });
                            $('#editPosition').modal('show');
                        }
                    }
                });
            });
        });
        function dataTables() {
            $('#loading-overlay').show();
            $('#tbl-position').DataTable({
                pageLength: 10,
                destroy: true,
                processing: true,
                serverSide: true,
                order: [[0, 'desc']],
                lengthMenu: [ [10, 25, 50, 100], [10, 25, 50, 100] ],
                ajax: {
                    url: '{{ URL("admin/configuration/position") }}',
                    type: 'GET',
                }, 
                columns: [
                    {
                        data: null,
                        name: 'id',
                        orderable: false,
                        searchable: false,
                        className: 'text-center',
                        render: function (data, type, row, meta) {
                            return meta.row + meta.settings._iDisplayStart + 1;
                        }
                    },
                    { 
                        data: 'type', 
                        name: 'type',
                        className: 'stuck-scroll-3',
                        render: function(data, type, row) {
                            let text = '';
                            if (data == "1") {
                                text = "Network Employee"
                            }
                            if(data == "2"){
                                text = "TMG"
                            }
                            return text;
                        },
                        orderable: true,
                        searchable: true,
                    },
                    { 
                        data: 'position_id', 
                        render: function(data, type, row) {
                            return row.hr_position.name_english;
                        },
                    },
                    {
                        data: '',
                        name: 'action',
                        render: function(data, type, row) {
                            let actionButtons = '';
                            if (catDelete) {
                                actionButtons += `<a href="javascript:void(0);" class="btn btn-sm btn-outline-danger btn-icon btn-inline-block mr-2 btnDelete" data-toggle="modal" data-target="#delete_position" title="Delete Record" data-id="${row.id}"><i class="fal fa-times"></i></a>`;
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
            $('#tbl-position').on('processing.dt', function (e, settings, processing) {
                if (processing) {
                    $('#loading-overlay').show();
                } else {
                    $('#loading-overlay').hide();
                }
            });
        }
    </script>
@endsection