@extends('layouts.admin')
@section('content')
    {!! Toastr::message() !!}
    <div class="card mb-2">
        <div class="card-body">
            <div class="row filter-btn">
                <div class="col-sm-12 col-md-12">
                    <div class="float-right">
                        @if(Auth::user()->can('TMG Report Export'))
                            <button type="button" class="btn btn-sm btn-info waves-effect waves-themed btn_excel mr-1" id="icon-search-download-reload">
                                <span class="btn-text-excel"><i class="fal fa-arrow-circle-down"></i></span>
                                Excel
                                <span id="btn-text-loading-excel" style="display: none"><i class="fa fa-spinner fa-spin"></i></span>
                            </button>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div id="panel-1" class="panel">
        <div class="panel-hdr">
            <h2>
                TMG Reports
            </h2>
        </div>
        <div class="panel-container show">
            <div class="panel-content">
                <div class="table-responsive">
                    <table id="tbl-tmg-reports" class="table table-bordered table-hover table-striped">
                        <thead>
                            <tr>
                                <th  class="text-center" style="vertical-align: middle;">ល.រ</th>
                                <th  style="vertical-align: middle;" class="text-center">ឈ្មោះ</th>
                                <th class="text-center">តួនាទី</th>
                                <th  style="vertical-align: middle;" class="text-center">ការិយាល័យ</th>
                            </tr>
                        </thead>
                        <tbody>
                            {{-- DataTables នឹងបំពេញទិន្នន័យនៅទីនេះ --}}
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
@endsection
@section('script')
    <script>
        $(function(){
            $(".btn_excel").on("click", function() {
                let query = {
                    branch_id: ""
                };
                var url = "{{URL::to('admin/hr-report/tmg/download')}}?" + $.param(query)
                window.location = url;
            });
            // Initialize only once
            dataTables();
        });

        function dataTables() {
            $('#loading-overlay').show();
            // Check if DataTable instance exists, then destroy it
            if ($.fn.DataTable.isDataTable('#tbl-tmg-reports')) {
                $('#tbl-tmg-reports').DataTable().clear().destroy();
            }
            $('#tbl-tmg-reports').DataTable({
                pageLength: 20,
                destroy: true,
                processing: true,
                serverSide: true,
                // scrollX: true,
                scrollY: '350px',
                scroller: false,
                order: [[1, 'asc']], // តម្រៀបតាមឈ្មោះខេត្ត ដើម្បីងាយស្រួល Merge
                lengthMenu: [ [20, 25, 50, 100], [10, 25, 50, 100] ],
                ajax: {
                    url: '{{ URL("admin/hr-report/tmg") }}',
                    type: 'GET',
                    dataSrc: function (json) {
                        return json.data;
                    }
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
                    { data: 'employee_name_kh', name: 'employee_name_kh' },
                    { data: 'position_name_kh', name: 'position_name_kh' },
                    { data: 'branch_name_kh', name: 'branch_name_kh' },
                ],
                
            });
            $('#tbl-tmg-reports').on('processing.dt', function (e, settings, processing) {
                if (processing) {
                    $('#loading-overlay').show();
                } else {
                    $('#loading-overlay').hide();
                }
            });
        }
    </script>
@endsection