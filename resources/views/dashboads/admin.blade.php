@extends('layouts.admin')

{{-- <style>
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
</style> --}}
@section('content')
    <!-- CDN Tailwind CSS & Lucide Icons -->
    <script src="{{ asset('/admins/js/tailwindcss.js') }}"></script>
    <div class="subheader mb-4">
        <h1 class="subheader-title">
            <i class='subheader-icon fal fa-chart-area'></i> <span class='fw-500'>Dashboard</span>
        </h1>
    </div>
    @if(Auth::user()->can('Dashboard Branch Productivity'))
        {{-- <div class="row">
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
                                <div class="label">{{ $finalParRate }}%</div>
                            </div>
                        </div>
                        <hr>
                        <div class="row mb-2">
                            <div class="col-md-6" style="text-align: left;">
                                <div class="label"># Profitble</div>
                            </div>
                            <div class="col-md-6" style="text-align: right;">
                                <div class="label">{{ $Profitble }}</div>
                            </div>
                        </div>
                        <hr>
                        <div class="row mb-2">
                            <div class="col-md-6" style="text-align: left;">
                                <div class="label"># NPL</div>
                            </div>
                            <div class="col-md-6" style="text-align: right;">
                                <div class="label">{{ $totalAssetClass }}</div>
                            </div>
                        </div>
                        <div class="row mb-2">
                            <div class="col-md-6">
                                <div class="type">KHR</div>
                            </div>
                            <div class="col-md-6" style="text-align: right;">
                                <div class="amount">{{ number_format($dataAssetClass->khr ?? 0, 0) }}</div>
                            </div>
                        </div>
                        <div class="row mb-2">
                            <div class="col-md-6">
                                <div class="type">USD</div>
                            </div>
                            <div class="col-md-6" style="text-align: right;">
                                <div class="amount">{{ number_format($dataAssetClass->usd ?? 0, 2) }}</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div> --}}
        <!-- ================= DASHBOARD CONTENT ================= -->
        <div class="mt-2">
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-5 items-stretch">

                <!-- 1. LEFT COLUMN: LOAN PORTFOLIO -->
                <div class="lg:col-span-1">
                    <div class="h-full relative overflow-hidden bg-gradient-to-br from-blue-700 via-indigo-600 to-cyan-500 rounded-3xl p-5 xl:p-6 text-white shadow-lg flex flex-col justify-between">
                        
                        <!-- Header Info -->
                        <div class="flex justify-between items-start relative z-10">
                            <div>
                                <span class="text-xs uppercase font-semibold text-blue-100 tracking-wider"># LOAN PORTFOLIO</span>
                                <div class="flex items-baseline gap-2 mt-2">
                                    <h2 class="text-3xl xl:text-4xl font-extrabold tracking-tight">{{ number_format($loan) }}</h2>
                                    <span class="text-xs bg-white/20 px-2.5 py-0.5 rounded-full font-medium text-white shadow-sm">Total</span>
                                </div>
                            </div>
                            <div class="p-3 bg-white/10 backdrop-blur-md rounded-2xl border border-white/20 shadow-inner">
                                <i data-lucide="banknote" class="w-7 h-7 text-white"></i>
                            </div>
                        </div>

                        <!-- KHR & USD Currency Breakdown (កែសម្រួលទំហំ Font & Space) -->
                        <div class="mt-6 grid grid-cols-2 gap-2 bg-white/10 backdrop-blur-md p-3.5 xl:p-4 rounded-2xl border border-white/15 relative z-10">
                            <div class="flex flex-col justify-center min-w-0">
                                <div class="text-[10px] xl:text-xs text-blue-100 font-medium uppercase tracking-wider">LOAN (KHR)</div>
                                <div class="text-[11px] sm:text-sm lg:text-[12px] xl:text-base 2xl:text-lg font-bold mt-1 flex items-baseline gap-0.5 text-white tracking-tight">
                                    <span class="text-xs font-semibold">៛</span>
                                    <span>{{ number_format($data->khr ?? 0, 0) }}</span>
                                </div>
                            </div>
                            <div class="border-l border-white/20 pl-2.5 xl:pl-4 flex flex-col justify-center min-w-0">
                                <div class="text-[10px] xl:text-xs text-blue-100 font-medium uppercase tracking-wider">LOAN (USD)</div>
                                <div class="text-[11px] sm:text-sm lg:text-[12px] xl:text-base 2xl:text-lg font-bold mt-1 flex items-baseline gap-0.5 text-white tracking-tight">
                                    <span class="text-xs font-semibold">$</span>
                                    <span>{{ number_format($data->usd ?? 0, 2) }}</span>
                                </div>
                            </div>
                        </div>

                        <!-- Decorative Background Waves -->
                        <div class="absolute -bottom-10 left-0 right-0 h-40 opacity-15 pointer-events-none">
                            <svg viewBox="0 0 500 150" preserveAspectRatio="none" class="w-full h-full">
                                <path d="M0,50 C150,150 350,-50 500,50 L500,150 L0,150 Z" fill="white"></path>
                            </svg>
                        </div>

                    </div>
                </div>

                <!-- 2. MIDDLE COLUMN: NPL -->
                <div class="lg:col-span-1">
                    <div class="h-full relative overflow-hidden bg-gradient-to-br from-blue-700 via-indigo-600 to-cyan-500 rounded-3xl p-5 xl:p-6 text-white shadow-lg flex flex-col justify-between">
                        
                        <!-- Header Info -->
                        <div class="flex justify-between items-start relative z-10">
                            <div>
                                <span class="text-xs uppercase font-semibold text-blue-100 tracking-wider"># NPL</span>
                                <div class="flex items-baseline gap-2 mt-2">
                                    <h2 class="text-3xl xl:text-4xl font-extrabold tracking-tight">{{ number_format($totalAssetClass) }}</h2>
                                    <span class="text-xs bg-white/20 px-2.5 py-0.5 rounded-full font-medium text-white shadow-sm">Total</span>
                                </div>
                            </div>
                            <div class="p-3 bg-white/10 backdrop-blur-md rounded-2xl border border-white/20 shadow-inner">
                                <i data-lucide="shield-alert" class="w-7 h-7 text-white"></i>
                            </div>
                        </div>

                        <!-- KHR & USD Currency Breakdown (កែសម្រួលទំហំ Font & Space) -->
                        <div class="mt-6 grid grid-cols-2 gap-2 bg-white/10 backdrop-blur-md p-3.5 xl:p-4 rounded-2xl border border-white/15 relative z-10">
                            <div class="flex flex-col justify-center min-w-0">
                                <div class="text-[10px] xl:text-xs text-blue-100 font-medium uppercase tracking-wider">KHR</div>
                                <div class="text-[11px] sm:text-sm lg:text-[12px] xl:text-base 2xl:text-lg font-bold mt-1 flex items-baseline gap-0.5 text-white tracking-tight">
                                    <span class="text-xs font-semibold">៛</span>
                                    <span>{{ number_format($dataAssetClass->khr ?? 0, 0) }}</span>
                                </div>
                            </div>
                            <div class="border-l border-white/20 pl-2.5 xl:pl-4 flex flex-col justify-center min-w-0">
                                <div class="text-[10px] xl:text-xs text-blue-100 font-medium uppercase tracking-wider">USD</div>
                                <div class="text-[11px] sm:text-sm lg:text-[12px] xl:text-base 2xl:text-lg font-bold mt-1 flex items-baseline gap-0.5 text-white tracking-tight">
                                    <span class="text-xs font-semibold">$</span>
                                    <span>{{ number_format($dataAssetClass->usd ?? 0, 2) }}</span>
                                </div>
                            </div>
                        </div>

                        <!-- Decorative Background Waves -->
                        <div class="absolute -bottom-10 left-0 right-0 h-40 opacity-15 pointer-events-none">
                            <svg viewBox="0 0 500 150" preserveAspectRatio="none" class="w-full h-full">
                                <path d="M0,50 C150,150 350,-50 500,50 L500,150 L0,150 Z" fill="white"></path>
                            </svg>
                        </div>

                    </div>
                </div>

                <!-- 3. RIGHT COLUMN: 3 PRODUCTIVITY METRICS -->
                <div class="lg:col-span-1 flex flex-col gap-4 justify-between h-full">
                    
                    <!-- CUSTOMER -->
                    <div class="bg-white p-4 lg:p-5 rounded-3xl shadow-sm border border-slate-100 flex items-center justify-between hover:shadow-md transition flex-1">
                        <div class="flex items-center gap-3.5">
                            <div class="w-11 h-11 rounded-2xl bg-blue-50 text-blue-600 flex items-center justify-center font-bold flex-shrink-0">
                                <i data-lucide="users" class="w-5.5 h-5.5"></i>
                            </div>
                            <div>
                                <span class="text-[11px] font-semibold text-slate-400 uppercase tracking-wide"># CUSTOMER</span>
                                <h4 class="text-xl font-bold text-slate-800 mt-0.5">{{ number_format($customer) }}</h4>
                            </div>
                        </div>
                        <span class="text-xs font-semibold text-emerald-600 bg-emerald-50 px-2.5 py-1 rounded-full">Total</span>
                    </div>

                    <!-- PARs 30+ -->
                    <div class="bg-white p-4 lg:p-5 rounded-3xl shadow-sm border border-slate-100 flex items-center justify-between hover:shadow-md transition flex-1">
                        <div class="flex items-center gap-3.5">
                            <div class="w-11 h-11 rounded-2xl bg-amber-50 text-amber-600 flex items-center justify-center font-bold flex-shrink-0">
                                <i data-lucide="alert-triangle" class="w-5.5 h-5.5"></i>
                            </div>
                            <div>
                                <span class="text-[11px] font-semibold text-slate-400 uppercase tracking-wide"># PARs 30+</span>
                                <h4 class="text-xl font-bold text-slate-800 mt-0.5">{{ $finalParRate }}%</h4>
                            </div>
                        </div>
                        <span class="text-xs font-semibold text-amber-600 bg-amber-50 px-2.5 py-1 rounded-full">Low Risk</span>
                    </div>

                    <!-- PROFITABLE -->
                    <div class="bg-white p-4 lg:p-5 rounded-3xl shadow-sm border border-slate-100 flex items-center justify-between hover:shadow-md transition flex-1">
                        <div class="flex items-center gap-3.5">
                            <div class="w-11 h-11 rounded-2xl bg-blue-50 text-blue-600 flex items-center justify-center font-bold flex-shrink-0">
                                <i data-lucide="users" class="w-5.5 h-5.5"></i>
                            </div>
                            <div>
                                <span class="text-[11px] font-semibold text-slate-400 uppercase tracking-wide"># PROFITABLE</span>
                                <h4 class="text-xl font-bold text-slate-800 mt-0.5">{{ $Profitble }}</h4>
                            </div>
                        </div>
                        <span class="text-xs font-semibold text-emerald-600 bg-emerald-50 px-2.5 py-1 rounded-full">Total</span>
                    </div>

                </div>

            </div>
        </div>
    @endif
@endsection
@section('script')
    
    <script src="{{ asset('/admins/js/lucide.min.js') }}"></script>
    <script>
        lucide.createIcons();
    </script>
@endsection