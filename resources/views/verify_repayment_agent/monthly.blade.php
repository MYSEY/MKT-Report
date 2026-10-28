@extends('layouts.admin')
@section('content')
    <div class="row mb-2">
        <div class="col-md-6">
            <h3 class="">Verify Repayment Agent Monthly</h3>
        </div>
    </div>
    <div id="panel-1" class="panel">
        <div class="panel-hdr">
            <h2>
                Verify Repayment Agent Monthly
            </h2>
        </div>
        <div class="panel-container show">
            <div class="panel-content">
                <div class="table-responsive">
                    <table id="tbl_veryfy_repayment_agent" class="table table-bordered table-hover table-striped">
                        <thead>
                            <tr>
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
@endsection
@section('script')
    <script>
        var Verifydelete = @json(Auth::user()->can('Veryfy Repayment Agent Report Delete'));    
        $(function(){
            dataTables();
        });

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
                    url: '{{ URL("admin/mkt-report/veryfy/repayment/agent/monthly") }}',
                    type: 'GET',
                    data: function (d) {
                        d.date = $('#date').val();
                    },
                },
                columns: [
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