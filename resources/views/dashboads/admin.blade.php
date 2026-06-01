@extends('layouts.admin')
@section('content')
    <style>
        .card-header {
            background: #9F2E32;
            display: flex;
            justify-content: space-between;
            align-items: center;
            font-weight: bold;
        }

        .label {
            font-size: 13px;
            font-weight: bold;
        }

        .portfolio-title {
            font-size: 14px;
            color: #666;
            margin-bottom: 20px;
        }
    </style>
    <div class="subheader">
        <h1 class="subheader-title">
            <i class='subheader-icon fal fa-chart-area'></i> <span class='fw-500'>Dashboard</span>
        </h1>
    </div>
    @if(Auth::user()->can('Dashboard Branch Productivity'))
        <div class="row">
            <div class="col-md-4">
                <div class="card">
                    <div class="card-header">
                        <span style="color: white;">Branch Productivity</span>
                    </div>
                    <div class="card-body">
                        <div class="row mb-2">
                            <div class="col-md-6" style="text-align: left;">
                                <div class="label"># CUSTOMER</div>
                            </div>
                            <div class="col-md-6" style="text-align: right;">
                                <div class="label">{{ number_format($customer) }}</div>
                            </div>
                        </div>
                        <hr>
                        <div class="row mb-2">
                            <div class="col-md-6" style="text-align: left;">
                                <div class="label"># LOAN</div>
                            </div>
                            <div class="col-md-6" style="text-align: right;">
                                <div class="label">{{ number_format($loan) }}</div>
                            </div>
                        </div>
                        <div class="row mb-2">
                            <div class="col-md-6">
                                <div class="type">KHR</div>
                            </div>
                            <div class="col-md-6" style="text-align: right;">
                                <div class="amount">{{ number_format($data->khr ?? 0, 0) }}</div>
                            </div>
                        </div>
                        <div class="row mb-2">
                            <div class="col-md-6">
                                <div class="type">USD</div>
                            </div>
                            <div class="col-md-6" style="text-align: right;">
                                <div class="amount">{{ number_format($data->usd ?? 0, 2) }}</div>
                            </div>
                        </div>
                        <hr>
                        <div class="row mb-2">
                            <div class="col-md-6" style="text-align: left;">
                                <div class="label"># PARs 30+</div>
                            </div>
                            <div class="col-md-6" style="text-align: right;">
                                <div class="label">30%</div>
                            </div>
                        </div>
                        <hr>
                        <div class="row mb-2">
                            <div class="col-md-6" style="text-align: left;">
                                <div class="label"># Profitble</div>
                            </div>
                            <div class="col-md-6" style="text-align: right;">
                                <div class="label">30%</div>
                            </div>
                        </div>
                        <hr>
                        <div class="row mb-2">
                            <div class="col-md-6" style="text-align: left;">
                                <div class="label"># NPL</div>
                            </div>
                            <div class="col-md-6" style="text-align: right;">
                                <div class="label">30%</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    @endif
@endsection