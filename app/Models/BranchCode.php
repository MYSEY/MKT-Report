<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class BranchCode extends Model
{
    use HasFactory;
    use SoftDeletes;
    protected $table = 'branch_codes';
    protected $guarded = ['id'];
    protected $fillable = [
        'code',
        'abbreviations',
        'name',
        'created_by',
        'updated_by',
        'deleted_at',
    ];
}
