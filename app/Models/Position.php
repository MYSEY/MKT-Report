<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Position extends Model
{
    use HasFactory;
    use SoftDeletes;
    protected $table = 'positions';
    protected $guarded = ['id'];
    protected $fillable = [
        'position_id',
        'type',
        'created_by',
        'updated_by',
        'deleted_at',
    ];
    public function hrPosition() 
    {
        return $this->belongsTo(HrPosition::class, 'position_id', 'id');
    }
}
