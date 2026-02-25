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
    <div class="row">
        <div class="col-md-4">
            <h3 class="">Loan Detail Listing</h3>
            <h5 class="">{{ $data->SystemDate ?? 'N/A' }}</h5>
        </div>
    </div>
    <div class="row">
        <div class="col-xl-12">
            <div class="card mb-2">
                <div class="card-body">
                    <div class="row filter-btn">
                        <div class="col-sm-3 col-md-3 col-lg-3 col-xl-3">
                            <div class="form-group">
                                <input type="text" name="LCID" id="LCID" class="form-control" placeholder="Loan Contract ID">
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
                        <div class="col-sm-6 col-md-6 col-lg-6 col-xl-6">
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
                        Loan Detail Listing
                    </h2>
                </div>
                <div class="panel-container show">
                    <div class="panel-content">
                        <div class="table-responsive">
                            <table id="tbl_loan_detail" class="table table-bordered table-hover table-striped">
                                <thead>
                                    <tr>
                                        <th class="sorting stuck-scroll-3">ID</th>
                                        <th class="sorting stuck-scroll-3">CustomerID</th>
                                        <th class="sorting stuck-scroll-3">CustomerName</th>
                                        <th class="sorting">Branch</th>
                                        <th class="sorting">Gender</th>
                                        <th class="sorting">Address</th>
                                        <th class="sorting">Village</th>
                                        <th class="sorting">Commune</th>
                                        <th class="sorting">District</th>
                                        <th class="sorting">Province</th>
                                        <th class="sorting">Accoun#</th>
                                        <th class="sorting">Currency</th>
                                        <th class="sorting">Disbursed</th>
                                        <th class="sorting">LoanBalanceAS</th>
                                        <th class="sorting">OutstandingAmountAS</th>
                                        <th class="sorting">InterestRateAS</th>
                                        <th class="sorting">AccruedInterestAS</th>
                                        <th class="sorting">InterestEarned($)</th>
                                        <th class="sorting">TotalInterest</th>
                                        <th class="sorting">DisbursementDate</th>
                                        <th class="sorting">MaturityDate</th>
                                        <th class="sorting">LoanProduct</th>
                                        <th class="sorting">Term</th>
                                        <th class="sorting">Status</th>
                                        <th class="sorting">AssetClass</th>
                                        <th class="sorting">MoreThanOneYear</th>
                                        <th class="sorting">CBCSubSection(Loan)</th>
                                        <th class="sorting">CBCSubSection(Customer)</th>
                                        <th class="sorting">MACode</th>
                                        <th class="sorting">MADescription</th>
                                        <th class="sorting">LoanPurpose</th>
                                        <th class="sorting">Officer</th>
                                        <th class="sorting">IDType</th>
                                        <th class="sorting">IDNumber</th>
                                        <th class="sorting">LastPaymentDate</th>
                                        <th class="sorting">OverdueDays</th>
                                        <th class="sorting">OverdueDate</th>
                                        <th class="sorting">LoanType</th>
                                        <th class="sorting">LoanCharge(%)</th>
                                        <th class="sorting">ChargeEarned</th>
                                        <th class="sorting">ChargeUnearned</th>
                                        <th class="sorting">ScheduleType</th>
                                        <th class="sorting">CustomerOccupation</th>
                                        <th class="sorting">RestructuredCycle</th>
                                        <th class="sorting">AddressCode</th>
                                        <th class="sorting">CollateralID</th>
                                        <th class="sorting">CustomerPhoneNumber</th>
                                        <th class="sorting">LoanCycle</th>
                                        <th class="sorting">LoanAmountFIRS</th>
                                        <th class="sorting">OutstandingAmountFIRS</th>
                                        <th class="sorting">InterestRateFIRS</th>
                                        <th class="sorting">InterestPerDayFIRS</th>
                                        <th class="sorting">AccruedInterestFIRS</th>
                                        <th class="sorting">RegularCharge(%)</th>
                                        <th class="sorting">SubAmount</th>
                                        <th class="sorting">SubLoanPurpose</th>
                                        <th class="sorting">PartneredWith</th>
                                        <th class="sorting">RestructureType</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    
                                </tbody>
                            </table>
                        </div>
                    </div>
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
                    branch_id: $("#branch_id").val(),
                    LCID: $('#LCID').val()
                };
                let url = "{{ route('loan.detail.download') }}?" + $.param(query);
                window.location = url;
            });
            $('.btn-search').on('click', function() {
                $('#loading-overlay').hide();
                branch_id = $('#branch_id').val();
                $('#tbl_loan_detail').DataTable().ajax.reload(null, false);
            });
            $(".reset-btn").on("click", function() {
                $(this).prop('disabled', true);
                $(".btn-text-reset").hide();
                $("#btn-text-loading").css('display', 'block');
                window.location.replace("{{ URL('admin/report/loan/detail') }}");
            });
            dataTables();
        });

        function dataTables() {
            $('#loading-overlay').show();
            if ($.fn.DataTable.isDataTable('#tbl_loan_detail')) {
                $('#tbl_loan_detail').DataTable().clear().destroy();
            }
            $('#tbl_loan_detail').DataTable({
                pageLength: 10,
                destroy: true,
                processing: true,
                serverSide: true,
                scrollX: true,
                scrollY: '350px',
                scroller: false,
                order: [[0, 'desc']],
                lengthMenu: [ [10, 25, 50, 100], [10, 25, 50, 100] ],
                ajax: {
                    url: '{{ URL("admin/report/loan/detail") }}',
                    type: 'GET',
                    headers: {
                        'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                    },
                    data: function (d) {
                        d.LCID = $('#LCID').val();
                        d.branch_id = $('select[name="branch_id"]').val();
                    },
                },
                columns: [
                    { 
                        data: 'ID', 
                        name: 'ID',
                        className: 'stuck-scroll-3',
                        orderable: true,
                        searchable: true,
                    },
                    { 
                        data: 'ContractCustomerID', 
                        name: 'ContractCustomerID',
                        className: 'stuck-scroll-3',
                        orderable: true,
                        searchable: true,
                    },
                    { 
                        data: 'CustomerName', 
                        name: 'LastNameEn',
                        className: 'stuck-scroll-3',
                        orderable: true,
                        searchable: true,
                        render: function (data, type, row) {
                            return row.LastNameEn + ' ' + row.FirstNameEn;
                        }
                    },
                    { 
                        data: 'Branch', 
                        name: 'Branch',
                        orderable: true,
                        searchable: true,
                    },
                    { 
                        data: 'Gender', 
                        name: 'Gender',
                        orderable: true,
                        searchable: true,
                    },
                    { 
                        data: 'Address', 
                        name: 'Address',
                        orderable: true,
                        searchable: true,
                        render: function (data, type, row) {
                            return row.Street;
                        }
                    },
                    { 
                        data: 'Village', 
                        name: 'Village',
                        orderable: true,
                        searchable: true,
                    },
                    { 
                        data: 'Commune', 
                        name: 'Commune',
                        orderable: true,
                        searchable: true,
                    },
                    { 
                        data: 'District', 
                        name: 'District',
                        orderable: true,
                        searchable: true,
                    },
                    { 
                        data: 'Province', 
                        name: 'Province',
                        orderable: true,
                        searchable: true,
                    },
                    { 
                        data: 'Account', 
                        name: 'Account',
                        orderable: true,
                        searchable: true,
                    },
                    { 
                        data: 'Currency', 
                        name: 'Currency',
                        orderable: true,
                        searchable: true,
                    },
                    { 
                        data: 'Disbursed', 
                        name: 'Disbursed',
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
                        data: 'LoanBalanceAS', 
                        name: 'LoanBalanceAS',
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
                        data: 'OutstandingAmountAS', 
                        name: 'OutstandingAmountAS',
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
                        data: 'InterestRate', 
                        name: 'InterestRate',
                        orderable: true,
                        searchable: true,
                    },
                    { 
                        data: 'AccrInterest', 
                        name: 'AccrInterest',
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
                        data: 'IntIncEarned', 
                        name: 'IntIncEarned',
                        // name: 'AIRAS',
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
                        data: 'TotalInterest', 
                        name: 'TotalInterest',
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
                        data: 'ValueDate', 
                        name: 'ValueDate',
                        orderable: true,
                        searchable: true,
                        render: function (data) {
                            if (!data) return '';
                            const date = new Date(data);
                            const mm = String(date.getMonth() + 1).padStart(2, '0');
                            const dd = String(date.getDate()).padStart(2, '0');
                            const yyyy = date.getFullYear();
                            return dd + '-' + mm + '-' + yyyy;
                        }
                    },
                    { 
                        data: 'MaturityDate', 
                        name: 'MaturityDate',
                        orderable: true,
                        searchable: true,
                        render: function (data) {
                            if (!data) return '';

                            const date = new Date(data);
                            const mm = String(date.getMonth() + 1).padStart(2, '0');
                            const dd = String(date.getDate()).padStart(2, '0');
                            const yyyy = date.getFullYear();

                            return dd + '-' + mm + '-' + yyyy;

                        }
                    },
                    { 
                        data: 'LoanProduct', 
                        name: 'LoanProduct',
                        orderable: true,
                        searchable: true,
                        render: function (data, type, row) {
                            return row.LoanProduct + ' ' + row.LoanProductDes;
                        }
                    },
                    { 
                        data: 'Term', 
                        name: 'Term',
                        orderable: true,
                        searchable: true,
                    },
                    { 
                        data: 'DisbursedStat', 
                        name: 'DisbursedStat',
                        orderable: true,
                        searchable: true,
                    },
                    { 
                        data: 'AssetClass', 
                        name: 'AssetClass',
                        orderable: true,
                        searchable: true,
                    },
                    { 
                        data: 'MoreThanOneYear', 
                        name: 'MoreThanOneYear',
                        orderable: true,
                        searchable: true,
                    },
                    { 
                        data: 'CBCSubSection', 
                        name: 'CBCSubSection',
                        orderable: true,
                        searchable: true,
                    },
                    { 
                        data: 'CBCISSubSectionCuSt', 
                        name: 'CBCISSubSectionCuSt',
                        orderable: true,
                        searchable: true,
                    },
                    { 
                        data: 'MACode', 
                        name: 'MACode',
                        orderable: true,
                        searchable: true,
                    },
                    { 
                        data: 'MADes', 
                        name: 'MADes',
                        orderable: true,
                        searchable: true,
                    },
                    { 
                        data: 'LoanPurpose', 
                        name: 'LoanPurpose',
                        orderable: true,
                        searchable: true,
                    },
                    { 
                        data: 'ContractOfficerID', 
                        name: 'ContractOfficerID',
                        orderable: true,
                        searchable: true,
                    },
                    { 
                        data: 'IDType', 
                        name: 'IDType',
                        orderable: true,
                        searchable: true,
                    },
                    { 
                        data: 'IDNumber', 
                        name: 'IDNumber',
                        orderable: true,
                        searchable: true,
                    },
                    { 
                        data: 'LastPaymentDate', 
                        name: 'LastPaymentDate',
                        orderable: true,
                        searchable: true,
                        render: function (data) {
                            if (!data) return '';

                            const date = new Date(data);
                            const mm = String(date.getMonth() + 1).padStart(2, '0');
                            const dd = String(date.getDate()).padStart(2, '0');
                            const yyyy = date.getFullYear();

                            return dd + '-' + mm + '-' + yyyy;
                        }
                    },
                    { 
                        data: 'DueDay', 
                        name: 'DueDay',
                        orderable: true,
                        searchable: true,
                    },
                    { 
                        data: 'OverdueDate', 
                        name: 'OverdueDate',
                        orderable: true,
                        searchable: true,
                        render: function (data) {
                            if (!data) return '';

                            const date = new Date(data);
                            const mm = String(date.getMonth() + 1).padStart(2, '0');
                            const dd = String(date.getDate()).padStart(2, '0');
                            const yyyy = date.getFullYear();

                            return dd + '-' + mm + '-' + yyyy;
                        }
                    },
                    { 
                        data: 'LoanType', 
                        name: 'LoanType',
                        orderable: true,
                        searchable: true,
                    },
                    { 
                        data: 'LoanCharge', 
                        name: 'LoanCharge',
                        orderable: true,
                        searchable: true,
                        render: function (data) {
                            if (!data) return "";
                            return Number(data).toLocaleString(undefined, {
                                minimumFractionDigits: 1,
                                maximumFractionDigits: 1
                            });
                        }
                    },
                    { 
                        data: 'ChargeEarned', 
                        name: 'ChargeEarned',
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
                        data: 'ChargeUnearned', 
                        name: 'ChargeUnearned',
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
                        data: 'ScheduleType', 
                        name: 'ScheduleType',
                        orderable: true,
                        searchable: true,
                    },
                    { 
                        data: 'CustomerOccupation', 
                        name: 'CustomerOccupation',
                        orderable: true,
                        searchable: true,
                    },
                    { 
                        data: 'RestructuredCycle', 
                        name: 'RestructuredCycle',
                        orderable: true,
                        searchable: true,
                    },
                    { 
                        data: 'AddressCode', 
                        name: 'AddressCode',
                        orderable: true,
                        searchable: true,
                    },
                    { 
                        data: 'CollateralID', 
                        name: 'CollateralID',
                        orderable: true,
                        searchable: true,
                    },
                    { 
                        data: 'Mobile1', 
                        name: 'Mobile1',
                        orderable: true,
                        searchable: true,
                        render: function (data, type, row) {
                            return row.Mobile1 + ' ' + row.Mobile2;
                        }
                    },
                    { 
                        data: 'Cycle', 
                        name: 'Cycle',
                        orderable: true,
                        searchable: true,
                    },
                    { 
                        data: 'Amount', 
                        name: 'Amount',
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
                        data: 'OutstandingAmount', 
                        name: 'OutstandingAmount',
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
                        data: 'EIRRate', 
                        name: 'EIRRate',
                        orderable: true,
                        searchable: true,
                    },
                    { 
                        data: 'AccrIntPerDay', 
                        name: 'AccrIntPerDay',
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
                        data: 'AIRAS', 
                        name: 'AIRAS',
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
                        data: 'RegularCharge', 
                        name: 'RegularCharge',
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
                        data: 'SubAmount', 
                        name: 'SubAmount',
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
                        data: 'SubLoanPurpose', 
                        name: 'SubLoanPurpose',
                        orderable: true,
                        searchable: true,
                    },
                    { 
                        data: 'PartneredWith', 
                        name: 'PartneredWith',
                        orderable: true,
                        searchable: true,
                    },
                    { 
                        data: 'RestructureType', 
                        name: 'RestructureType',
                        orderable: true,
                        searchable: true,
                    },
                ],
                initComplete: function() {
                    $('#loading-overlay').hide();
                }
            });
            $('#tbl_loan_detail').on('processing.dt', function (e, settings, processing) {
                if (processing) {
                    $('#loading-overlay').show();
                } else {
                    $('#loading-overlay').hide();
                }
            });
        }
    </script>
@endsection