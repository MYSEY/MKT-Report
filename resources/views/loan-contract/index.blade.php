@extends('layouts.admin')
@section('content')
    <div class="row mb-2">
        <div class="col-md-6">
            <h3 class="">Loan Contract Report</h3>
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
                        @if(Auth::user()->can('Loan Contract Export'))
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
                Loan Contract Report
            </h2>
        </div>
        <div class="panel-container show">
            <div class="panel-content">
                <div class="table-responsive">
                    <table id="tbl_loan_contract" class="table table-bordered table-hover table-striped">
                        <thead>
                            <tr>
                                <th>Branch</th>
                                <th>ID</th>
                                <th>LoanApplicationID</th>
                                <th>ContractCustomerID</th>
                                <th>Account</th>
                                <th>Currency</th>
                                <th>Amount</th>
                                <th>Disbursed</th>
                                <th>OutstandingAmount</th>
                                <th>DisbursedStat</th>
                                <th>ValueDate</th>
                                <th>Term</th>
                                <th>Cycle</th>
                                <th>MaturityDate</th>
                                <th>LoanProduct</th>
                                <th>Category</th>
                                <th>InterestRate</th>
                                <th>FreqType</th>
                                <th>Frequency</th>
                                <th>DeliqMode</th>
                                <th>LoanPurpose</th>
                                <th>ContractVB</th>
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
                $('#tbl_loan_contract').DataTable().ajax.reload(null, false);
            });
            $('.btn_excel').on('click', function () {
                const query = {
                    branch_id: $('#branch_id').val(),
                    from_date: toIso($('#from_date').val()),
                    to_date: toIso($('#to_date').val()),
                };
                const url = "{{ route('admin/mkt-report/loan-contract.download') }}?" + $.param(query);
                window.location.href = url;
            });
            dataTables();
        });

        function dataTables() {
            if ($.fn.DataTable.isDataTable('#tbl_loan_contract')) {
                $('#tbl_loan_contract').DataTable().destroy();
            }
            $('#tbl_loan_contract').DataTable({
                pageLength: 10,
                destroy: true,
                processing: true,
                serverSide: true,
                order: [[0, 'desc']],
                lengthMenu: [ [10, 25, 50, 100], [10, 25, 50, 100] ],
                ajax: {
                    url: '{{ URL("admin/mkt-report/loan-contract") }}',
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
                        data: 'LoanApplicationID',
                        name: 'LoanApplicationID',
                        orderable: false,
                        searchable: false,
                    },
                    {
                        data: 'ContractCustomerID',
                        name: 'ContractCustomerID',
                        orderable: false,
                        searchable: false,
                    },
                    {
                        data: 'Account',
                        name: 'Account',
                        orderable: false,
                        searchable: false,
                    },
                    {
                        data: 'Currency',
                        name: 'Currency',
                        orderable: false,
                        searchable: false,
                    },
                    {
                        data: 'Amount',
                        name: 'Amount',
                        orderable: false,
                        searchable: false,
                    },
                    {
                        data: 'Disbursed',
                        name: 'Disbursed',
                        orderable: false,
                        searchable: false,
                    },
                    {
                        data: 'OutstandingAmount',
                        name: 'OutstandingAmount',
                        orderable: false,
                        searchable: false,
                    },
                    {
                        data: 'DisbursedStat',
                        name: 'DisbursedStat',
                        orderable: false,
                        searchable: false,
                    },
                    {
                        data: 'ValueDate',
                        name: 'ValueDate',
                        orderable: false,
                        searchable: false,
                    },
                    {
                        data: 'Term',
                        name: 'Term',
                        orderable: false,
                        searchable: false,
                    },
                    {
                        data: 'Cycle',
                        name: 'Cycle',
                        orderable: false,
                        searchable: false,
                    },
                    {
                        data: 'MaturityDate',
                        name: 'MaturityDate',
                        orderable: false,
                        searchable: false,
                    },
                    {
                        data: 'LoanProduct',
                        name: 'LoanProduct',
                        orderable: false,
                        searchable: false,
                    },
                    {
                        data: 'Category',
                        name: 'Category',
                        orderable: false,
                        searchable: false,
                    },
                    {
                        data: 'InterestRate',
                        name: 'InterestRate',
                        orderable: false,
                        searchable: false,
                    },
                    {
                        data: 'FreqType',
                        name: 'FreqType',
                        orderable: false,
                        searchable: false,
                    },
                    {
                        data: 'Frequency',
                        name: 'Frequency',
                        orderable: false,
                        searchable: false,
                    },
                    {
                        data: 'DeliqMode',
                        name: 'DeliqMode',
                        orderable: false,
                        searchable: false,
                    },
                    {
                        data: 'LoanPurpose',
                        name: 'LoanPurpose',
                        orderable: false,
                        searchable: false,
                    },
                    {
                        data: 'ContractVB',
                        name: 'ContractVB',
                        orderable: false,
                        searchable: false,
                    },
                ],
            });
            $('#tbl_loan_contract').on('processing.dt', function (e, settings, processing) {
                if (processing) {
                    $('#loading-overlay').show();
                } else {
                    $('#loading-overlay').hide();
                }
            });
        }
    </script>
@endsection