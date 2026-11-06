@extends('layouts.admin')
@section('content')
    <div class="row mb-2">
        <div class="col-md-6">
            <h3 class="">Journal De Report</h3>
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
                        @if(Auth::user()->can('Loan Detail Export'))
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
    {!! app('toastr')->message() !!}
    <div id="panel-1" class="panel">
        <div class="panel-hdr">
            <h2>
                Journal De Report
            </h2>
        </div>
        <div class="panel-container show">
            <div class="panel-content">
                <div class="table-responsive">
                    <table id="tbl_journal_de" class="table table-bordered table-hover table-striped">
                        <thead>
                            <tr>
                                <th>Branch</th>
                                <th>TransactionDate</th>
                                <th>ID</th>
                                <th>Description</th>
                                <th>CategoryID</th>
                                <th>GL_KEYS</th>
                                <th>Currency</th>
                                <th>DebitCredit</th>
                                <th>Amount</th>
                                <th>PrevBalance</th>
                                <th>Module</th>
                                <th>Transaction</th>
                                <th>Reference</th>
                                <th>LCYAmount</th>
                                <th>LCYPrevBalance</th>
                                <th>UserReference</th>
                                <th>Account</th>
                                <th>CustomerID</th>
                                <th>Status</th>
                                <th>Inputter</th>
                                <th>Createdon</th>
                                <th>Authorizer</th>
                                <th>Authorizeon</th>
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
                $('#tbl_journal_de').DataTable().ajax.reload(null, false);
            });
            $(".btn_excel").on("click", function() {
                let query = {
                    branch_id: $("#branch_id").val(),
                    from_date: $("#from_date").val(),
                    to_date: $("#to_date").val(),
                };
                var url = "{{URL::to('admin/mkt-report/journal-de/download')}}?" + $.param(query)
                window.location = url;
            });
            dataTables();
        });

        function dataTables() {
            if ($.fn.DataTable.isDataTable('#tbl_journal_de')) {
                $('#tbl_journal_de').DataTable().destroy();
            }
            $('#tbl_journal_de').DataTable({
                pageLength: 10,
                destroy: true,
                processing: true,
                serverSide: true,
                order: [[0, 'desc']],
                lengthMenu: [ [10, 25, 50, 100], [10, 25, 50, 100] ],
                ajax: {
                    url: '{{ URL("admin/mkt-report/journal-de") }}',
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
                        data: 'TransactionDate',
                        name: 'TransactionDate',
                        orderable: false,
                        searchable: false,
                    },
                    { 
                        data: 'ID', 
                        name: 'ID',
                    },
                    { 
                        data: 'Description', 
                        name: 'Description',
                    },
                    { 
                        data: 'CategoryID', 
                        name: 'CategoryID',
                    },
                    { 
                        data: 'GL_KEYS', 
                        name: 'GL_KEYS',
                    },
                    { 
                        data: 'Currency', 
                        name: 'Currency',
                    },
                    { 
                        data: 'DebitCredit', 
                        name: 'DebitCredit',
                    },
                    { 
                        data: 'Amount', 
                        name: 'Amount',
                    },
                    { 
                        data: 'PrevBalance', 
                        name: 'PrevBalance',
                    },
                    { 
                        data: 'Module', 
                        name: 'Module',
                    },
                    { 
                        data: 'Transaction', 
                        name: 'Transaction',
                    },
                    { 
                        data: 'Reference', 
                        name: 'Reference',
                    },
                    { 
                        data: 'LCYAmount', 
                        name: 'LCYAmount',
                    },
                    { 
                        data: 'LCYPrevBalance', 
                        name: 'LCYPrevBalance',
                    },
                    { 
                        data: 'UserReference', 
                        name: 'UserReference',
                    },
                    { 
                        data: 'Account', 
                        name: 'Account',
                    },
                    { 
                        data: 'CustomerID', 
                        name: 'CustomerID',
                    },
                    { 
                        data: 'Status', 
                        name: 'Status',
                    },
                    { 
                        data: 'Inputter', 
                        name: 'Inputter',
                    },
                    { 
                        data: 'Createdon', 
                        name: 'Createdon',
                    },
                    { 
                        data: 'Authorizer', 
                        name: 'Authorizer',
                    },
                    { 
                        data: 'Authorizeon', 
                        name: 'Authorizeon',
                    }
                ],
            });
            $('#tbl_journal_de').on('processing.dt', function (e, settings, processing) {
                if (processing) {
                    $('#loading-overlay').show();
                } else {
                    $('#loading-overlay').hide();
                }
            });
        }
    </script>
@endsection