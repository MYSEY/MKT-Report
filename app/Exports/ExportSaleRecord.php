<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\WithEvents;
use Illuminate\Contracts\View\View;
use Maatwebsite\Excel\Concerns\FromView;
use Maatwebsite\Excel\Events\AfterSheet;
use Maatwebsite\Excel\Concerns\WithDrawings;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Worksheet\Drawing;

class ExportSaleRecord implements FromView, WithEvents
{
    protected $data;
    protected $date;
    protected $currency;
    protected $type;

    public function __construct($data,$date,$currency,$type)
    {
        $this->data = $data;
        $this->date = $date;
        $this->currency = $currency;
        $this->type = $type;
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
        $name_file ="mkt-reports.sale-records.sale_record_export";
        if ($this->type == "2" || $this->type == "1") {
            $name_file = "mkt-reports.sale-records.sale_record_ExCs_export";
        }
        return view($name_file, [
            'data' => $this->data,
            'date'=>$this->date,
            'currency'=>$this->currency,
            'type'=> $this->type
        ]);
    }
    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {

                $sheet = $event->sheet->getDelegate();
                // Set Khmer OS Battambang for header
                $sheet->getStyle('A1:N3')->getFont()->setName('Khmer OS Battambang');
                $highestRow = $sheet->getHighestRow();
                $sheet->getStyle("A3:N$highestRow")->getFont()->setName('Khmer OS Battambang');
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
                ];

                foreach ($widths as $col => $width) {
                    $sheet->getColumnDimension($col)->setWidth($width);
                }

                $sheet->getParent()->getDefaultStyle()->getFont()->setName('Khmer OS Battambang');
            }
        ];
    }
}
