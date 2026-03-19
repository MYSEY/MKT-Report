@extends('layouts.admin')
@section('content')
    <div class="row">
        <div class="col-xl-12">
            <div id="panel-1" class="panel">
                <div class="panel-hdr">
                    <h2>
                        Menu
                    </h2>
                </div>
                <div class="panel-container show">
                    <div class="panel-content">
                        <!-- datatable start -->
                        <table id="tbl_suer" class="table table-bordered table-hover table-striped w-100">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Name</th>
                                    <th>Active</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td>1</td>
                                    <td>FinanceDpt</td>
                                    <td>edit</td>
                                </tr>
                                <tr>
                                    <td>2</td>
                                    <td>CreditDpt</td>
                                    <td>edit</td>
                                </tr>
                                <tr>
                                    <td>3</td>
                                    <td>HRAdmDpt</td>
                                    <td>edit</td>
                                </tr>
                            </tbody>
                        </table>
                        <!-- datatable end -->
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
@section('script')
    
@endsection