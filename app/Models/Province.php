<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Province extends Model
{
    use HasFactory;
    protected $connection = 'mysqlhrconnection';
    protected $table = 'provinces';
    protected $guarded = ['id'];
    protected $fillable = [
        'code',
        'khaet_name_km',
        'khaet_name_latin',
        'khaet_name_en',
        'name_km',
        'name_latin',
        'name_en',
        'full_name_km',
        'full_name_latin',
        'full_name_en',
        'address_km',
        'address_latin',
        'address_en',
    ];
    // In Province.php Model
    public function branches() {
        return $this->hasMany(Branch::class, 'current_province', 'code');
    }
}
