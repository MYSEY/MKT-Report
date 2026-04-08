<table>
    <tr>
        <td colspan="4" style="text-align: center; font-weight: bold; font-size: 20pt; color: #ff0000;">
            <img src="{{ public_path('admins/img/logo/commalogo1.png') }}" height="100">
            ខេមា មីក្រូហិរញ្ញវត្ថុ លីមីតធីត
        </td>
    </tr>
    <tr><td colspan="4" style="text-align: center; font-weight: bold; font-size: 14pt;">CAMMA MICROFINANCE LIMITED</td></tr>
    <tr><td colspan="4" style="text-align: center; font-weight: bold; font-size: 16pt;">បញ្ជីរាយនាមគណៈគ្រប់គ្រងគិតត្រឹមខែ..... ឆ្នាំ......</td></tr>
    <tr><td colspan="4"></td></tr>
    <thead>
        <tr>
            <th style="border: 1px solid #000; background-color: #f2f2f2; font-weight: bold;">ល.រ</th>
            <th style="border: 1px solid #000; background-color: #f2f2f2; font-weight: bold;">ឈ្មោះ</th>
            <th style="border: 1px solid #000; text-align: center; background-color: #f2f2f2; font-weight: bold;">តួនាទី</th>
            <th style="border: 1px solid #000; text-align: center; background-color: #f2f2f2; font-weight: bold;">ការិយាល័យ</th>
        </tr>
    </thead>
    <tbody>

        @foreach($data as $index => $items)
                <tr>
                    <td style="border: 1px solid #000;">{{ $index+1 }}</td>
                    <td style="border: 1px solid #000;">{{ $items->employee_name_kh }}</td>
                    <td style="border: 1px solid #000;">{{ $items->position_name_kh }}</td>
                    <td style="border: 1px solid #000;">{{ $items->branch_name_kh }}</td>
                </tr>
        @endforeach
    </tbody>
</table>