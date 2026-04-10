@extends('layouts.admin')
@section('content')
    {!! Toastr::message() !!}
    <div class="card mb-2">
        <div class="card-body">
            <div class="row filter-btn">
                <div class="col-sm-4 col-md-4 col-lg-4 col-xl-4">
                    <div class="form-group">
                        <select class="select2 form-control btn-filter filter-branch" id="branch_id" data-select2-id="select2-data-2-c0n2" name="branch_id">
                            <option value="" data-select2-id="select2-data-2-c0n2">All Branch</option>
                            @foreach ($branch as $item)
                                <option value="{{ $item->id }}">
                                    {{ Helper::getLang() == 'en' ? $item->branch_name_en : $item->branch_name_kh }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                </div>
                <div class="col-sm-4 col-md-4">
                    <div class="form-group">
                        <input type="text" 
                            class="form-control datepicker_month btn-filter" 
                            name="network_date" 
                            id="network_date" 
                            value="{{ date('Y-m') }}"
                            placeholder="mm/yyyy" 
                            readonly 
                            style="background-color: #fff;">
                    </div>
                </div>
                <div class="col-sm-4 col-md-4">
                    <div class="float-right">
                        @if(Auth::user()->can('Network Employee Export'))
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
                Network Employee
            </h2>
        </div>
        <div class="panel-container show">
            <div class="panel-content">
                <div class="table-responsive">
                    <table id="tbl-network-employee" class="table table-bordered table-hover table-striped">
                        <thead>
                            <tr>
                                <th rowspan="2" class="text-center" style="vertical-align: middle;">ល.រ</th>
                                <th rowspan="2" style="vertical-align: middle;" class="text-center">ខេត្ត-រាជធានី</th>
                                <th colspan="2" class="text-center">ឈ្មោះសាខា</th>
                                <th rowspan="2" style="vertical-align: middle;" class="text-center">ចំនួនសាខា</th>
                                <th colspan="3" class="text-center">បុគ្គលិក</th>
                                <th rowspan="2" style="vertical-align: middle;" class="text-center"># of COs</th>
                            </tr>
                            <tr>
                                <th>ឈ្មោះសាខា</th>
                                <th class="text-center">ស្នាក់ការកណ្តាល</th>
                                <th class="text-center">ប្រុស</th>
                                <th class="text-center">ស្រី</th>
                                <th class="text-center">សរុប</th>
                            </tr>
                        </thead>
                        <tbody>
                            {{-- DataTables នឹងបំពេញទិន្នន័យនៅទីនេះ --}}
                        </tbody>
                        <tfoot style="background-color: #f2f2f2; font-weight: bold;">
                            <tr>
                                <th colspan="3" class="text-right">សរុបរួម:</th>
                                <th class="text-center"></th> <th class="text-center"></th> <th class="text-center"></th> <th class="text-center"></th> <th class="text-center"></th> <th class="text-center"></th> </tr>
                        </tfoot>
                    </table>
                </div>
            </div>
        </div>
    </div>
@endsection
@section('script')
    <script>
        $(function(){
            $(document).ready(function() {
                $('.datepicker_month').datepicker({
                    format: "yyyy-mm",     // កំណត់ Format បង្ហាញ
                    viewMode: "months",    // បង្ហាញផ្ទាំងខែពេលបើកដំបូង
                    minViewMode: "months", // កំណត់ឱ្យរើសបានត្រឹមខែ (ចុចលើខែហើយបិទតែម្ដង)
                    autoclose: true,
                    todayHighlight: true
                });
            });
            $(".btn_excel").on("click", function() {
                let query = {
                    network_date: $('input[name="network_date"]').val(),
                    branch_id: $("#branch_id").val()
                };
                var url = "{{URL::to('admin/hr-report/network-employee/download')}}?" + $.param(query)
                window.location = url;
            });
            // Reload only (DON'T destroy/reinit)
            $('.btn-filter').on('change', function() {
                $('#loading-overlay').hide();
                $('#tbl-network-employee').DataTable().ajax.reload(null, false);
            });
            // Initialize only once
            dataTables();
        });

        function dataTables() {
            $('#loading-overlay').show();
            // Check if DataTable instance exists, then destroy it
            if ($.fn.DataTable.isDataTable('#tbl-network-employee')) {
                $('#tbl-network-employee').DataTable().clear().destroy();
            }
            let lastProvinceName = null;
            $('#tbl-network-employee').DataTable({
                pageLength: 10,
                destroy: true,
                processing: true,
                serverSide: true,
                // scrollX: true,
                scrollY: '350px',
                scroller: false,
                order: [[1, 'asc']], // តម្រៀបតាមឈ្មោះខេត្ត ដើម្បីងាយស្រួល Merge
                lengthMenu: [ [10, 25, 50, 100], [10, 25, 50, 100] ],
                ajax: {
                    url: '{{ URL("admin/hr-report/network-employee") }}',
                    type: 'GET',
                    data: function (d) {
                        d.branch_id = $('select[name="branch_id"]').val();
                        d.network_date = $('input[name="network_date"]').val();
                    },
                    dataSrc: function (json) {
                        return json.data;
                    }
                },
                columns: [
                    { 
                        data: 'province_no', 
                        name: 'province_no', 
                        className: 'text-center' 
                    },
                    { data: 'pro_name_km', name: 'pro_name_km' },
                    { data: 'branch_name_kh', name: 'branch_name_kh' },
                    { 
                        data: 'is_hq',
                        className: 'text-center',
                    },
                    { data: 'branch_count', className: 'text-center' },
                    { data: 'male', className: 'text-center' },
                    { data: 'female', className: 'text-center' },
                    { data: 'total', className: 'text-center' },
                    { data: 'co_count', className: 'text-center' }
                ],
                
                drawCallback: function (settings) {
                    var api = this.api();
                    var rows = api.rows({ page: 'current' }).nodes();
                    var lastProvince = null;
                    var lastMergeGroup = null;

                    api.rows({ page: 'current' }).data().each(function (row, i) {
                        
                        // --- 1. Merge ល.រ (Col 0) និង ឈ្មោះខេត្ត (Col 1) ---
                        if (lastProvince === row.province_name) {
                            $(rows).eq(i).find('td:eq(0)').hide(); // លាក់ ល.រ
                            $(rows).eq(i).find('td:eq(1)').hide(); // លាក់ ឈ្មោះខេត្ត
                            
                            // រកជួរមេដើម្បីបូក Rowspan
                            for (let j = i - 1; j >= 0; j--) {
                                let cellNo = $(rows).eq(j).find('td:eq(0)');
                                let cellProv = $(rows).eq(j).find('td:eq(1)');
                                if (cellProv.is(':visible')) {
                                    cellNo.attr('rowspan', parseInt(cellNo.attr('rowspan') || 1) + 1);
                                    cellProv.attr('rowspan', parseInt(cellProv.attr('rowspan') || 1) + 1);
                                    break;
                                }
                            }
                        } else {
                            lastProvince = row.province_name;
                        }

                        // --- 2. Merge ចំនួនសាខា (Col 4) សម្រាប់ HQ & Digital ---
                        if (row.merge_group === 'special_group' && lastMergeGroup === 'special_group') {
                            $(rows).eq(i).find('td:eq(3)').hide(); 
                            for (let k = i - 1; k >= 0; k--) {
                                let cellSpecial = $(rows).eq(k).find('td:eq(3)');
                                if (cellSpecial.is(':visible')) {
                                    cellSpecial.attr('rowspan', parseInt(cellSpecial.attr('rowspan') || 1) + 1);
                                    cellSpecial.html('1'); 
                                    break;
                                }
                            }
                        }
                        lastMergeGroup = row.merge_group;
                    });
                },
                footerCallback: function (row, data, start, end, display) {
                    var api = this.api();

                    // មុខងារជំនួយសម្រាប់បូកលេខ
                    var intVal = function (i) {
                        return typeof i === 'string' ? i.replace(/[\$,]/g, '') * 1 : typeof i === 'number' ? i : 0;
                    };

                    // បញ្ជី Column Index ដែលត្រូវបូក (3, 4, 5, 6, 7, 8)
                    [3, 4, 5, 6, 7, 8].forEach(function (index) {
                        let total = api.column(index).data().reduce(function (a, b) {
                            return intVal(a) + intVal(b);
                        }, 0);
                        $(api.column(index).footer()).html(total > 0 ? total : '');
                    });
                }
            });
            $('#tbl-network-employee').on('processing.dt', function (e, settings, processing) {
                if (processing) {
                    $('#loading-overlay').show();
                } else {
                    $('#loading-overlay').hide();
                }
            });
        }
    </script>
@endsection