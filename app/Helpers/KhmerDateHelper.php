<?php

namespace App\Helpers;

use Carbon\Carbon;

class KhmerDateHelper
{
    private static $locale = [
        'en' => [
            'month' => ['January', 'February', 'March', 'April', 'May', 'June', 'July', 'August', 'September', 'October', 'November', 'December'],
            'ampm' => ['am', 'pm']
        ],
        'km' => [
            'month' => ['មករា', 'កុម្ភៈ', 'មីនា', 'មេសា', 'ឧសភា', 'មិថុនា', 'កក្កដា', 'សីហា', 'កញ្ញា', 'តុលា', 'វិច្ឆិកា', 'ធ្នូ'],
            'ampm' => ['ព្រឹក', 'ល្ងាច']
        ]
    ];

    public static function toLocaleNumber($num, $lang, $zeroPadding = 0)
    {
        $numString = (string)$num;

        if ($zeroPadding > 0 && strlen($numString) < $zeroPadding) {
            $numString = str_pad($numString, $zeroPadding, '0', STR_PAD_LEFT);
        }

        if ($lang !== 'km') {
            return $numString;
        }

        $khmerNumbers = ['០', '១', '២', '៣', '៤', '៥', '៦', '៧', '៨', '៩'];
        $khmerNumber = '';

        for ($i = 0; $i < strlen($numString); $i++) {
            $khmerNumber .= $khmerNumbers[(int)$numString[$i]];
        }

        return $khmerNumber;
    }

    public static function formatDate($date, $lang = 'km', $format_date = null)
    {
        // បំប្លែងទៅជា Carbon instance ដើម្បីងាយស្រួល handle
        $date = Carbon::parse($date);
        
        $hours = $date->format('H');
        $ampmIndex = ($hours >= 12) ? 1 : 0;
        
        // ប្តូរម៉ោងទៅជា format ១២ម៉ោង
        $hours12 = $date->format('g'); 

        $formattedDate = null;

        if ($format_date) {
            $day = $format_date['day'] ?? false;
            $month = $format_date['month'] ?? false;
            $year = $format_date['year'] ?? false;
            $time = $format_date['time'] ?? false;

            if ($day && !$month && !$year) {
                $formattedDate = self::toLocaleNumber($date->format('d'), $lang, 2);
            } elseif ($month && !$day && !$year) {
                $formattedDate = self::$locale[$lang]['month'][$date->month - 1];
            } elseif ($year && !$day && !$month) {
                $formattedDate = self::toLocaleNumber($date->year, $lang);
            } elseif ($time) {
                $formattedDate = self::toLocaleNumber($hours12, $lang, 2)
                    . ':' . self::toLocaleNumber($date->format('i'), $lang, 2)
                    . ' ' . self::$locale[$lang]['ampm'][$ampmIndex];
            }

            if ($day && $month) {
                $formattedDate = self::toLocaleNumber($date->format('d'), $lang, 2)
                    . '-' . self::$locale[$lang]['month'][$date->month - 1];
            }
            
            if ($month && $year) {
                $formattedDate = self::$locale[$lang]['month'][$date->month - 1]
                    . '-' . self::toLocaleNumber($date->year, $lang);
            }
        } else {
            // Default format
            $formattedDate = self::toLocaleNumber($date->format('d'), $lang, 2)
                . '-' . self::$locale[$lang]['month'][$date->month - 1]
                . '-' . self::toLocaleNumber($date->year, $lang)
                . ' ' . self::toLocaleNumber($hours12, $lang, 2)
                . ':' . self::toLocaleNumber($date->format('i'), $lang, 2)
                . ' ' . self::$locale[$lang]['ampm'][$ampmIndex];
        }

        return $formattedDate;
    }
}