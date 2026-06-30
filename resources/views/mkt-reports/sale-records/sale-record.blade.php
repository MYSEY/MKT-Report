@extends('layouts.admin')
@section('content')
    <div class="row">
        <div class="col-md-4">
            <h3 class="">Sale Record AS</h3>
            <h5 class=""><strong>As of month:</strong> <span class="currency_month">{{ date('Y-m') }}</span></h5>
            <h6><strong>Currency:</strong> <span class="currency_rate"></span></h6>
        </div>
    </div>
    {!! Toastr::message() !!}
    <div class="card mb-2">
        <div class="card-body">
            <div class="row filter-btn">
                <div class="col-sm-2 col-md-2">
                    <div class="form-group">
                        <input type="text" 
                            class="form-control datepicker_month" 
                            name="sale_date" 
                            id="sale_date" 
                            value=""
                            placeholder="mm/yyyy" 
                            readonly 
                            style="background-color: #fff;">
                    </div>
                </div>
                <div class="col-sm-2 col-md-2">
                    <div class="form-group">
                        <select class="select2 form-control btn-filter filter-branch" id="branch_id" data-select2-id="select2-data-2-c0n2" name="branch_id">
                            <option value="" data-select2-id="select2-data-2-c0n2">All Branch</option>
                            @foreach ($branchs as $item)
                                <option value="{{ $item->ID }}">
                                    {{ $item->ID }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                </div>
                <div class="col-sm-2 col-md-2">
                    <div class="form-group">
                        <input type="text"  placeholder="Category ID" name="category_id" class="form-control category_id">
                    </div>
                </div>
                <div class="col-sm-2 col-md-2">
                    <div class="form-group">
                        <input type="text"  placeholder="LC" name="reference"  class="form-control reference">
                    </div>
                </div>
                <div class="col-sm-4 col-md-4">
                    <div class="float-right">
                        <button type="button" class="btn btn-sm btn-outline-secondary btn-search mr-1" id="icon-search-download-reload">
                            <span class="btn-txt"><i class="fal fa-search"></i></span>
                            Search
                            <span class="loading-icon" style="display: none"><i class="fa fa-spinner fa-spin"></i></span>
                        </button>
                        @if(Auth::user()->can('Sale Record Export'))
                           <button type="button" class="btn btn-sm btn-info waves-effect waves-themed btn_excel mr-1">
                                <span class="btn-text-excel"><i class="fal fa-arrow-circle-down"></i></span>
                                <span id="btn-text-loading-excel-1" style="display: none"><i class="fa fa-spinner fa-spin"></i></span>
                                Excel
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
                Sale Record AS
            </h2>
        </div>
         <div class="panel-container show">
            <div class="panel-content">
                <div class="table-responsive">
                    <table id="tbl-sale-record" class="table table-bordered table-hover table-striped">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>TransactionDate</th>
                                <th>Branch</th>
                                <th>CategoryID</th>
                                <th>Currency</th>
                                <th>LoanType</th>
                                <th>ID</th>
                                <th>Name_KH</th>
                                <th>Name_EN</th>
                                <th>Type_of_Supply</th>
                                <th>Amount_KHR</th>
                                <th>Amount_USD</th>
                                <th>Total_Amount_KHR</th>
                                <th>Income_Tax_Rate_1%</th>
                                <th>Description</th>
                                <th>Acc_Method*</th>
                            </tr>
                        </thead>
                        <tbody>
                        </tbody>
                        <tfoot>
                            <tr>
                                <th colspan="10" class="text-right" style="text-align: right;">Total:</th>
                                <th id="sum_khr" class="text-right"></th>
                                <th id="sum_usd" class="text-right"></th>
                                <th id="sum_total_khr" class="text-right"></th>
                                <th id="sum_tax" class="text-right"></th>
                                <th colspan="2"></th>
                            </tr>
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
            $('.btn_excel').on('click', function() {
                const actionBTN = $(this).data("btn");
                let btn_export = "";
                if (actionBTN == "lc") {
                    btn_export = true;
                }
                let table = $('#tbl-sale-record').DataTable();
                let pageInfo = table.page.info();
                let start = pageInfo.start;
                let length = pageInfo.length;

                let date = $('input[name="sale_date"]').val() || '';
                let category_id = $('input[name="category_id"]').val() || '';
                let reference = $('input[name="reference"]').val() || '';
                let branch_id = $('select[name="branch_id"]').val();

                let exportUrl = '{{ url("admin/mkt-report/sale-record/download") }}' + 
                    `?date=${date}&category_id=${category_id}&reference=${reference}&branch_id=${branch_id}&lc=${btn_export}` +
                    `&start=${start}&length=${length}`;
                window.location.href = exportUrl;
            });

            $('.btn-search').on('click', function() {
                dataTables();
            });
            // dataTables();
        });

        function dataTables() {
            $('#loading-overlay').show();
            // Check if DataTable instance exists, then destroy it
            if ($.fn.DataTable.isDataTable('#tbl-sale-record')) {
                $('#tbl-sale-record').DataTable().clear().destroy();
            }
           $('#tbl-sale-record').DataTable({
                pageLength: 20,
                searching: false,
                destroy: true,
                processing: true,
                serverSide: true,
                scrollX: true, // បើកវិញប្រសិនបើ Column ច្រើនពេកហៀរចេញក្រៅ
                scrollY: '350px',
                scroller: false,
                order: [[1, 'desc']],
                lengthMenu: [ 
                    [20, 25, 50, 100, -1],
                    [20, 25, 50, 100, "All"]
                ],
                ajax: {
                    url: '{{ URL("admin/mkt-report/sale-record") }}',
                    type: 'GET',
                    data: function (d) {
                        d.date = $('input[name="sale_date"]').val();
                        d.category_id = $('input[name="category_id"]').val();
                        d.full_name = $('input[name="full_name"]').val();
                        d.reference = $('input[name="reference"]').val();
                        d.branch_id = $('select[name="branch_id"]').val();
                        
                    },
                    dataSrc: function (json) {
                        let currency = json.currency;
                        $(".currency_rate").text(currency+"៛");
                        $(".currency_month").text($('input[name="sale_date"]').val());
                        return json.data;
                    }
                },
                columns: [
                    {
                        data: null,
                        name: 'no',
                        orderable: false,
                        searchable: false,
                        className: 'text-center',
                        render: (data, type, row, meta) => meta.row + meta.settings._iDisplayStart + 1
                    },
                    { 
                        data: 'TransactionMonth', 
                        name: 'air.TransactionMonth', 
                        className: 'text-center',
                        render: function (data, type, row) {
                            if (!data) return '-';
                            let parts = data.split('-'); 
                            let year = parseInt(parts[0], 10);
                            let month = parseInt(parts[1], 10);
                            // 💡 ល្បិចរកថ្ងៃចុងក្រោយ៖ កំណត់ថ្ងៃទី 0 នៃខែបន្ទាប់ (month) វានឹងធ្លាក់មកថ្ងៃចុងក្រោយនៃខែបច្ចុប្បន្នវិញ
                            let lastDate = new Date(year, month, 0).getDate(); 
                            return String(lastDate).padStart(2, '0')+ '-' + String(month).padStart(2, '0') +'-'+ year;
                        }
                    },
                    {data: 'Branch', name:'air.Branch'},
                    { data: 'CategoryID', name: 'air.CategoryID'},
                    { data: 'Currency', name: 'air.Currency' },
                    { data: 'Description', name: 'ld.Description' },
                    { data: 'Reference', name: 'air.Reference' },
                    
                    // 💡 កែសម្រួលត្រង់នេះ៖ ប្តូរពី CUST.LastNameKh មកជា cust.LastNameKh (អក្សរតូចដូចក្នុង Backend)
                    { data: 'KhName', name: 'cm.LastNameKh' },
                    { data: 'EnName', name: 'cm.LastNameEn' },
                    
                    { data: null, render: () => 3, className: 'text-center', orderable: false, searchable: false },

                    // // Amount KHR
                    { 
                        data: 'Amount_KHR', 
                        name: 'Amount_KHR', 
                        className: 'text-right',
                        render: d => d != 0 ? Number(d).toLocaleString() + ' ៛' : '-'
                    },
                    // // Amount USD
                    { 
                        data: 'Amount_USD', 
                        name: 'Amount_USD', 
                        className: 'text-right',
                        render: d => d != 0 ? '$ ' + Number(d).toLocaleString(undefined, {minimumFractionDigits: 2}) : '-'
                    },
                    // // Total Amount KHR
                    { 
                        data: 'Total_Amount_KHR', 
                        name: 'Total_Amount_KHR', 
                        className: 'text-right font-weight-bold',
                        render: d => Number(d).toLocaleString() + ' ៛'
                    },
                    // // Income Tax 1%
                    { 
                        data: 'Income_Tax', 
                        name: 'Income_Tax', 
                        className: 'text-right',
                        render: d => Math.round(d).toLocaleString() + ' ៛'
                    },
                    { data: null, render: () => 'Loan Repayment', orderable: false, searchable: false },
                    { data: null, render: () => 0, className: 'text-center', orderable: false, searchable: false },
                ],

                footerCallback: function (row, data, start, end, display) {
                    const api = this.api();

                    // 💡 មុខងារជំនួយសម្រាប់បំប្លែងតម្លៃទៅជាលេខទោលសុទ្ធ (Raw Number) យកមកគណនាផ្ទាល់ភ្លាមៗ
                    const parseNum = i => typeof i === 'number' ? i : (i ? parseFloat(i) : 0);

                    // 💡 បូកសរុបតាម Column នីមួយៗ (ផ្អែកលើ Data Index 10, 11, 12, 13)
                    const totalKHR   = api.column(10, { page: 'current' }).data().reduce((a, b) => parseNum(a) + parseNum(b), 0);
                    const totalUSD   = api.column(11, { page: 'current' }).data().reduce((a, b) => parseNum(a) + parseNum(b), 0);
                    const totalMount = api.column(12, { page: 'current' }).data().reduce((a, b) => parseNum(a) + parseNum(b), 0);
                    const totalTax   = api.column(13, { page: 'current' }).data().reduce((a, b) => parseNum(a) + parseNum(b), 0);

                    // 💡 ជួសជុល៖ បោះទិន្នន័យចូលទៅក្នុង <th> តាមរយៈ ID ផ្ទាល់ ដាច់ខាតមិនវង្វេងជួរឡើយ
                    $('#sum_khr').html(totalKHR != 0 ? totalKHR.toLocaleString() + ' ៛' : '-');
                    $('#sum_usd').html(totalUSD != 0 ? '$ ' + totalUSD.toLocaleString(undefined, {minimumFractionDigits: 2}) : '-');
                    $('#sum_total_khr').html(totalMount != 0 ? totalMount.toLocaleString() + ' ៛' : '-');
                    $('#sum_tax').html(totalTax != 0 ? Math.round(totalTax).toLocaleString() + ' ៛' : '-');
                }
            });

            $('#tbl-sale-record').on('processing.dt', function (e, settings, processing) {
                if (processing) {
                    $('#loading-overlay').show();
                } else {
                    $('#loading-overlay').hide();
                }
            });
        }
    </script>
@endsection