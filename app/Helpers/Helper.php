<?php

namespace App\Helpers;

class Helper
{
    static function getLang()
    {
        return app()->getLocale();
    }
}