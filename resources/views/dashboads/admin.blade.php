@extends('layouts.admin')
@section('content')
    <style>
        .card {
            width: 600px;
            /* background: #fff; */
            border-radius: 6px;
            box-shadow: 0 2px 6px rgba(0,0,0,0.1);
            overflow: hidden;
        }

        .card-header {
            background: #d9edf7;
            display: flex;
            justify-content: space-between;
            align-items: center;
            font-weight: bold;
        }

        .refresh {
            cursor: pointer;
            font-size: 18px;
        }

        .card-body {
            padding: 25px;
            text-align: center;
        }

        .top-section {
            display: flex;
            justify-content: space-around;
            margin-bottom: 30px;
        }

        .box {
            text-align: center;
        }

        .box .label {
            font-size: 13px;
            color: #666;
            margin-bottom: 8px;
        }

        .box .value {
            font-size: 36px;
            font-weight: bold;
            color: #444;
        }

        .portfolio-title {
            font-size: 14px;
            color: #666;
            margin-bottom: 20px;
        }

        .bottom-section {
            display: flex;
            justify-content: center;
            align-items: center;
        }

        .currency {
            width: 50%;
            text-align: center;
        }

        .currency .type {
            font-weight: bold;
            color: #555;
            margin-bottom: 5px;
        }

        .currency .amount {
            font-size: 18px;
            font-weight: bold;
            color: #333;
        }

        .divider {
            width: 1px;
            height: 40px;
            background: #ccc;
        }
    </style>

    <div class="subheader">
        <h1 class="subheader-title">
            <i class='subheader-icon fal fa-chart-area'></i> <span class='fw-300'>Dashboard</span>
        </h1>
    </div>
    @if(Auth::user()->can('Dashboard Branch Productivity'))
        <div class="row mb-2">
            <div class="col-md-6 col-sm-6 col-xl-3">
                <div class="card">
                    <div class="card-header">
                        <span>Branch Productivity</span>
                        <span class="refresh">⟳</span>
                    </div>
                
                    <div class="card-body">
                        <div class="top-section">
                            <div class="box">
                                <div class="label"># OF CUSTOMER</div>
                                <div class="value">{{ number_format($customer) }}</div>
                            </div>
                
                            <div class="box">
                                <div class="label"># LOAN CONTRACT</div>
                                <div class="value">{{ number_format($loan) }}</div>
                            </div>
                        </div>
                
                        <div class="portfolio-title">TOTAL PORTFOLIO</div>
                
                        <div class="bottom-section">
                            <div class="currency">
                                <div class="type">KHR</div>
                                <div class="amount">{{ number_format($data->khr ?? 0, 0) }}</div>
                            </div>
                
                            <div class="divider"></div>
                
                            <div class="currency">
                                <div class="type">USD</div>
                                <div class="amount">{{ number_format($data->usd ?? 0, 2) }}</div>
                            </div>
                        </div>
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