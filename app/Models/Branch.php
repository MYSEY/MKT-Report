<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Branch extends Model
{
    use HasFactory;
    protected $connection = 'mysqlhrconnection';
    protected $table = 'branchs';
    protected $guarded = ['id'];
    protected $fillable = [
        'branch_name_kh',
        'branch_name_en',
        'direct_manager_id',
        'abbreviations',
        'current_province',
        'address',
        'address_kh',
        'created_by',
        'updated_by',
        'deleted_at',
    ];
    // In user_hr.php Model
    public function users() {
        return $this->hasMany(HRConnection::class, 'branch_id', 'id');
    }
}
