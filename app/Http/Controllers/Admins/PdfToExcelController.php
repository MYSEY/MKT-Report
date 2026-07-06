<?php

namespace App\Http\Controllers\Admins;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\PdfToExcelExport;
use Smalot\PdfParser\Parser;

class PdfToExcelController extends Controller
{
    public function index()
    {
        return view('pdf.pdf-to-excel');
    }

    public function convert(Request $request)
    {
        $request->validate([
            'pdf_file' => 'required|mimes:pdf|max:10240',
        ]);

        $file   = $request->file('pdf_file');
        $parser = new Parser();
        $pdf    = $parser->parseFile($file->getRealPath());

        $rows   = [];
        $rows[] = ['Date', 'Transaction Details', 'Money In', 'Money Out', 'Balance'];

        // Combine all pages into one list of raw lines (keep tabs!)
        $allLines = [];
        foreach ($pdf->getPages() as $page) {
            $lines = preg_split("/\r\n|\n|\r/", $page->getText());
            foreach ($lines as $line) {
                if (trim($line) !== '') {
                    $allLines[] = $line;
                }
            }
        }

        // Transaction starts with "Mon DD, YYYY" + description
        // EXCLUDE lines where date is followed by a TIME (e.g. "03:50 PM")
        // because those are continuation lines from wrapped description, NOT new transactions
        $dateStartPattern = '/^([A-Z][a-z]{2}\s+\d{2},\s+\d{4})\s+(?!\d{2}:\d{2}\s+(?:AM|PM))(.+)$/';

        // Amount line patterns (after normalizing tabs to spaces)
        // Currency captured dynamically (KHR or USD)
        // Pattern A: "3,700.00 KHR KHR 8,063.53 KHR"  -> Money In filled
        $patternA = '/^([\d,]+\.\d{2}) (KHR|USD)\s*(?:KHR|USD)\s+([\d,]+\.\d{2}) (KHR|USD)$/';
        // Pattern B: "KHR 5,000.00 KHR 3,063.53 KHR"  -> Money Out filled
        $patternB = '/^(?:KHR|USD)\s*([\d,]+\.\d{2}) (KHR|USD)\s+([\d,]+\.\d{2}) (KHR|USD)$/';

        $skipContains = [
            'Advanced Bank of Asia', 'ACCOUNT ACTIVITY', 'Date Transaction Details',
            'For period', 'NA, NA,', 'Phnom Penh', 'Account Holder', 'Account Type',
            'Account No', 'Account Currency', 'Bank SWIFT', 'ACCOUNT SUMMARY',
            'Opening Balance', 'Total Money', 'Ending Balance', 'ACCOUNT DETAILS',
            'ACCOUNT STATEMENT', 'DISCLAIMER', 'informational', 'error-free',
            'not responsible', 'Page ',
        ];

        $shouldSkip = function (string $line) use ($skipContains): bool {
            foreach ($skipContains as $s) {
                if (str_contains($line, $s)) return true;
            }
            return false;
        };

        $n = count($allLines);
        $i = 0;

        while ($i < $n) {
            $line = trim($allLines[$i]);

            if ($line === '' || $shouldSkip($line)) {
                $i++;
                continue;
            }

            if (!preg_match($dateStartPattern, $line, $dm)) {
                $i++;
                continue;
            }

            // ---- Found a transaction start ----
            $date      = $dm[1];
            $firstDesc = $dm[2];
            $i++;

            $extraDesc = [];
            $moneyIn   = '';
            $moneyOut  = '';
            $balance   = '';

            while ($i < $n) {
                $rawNext = $allLines[$i];
                $next    = trim($rawNext);

                if ($next === '')          { $i++; continue; }
                if ($shouldSkip($next))    { $i++; continue; }
                if (preg_match($dateStartPattern, $next)) break;

                // Normalize tabs + multi-spaces to single space
                $normalized = preg_replace('/\s+/', ' ', str_replace("\t", ' ', $next));

                if (preg_match($patternA, $normalized, $ma)) {
                    $moneyIn  = $ma[1] . ' ' . $ma[2]; // e.g. "3,700.00 KHR"
                    $moneyOut = $ma[2];                 // currency only e.g. "KHR"
                    $balance  = $ma[3] . ' ' . $ma[4]; // e.g. "8,063.53 KHR"
                    $i++;
                    continue;
                }

                if (preg_match($patternB, $normalized, $mb)) {
                    $moneyIn  = $mb[2];                 // currency only e.g. "KHR"
                    $moneyOut = $mb[1] . ' ' . $mb[2]; // e.g. "5,000.00 KHR"
                    $balance  = $mb[3] . ' ' . $mb[4]; // e.g. "3,063.53 KHR"
                    $i++;
                    continue;
                }

                $extraDesc[] = $next;
                $i++;
            }

            $detail = trim($firstDesc . ' ' . implode(' ', $extraDesc));
            $detail = preg_replace('/\s+/', ' ', $detail);

            if ($date !== '' && $detail !== '') {
                $rows[] = [$date, $detail, $moneyIn, $moneyOut, $balance];
            }
        }

        return Excel::download(
            new PdfToExcelExport($rows),
            'BankStatement.xlsx'
        );
    }
}