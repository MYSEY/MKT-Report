<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class VerifyRepaymentAgentBranchDetail extends Model
{
    use HasFactory;
    protected $table = 'verify_repayment_agent_branch_details';
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
        'LDNumber',
        'CCY',
        'KHName',
        'Outstanding',
        'TotaArr',
        'PricipalArr',
        'InteresArr',
        'PenaltyArr',
        'ChargeArr',
        'DateCol',
        'TotalCol',
        'Principal',
        'Interest',
        'Charge',
        'LoanProduct',
        'Note',
        'Agent',
        'Officer',
        'ClientTel',
        'created_by',
        'updated_by',
    ];
}
