@extends('layouts.admin')
@section('content')
    <style>
        .co-performance-wrapper {
            background: #fff;
            padding: 15px;
            border-radius: 8px;
            border: 1px solid #e6e6e6;
        }

        .table-title {
            font-size: 20px;
            font-weight: bold;
            padding-bottom: 12px;
            color: #1a237e;
        }

        /* Table Header */
        .table-header th {
            background: #1a237e;
            color: white;
            text-align: center;
            font-size: 13px;
            white-space: nowrap;
        }

        /* Table Footer */
        .table-footer td {
            background: #efefef;
            font-weight: bold;
            font-size: 13px;
        }

        /* Sticky columns */
        .sticky-col-1 {
            position: sticky;
            left: 0;
            background: white;
            z-index: 7;
        }

        .sticky-col-2 {
            position: sticky;
            left: 110px;
            background: white;
            z-index: 7;
        }

        /* General Table Style */
        .co-table th,
        .co-table td {
            vertical-align: middle !important;
            font-size: 13px;
            white-space: nowrap;
        }

        .text-right {
            text-align: right !important;
        }
         /** class scroll 3 bth-child */
        thead th.stuck-scroll-3 {
            background: #fff;
            position: sticky !important;
            left: 0 !important;
            z-index: 1 !important;
        }
        thead th.stuck-scroll-3:nth-child(3) {
            left:  80px !important;
        }
        tbody td.stuck-scroll-3 {
            background: #fff;
            position: sticky;
            left: 0;
            z-index: 1;
        }
        tbody td.stuck-scroll-3:nth-child(3) {
            left: 84px;
        }
    </style>
    <h3 class="breadcrumb page-breadcrumb">CO Performance Report {{ $data->SystemDate ?? 'N/A' }}</h3>
    {!! Toastr::message() !!}
    <div class="card mb-2">
        <div class="card-body">
            <div class="row filter-btn">
                <div class="col-sm-4 col-md-4 col-lg-4 col-xl-4">
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
                <div class="col-sm-8 col-md-8">
                    <div class="float-right">
                        <button type="button" class="btn btn-sm btn-outline-secondary btn-search mr-1" data-dismiss="modal" id="icon-search-download-reload">
                            <span class="btn-txt"><i class="fal fa-search"></i></span>
                            Search
                            <span class="loading-icon" style="display: none"><i class="fa fa-spinner fa-spin"></i></span>
                        </button>
                        <button type="button" class="btn btn-sm btn-outline-secondary btn_excel mr-1" id="icon-search-download-reload">
                            <span class="btn-text-excel"><i class="fal fa-arrow-circle-down"></i></span>
                            Excel
                            <span id="btn-text-loading-excel" style="display: none"><i class="fa fa-spinner fa-spin"></i></span>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div id="panel-1" class="panel">
        <div class="panel-hdr">
            <h2>
                CO Performance
            </h2>
        </div>
        <div class="panel-container show">
            <div class="panel-content">
                <div class="table-responsive">
                    <table id="tbl_co_performance" class="table table-bordered table-hover table-striped">
                        <thead>
                            <tr>
                                <th>COID</th>
                                <th>COName</th>
                                <th>Currency</th>
                                <th>#Borrowers</th>
                                <th>#Loans</th>
                                <th>DisbursedAmt</th>
                                <th>OustandingAmt</th>
                                <th>LoanBalance</th>
                                <th>#PARs</th>
                                <th>PARAmt</th>
                                <th>PARRate</th>
                                <th>PDPrincipal</th>
                                <th>PDInterest</th>
                                <th>PDPenalty</th>
                                <th>ArrearRate</th>
                                <th>#Loans</th>
                                <th>OustandingAmt</th>
                                <th>#PARs</th>
                                <th>PARAmt</th>
                                <th>PARRate</th>
                            </tr>
                        </thead>
                        <tbody>
                        </tbody>
                        {{-- <tfoot>
                            <tr class="text-bold cls-border odd">
                                <td class="text-left"></td> 
                                <td class="text-left"></td>
                                <td class="text-left"></td>
                                <td class="text-right"></td>
                                <td class="text-right"></td>
                                <td class="text-right"></td>
                                <td class="text-right"></td>
                                <td class="text-right"></td>
                                <td class="text-right"></td>
                                <td class="text-right"></td>
                                <td class="text-right"></td>
                                <td class="text-right"></td>
                                <td class="text-right"></td>
                                <td class="text-right"></td>
                                <td class="text-right"></td>
                            </tr>
                        </tfoot> --}}
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
                    branch_id: $("#branch_id").val()
                };
                var url = "{{URL::to('admin/report/co-performance/download')}}?" + $.param(query)
                window.location = url;
            });
            // Reload only (DON'T destroy/reinit)
            $('.btn-search').on('click', function() {
                $('#loading-overlay').hide();
                branch_id = $('#branch_id').val();
                $('#tbl_co_performance').DataTable().ajax.reload(null, false);
            });
            $(".reset-btn").on("click", function() {
                $(this).prop('disabled', true);
                $(".btn-text-reset").hide();
                $("#btn-text-loading").css('display', 'block');
                window.location.replace("{{ URL('admin/report/co-performance') }}");
            });
            // Initialize only once
            dataTables();
        });

        function dataTables() {
            $('#loading-overlay').show();
            // Check if DataTable instance exists, then destroy it
            if ($.fn.DataTable.isDataTable('#tbl_co_performance')) {
                $('#tbl_co_performance').DataTable().clear().destroy();
            }
            $('#tbl_co_performance').DataTable({
                pageLength: 10,
                destroy: true,
                processing: true,
                serverSide: true,
                scrollX: true,
                scrollY: '500px',
                scroller: false,
                order: [[0, 'desc']],
                lengthMenu: [ [10, 25, 50, 100], [10, 25, 50, 100] ],
                ajax: {
                    url: '{{ URL("admin/report/co-performance") }}',
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
                        data: 'ContractOfficerID', 
                        name: 'ContractOfficerID',
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
                        data: 'Currency', 
                        name: 'Currency',
                        className: 'stuck-scroll-3',
                    },
                    {
                        data: 'TotalBorrowers', 
                        name: 'TotalBorrowers',
                        orderable: true,
                        searchable: true,
                    },
                    {
                        data: 'TotalLoans', 
                        name: 'TotalLoans',
                        orderable: true,
                        searchable: true,
                    },
                    {
                        data: 'TotalDisbursed', 
                        name: 'TotalDisbursed',
                        orderable: true,
                        searchable: true,
                        render: function (data) {
                            if (!data) return "";
                            return Number(data).toLocaleString(undefined, {
                                minimumFractionDigits: 2,
                                maximumFractionDigits: 2
                            });
                        }
                    },
                    {
                        data: 'TotalOutstanding', 
                        name: 'TotalOutstanding',
                        orderable: true,
                        searchable: true,
                        render: function (data) {
                            if (!data) return "";
                            return Number(data).toLocaleString(undefined, {
                                minimumFractionDigits: 2,
                                maximumFractionDigits: 2
                            });
                        }
                    },
                    {
                        data: 'TotalLoanBalanceAs', 
                        name: 'TotalLoanBalanceAs',
                        orderable: true,
                        searchable: true,
                        render: function (data) {
                            if (!data) return "";
                            return Number(data).toLocaleString(undefined, {
                                minimumFractionDigits: 2,
                                maximumFractionDigits: 2
                            });
                        }
                    },
                    {
                        data: 'Pars', 
                        name: 'Pars',
                        orderable: true,
                        searchable: true,
                    },
                    { 
                        data: 'ParAmount', 
                        name: 'ParAmount',
                        orderable: true,
                        searchable: true,
                        render: function (data) {
                            if (!data) return "0";
                            return Number(data).toLocaleString(undefined, {
                                minimumFractionDigits: 2,
                                maximumFractionDigits: 2
                            });
                        }
                    },
                    { 
                        data: 'parRate', 
                        name: 'parRate',
                        orderable: true,
                        searchable: true,
                        render: function (data, type, row) {
                            if (data === null || data === undefined || data === "") {
                                return "";
                            }

                            return Number(data).toLocaleString(undefined, {
                                minimumFractionDigits: 2,
                                maximumFractionDigits: 2
                            }) + ' %';
                        }
                    },
                    { 
                        data: 'TotalPDPrincipal', 
                        name: 'TotalPDPrincipal',
                        orderable: true,
                        searchable: true,
                        render: function (data) {
                            if (!data) return "0";
                            return Number(data).toLocaleString(undefined, {
                                minimumFractionDigits: 2,
                                maximumFractionDigits: 2
                            });
                        }
                    },
                    { 
                        data: 'TotalPDInterest', 
                        name: 'TotalPDInterest',
                        orderable: true,
                        searchable: true,
                        render: function (data) {
                            if (!data) return "0";
                            return Number(data).toLocaleString(undefined, {
                                minimumFractionDigits: 2,
                                maximumFractionDigits: 2
                            });
                        }
                    },
                    { 
                        data: 'TotalPDPenalty', 
                        name: 'TotalPDPenalty',
                        orderable: true,
                        searchable: true,
                        render: function (data) {
                            if (!data) return "0";
                            return Number(data).toLocaleString(undefined, {
                                minimumFractionDigits: 2,
                                maximumFractionDigits: 2
                            });
                        }
                    },
                    { 
                        data: 'ArrearRate', 
                        name: 'ArrearRate',
                        orderable: true,
                        searchable: true,
                        render: function (data, type, row) {
                            if (data === null || data === undefined || data === "") {
                                return "";
                            }

                            return Number(data).toLocaleString(undefined, {
                                minimumFractionDigits: 2,
                                maximumFractionDigits: 2
                            }) + ' %';
                        }
                    },
                    { 
                        data: 'Loans', 
                        name: 'Loans',
                        orderable: true,
                        searchable: true,
                    },
                    { 
                        data: 'OutstandingAmt', 
                        name: 'OutstandingAmt',
                        orderable: true,
                        searchable: true,
                        render: function (data) {
                            if (!data) return "0.00";
                            return Number(data).toLocaleString(undefined, {
                                minimumFractionDigits: 2,
                                maximumFractionDigits: 2
                            });
                        }
                    },
                    { 
                        data: 'OutPARs', 
                        name: 'OutPARs',
                        orderable: true,
                        searchable: true,
                    },
                    { 
                        data: 'ParAmtAS', 
                        name: 'ParAmtAS',
                        orderable: true,
                        searchable: true,
                        render: function (data) {
                            if (!data) return "0.00";
                            return Number(data).toLocaleString(undefined, {
                                minimumFractionDigits: 2,
                                maximumFractionDigits: 2
                            });
                        }
                    },
                    { 
                        data: 'OutPARRate', 
                        name: 'OutPARRate',
                        orderable: true,
                        searchable: true,
                        render: function (data, type, row) {
                            if (data === null || data === undefined || data === "") {
                                return "";
                            }

                            return Number(data).toLocaleString(undefined, {
                                minimumFractionDigits: 0,
                                maximumFractionDigits: 0
                            }) + ' %';
                        }
                    },
                ],
                rowCallback: function (row, data) {
                    if (data.subtotal_row) {
                        $('td', row).css({
                            "font-weight": "bold",
                            "color": "#080808",
                            "font-size": "14px",
                            "font-family": '"Khmer Battambang", sans-serif',
                        });
                    }
                },
                initComplete: function() {
                    $('#loading-overlay').hide();
                },
            });
            $('#tbl_co_performance').on('processing.dt', function (e, settings, processing) {
                if (processing) {
                    $('#loading-overlay').show();
                } else {
                    $('#loading-overlay').hide();
                }
            });
        }
        function numberFormat(num) {
            if (!num) return '';
            return Number(num).toLocaleString(undefined, {
                minimumFractionDigits: 2,
                maximumFractionDigits: 2
            });
        }
    </script>
@endsection