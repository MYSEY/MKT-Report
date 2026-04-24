<table border="1" style="border-collapse: collapse; width: 100%; font-family: 'Kantumruy Pro', sans-serif; font-size: 12px;">
    <tr>
        <td colspan="14" style="text-align: center; font-weight: bold; font-size: 20pt;">
            <img src="{{ public_path('admins/img/logo/commalogo1.png') }}" height="100">
            សៀវភៅទិន្នានុប្បវត្តិលក់
        </td>
    </tr>
    <tr><td colspan="14" style="text-align: center; font-weight: bold; font-size: 14pt;">
        ប្រចំាខែ {{ \App\Helpers\KhmerDateHelper::formatDate($date, 'km', ['month' => true]) }} ឆ្នំា {{ \App\Helpers\KhmerDateHelper::formatDate($date, 'km', ['year' => true]) }}
    </td></tr>
    <tr><td colspan="14" style="font-weight: bold;">នាមករណ៍សហគ្រាស : ខេមា មីក្រូហិរញ្ញវត្ថុ លីមីតធីត</td></tr>
    <tr><td colspan="14">អាស័យដ្ឋានៈផ្ទះលេខ១០១A ផ្លូវ 289 សង្កាត់បឹងកក់១ ខណ្ឌ ទួលគោក រាជធានី ភ្នំពេញ</td></tr>
    <tr>
        <td colspan="12">គណនីសហគ្រាស​​​ :  L001-10​7008408</td>
        <td style="font-weight: bold; text-align: right;">អត្រាប្តូរប្រាក់</td>
        <td style="font-weight: bold; text-align: center;">{{$currency}}៛ </td>
    </tr>
    <thead>
        <tr style="background-color: #d9ead3; text-align: center;">
            <th rowspan="2" style="border: 1px solid #000; text-align: center; background-color: #f2f2f2; font-weight: bold;">ល.រ</th>
            <th rowspan="2" style="border: 1px solid #000; text-align: center; background-color: #f2f2f2; font-weight: bold;">កាលបរិច្ឆេទ</th>
            <th rowspan="2" style="border: 1px solid #000; text-align: center; background-color: #f2f2f2; font-weight: bold;">លេខវិក្កយបត្រ ប្រតិវេទន៍គយ ឬ លេខសក្ខីបត្របង្គរ*</th>
            <th colspan="4" style="border: 1px solid #000; text-align: center; background-color: #f2f2f2; font-weight: bold;">អ្នកទិញ</th>
            <th rowspan="2" style="border: 1px solid #000; text-align: center; background-color: #f2f2f2; font-weight: bold;">ប្រភេទផ្គត់ផ្គង់ទំនិញ<br>ឬសេវាកម្ម</th>
            <th rowspan="2" style="border: 1px solid #000; text-align: center; background-color: #f2f2f2; font-weight: bold;">តម្លៃ ជាប្រាក់រៀល</th>
            <th rowspan="2" style="border: 1px solid #000; text-align: center; background-color: #f2f2f2; font-weight: bold;">តម្លៃ ជាប្រាក់ដុល្លារ</th>
            <th rowspan="2" style="border: 1px solid #000; text-align: center; background-color: #f2f2f2; font-weight: bold;">តម្លៃសរុប ជាប្រាក់រៀល</th>
            <th rowspan="2" style="border: 1px solid #000; text-align: center; background-color: #f2f2f2; font-weight: bold;">អត្រាប្រាក់ពន្ធរំដោះលើប្រាក់ចំណូល ១%</th>
            <th rowspan="2" style="border: 1px solid #000; text-align: center; background-color: #f2f2f2; font-weight: bold;">បរិយាយ</th>
            <th rowspan="2" style="border: 1px solid #000; text-align: center; background-color: #f2f2f2; font-weight: bold;">វិធីសាស្ត្រ<br>គណនេយ្យ</th>
        </tr>
        <tr style="background-color: #d9ead3; text-align: center;">
            <th style="border: 1px solid #000; text-align: center; background-color: #f2f2f2; font-weight: bold;">ប្រភេទ</th>
            <th style="border: 1px solid #000; text-align: center; background-color: #f2f2f2; font-weight: bold;">លេខសម្គាល់</th>
            <th style="border: 1px solid #000; text-align: center; background-color: #f2f2f2; font-weight: bold;">ឈ្មោះ (ខ្មែរ)</th>
            <th style="border: 1px solid #000; text-align: center; background-color: #f2f2f2; font-weight: bold;">ឈ្មោះ (ឡាតាំង)</th>
        </tr>
    </thead>
    <tbody>
        @foreach($data as $index => $row)
            <tr>
                <td align="center" style="border: 1px solid #000;">{{ $index + 1 }}</td>
                <td align="center" style="border: 1px solid #000;">{{ $row->TransactionDate }}</td>
                <td align="center" style="border: 1px solid #000;">11111</td>
                <td align="center" style="border: 1px solid #000;">2</td>
                <td style="border: 1px solid #000;">{{ $row->Reference }}</td>
                <td style="border: 1px solid #000;">{{ $row->KhName }}</td>
                <td style="border: 1px solid #000;">{{ $row->EnName }}</td>
                <td align="center" style="border: 1px solid #000;">3</td>
                
                {{-- ✅ បង្ហាញ Amount KHR --}}
                <td align="right" style="border: 1px solid #000;">
                    {{ $row->Amount_KHR != 0 ? number_format($row->Amount_KHR) : '-' }}
                </td>

                {{-- ✅ បង្ហាញ Amount USD --}}
                <td align="right" style="border: 1px solid #000;">
                    {{ $row->Amount_USD != 0 ? number_format($row->Amount_USD, 2) : '-' }}
                </td>

                {{-- ✅ បង្ហាញ Total Amount KHR --}}
                <td align="right" style="font-weight: bold; border: 1px solid #000;">
                    {{ number_format($row->Total_Amount_KHR) }}
                </td>

                {{-- ✅ បង្ហាញ Income Tax 1% --}}
                <td align="right" style="border: 1px solid #000;">
                    {{ number_format(round($row->Income_Tax)) }}
                </td>
                
                <td style="border: 1px solid #000;">Loan Repayment</td>
                <td align="center" style="border: 1px solid #000;">0</td>
            </tr>
        @endforeach
    </tbody>
    <tfoot>
        <tr style="background-color: #f2f2f2; font-weight: bold;">
            <td colspan="8" style="border: 1px solid #000; text-align: right;">សរុបរួម:</td>

            {{-- ✅ បូកសរុប Amount_KHR --}}
            <td align="right" style="border: 1px solid #000;">
                {{ number_format($data->sum('Amount_KHR')) }}
            </td>

            {{-- ✅ បូកសរុប Amount_USD --}}
            <td align="right" style="border: 1px solid #000;">
                {{ number_format($data->sum('Amount_USD'), 2) }}
            </td>

            {{-- ✅ បូកសរុប Total_Amount_KHR --}}
            <td align="right" style="border: 1px solid #000; font-weight: bold;">
                {{ number_format($data->sum('Total_Amount_KHR')) }}
            </td>

            {{-- ✅ បូកសរុប Income_Tax --}}
            <td align="right" style="border: 1px solid #000;">
                {{ number_format($data->sum(fn($r) => round($r->Income_Tax))) }}
            </td>

            <td colspan="2" style="border: 1px solid #000; background-color: #f2f2f2;"></td>
        </tr>
        <tr>
            <td></td>
            <td></td>
            <td></td>
            <td></td>
            <td></td>
            <td></td>
            <td></td>
            <td></td>
            <td></td>
            <td></td>
            <td></td>
            <td></td>
            <td></td>
            <td></td>
            <td></td>
            <td></td>
        </tr>
        <tr>
            <td></td>
            <td></td>
            <td></td>
            <td></td>
            <td></td>
            <td></td>
            <td></td>
            <td></td>
            <td colspan="6"style="text-align: center">
                <span>រាជធានីភ្នំពេញ, ថ្ងៃទី......... ខែ......... ឆ្នាំ២០២៦</span>
            </td>
        </tr>
        <tr>
            <td colspan="4" style="text-align: center">
                <span>អនុញ្ញាតដោយ</span>
            </td>

            <td colspan="4" style="text-align: center">
                <span>ពិនិត្យដោយ</span>
            </td>

            <td colspan="6" style="text-align: center">
                <span>រៀបចំដោយ</span>
            </td>
        </tr>
        <tr>
            <td colspan="4" style="text-align: center">
                <span>អគ្គនាយកប្រតិបត្តិ</span>
            </td>

            <td colspan="4" style="text-align: center">
                <span>នាយិកានាយកដ្ឋានគណនេយ្យ និងហិរញ្ញវត្ថុ</span>
            </td>

            <td colspan="6" style="text-align: center">
                <span>នាយិកានាយកដ្ឋានគណនេយ្យ និងហិរញ្ញវត្ថុ</span>
            </td>
        </tr>
    </tfoot>
</table>