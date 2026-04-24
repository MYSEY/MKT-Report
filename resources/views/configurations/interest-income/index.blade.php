@extends('layouts.admin')
@section('content')
    <div class="row">
        <div class="col-md-6">
            <h3 class="breadcrumb page-breadcrumb">Interest Income</h3>
        </div>
        @if(Auth::user()->can('Interest Income Create'))
            <div class="col-md-6">
                <div class="text-lg-right">
                    <button type="button" class="btn btn-sm btn-info waves-effect waves-themed btn_import mr-1">
                        <span class="btn-text-import"><i class="fal fa-arrow-circle-up"></i></span>
                        Imports
                    </button>
                    <button class="btn btn-success btn-sm mr-1" data-toggle="modal" data-target="#interest-income-create" type="button"><span><i class="fal fa-plus mr-1"></i> Add New</span></button>
                </div>
            </div>
        @endif
    </div>
    
    <div id="panel-1" class="panel">
        <div class="panel-hdr">
            <h2>
                Interest Income
            </h2>
        </div>
        <div class="panel-container show">
            <div class="panel-content">
                <div class="table-responsive">
                    <table id="btl_interest_income" class="table table-bordered table-hover table-striped">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Type</th>
                                <th>Account Number</th>
                                <th>Account Name</th>
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
                    <h5 class="modal-title">Add New Interest Income</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true"><i class="fal fa-times"></i></span>
                    </button>
                </div>
                <div class="modal-body">
                    <form action="{{url('admin/configuration/interest-income')}}" method="POST" enctype="multipart/form-data" class="needs-validation" novalidate>
                        @csrf
                        <div class="form-group">
                            <label class="form-label">Type <span class="text-danger">*</span></label>
                            <select name="type" id="" class="form-control" required>
                                <option value=""> </option>
                                <option value="1">Loan Product</option>
                                <option value="2">Other Bank</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Account Number <span class="text-danger">*</span></label>
                            <input type="text" class="form-control @error('name') is-invalid @enderror" name="account_number" required>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Account Name <span class="text-danger">*</span></label>
                            <textarea name="account_name" cols="20" rows="3" class="form-control @error('name') is-invalid @enderror" required></textarea>
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

    <div class="modal fade" id="editInterestIncome" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Edit Interest Income</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true"><i class="fal fa-times"></i></span>
                    </button>
                </div>
                <div class="modal-body">
                    <form action="{{url('admin/configuration/interest-income/update')}}" method="POST" enctype="multipart/form-data" class="needs-validation" novalidate>
                        @csrf
                        @method('PUT')
                        <input type="hidden" name="id" class="e_id" id="e_id" value="">
                        <div class="form-group">
                            <label class="form-label">Type <span class="text-danger">*</span></label>
                            <select name="type" id="e_type" class="form-control" required>
                            </select>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Account Number <span class="text-danger">*</span></label>
                            <input type="text" class="form-control @error('name') is-invalid @enderror" name="account_number" id="e_account_number" required>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Account Name <span class="text-danger">*</span></label>
                            <textarea name="account_name" cols="20" rows="3" class="form-control @error('name') is-invalid @enderror" id="e_account_name" required></textarea>
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
    <div class="modal custom-modal fade" id="delete_interest_income" role="dialog">
        <div class="modal-dialog modal-sm modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-body">
                    <div class="form-header">
                        <h5 class="modal-title">Delete</h5>
                        <p>Are you sure want to delete?</p>
                    </div>
                    <div class="modal-btn delete-action">
                        <form action="{{url('admin/configuration/interest-income/delete')}}" method="POST" enctype="multipart/form-data">
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
    @include('configurations.interest-income.import')
@endsection
@section('script')
    <script>
        var edit = @json(Auth::user()->can('Interest Income Edit'));
        var catDelete = @json(Auth::user()->can('Interest Income Delete'));
        $(function(){
            dataTables();
            $(document).on('click','.btnDelete',function(){
                $('.e_id').val($(this).data("id"));
            });
            $(document).on('click','.btnEdit',function(){
                let id = $(this).data("id");
                $.ajax({
                    type: "GET",
                    url: "{{url('admin/configuration/interest-income')}}/" + id + '/edit',
                    data: {
                        id : id
                    },
                    dataType: "JSON",
                    success: function (response) {
                        if (response.success) {
                            $('#e_id').val(response.success.id);
                            $('#e_account_name').val(response.success.account_name);
                            $('#e_account_number').val(response.success.account_number);
                            if (response.success.type == "2") {
                                $("#e_type").append('<option selected value="1">Other Bank</option><option value="2">Loan Product</option>');
                            } else {
                                $("#e_type").append('<option value=" "> </option><option selected value="2">Loan Product</option> <option value="1">Other Bank</option>');   
                            }
                            $('#editInterestIncome').modal('show');
                        }
                    }
                });
            });
            $(document).on('click','.btn_import',function(){
                $(".thanLess").hide();
                $("#thanLess").text("");
                $('#importModal').modal('show');
            });
            $("#result_file").on("change", function(){
                $(".thanLess").hide();
                $("#thanLess").text("");
            });

            $(".upload_file_data").on("click", function() {
                if ($('#result_file').val() == "") {
                    $("#thanLess").text("Please select a xls,xlsx and csv file and size less then 1MB").css(
                        "color", "red");
                    $(".thanLess").show();
                    return false;
                }
                var file_data = $('#result_file').prop('files')[0];
                var fileName = file_data['name'];
                var form_data = new FormData();
                var fileExtension = fileName.split('.').pop();
                var fileSize = file_data['size'];
                form_data.append('file', file_data);
                form_data.append('_token', "{{ csrf_token() }}");
                if (fileExtension == "xls" || fileExtension == "xlsx" || fileExtension == "csv" && fileSize < 1048576) {
                    $(".upload_file_data").prop('disabled', true);
                    $(".btn-text-submit").hide();
                    $("#btn-loading").css('display', 'block');

                    $("#importModal").modal("show");
                    $.ajax({
                        type: 'POST',
                        url: "{{ url('admin/configuration/interest-income/import') }}",
                        data: form_data,
                        contentType: false,
                        cache: false,
                        processData: false,
                        success: function(data) {
                            if (data == 1) {
                                $("#importModal").modal("hide");
                                toastr.success('Data has been save success');
                                window.location.replace("{{ URL('admin/configuration/interest-income') }}");
                            }
                            if (data == 2) {
                                $("#importModal").modal("hide");
                                $("#thanLess").text("Data duplicate").css("color", "red");
                                $(".thanLess").show();
                            }
                            if (data == 0) {
                                $("#importModal").modal("show");
                                data == 0;
                                $("#thanLess").text(
                                    "Please select a xls,xlsx and csv file and size less then 1MB"
                                    ).css("color", "red");
                                $(".thanLess").show();
                            }
                        }
                    });
                }else{
                    $("#thanLess").text("Please select a xls,xlsx and csv file and size less then 1MB").css(
                        "color", "red");
                    $(".thanLess").show();
                }
            });
        });

        function dataTables() {
            $('#loading-overlay').show();
            $('#btl_interest_income').DataTable({
                pageLength: 10,
                destroy: true,
                processing: true,
                serverSide: true,
                order: [[0, 'desc']],
                lengthMenu: [ [10, 25, 50, 100], [10, 25, 50, 100] ],
                ajax: {
                    url: '{{ URL("admin/configuration/interest-income") }}',
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
                                text = "Loan Product"
                            }
                            if(data == "2"){
                                text = "Other Bank"
                            }
                            return text;
                        },
                        orderable: true,
                        searchable: true,
                    },
                    { 
                        data: 'account_number', 
                        name: 'account_number',
                        className: 'stuck-scroll-3',
                        orderable: true,
                        searchable: true,
                    },
                    { 
                        data: 'account_name', 
                        name: 'account_name',
                        orderable: true,
                        searchable: true,
                    },
                    {
                        data: '',
                        name: 'action',
                        render: function(data, type, row) {
                            let actionButtons = '';
                            if (catDelete) {
                                actionButtons += `<a href="javascript:void(0);" class="btn btn-sm btn-outline-danger btn-icon btn-inline-block mr-2 btnDelete" data-toggle="modal" data-target="#delete_interest_income" title="Delete Record" data-id="${row.id}"><i class="fal fa-times"></i></a>`;
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
            $('#btl_interest_income').on('processing.dt', function (e, settings, processing) {
                if (processing) {
                    $('#loading-overlay').show();
                } else {
                    $('#loading-overlay').hide();
                }
            });
        }
    </script>
@endsection