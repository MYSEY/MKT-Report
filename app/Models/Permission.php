<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Permission extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $table = 'permissions';
    protected $guarded = ['id'];
    protected $fillable = [
        'category_id',
        'name',
        'created_by',
        'updated_by',
        'deleted_at',
    ];
}
