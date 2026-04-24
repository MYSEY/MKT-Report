<table border="1" style="border-collapse: collapse; width: 100%; font-family: 'Kantumruy Pro', sans-serif; font-size: 12px;">
    <tr>
        <td colspan="10" style="text-align: center; font-weight: bold; font-size: 20pt;">
            <img src="{{ public_path('admins/img/logo/commalogo1.png') }}" height="100">
            សៀវភៅទិន្នានុប្បវត្តិលក់
        </td>
    </tr>
    <tr><td colspan="10" style="text-align: center; font-weight: bold; font-size: 14pt;">
        ប្រចំាខែ {{ \App\Helpers\KhmerDateHelper::formatDate($date, 'km', ['month' => true]) }} ឆ្នំា {{ \App\Helpers\KhmerDateHelper::formatDate($date, 'km', ['year' => true]) }}
    </td></tr>
    <tr><td colspan="10" style="font-weight: bold;">នាមករណ៍សហគ្រាស : ខេមា មីក្រូហិរញ្ញវត្ថុ លីមីតធីត</td></tr>
    <tr><td colspan="10">អាស័យដ្ឋានៈផ្ទះលេខ១០១A ផ្លូវ 289 សង្កាត់បឹងកក់១ ខណ្ឌ ទួលគោក រាជធានី ភ្នំពេញ</td></tr>
    <tr>
        <td colspan="8">គណនីសហគ្រាស​​​ :  L001-10​7008408</td>
        <td style="font-weight: bold; text-align: right;">អត្រាប្តូរប្រាក់</td>
        <td style="font-weight: bold; text-align: center;">{{$currency}}៛ </td>
    </tr>
    <thead>
        <tr style="background-color: #d9ead3; text-align: center;">
            <th style="border: 1px solid #000; text-align: center; background-color: #f2f2f2; font-weight: bold;">ល.រ</th>
            <th style="border: 1px solid #000; text-align: center; background-color: #f2f2f2; font-weight: bold;">កាលបរិច្ឆេទ</th>
            <th style="border: 1px solid #000; text-align: center; background-color: #f2f2f2; font-weight: bold;">លេខប្រតិបត្តិការ</th>
            <th style="border: 1px solid #000; text-align: center; background-color: #f2f2f2; font-weight: bold;">លេខគណនី</th>
            <th style="border: 1px solid #000; text-align: center; background-color: #f2f2f2; font-weight: bold;">ឈ្មោះគណនី </th>
            <th style="border: 1px solid #000; text-align: center; background-color: #f2f2f2; font-weight: bold;">ឈ្មោះអ្នកទិញ</th>
            <th style="border: 1px solid #000; text-align: center; background-color: #f2f2f2; font-weight: bold;">តម្លៃជាប្រាក់រៀល</th>
            <th style="border: 1px solid #000; text-align: center; background-color: #f2f2f2; font-weight: bold;">តម្លៃជាប្រាក់ដុល្លារ</th>
            <th style="border: 1px solid #000; text-align: center; background-color: #f2f2f2; font-weight: bold;">តម្លៃសរុបជាប្រាក់រៀល</th>
            <th style="border: 1px solid #000; text-align: center; background-color: #f2f2f2; font-weight: bold;">ចំណូលមិនត្រូវបង់ (Exemption1%)</th>
        </tr>
    </thead>
    @php
        $sumKHR = 0;
        $sumUSD = 0;
        $sumTotalKHR = 0;
        $sumTax = 0;
    @endphp

    <tbody>
        @foreach($data as $index => $row)
            @php
                // បូកសរុបសម្រាប់ជួរនីមួយៗ
                $sumKHR += (float)$row->AmountKHR;
                $sumUSD += (float)$row->AmountUSD;
                $sumTotalKHR += (float)$row->TotalAmountKHR;
                $sumTax += round((float)$row->Exemption1Percent);
            @endphp
            <tr>
                <td align="center" style="border: 1px solid #000;">{{ $index + 1 }}</td>
                <td align="center" style="border: 1px solid #000;">
                    {{ $row->GLDay && $row->GLMonth && $row->GLYear ? $row->GLDay .'-'. $row->GLMonth .'-'. $row->GLYear : '-' }}
                </td>
                <td align="center" style="border: 1px solid #000;">11111</td>
                <td align="center" style="border: 1px solid #000;">{{ $row->ID }}</td>
                <td style="border: 1px solid #000; padding-left: 5px;">{{ $row->Description ?? 'No Description' }}</td>

                @if($index == 0)
                    <td rowspan="{{ count($data) }}" align="center" style="border: 1px solid #000; vertical-align: middle; padding: 5px;">
                        ក្រុមហ៊ុន
                    </td>
                @endif

                <td align="right" style="border: 1px solid #000; padding-right: 5px;">
                    {{ $row->AmountKHR > 0 ? number_format($row->AmountKHR) . ' ៛' : '-' }}
                </td>
                
                <td align="right" style="border: 1px solid #000; padding-right: 5px;">
                    {{ $row->AmountUSD > 0 ? '$ ' . number_format($row->AmountUSD, 2) : '-' }}
                </td>
                
                <td align="right" style="border: 1px solid #000; padding-right: 5px; font-weight: bold;">
                    {{ number_format($row->TotalAmountKHR) }} ៛
                </td>
                
                <td align="right" style="border: 1px solid #000; padding-right: 5px;">
                    {{ $row->Exemption1Percent > 0 ? number_format(round($row->Exemption1Percent)) . ' ៛' : '0 ៛' }}
                </td>
            </tr>
        @endforeach
    </tbody>

    <tfoot>
        <tr style="background-color: #f2f2f2; font-weight: bold;">
            <td colspan="6" align="right" style="border: 1px solid #000; padding: 8px;">សរុបរួម:</td>
            <td align="right" style="border: 1px solid #000; padding-right: 5px;">{{ number_format($sumKHR) }} ៛</td>
            <td align="right" style="border: 1px solid #000; padding-right: 5px;">$ {{ number_format($sumUSD, 2) }}</td>
            <td align="right" style="border: 1px solid #000; padding-right: 5px;">{{ number_format($sumTotalKHR) }} ៛</td>
            <td align="right" style="border: 1px solid #000; padding-right: 5px;">{{ number_format($sumTax) }} ៛</td>
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
        </tr>
        <tr>
            <td></td>
            <td></td>
            <td></td>
            <td></td>
            <td></td>
            <td></td>
            <td colspan="4"style="text-align: center">
                <span>រាជធានីភ្នំពេញ, ថ្ងៃទី......... ខែ......... ឆ្នាំ២០២៦</span>
            </td>
        </tr>
        <tr>
            <td colspan="3" style="text-align: center">
                <span>អនុញ្ញាតដោយ</span>
            </td>

            <td colspan="3" style="text-align: center">
                <span>ពិនិត្យដោយ</span>
            </td>

            <td colspan="4" style="text-align: center">
                <span>រៀបចំដោយ</span>
            </td>
        </tr>
        <tr>
            <td colspan="3" style="text-align: center">
                <span>អគ្គនាយកប្រតិបត្តិ</span>
            </td>

            <td colspan="3" style="text-align: center">
                <span>នាយិកានាយកដ្ឋានគណនេយ្យ និងហិរញ្ញវត្ថុ</span>
            </td>

            <td colspan="4" style="text-align: center">
                <span>ប្រធានផ្នែកពន្ធ</span>
            </td>
        </tr>
    </tfoot>
</table>