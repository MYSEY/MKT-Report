@extends('layouts.admin')
@section('content')
    <div class="row mb-2">
        <div class="col-md-6">
            <h3 class="">Verify Repayment Agent Detail Report</h3>
        </div>
        <div class="col-md-6" style="text-align: right;">
            <a href="{{ url('admin/mkt-report/veryfy/repayment/agent') }}" class="btn btn-sm btn-outline-secondary btn-search mr-1">Back</a>
        </div>
    </div>
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
                        @if(Auth::user()->can('Veryfy Repayment Agent Report Export'))
                            <button type="button" class="btn btn-sm btn-info waves-effect waves-themed mr-1" id="downloadToMorakot">
                                <span class="btn-text-excel"><i class="fal fa-arrow-circle-down"></i></span>
                                Download To Morakot
                                <span id="btn-text-loading-excel" style="display: none"><i class="fa fa-spinner fa-spin"></i></span>
                            </button>
                            <button type="button" class="btn btn-sm btn-info waves-effect waves-themed mr-1" id="downloadToBranch">
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
    <div id="panel-1" class="panel">
        <div class="panel-hdr">
            <h2>
                Verify Repayment Agent Detail Report
            </h2>
        </div>
        <div class="panel-container show">
            <div class="panel-content">
                <div class="table-responsive">
                    <table id="dt-basic-example" class="table table-bordered table-hover table-striped w-100">
                        <thead>
                            <tr>
                                <th>Branch</th>
                                <th>DrAccount</th>
                                <th>DrCategory</th>
                                <th>DrCurrency</th>
                                <th>CrAccount</th>
                                <th>CrCategory</th>
                                <th>CrCurrency</th>
                                <th>Amount</th>
                                <th>LCYAmount</th>
                                <th>ExchangeRate</th>
                                <th>Transaction</th>
                                <th>TranDate</th>
                                <th>Reference</th>
                                <th>Note</th>
                                <th>DrGLKey</th>
                                <th>CrGLKey</th>
                                <th>Module</th>
                                <th>Officer</th>
                                <th>DisbursementList</th>
                                <th>TargetBranch</th>
                                <th>TargetBranchDrCr</th>
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
        $(function(){
            dataTables();
            $('.btn-search').on('click', function() {
                $('#dt-basic-example').DataTable().ajax.reload();
            });
            $("#downloadToMorakot").on("click", function () {
                let id = "{{ $id }}";
                let query = {
                    branch_id: $("#branch_id").val()
                };
                let url = "{{ URL::to('admin/mkt-report/veryfy/repayment/agent/download/morakot') }}"+ "/" + id + "?" + $.param(query);
                window.location.href = url;
            });
            $("#downloadToBranch").on("click", function () {
                let id = "{{ $id }}";
                let query = {
                    branch_id: $("#branch_id").val()
                };
                let url = "{{ URL::to('admin/mkt-report/veryfy/repayment/agent/download/branch') }}"+ "/" + id + "?" + $.param(query);
                window.location.href = url;
            });
        });
        function dataTables() {
            if ($.fn.DataTable.isDataTable('#dt-basic-example')) {
                $('#dt-basic-example').DataTable().destroy();
            }

            $('#dt-basic-example').DataTable({
                pageLength: 10,
                destroy: true,
                processing: true,
                serverSide: true,
                order: [[12, 'asc']],
                ajax: {
                    url: '{{ url("admin/mkt-report/veryfy/repayment/agent/detail") }}' + '/{{ $id }}',
                    type: 'GET',
                    data: function (d) {
                        d.branch_id = $('#branch_id').val();
                    },
                },
                columns: [
                    { data: 'Branch',           name: 'Branch' },
                    { data: 'DrAccount',        name: 'DrAccount' },
                    { data: 'DrCategory',       name: 'DrCategory' },
                    { data: 'DrCurrency',       name: 'DrCurrency' },
                    { data: 'CrAccount',        name: 'CrAccount' },
                    { data: 'CrCategory',       name: 'CrCategory' },
                    { data: 'CrCurrency',       name: 'CrCurrency' },
                    { 
                        data: 'Amount',           
                        name: 'Amount',
                        render: function (data) {
                            if (!data) return "";
                            return Number(data).toLocaleString(undefined, {
                                minimumFractionDigits: 2,
                                maximumFractionDigits: 2
                            });
                        }
                    },
                    { data: 'LCYAmount',        name: 'LCYAmount' },
                    { data: 'ExchangeRate',     name: 'ExchangeRate' },
                    { data: 'Transaction',      name: 'Transaction' },
                    { data: 'TranDate',         name: 'TranDate' },
                    { data: 'Reference',        name: 'Reference' },
                    { data: 'Note',             name: 'Note' },
                    { data: 'DrGLKey',          name: 'DrGLKey' },
                    { data: 'CrGLKey',          name: 'CrGLKey' },
                    { data: 'Module',           name: 'Module' },
                    { data: 'Officer',          name: 'Officer' },
                    { data: 'DisbursementList', name: 'DisbursementList' },
                    { data: 'TargetBranch',     name: 'TargetBranch' },
                    { data: 'TargetBranchDrCr', name: 'TargetBranchDrCr' },
                ],
            });
        }
    </script>
@endsection