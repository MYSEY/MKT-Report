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
                
                        {{-- <div class="portfolio-title">TOTAL PORTFOLIO</div> --}}
                
                        {{-- <div class="bottom-section">
                            <div class="currency">
                                <div class="type">KHR</div>
                                <div class="amount">{{ number_format($data->khr ?? 0, 0) }}</div>
                            </div>
                
                            <div class="divider"></div>
                
                            <div class="currency">
                                <div class="type">USD</div>
                                <div class="amount">{{ number_format($data->usd ?? 0, 2) }}</div>
                            </div>
                        </div> --}}
                    </div>
                </div>
            </div>
        </div>
    @endif

    {{-- <div class="row">
        <div class="col-sm-6 col-xl-3">
            <div class="p-3 bg-primary-300 rounded overflow-hidden position-relative text-white mb-g">
                <div class="">
                    <h3 class="display-4 d-block l-h-n m-0 fw-500">
                        00
                        <small class="m-0 l-h-n">users signed up</small>
                    </h3>
                </div>
                <i class="fal fa-user position-absolute pos-right pos-bottom opacity-15 mb-n1 mr-n1" style="font-size:6rem"></i>
            </div>
        </div>
        <div class="col-sm-6 col-xl-3">
            <div class="p-3 bg-warning-400 rounded overflow-hidden position-relative text-white mb-g">
                <div class="">
                    <h3 class="display-4 d-block l-h-n m-0 fw-500">
                        00
                        <small class="m-0 l-h-n">Visual Index Figure</small>
                    </h3>
                </div>
                <i class="fal fa-gem position-absolute pos-right pos-bottom opacity-15  mb-n1 mr-n4" style="font-size: 6rem;"></i>
            </div>
        </div>
        <div class="col-sm-6 col-xl-3">
            <div class="p-3 bg-success-200 rounded overflow-hidden position-relative text-white mb-g">
                <div class="">
                    <h3 class="display-4 d-block l-h-n m-0 fw-500">
                        00
                        <small class="m-0 l-h-n">Offset Balance Ratio</small>
                    </h3>
                </div>
                <i class="fal fa-lightbulb position-absolute pos-right pos-bottom opacity-15 mb-n5 mr-n6" style="font-size: 8rem;"></i>
            </div>
        </div>
        <div class="col-sm-6 col-xl-3">
            <div class="p-3 bg-info-200 rounded overflow-hidden position-relative text-white mb-g">
                <div class="">
                    <h3 class="display-4 d-block l-h-n m-0 fw-500">
                        00
                        <small class="m-0 l-h-n">Product level increase</small>
                    </h3>
                </div>
                <i class="fal fa-globe position-absolute pos-right pos-bottom opacity-15 mb-n1 mr-n4" style="font-size: 6rem;"></i>
            </div>
        </div>
    </div> --}}
@endsection