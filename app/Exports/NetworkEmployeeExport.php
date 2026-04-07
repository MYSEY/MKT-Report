<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\WithEvents;
use Illuminate\Contracts\View\View;
use Maatwebsite\Excel\Concerns\FromView;
use Maatwebsite\Excel\Events\AfterSheet;
use Maatwebsite\Excel\Concerns\WithDrawings;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Worksheet\Drawing;

class NetworkEmployeeExport implements FromView, WithEvents
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
        return view('hr-reports.network_employee_export', [
            'data' => $this->data
        ]);
    }
    
    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {

                $sheet = $event->sheet->getDelegate();
                // Set Khmer OS Battambang for header
                $sheet->getStyle('A1:I3')->getFont()->setName('Khmer OS Battambang');
                $highestRow = $sheet->getHighestRow();
                $sheet->getStyle("A3:I$highestRow")->getFont()->setName('Khmer OS Battambang');
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
                ];

                foreach ($widths as $col => $width) {
                    $sheet->getColumnDimension($col)->setWidth($width);
                }

                $sheet->getParent()->getDefaultStyle()->getFont()->setName('Khmer OS Battambang');
            }
        ];
    }
}
