<table>
    <tr>
        <td colspan="9" style="text-align: center; font-weight: bold; font-size: 20pt; color: #ff0000;">
            <img src="{{ public_path('admins/img/logo/commalogo1.png') }}" height="100">
            ខេមា មីក្រូហិរញ្ញវត្ថុ លីមីតធីត
        </td>
    </tr>
    <tr><td colspan="9" style="text-align: center; font-weight: bold; font-size: 14pt;">CAMMA MICROFINANCE LIMITED</td></tr>
    <tr><td colspan="9" style="text-align: center; font-weight: bold; font-size: 16pt;">របាយការណ៍ប្រចាំខែ....... ឆ្នាំ..... ស្តីពីព័ត៌មានបណ្តាញប្រតិបត្តិការ</td></tr>
    <tr><td colspan="9"></td></tr>
    <thead>
        <tr>
            <th rowspan="2" style="border: 1px solid #000; text-align: center; vertical-align: middle; background-color: #f2f2f2; font-weight: bold;">ល.រ</th>
            <th rowspan="2" style="border: 1px solid #000; text-align: center; vertical-align: middle; background-color: #f2f2f2; font-weight: bold;">ខេត្ត-រាជធានី</th>
            <th colspan="2" style="border: 1px solid #000; text-align: center; background-color: #f2f2f2; font-weight: bold;">ឈ្មោះសាខា</th>
            <th rowspan="2" style="border: 1px solid #000; text-align: center; vertical-align: middle; background-color: #f2f2f2; font-weight: bold;">ចំនួនសាខា</th>
            <th colspan="3" style="border: 1px solid #000; text-align: center; background-color: #f2f2f2; font-weight: bold;">បុគ្គលិក</th>
            <th rowspan="2" style="border: 1px solid #000; text-align: center; vertical-align: middle; background-color: #f2f2f2; font-weight: bold;"># of COs</th>
        </tr>
        <tr>
            <th style="border: 1px solid #000; background-color: #f2f2f2; font-weight: bold;">ឈ្មោះសាខា</th>
            <th style="border: 1px solid #000; text-align: center; background-color: #f2f2f2; font-weight: bold;">ស្នាក់ការកណ្តាល</th>
            <th style="border: 1px solid #000; text-align: center; background-color: #f2f2f2; font-weight: bold;">ប្រុស</th>
            <th style="border: 1px solid #000; text-align: center; background-color: #f2f2f2; font-weight: bold;">ស្រី</th>
            <th style="border: 1px solid #000; text-align: center; background-color: #f2f2f2; font-weight: bold;">សរុប</th>
        </tr>
    </thead>
    <tbody>
        @php
            // Group ទិន្នន័យតាមខេត្ត ដើម្បីធ្វើ Rowspan
            $groupedData = $data->groupBy('pro_name_km');
        @endphp

        @foreach($groupedData as $provinceName => $items)
            @php
                $provinceCount = count($items); // ចំនួនជួរក្នុងខេត្តនេះ
                
                // រកមើលជួរដែលជា special_group (HQ & Digital) ដើម្បី Merge តែក្នុង Column "ស្នាក់ការកណ្តាល"
                $specialGroupItems = $items->where('merge_group', 'special_group');
                $specialGroupCount = $specialGroupItems->count();
                
                // ទាញយក Key ដំបូងគេនៃ special_group ក្នុងក្រុមខេត្តនេះ
                $firstSpecialKey = $specialGroupItems->keys()->first();
            @endphp

            @foreach($items as $index => $row)
                @php 
                    // បង្កើត variable ដើម្បីងាយស្រួលឆែក key បច្ចុប្បន្ន
                    $currentKey = $items->keys()[$index]; 
                @endphp
                <tr>
                    {{-- ១. Merge ល.រ (Col 0) និង ឈ្មោះខេត្ត (Col 1) --}}
                    @if($loop->first)
                        <td rowspan="{{ $provinceCount }}" style="border: 1px solid #000; text-align: center; vertical-align: middle;">
                            {{ $row->province_no }}
                        </td>
                        <td rowspan="{{ $provinceCount }}" style="border: 1px solid #000; vertical-align: middle;">
                            {{ $row->pro_name_km }}
                        </td>
                    @endif

                    {{-- ២. ឈ្មោះសាខា (Col 2) --}}
                    <td style="border: 1px solid #000;">{{ $row->branch_name_kh }}</td>

                    {{-- ៣. Merge "ស្នាក់ការកណ្តាល" (Col 3) ជំនួសវិញតាមរូបភាពរបស់អ្នក --}}
                    @if($row->merge_group === 'special_group')
                        @if($currentKey === $firstSpecialKey)
                            <td rowspan="{{ $specialGroupCount }}" style="border: 1px solid #000; text-align: center; vertical-align: middle;">
                                1
                            </td>
                        @endif
                        {{-- បើជា special_group តែមិនមែនជួរដំបូង វានឹងមិនបង្កើត <td> ទេ (Rowspan យកអស់ហើយ) --}}
                    @else
                        <td style="border: 1px solid #000; text-align: center;">
                            {{ $row->is_hq == 1 ? '1' : '' }}
                        </td>
                    @endif

                    {{-- ៤. "ចំនួនសាខា" (Col 4) បង្ហាញលេខ ១ ធម្មតា លែង Merge ហើយ --}}
                    <td style="border: 1px solid #000; text-align: center;">
                        {{ $row->branch_count > 0 ? $row->branch_count : '' }}
                    </td>

                    {{-- ៥. បុគ្គលិក និង COs (Col 5, 6, 7, 8) --}}
                    <td style="border: 1px solid #000; text-align: center;">{{ $row->male }}</td>
                    <td style="border: 1px solid #000; text-align: center;">{{ $row->female }}</td>
                    <td style="border: 1px solid #000; text-align: center;">{{ $row->total }}</td>
                    <td style="border: 1px solid #000; text-align: center;">
                        {{ $row->co_count > 0 ? $row->co_count : '' }}
                    </td>
                </tr>
            @endforeach
        @endforeach
    </tbody>
    <tfoot>
        <tr style="background-color: #f2f2f2; font-weight: bold;">
            <td colspan="3" style="border: 1px solid #000; text-align: right;">សរុបរួម:</td>
            
            {{-- ស្នាក់ការកណ្តាល --}}
            <td style="border: 1px solid #000; text-align: center;">
                {{ $data->where('is_hq', 1)->count() }}
            </td>

            {{-- បូក branch_count ដោយ convert ទៅ int ការពារ error --}}
            <td style="border: 1px solid #000; text-align: center;">
                {{ $data->sum(fn($row) => (int)$row->branch_count) }}
            </td>

            <td style="border: 1px solid #000; text-align: center;">
                {{ $data->sum(fn($row) => (int)$row->male) }}
            </td>
            
            <td style="border: 1px solid #000; text-align: center;">
                {{ $data->sum(fn($row) => (int)$row->female) }}
            </td>
            
            <td style="border: 1px solid #000; text-align: center;">
                {{ $data->sum(fn($row) => (int)$row->total) }}
            </td>
            
            <td style="border: 1px solid #000; text-align: center;">
                {{ $data->sum(fn($row) => (int)$row->co_count) }}
            </td>
        </tr>
    </tfoot>
</table>