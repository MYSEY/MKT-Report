<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithEvents;
use Illuminate\Contracts\View\View;
use Maatwebsite\Excel\Concerns\FromView;
use Maatwebsite\Excel\Events\AfterSheet;

class ExportLoanInactive implements FromView, WithEvents
{
    protected $data;
    public function __construct($data)
    {
        $this->data = $data;
    }
    /**
    * @return \Illuminate\Support\Collection
    */
    
    public function collection()
    {
        //
    }
    public function view(): View
    {
        $name_file ="mkt-reports.loans.loan_inactive_export";
        return view($name_file, [
            'data' => $this->data,
        ]);
    }
    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {

                $sheet = $event->sheet->getDelegate();
                // Set Khmer OS Battambang for header
                $sheet->getStyle('A1:Q3')->getFont()->setName('Khmer OS Battambang');
                $highestRow = $sheet->getHighestRow();
                $sheet->getStyle("A3:Q$highestRow")->getFont()->setName('Khmer OS Battambang');
                // 🧭 Set column widths
                $widths = [
                    'A' => 15,
                    'B' => 30,
                    'C' => 25,
                    'D' => 25,
                    'E' => 15,
                    'F' => 15,
                    'G' => 15,
                    'H' => 15,
                    'I' => 15,
                    'J' => 15,
                    'K' => 15,
                    'L' => 15,
                    'M' => 15,
                    'N' => 15,
                    'O' => 15,
                    'P' => 15,
                    'Q' => 15,
                ];

                foreach ($widths as $col => $width) {
                    $sheet->getColumnDimension($col)->setWidth($width);
                }

                $sheet->getParent()->getDefaultStyle()->getFont()->setName('Khmer OS Battambang');
            }
        ];
    }
}
