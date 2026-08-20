@extends('layouts.admin')
@section('content')
    <div class="row mb-2">
        <div class="col-md-6">
            <h3 class="">Verify Repayment Agent Report</h3>
        </div>
        <div class="col-md-6" style="text-align: right;">
            @if(Auth::user()->can('Veryfy Repayment Agent Report Import'))
                <a type="button" id="btn-import" href="#" data-toggle="modal" data-target="#modal-import" class="btn btn-danger btn-sm mr-1">Upload Verify</a>
            @endif
        </div>
    </div>
    {!! app('toastr')->message() !!}
    <div id="panel-1" class="panel">
        <div class="panel-hdr">
            <h2>
                Verify Repayment Agent Report
            </h2>
        </div>
        <div class="panel-container show">
            <div class="panel-content">
                <div class="table-responsive">
                    <table id="tbl_veryfy_repayment_agent" class="table table-bordered table-hover table-striped">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Branch</th>
                                <th>Name</th>
                                <th>Date</th>
                                <th>Memo</th>
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
    @include('verify_repayment_agent.import')
@endsection
@section('script')
    <script>
        var Verifydelete = @json(Auth::user()->can('Veryfy Repayment Agent Report Delete'));
        $(function(){
            dataTables();
            $(".upload_file_data").on("click", function () {
                if ($('#result_file').val() == "") {
                    $("#thanLess").text("Please select xls, xlsx or csv file and size less than 1MB").css("color", "red");
                    $(".thanLess").show();
                    return false;
                }

                var file_data    = $('#result_file').prop('files')[0];
                var fileName     = file_data.name;
                var fileExtension = fileName.split('.').pop().toLowerCase();
                var fileSize     = file_data.size;

                if (!((fileExtension == "xls" || fileExtension == "xlsx" || fileExtension == "csv") && fileSize < 1048576)) {
                    $("#thanLess").text("Please select xls, xlsx or csv file and size less than 1MB").css("color", "red");
                    $(".thanLess").show();
                    return false;
                }
                $.ajax({
                    url: '{{ url("admin/mkt-report/veryfy/repayment/agent/check-delete") }}',
                    type: 'GET',
                    success: function(response) {
                        if (response.willDelete) {
                            $("#modal-import").modal("hide");
                            $(".upload_file_data").prop('disabled', false);
                            $(".btn-hidden-show").show();
                            $(".btn-impot-loading").hide();
                            Swal.fire({
                                title: 'Warning!',
                                html: `Data of <b>${response.oldestDay}</b> will be deleted!<br>
                                    Total Uploads: <b>${response.totalUploads}</b><br>
                                    Total Records: <b>${response.totalRecords}</b><br><br>
                                    Do you want to continue?`,
                                icon: 'warning',
                                showCancelButton: true,
                                confirmButtonColor: '#3085d6',
                                cancelButtonColor: '#d33',
                                confirmButtonText: 'Yes, Continue!',
                                cancelButtonText: 'Cancel',
                            }).then((result) => {
                                submitImport(file_data);
                            });
                        } else {
                            submitImport(file_data);
                        }
                    },
                    error: function() {
                        submitImport(file_data);
                    }
                });
            });
        });

        function submitImport(file_data) {
            var form_data = new FormData();
            form_data.append('file', file_data);
            form_data.append('exchange_rate', $('#exchange_rate').val());
            form_data.append('date', $('#date').val());
            form_data.append('_token', "{{ csrf_token() }}");

            $.ajax({
                type: 'POST',
                url: "{{ url('admin/mkt-report/veryfy/repayment/agent/import') }}",
                data: form_data,
                contentType: false,
                cache: false,
                processData: false,
                success: function(data) {
                    $("#modal-import").modal("hide");
                    $(".upload_file_data").prop('disabled', false);
                    $(".btn-hidden-show").show();
                    $(".btn-impot-loading").hide();
                    toastr.success('Verify completed successfully');
                    dataTables();
                },
                error: function(xhr, status, error) {
                    $("#modal-import").modal("hide");
                    $(".upload_file_data").prop('disabled', false);
                    $(".btn-hidden-show").show();
                    $(".btn-impot-loading").hide();
                    Swal.fire("Error!", "An error occurred while processing your request.", "error");
                }
            });
        }
        function deleteRecord(id) {
            Swal.fire({
                title: 'Are you sure?',
                text: 'This record will be permanently deleted!',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#d33',
                cancelButtonColor: '#3085d6',
                confirmButtonText: 'Yes, Delete!',
                cancelButtonText: 'Cancel',
            }).then(function(result) {
                if (result.value === true) {
                    $.ajax({
                        url: '{{ url("admin/mkt-report/veryfy/repayment/agent/destroy") }}/' + id,
                        type: 'DELETE',
                        data: {
                            '_token': '{{ csrf_token() }}',
                        },
                        success: function(response) {
                            toastr.success('Deleted successfully');
                            dataTables(); // ✅ reload table
                        },
                        error: function() {
                            Swal.fire('Error!', 'Failed to delete.', 'error');
                        }
                    });
                }
            });
        }
        function dataTables() {
            if ($.fn.DataTable.isDataTable('#tbl_veryfy_repayment_agent')) {
                $('#tbl_veryfy_repayment_agent').DataTable().destroy();
            }
            $('#tbl_veryfy_repayment_agent').DataTable({
                pageLength: 10,
                destroy: true,
                processing: true,
                serverSide: true,
                order: [[0, 'desc']],
                lengthMenu: [ [10, 25, 50, 100], [10, 25, 50, 100] ],
                ajax: {
                    url: '{{ URL("admin/mkt-report/veryfy/repayment/agent") }}',
                    type: 'GET',
                    data: function (d) {
                        d.date = $('#date').val();
                    },
                },
                columns: [
                    {
                        data: 'id',
                        name: 'id',
                        orderable: false,
                        searchable: false,
                    },
                    {
                        data: 'branch',
                        name: 'branch',
                        orderable: false,
                        searchable: false,
                    },
                    { 
                        data: 'name', 
                        name: 'name',
                    },
                    { 
                        data: 'date', 
                        name: 'date',
                    },
                    { 
                        data: 'memo', 
                        name: 'memo',
                    },
                    {
                        data: 'id',
                        name: 'action',
                        render: function(data, type, row) {
                            let actionButtons = '';
                            actionButtons += `<a href="{{url('/admin/mkt-report/veryfy/repayment/agent/detail')}}/${row.id}" class="btn btn-sm btn-outline-success btn-icon btn-inline-block mr-2" title="show detail"><i class="fal fa-eye"></i></a>`;
                            if (Verifydelete) {
                                actionButtons += `<button onclick="deleteRecord(${row.id})" class="btn btn-sm btn-outline-danger btn-icon btn-inline-block mr-1" title="Delete">
                                    <i class="fal fa-trash"></i>
                                </button>`;
                            }
                            return actionButtons;

                            // return `
                            //     <a href="{{url('/admin/mkt-report/veryfy/repayment/agent/detail')}}/${row.id}" class="btn btn-sm btn-outline-success btn-icon btn-inline-block mr-2" title="show detail"><i class="fal fa-eye"></i></a>
                            //     <button onclick="deleteRecord(${row.id})" class="btn btn-sm btn-outline-danger btn-icon btn-inline-block mr-1" title="Delete">
                            //         <i class="fal fa-trash"></i>
                            //     </button>
                            // `;
                        },
                        orderable: false,
                        searchable: false,
                    },
                ],
            });
            $('#tbl_veryfy_repayment_agent').on('processing.dt', function (e, settings, processing) {
                if (processing) {
                    $('#loading-overlay').show();
                } else {
                    $('#loading-overlay').hide();
                }
            });
        }
    </script>
@endsection