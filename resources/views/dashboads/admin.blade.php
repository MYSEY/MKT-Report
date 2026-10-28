@extends('layouts.admin')
@section('content')

<!-- Font and Icons -->
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
<script src="https://cdn.tailwindcss.com"></script>

<div class="subheader mb-3">
    <h1 class="subheader-title">
        <i class='subheader-icon fal fa-chart-area'></i> <span class='fw-500'>Dashboard</span>
    </h1>
</div>

@if(Auth::user()->can('Dashboard Branch Productivity'))
    <div class="mt-2">
        <!-- ================= TOP WIDGETS (LOAN, NPL, PRODUCTIVITY) ================= -->
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-5 items-stretch mb-3">
            
            <!-- 1. LEFT COLUMN: LOAN PORTFOLIO (Classic Style) -->
            <div class="lg:col-span-1">
                <div class="bg-white border border-[#bce8f1] rounded h-full flex flex-col shadow-sm">
                    
                    <!-- Header Section -->
                    <div class="bg-[#d9edf7] px-3 py-2.5 flex items-center justify-between border-b border-[#bce8f1]">
                        <div class="flex items-center gap-2">
                            <div class="text-[#31708f]">
                                {{-- <i data-lucide="banknote" class="w-[18px] h-[18px]"></i> --}}
                            </div>
                            <h2 class="text-[#31708f] text-[15px] font-normal tracking-wide m-0" style="font-family: inherit;">#LOAN PORTFOLIO</h2>
                        </div>
                        <span class="text-[11px] font-semibold text-[#31708f] bg-white/60 px-2 py-0.5 rounded border border-[#bce8f1]">Total</span>
                    </div>

                    <!-- Body Section -->
                    <div class="p-5 flex-1 flex flex-col justify-center">
                        <div class="text-center mb-6">
                            <h2 class="text-3xl xl:text-4xl font-bold text-gray-700 tracking-tight">{{ number_format($loan) }}</h2>
                        </div>
                        
                        <div class="grid grid-cols-2 gap-2 border-t border-gray-100 pt-5">
                            <div class="text-center border-r border-gray-100">
                                <div class="text-[11px] text-gray-400 font-medium uppercase tracking-wider mb-1">LOAN (KHR)</div>
                                <div class="text-sm xl:text-base font-bold text-[#31708f] flex items-baseline justify-center gap-1">
                                    <span class="text-xs">៛</span>
                                    <span>{{ number_format($data->khr ?? 0, 0) }}</span>
                                </div>
                            </div>
                            <div class="text-center">
                                <div class="text-[11px] text-gray-400 font-medium uppercase tracking-wider mb-1">LOAN (USD)</div>
                                <div class="text-sm xl:text-base font-bold text-[#31708f] flex items-baseline justify-center gap-1">
                                    <span class="text-xs">$</span>
                                    <span>{{ number_format($data->usd ?? 0, 2) }}</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- 2. MIDDLE COLUMN: NPL (Classic Style) -->
            <div class="lg:col-span-1">
                <div class="bg-white border border-[#bce8f1] rounded h-full flex flex-col shadow-sm">
                    
                    <!-- Header Section -->
                    <div class="bg-[#d9edf7] px-3 py-2.5 flex items-center justify-between border-b border-[#bce8f1]">
                        <div class="flex items-center gap-2">
                            <div class="text-[#31708f]">
                                {{-- <i data-lucide="shield-alert" class="w-[18px] h-[18px]"></i> --}}
                            </div>
                            <h2 class="text-[#31708f] text-[15px] font-normal tracking-wide m-0" style="font-family: inherit;">#NPL</h2>
                        </div>
                        <span class="text-[11px] font-semibold text-[#31708f] bg-white/60 px-2 py-0.5 rounded border border-[#bce8f1]">Total</span>
                    </div>

                    <!-- Body Section -->
                    <div class="p-5 flex-1 flex flex-col justify-center">
                        <div class="text-center mb-6">
                            <h2 class="text-3xl xl:text-4xl font-bold text-gray-700 tracking-tight">{{ number_format($totalAssetClass) }}</h2>
                        </div>
                        
                        <div class="grid grid-cols-2 gap-2 border-t border-gray-100 pt-5">
                            <div class="text-center border-r border-gray-100">
                                <div class="text-[11px] text-gray-400 font-medium uppercase tracking-wider mb-1">KHR</div>
                                <div class="text-sm xl:text-base font-bold text-[#31708f] flex items-baseline justify-center gap-1">
                                    <span class="text-xs">៛</span>
                                    <span>{{ number_format($dataAssetClass->khr ?? 0, 0) }}</span>
                                </div>
                            </div>
                            <div class="text-center">
                                <div class="text-[11px] text-gray-400 font-medium uppercase tracking-wider mb-1">USD</div>
                                <div class="text-sm xl:text-base font-bold text-[#31708f] flex items-baseline justify-center gap-1">
                                    <span class="text-xs">$</span>
                                    <span>{{ number_format($dataAssetClass->usd ?? 0, 2) }}</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- 3. RIGHT COLUMN: 3 PRODUCTIVITY METRICS (ទុកប្រអប់៣នេះនៅដដែល) -->
            <div class="lg:col-span-1 flex flex-col gap-4 justify-between h-full">
                <!-- CUSTOMER -->
                <div class="bg-white p-4 lg:p-4 rounded shadow-sm border border-slate-100 flex items-center justify-between hover:shadow-md transition flex-1">
                    <div class="flex items-center gap-3.5">
                        <div class="w-10 h-10 rounded-full bg-blue-50 text-blue-600 flex items-center justify-center font-bold flex-shrink-0">
                            {{-- <i data-lucide="users" class="w-5 h-5"></i> --}}
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="w-5 h-5">
                                <path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/>
                                <circle cx="9" cy="7" r="4"/>
                                <path d="M22 21v-2a4 4 0 0 0-3-3.87"/>
                                <path d="M16 3.13a4 4 0 0 1 0 7.75"/>
                            </svg>
                        </div>
                        <div>
                            <span class="text-[11px] font-semibold text-slate-400 uppercase tracking-wide"># CUSTOMER</span>
                            <h4 class="text-lg font-bold text-slate-700 mt-0.5">{{ number_format($customer) }}</h4>
                        </div>
                    </div>
                    <span class="text-[10px] font-semibold text-emerald-600 bg-emerald-50 px-2.5 py-1 rounded">Total</span>
                </div>

                <!-- PARs 30+ -->
                <div class="bg-white p-4 lg:p-4 rounded shadow-sm border border-slate-100 flex items-center justify-between hover:shadow-md transition flex-1">
                    <div class="flex items-center gap-3.5">
                        <div class="w-10 h-10 rounded-full bg-amber-50 text-amber-600 flex items-center justify-center font-bold flex-shrink-0">
                            {{-- <i data-lucide="alert-triangle" class="w-5 h-5"></i> --}}
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="w-5 h-5">
                                <path d="m21.73 18-8-14a2 2 0 0 0-3.48 0l-8 14A2 2 0 0 0 4 21h16a2 2 0 0 0 1.73-3Z"/>
                                <path d="M12 9v4"/>
                                <path d="M12 17h.01"/>
                            </svg>
                        </div>
                        <div>
                            <span class="text-[11px] font-semibold text-slate-400 uppercase tracking-wide"># PARs 30+</span>
                            <h4 class="text-lg font-bold text-slate-700 mt-0.5">{{ $finalParRate }}%</h4>
                        </div>
                    </div>
                    <span class="text-[10px] font-semibold text-amber-600 bg-amber-50 px-2.5 py-1 rounded">Low Risk</span>
                </div>

                <!-- PROFITABLE -->
                <div class="bg-white p-4 lg:p-4 rounded shadow-sm border border-slate-100 flex items-center justify-between hover:shadow-md transition flex-1">
                    <div class="flex items-center gap-3.5">
                        <div class="w-10 h-10 rounded-full bg-blue-50 text-blue-600 flex items-center justify-center font-bold flex-shrink-0">
                            {{-- <i data-lucide="trending-up" class="w-5 h-5"></i> --}}
                                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="w-5 h-5">
                                <polyline points="22 7 13.5 15.5 8.5 10.5 2 17"/>
                                <polyline points="16 7 22 7 22 13"/>
                            </svg>
                        </div>
                        <div>
                            <span class="text-[11px] font-semibold text-slate-400 uppercase tracking-wide"># PROFITABLE</span>
                            <h4 class="text-lg font-bold text-slate-700 mt-0.5">{{ $Profitble }}</h4>
                        </div>
                    </div>
                    <span class="text-[10px] font-semibold text-emerald-600 bg-emerald-50 px-2.5 py-1 rounded">Total</span>
                </div>
            </div>
            
        </div>

        <!-- ================= CLASSIC LOAN TYPE CHART CARD ================= -->
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-5 items-stretch mb-3">
            <div class="lg:col-span-1">
                <div class="bg-white border border-[#bce8f1] rounded h-full flex flex-col shadow-sm">
                    <!-- Header Section -->
                    <div class="bg-[#d9edf7] px-3 py-2.5 flex items-center gap-2 border-b border-[#bce8f1]">
                        <!-- Stacked Layers Icon -->
                        <div class="text-[#31708f]">
                            <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="currentColor" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
                                <polygon points="12 2 2 7 12 12 22 7 12 2" fill="#31708f" fill-opacity="0.8"></polygon>
                                <polyline points="2 17 12 22 22 17" fill="none"></polyline>
                                <polyline points="2 12 12 17 22 12" fill="none"></polyline>
                            </svg>
                        </div>
                        <h2 class="text-[#31708f] text-[15px] font-normal tracking-wide m-0" style="font-family: inherit;">Loan Type</h2>
                    </div>

                    <!-- Body Section (Chart) -->
                    <div class="p-6 flex flex-col items-center">
                        <!-- Chart Container -->
                        <div class="relative w-56 h-56">
                            <canvas id="loanDonutChart"></canvas>
                            <!-- Center Text overlay - Added IDs here -->
                            <div class="absolute inset-0 flex flex-col items-center justify-center pointer-events-none mt-1">
                                <span id="chartCenterLabel" class="text-[20px] font-bold text-gray-500 leading-tight transition-all duration-200">Micro Loan</span>
                                <span id="chartCenterValue" class="text-[18px] font-normal text-gray-500 leading-tight mt-1 transition-all duration-200">39%</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endif
@endsection

@section('script')
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const ctx = document.getElementById('loanDonutChart');
            if (!ctx) return;

            const centerLabel = document.getElementById('chartCenterLabel');
            const centerValue = document.getElementById('chartCenterValue');

            // ទទួលទិន្នន័យពី Controller
            const chartDataDB = @json($loanTypeChart);

            // តម្រៀបទិន្នន័យពីចំនួនច្រើន ទៅ តិច (ធានាថាចំណែកធំជាងគេ ចេញពណ៌ក្រហមមុនគេ)
            chartDataDB.sort((a, b) => b.total_count - a.total_count);

            // ទាញយកឈ្មោះ(Labels) និងចំនួន(Values) ពីទិន្នន័យ DB
            const labels = chartDataDB.map(item => item.type_name || 'Unknown');
            const dataValues = chartDataDB.map(item => Number(item.total_count) || 0);

            // ប្រសិនបើគ្មានទិន្នន័យសោះ បង្ហាញទិន្នន័យទទេការពារ Error
            if(dataValues.length === 0) {
                labels.push('No Data');
                dataValues.push(1);
            }

            // Calculate total for percentage
            const total = dataValues.reduce((acc, curr) => acc + curr, 0);

            // Default Display (First item or Total)
            const defaultLabel = labels[0];
            const defaultPercent = total > 0 ? Math.floor((dataValues[0] / total) * 100) + '%' : '0%';
            
            // Initial render
            centerLabel.innerText = defaultLabel;
            centerValue.innerText = defaultPercent;

            // កូដពណ៌តម្រៀបតាមរូបភាព (រាប់ពីពណ៌ក្រហមធំជាងគេ វិលតាមទ្រនិចនាឡិកា)
            const classicColors = [
                '#d9534f', // 1. ក្រហម (Micro Loan - ធំជាងគេ)
                '#7ab9c4', // 2. ខៀវស្រាល (Teal)
                '#8bc34a', // 3. បៃតងខ្ចី (ចំណែកតូច)
                '#5e5e5e', // 4. ប្រផេះចាស់
                '#00b4f0', // 5. ខៀវភ្លឺ (Cyan)
                '#2c4eb8', // 6. ខៀវចាស់ (ចំណែកតូច)
                '#339966', // 7. បៃតង
                '#ab8565', // 8. ត្នោត
                '#688593', // 9. ប្រផេះខៀវ (Slate)
                '#f39c12', // 10. លឿងទុំ (Orange)
                '#df64e0', // 11. ផ្កាឈូក (Magenta)
                '#f1e144', // 12. លឿង (ចំណែកតូច)
                '#7244d4'  // 13. ស្វាយ
            ];

            new Chart(ctx.getContext('2d'), {
                type: 'doughnut',
                data: {
                    labels: labels,
                    datasets: [{
                        data: dataValues,
                        backgroundColor: classicColors,
                        borderWidth: 2,
                        borderColor: '#ffffff',
                        hoverOffset: 4
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: true,
                    cutout: '72%',
                    onHover: function(event, chartElement) {
                        // Check if mouse is hovering over a segment
                        if (chartElement && chartElement.length > 0) {
                            const index = chartElement[0].index;
                            const value = dataValues[index];
                            const percentage = Math.floor((value / total) * 100) + '%';
                            
                            // Update text dynamically
                            centerLabel.innerText = labels[index];
                            centerValue.innerText = percentage;
                        } else {
                            // Reset to default when not hovering
                            centerLabel.innerText = defaultLabel;
                            centerValue.innerText = defaultPercent;
                        }
                    },
                    plugins: {
                        legend: {
                            display: false
                        },
                        tooltip: {
                            backgroundColor: 'rgba(0, 0, 0, 0.8)',
                            padding: 10,
                            displayColors: true,
                            boxPadding: 4,
                            cornerRadius: 4,
                            callbacks: {
                                label: function(context) {
                                    let label = context.label || '';
                                    if (label) {
                                        label += ': ';
                                    }
                                    const val = context.raw;
                                    const percent = Math.floor((val / total) * 100) + '%';
                                    label += percent + ` (${val})`;
                                    return label;
                                }
                            }
                        }
                    },
                    animation: {
                        animateScale: true,
                        animateRotate: true
                    }
                }
            });
        });
    </script>
@endsection