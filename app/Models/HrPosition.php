<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class HrPosition extends Model
{
    use HasFactory;
    // 🔥 សំខាន់បំផុត: កំណត់ឱ្យទៅប្រើ Connection របស់ HR Database
    protected $connection = 'mysqlhrconnection'; 
    
    protected $table = 'positions'; // ឈ្មោះ table នៅក្នុង DB HR
    protected $primaryKey = 'id';
}
