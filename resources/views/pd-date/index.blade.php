@extends('layouts.admin')
@section('content')
    <div class="row mb-2">
        <div class="col-md-6">
            <h3 class="">PD Date Report</h3>
        </div>
    </div>
    <div class="card mb-2">
        <div class="card-body">
            <div class="row filter-btn">
                <div class="col-sm-3 col-md-3">
                    <div class="form-group">
                        <input type="text" class="form-control datepicker btn-filter"  name="from_date" id="from_date" value="{{ \Carbon\Carbon::now()->startOfMonth()->format('d-m-Y') }}" placeholder="From Date" readonly style="background-color: #fff;">
                    </div>
                </div>
                <div class="col-sm-3 col-md-3">
                    <div class="form-group">
                        <input type="text" class="form-control datepicker btn-filter" name="to_date" id="to_date" value="{{ \Carbon\Carbon::now()->endOfMonth()->format('d-m-Y') }}" placeholder="To Date" readonly style="background-color: #fff;">
                    </div>
                </div>
                <div class="col-sm-3 col-md-3 col-lg-3 col-xl-3">
                    <div class="form-group">
                        <select class="select2 form-control" id="branch_id" data-select2-id="select2-data-2-c0n2" name="branch_id">
                            <option value="" data-select2-id="select2-data-2-c0n2">All Branch</option>
                            @foreach ($branch as $item)
                                <option value="{{ $item->ID }}">
                                    {{ Helper::getLang() == 'en' ? $item->Description : $item->LocalDescription }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                </div>
                <div class="col-sm-3 col-md-3 col-lg-3 col-xl-3">
                    <div class="float-right">
                        <button type="button" class="btn btn-sm btn-outline-secondary btn-search mr-1" data-dismiss="modal" id="icon-search-download-reload">
                            <span class="btn-txt"><i class="fal fa-search"></i></span>
                            Search
                            <span class="loading-icon" style="display: none"><i class="fa fa-spinner fa-spin"></i></span>
                        </button>
                        @if(Auth::user()->can('PD Date Export'))
                            <button type="button" class="btn btn-sm btn-info waves-effect waves-themed btn_excel mr-1" id="icon-search-download-reload">
                                <span class="btn-text-excel"><i class="fal fa-arrow-circle-down"></i></span>
                                Download
                                <span id="btn-text-loading-excel" style="display: none"><i class="fa fa-spinner fa-spin"></i></span>
                            </button>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>
    {!! app('toastr')->message() !!}
    <div id="panel-1" class="panel">
        <div class="panel-hdr">
            <h2>
                PD Date Report
            </h2>
        </div>
        <div class="panel-container show">
            <div class="panel-content">
                <div class="table-responsive">
                    <table id="tbl_pd_date" class="table table-bordered table-hover table-striped">
                        <thead>
                            <tr>
                                <th>Branch</th>
                                <th>ID</th>
                                <th>PDID</th>
                                <th>Due Date</th>
                                <th>NumDayDue</th>
                                <th>ODStatus</th>
                                <th>TotODAmount</th>
                                <th>OutAmount</th>
                                <th>PrincipalDue</th>
                                <th>OutPriAmount</th>
                                <th>InterestDue</th>
                                <th>OutIntAmount</th>
                                <th>PenaltyDue</th>
                                <th>OutPenAmount</th>
                                <th>ChargeDue</th>
                                <th>OutChgAmount</th>
                                <th>NextRunDate</th>
                                <th>OutAmountAS</th>
                                <th>PrincipalDueAS</th>
                                <th>OutPriAmountAS</th>
                                <th>IntDueAmountAS</th>
                                <th>OutIntAmountAS</th>
                                <th>ChargeDueAS</th>
                                <th>OutChgAmountAS</th>
                                <th>PenaltyDueAS</th>
                                <th>OutPenAmountAS</th>
                                <th>VATDue</th>
                                <th>OutVATAmount</th>
                                <th>VATDueAS</th>
                                <th>OutVATAmountAS</th>
                                <th>WFStatus</th>
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
        const toIso = s => s ? s.split('-').reverse().join('-') : '';   // 01-10-2026 -> 2026-10-01
        let from_date = toIso($('#from_date').val());
        let to_date   = toIso($('#to_date').val());
        let branch_id = $('#branch_id').val();
        $(function(){
            $('.btn-search').on('click', function () {
                $('#loading-overlay').hide();
                branch_id = $('#branch_id').val();
                from_date = toIso($('#from_date').val());
                to_date   = toIso($('#to_date').val());
                $('#tbl_pd_date').DataTable().ajax.reload(null, false);
            });
            $('.btn_excel').on('click', function () {
                const query = {
                    branch_id: $('#branch_id').val(),
                    from_date: toIso($('#from_date').val()),
                    to_date: toIso($('#to_date').val()),
                };
                const url = "{{ route('admin/mkt-report/pd-date.download') }}?" + $.param(query);
                window.location.href = url;
            });
            dataTables();
        });

        function dataTables() {
            if ($.fn.DataTable.isDataTable('#tbl_pd_date')) {
                $('#tbl_pd_date').DataTable().destroy();
            }
            $('#tbl_pd_date').DataTable({
                pageLength: 10,
                destroy: true,
                processing: true,
                serverSide: true,
                order: [[0, 'desc']],
                lengthMenu: [ [10, 25, 50, 100], [10, 25, 50, 100] ],
                ajax: {
                    url: '{{ URL("admin/mkt-report/pd-date") }}',
                    type: 'GET',
                    data: function (d) {
                        d.branch_id = $('#branch_id').val();
                        d.from_date = $('#from_date').val();
                        d.to_date = $('#to_date').val();
                    },
                },
                columns: [
                    {
                        data: 'Branch',
                        name: 'Branch',
                        orderable: false,
                        searchable: false,
                    },
                    {
                        data: 'ID',
                        name: 'ID',
                        orderable: false,
                        searchable: false,
                    },
                    {
                        data: 'PDID',
                        name: 'PDID',
                        orderable: false,
                        searchable: false,
                    },
                    {
                        data: 'DueDate',
                        name: 'DueDate',
                        orderable: false,
                        searchable: false,
                    },
                    {
                        data: 'NumDayDue',
                        name: 'NumDayDue',
                        orderable: false,
                        searchable: false,
                    },
                    {
                        data: 'ODStatus',
                        name: 'ODStatus',
                        orderable: false,
                        searchable: false,
                    },
                    {
                        data: 'TotODAmount',
                        name: 'TotODAmount',
                        orderable: false,
                        searchable: false,
                        render: function (data, type, row) {
                            return data ? parseFloat(data).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 }) : '0.00';
                        }
                    },
                    {
                        data: 'OutAmount',
                        name: 'OutAmount',
                        orderable: false,
                        searchable: false,
                        render: function (data, type, row) {
                            return data ? parseFloat(data).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 }) : '0.00';
                        }
                    },
                    {
                        data: 'PrincipalDue',
                        name: 'PrincipalDue',
                        orderable: false,
                        searchable: false,
                        render: function (data, type, row) {
                            return data ? parseFloat(data).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 }) : '0.00';
                        }
                    },
                    {
                        data: 'OutPriAmount',
                        name: 'OutPriAmount',
                        orderable: false,
                        searchable: false,
                        render: function (data, type, row) {
                            return data ? parseFloat(data).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 }) : '0.00';
                        }
                    },
                    {
                        data: 'InterestDue',
                        name: 'InterestDue',
                        orderable: false,
                        searchable: false,
                        render: function (data, type, row) {
                            return data ? parseFloat(data).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 }) : '0.00';
                        }
                    },
                    {
                        data: 'OutIntAmount',
                        name: 'OutIntAmount',
                        orderable: false,
                        searchable: false,
                        render: function (data, type, row) {
                            return data ? parseFloat(data).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 }) : '0.00';
                        }
                    },
                    {
                        data: 'PenaltyDue',
                        name: 'PenaltyDue',
                        orderable: false,
                        searchable: false,
                        render: function (data, type, row) {
                            return data ? parseFloat(data).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 }) : '0.00';
                        }
                    },
                    {
                        data: 'OutPenAmount',
                        name: 'OutPenAmount',
                        orderable: false,
                        searchable: false,
                        render: function (data, type, row) {
                            return data ? parseFloat(data).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 }) : '0.00';
                        }
                    },
                    {
                        data: 'ChargeDue',
                        name: 'ChargeDue',
                        orderable: false,
                        searchable: false,
                        render: function (data, type, row) {
                            return data ? parseFloat(data).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 }) : '0.00';
                        }
                    },
                    {
                        data: 'OutChgAmount',
                        name: 'OutChgAmount',
                        orderable: false,
                        searchable: false,
                        render: function (data, type, row) {
                            return data ? parseFloat(data).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 }) : '0.00';
                        }
                    },
                    {
                        data: 'NextRunDate',
                        name: 'NextRunDate',
                        orderable: false,
                        searchable: false,
                    },
                    {
                        data: 'OutAmountAS',
                        name: 'OutAmountAS',
                        orderable: false,
                        searchable: false,
                        render: function (data, type, row) {
                            return data ? parseFloat(data).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 }) : '0.00';
                        }
                    },
                    {
                        data: 'PrincipalDueAS',
                        name: 'PrincipalDueAS',
                        orderable: false,
                        searchable: false,
                        render: function (data, type, row) {
                            return data ? parseFloat(data).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 }) : '0.00';
                        }
                    },
                    {
                        data: 'OutPriAmountAS',
                        name: 'OutPriAmountAS',
                        orderable: false,
                        searchable: false,
                        render: function (data, type, row) {
                            return data ? parseFloat(data).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 }) : '0.00';
                        }
                    },
                    {
                        data: 'IntDueAmountAS',
                        name: 'IntDueAmountAS',
                        orderable: false,
                        searchable: false,
                        render: function (data, type, row) {
                            return data ? parseFloat(data).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 }) : '0.00';
                        }
                    },
                    {
                        data: 'OutIntAmountAS',
                        name: 'OutIntAmountAS',
                        orderable: false,
                        searchable: false,
                        render: function (data, type, row) {
                            return data ? parseFloat(data).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 }) : '0.00';
                        }
                    },
                    {
                        data: 'ChargeDueAS',
                        name: 'ChargeDueAS',
                        orderable: false,
                        searchable: false,
                        render: function (data, type, row) {
                            return data ? parseFloat(data).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 }) : '0.00';
                        }
                    },
                    {
                        data: 'OutChgAmountAS',
                        name: 'OutChgAmountAS',
                        orderable: false,
                        searchable: false,
                        render: function (data, type, row) {
                            return data ? parseFloat(data).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 }) : '0.00';
                        }
                    },
                    {
                        data: 'PenaltyDueAS',
                        name: 'PenaltyDueAS',
                        orderable: false,
                        searchable: false,
                        render: function (data, type, row) {
                            return data ? parseFloat(data).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 }) : '0.00';
                        }
                    },
                    {
                        data: 'OutPenAmountAS',
                        name: 'OutPenAmountAS',
                        orderable: false,
                        searchable: false,
                        render: function (data, type, row) {
                            return data ? parseFloat(data).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 }) : '0.00';
                        }
                    },
                    {
                        data: 'VATDue',
                        name: 'VATDue',
                        orderable: false,
                        searchable: false,
                        render: function (data, type, row) {
                            return data ? parseFloat(data).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 }) : '0.00';
                        }
                    },
                    {
                        data: 'OutVATAmount',
                        name: 'OutVATAmount',
                        orderable: false,
                        searchable: false,
                        render: function (data, type, row) {
                            return data ? parseFloat(data).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 }) : '0.00';
                        }
                    },
                    {
                        data: 'VATDueAS',
                        name: 'VATDueAS',
                        orderable: false,
                        searchable: false,
                        render: function (data, type, row) {
                            return data ? parseFloat(data).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 }) : '0.00';
                        }
                    },
                    {
                        data: 'OutVATAmountAS',
                        name: 'OutVATAmountAS',
                        orderable: false,
                        searchable: false,
                        render: function (data, type, row) {
                            return data ? parseFloat(data).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 }) : '0.00';
                        }
                    },
                    {
                        data: 'WFStatus',
                        name: 'WFStatus',
                        orderable: false,
                        searchable: false,
                    },
                ],
            });
            $('#tbl_pd_date').on('processing.dt', function (e, settings, processing) {
                if (processing) {
                    $('#loading-overlay').show();
                } else {
                    $('#loading-overlay').hide();
                }
            });
        }
    </script>
@endsection