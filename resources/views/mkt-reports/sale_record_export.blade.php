<table border="1" style="border-collapse: collapse; width: 100%; font-family: 'Kantumruy Pro', sans-serif; font-size: 12px;">
    <tr>
        <td colspan="14" style="text-align: center; font-weight: bold; font-size: 20pt;">
            <img src="{{ public_path('admins/img/logo/commalogo1.png') }}" height="100">
            សៀវភៅទិន្នានុប្បវត្តិលក់
        </td>
    </tr>
    <tr><td colspan="14" style="text-align: center; font-weight: bold; font-size: 14pt;">ប្រចំាខែ..... ឆ្នំា.......</td></tr>
    <tr><td colspan="14" style="font-weight: bold;">នាមករណ៍សហគ្រាស : ខេមា មីក្រូហិរញ្ញវត្ថុ លីមីតធីត</td></tr>
    <tr><td colspan="14">អាស័យដ្ឋានៈផ្ទះលេខ១០១A ផ្លូវ 289 សង្កាត់បឹងកក់១ ខណ្ឌ ទួលគោក រាជធានី ភ្នំពេញ</td></tr>
    <tr>
        <td colspan="12">គណនីសហគ្រាស​​​ :  </td>
        <td style="font-weight: bold; text-align: right;">អត្រាប្តូរប្រាក់</td>
        <td style="font-weight: bold; text-align: center;">{{$currency}}៛ </td>
    </tr>
    <thead>
        <tr style="background-color: #d9ead3; text-align: center;">
            <th rowspan="2" style="border: 1px solid #000; text-align: center; background-color: #f2f2f2; font-weight: bold;">ល.រ</th>
            <th rowspan="2" style="border: 1px solid #000; text-align: center; background-color: #f2f2f2; font-weight: bold;">កាលបរិច្ឆេទ</th>
            <th rowspan="2" style="border: 1px solid #000; text-align: center; background-color: #f2f2f2; font-weight: bold;">លេខវិក្កយបត្រ<br>ប្រតិបត្តិការគយ<br>ឬ លេខសក្ខីប័ត្របង់ប្រាក់</th>
            <th colspan="4" style="border: 1px solid #000; text-align: center; background-color: #f2f2f2; font-weight: bold;">អ្នកទិញ</th>
            <th rowspan="2" style="border: 1px solid #000; text-align: center; background-color: #f2f2f2; font-weight: bold;">ប្រភេទផ្គត់ផ្គង់ទំនិញ<br>ឬសេវាកម្ម</th>
            <th rowspan="2" style="border: 1px solid #000; text-align: center; background-color: #f2f2f2; font-weight: bold;">តម្លៃ ជាប្រាក់រៀល</th>
            <th rowspan="2" style="border: 1px solid #000; text-align: center; background-color: #f2f2f2; font-weight: bold;">តម្លៃ ជាប្រាក់ដុល្លារ</th>
            <th rowspan="2" style="border: 1px solid #000; text-align: center; background-color: #f2f2f2; font-weight: bold;">តម្លៃសរុប ជាប្រាក់រៀល</th>
            <th rowspan="2" style="border: 1px solid #000; text-align: center; background-color: #f2f2f2; font-weight: bold;">អត្រាប្រាក់ពន្ធ<br>រ៉ាប់រងលើប្រាក់<br>ចំណូល ១%</th>
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
                <td align="right" style="border: 1px solid #000;">
                    {{ $row->Currency === 'KHR' ? number_format($row->Amount) . ' ៛' : '- ៛' }}
                </td>
                <td align="right" style="border: 1px solid #000;">
                    {{ $row->Currency === 'USD' ? '$ ' . number_format($row->Amount, 2) : '- $' }}
                </td>
                <td align="right" style="font-weight: bold; border: 1px solid #000;">
                    {{ number_format($row->TotalKHR) }} ៛
                </td>
                <td align="right" style="border: 1px solid #000;">
                    {{ $row->Tax1Percent > 0 ? number_format(round($row->Tax1Percent)) : 0 }} ៛
                </td>
                <td style="border: 1px solid #000;">Loan Repayment</td>
                <td align="center" style="border: 1px solid #000;">0</td>
            </tr>
        @endforeach
    </tbody>
</table>