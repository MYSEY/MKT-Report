<?php

namespace App\Models;
use Illuminate\Database\Eloquent\SoftDeletes;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class InterestIncome extends Model
{
    use HasFactory;
    use SoftDeletes;
    protected $table = 'interest_incomes';
    protected $guarded = ['id'];
    protected $fillable = [
        'account_number',
        'account_name',
        'type',
        'created_by',
        'updated_by',
        'deleted_at',
    ];
}
