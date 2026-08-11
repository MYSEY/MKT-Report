<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class VerifyRepaymentAgent extends Model
{
    use HasFactory;
    protected $table = 'verify_repayment_agents';
    protected $guarded = ['id'];
    protected $fillable = [
        'name',
        'date',
        'branch',
        'created_by',
        'updated_by',
    ];
}
