<table border="1" style="border-collapse: collapse; width: 100%; font-family: 'Kantumruy Pro', sans-serif; font-size: 12px;">
    <thead>
        <tr style="background-color: #d9ead3; text-align: center;">
            <th style="border: 1px solid #000; text-align: center; background-color: #f2f2f2; font-weight: bold;">ល.រ</th>
            <th style="border: 1px solid #000; text-align: center; background-color: #f2f2f2; font-weight: bold;">LoanStatus</th>
            <th style="border: 1px solid #000; text-align: center; background-color: #f2f2f2; font-weight: bold;">Branch</th>
            <th style="border: 1px solid #000; text-align: center; background-color: #f2f2f2; font-weight: bold;">ID</th>
            <th style="border: 1px solid #000; text-align: center; background-color: #f2f2f2; font-weight: bold;">ContractCustomerID</th>
            <th style="border: 1px solid #000; text-align: center; background-color: #f2f2f2; font-weight: bold;">CustomerName</th>
            <th style="border: 1px solid #000; text-align: center; background-color: #f2f2f2; font-weight: bold;">Currency</th>
            <th style="border: 1px solid #000; text-align: center; background-color: #f2f2f2; font-weight: bold;">DisburseDate</th>
            <th style="border: 1px solid #000; text-align: center; background-color: #f2f2f2; font-weight: bold;">ClosedDate</th>
            <th style="border: 1px solid #000; text-align: center; background-color: #f2f2f2; font-weight: bold;">Disbursed</th>
            <th style="border: 1px solid #000; text-align: center; background-color: #f2f2f2; font-weight: bold;">InterestRate</th>
            <th style="border: 1px solid #000; text-align: center; background-color: #f2f2f2; font-weight: bold;">Term</th>
            <th style="border: 1px solid #000; text-align: center; background-color: #f2f2f2; font-weight: bold;">MaturityDate</th>
            <th style="border: 1px solid #000; text-align: center; background-color: #f2f2f2; font-weight: bold;">LoanProduct</th>
            <th style="border: 1px solid #000; text-align: center; background-color: #f2f2f2; font-weight: bold;">Sector</th>
            <th style="border: 1px solid #000; text-align: center; background-color: #f2f2f2; font-weight: bold;">Category</th>
            <th style="border: 1px solid #000; text-align: center; background-color: #f2f2f2; font-weight: bold;">ContractOfficerID</th>
        </tr>
    </thead>
    <tbody>
        @php 
            $lastCustomer = null; 
            $no = 1;
        @endphp

        @foreach($data as $row)
            {{-- ១. ឆែកមើលបើប្តូរអតិថិជនថ្មី ត្រូវបង្ហាញជួរ Group Header --}}
            @if($lastCustomer !== $row->ContractCustomerID)
                <tr>
                    <td colspan="17" style="background-color: #e9ecef; border: 1px solid #000; padding: 5px; font-weight: bold;">
                        ID: {{ $row->ContractCustomerID }} | {{ $row->EnName }}
                    </td>
                </tr>
                @php $lastCustomer = $row->ContractCustomerID; @endphp
            @endif

            {{-- ២. បង្ហាញទិន្នន័យកម្ចីនីមួយៗ --}}
            <tr>
                <td style="border: 1px solid #000; text-align: center;">{{ $no++ }}</td>
                <td style="border: 1px solid #000; text-align: center; color: {{ $row->LoanStatus == 'Active' ? '#28a745' : '#dc3545' }};">
                    {{ $row->LoanStatus }}
                </td>
                <td style="border: 1px solid #000; text-align: center;">{{ $row->Branch }}</td>
                <td style="border: 1px solid #000;">{{ $row->ID }}</td>
                <td style="border: 1px solid #000;">{{ $row->ContractCustomerID }}</td>
                <td style="border: 1px solid #000;">{{ $row->EnName }}</td>
                <td style="border: 1px solid #000; text-align: center;">{{ $row->Currency }}</td>
                <td style="border: 1px solid #000; text-align: center;">{{ $row->ValueDate }}</td>
                <td style="border: 1px solid #000; text-align: center;">{{ $row->ClosedDate }}</td>
                <td style="border: 1px solid #000; text-align: right;">
                    {{ number_format($row->Disbursed, 2) }}
                </td>
                <td style="border: 1px solid #000; text-align: center;">{{ $row->InterestRate }}</td>
                <td style="border: 1px solid #000; text-align: center;">{{ $row->Term }}</td>
                <td style="border: 1px solid #000; text-align: center;">{{ $row->MaturityDate }}</td>
                <td style="border: 1px solid #000;">{{ $row->ProdName }}</td>
                <td style="border: 1px solid #000;">{{ $row->Sector }}</td>
                <td style="border: 1px solid #000;">{{ $row->Category }}</td>
                <td style="border: 1px solid #000;">{{ $row->ContractOfficerID }}</td>
            </tr>
        @endforeach
    </tbody>
</table>