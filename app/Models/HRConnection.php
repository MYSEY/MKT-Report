<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class HRConnection extends Model
{
    protected $connection = 'mysqlhrconnection';
    protected $table = 'users';
}
