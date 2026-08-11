<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class VerifyRepaymentAgentDetail extends Model
{
    use HasFactory;
    protected $table = 'verify_repayment_agent_details';
    protected $guarded = ['id'];
    protected $fillable = [
        'verify_repayment_agent_id',
        'Branch',
        'DrAccount',
        'DrCategory',
        'DrCurrency',
        'CrAccount',
        'CrCategory',
        'CrCurrency',
        'Amount',
        'LCYAmount',
        'ExchangeRate',
        'Transaction',
        'TranDate',
        'Reference',
        'Note',
        'DrGLKey',
        'CrGLKey',
        'Module',
        'Officer',
        'DisbursementList',
        'TargetBranch',
        'TargetBranchDrCr',
        'created_by',
        'updated_by',
    ];
}
