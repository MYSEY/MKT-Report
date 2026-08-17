<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\VerifyRepaymentAgent;
use App\Models\VerifyRepaymentAgentDetail;
use App\Models\VerifyRepaymentAgentBranchDetail;
use Carbon\Carbon;

class VerifyRepaymentAgentSeeder extends Seeder
{
    public function run()
    {
        $startDate = Carbon::parse('2026-07-14');
        $endDate   = Carbon::parse('2026-08-13');

        $referenceCounter = 0;
        $currentDate      = $startDate->copy();

        while ($currentDate <= $endDate) {
            $dateStr = $currentDate->format('Y-m-d');

            // ✅ Create 2 uploads per day
            for ($upload = 1; $upload <= 2; $upload++) {
                $memo = $dateStr . '-' . str_pad($upload, 2, '0', STR_PAD_LEFT);

                $verifyrepayment = VerifyRepaymentAgent::create([
                    'name'       => 'Test User',
                    'date'       => $dateStr . ' 0' . $upload . ':00:00',
                    'branch'     => 'HQ',
                    'memo'       => $memo,
                    'created_by' => 'Test User',
                    'created_at' => $dateStr . ' 0' . $upload . ':00:00',
                    'updated_at' => $dateStr . ' 0' . $upload . ':00:00',
                ]);

                // ✅ Create 5 detail records per upload
                for ($i = 1; $i <= 5; $i++) {
                    $referenceCounter++;

                    $detail = [
                        'verify_repayment_agent_id' => $verifyrepayment->id,
                        'Branch'                    => collect(['HQ', 'ANS', 'HO', 'SAB', 'KPB'])->random(),
                        'DrAccount'                 => '',
                        'DrCategory'                => collect(['2789101', '2789103', '2789104'])->random(),
                        'DrCurrency'                => collect(['USD', 'KHR'])->random(),
                        'CrAccount'                 => 'DD00' . rand(1000, 9999),
                        'CrCategory'                => '3852204',
                        'CrCurrency'                => collect(['USD', 'KHR'])->random(),
                        'Amount'                    => rand(100, 1000) . '.' . rand(10, 99),
                        'LCYAmount'                 => rand(100, 1000) . '.000000000000000000',
                        'ExchangeRate'              => '1.000000000000000000',
                        'Transaction'               => 40,
                        'TranDate'                  => $dateStr,
                        'Reference'                 => $referenceCounter,
                        'Note'                      => collect(['WING', 'TUME', 'AMKR', 'ACLB'])->random() . ' Test Name',
                        'DrGLKey'                   => '',
                        'CrGLKey'                   => '',
                        'Module'                    => 'FT',
                        'Officer'                   => '',
                        'DisbursementList'           => '',
                        'TargetBranch'              => 'HQ',
                        'TargetBranchDrCr'          => 'Dr',
                        'created_by'                => 'Test User',
                        'updated_by'                => null,
                        'created_at'                => $dateStr . ' 0' . $upload . ':00:00',
                        'updated_at'                => $dateStr . ' 0' . $upload . ':00:00',
                    ];

                    VerifyRepaymentAgentDetail::insert($detail);

                    // ✅ Branch detail
                    VerifyRepaymentAgentBranchDetail::insert([
                        'verify_repayment_agent_id' => $verifyrepayment->id,
                        'Branch'                    => $detail['Branch'],
                        'DrAccount'                 => '',
                        'DrCategory'                => $detail['DrCategory'],
                        'DrCurrency'                => $detail['DrCurrency'],
                        'CrAccount'                 => $detail['CrAccount'],
                        'CrCategory'                => '3852204',
                        'CrCurrency'                => $detail['DrCurrency'],
                        'Amount'                    => $detail['Amount'],
                        'LDNumber'                  => 'LC' . rand(100000, 999999),
                        'CCY'                       => $detail['DrCurrency'],
                        'KHName'                    => 'Test Name ' . $i,
                        'Outstanding'               => rand(1000, 50000),
                        'TotaArr'                   => rand(0, 500),
                        'PricipalArr'               => rand(0, 300),
                        'InteresArr'                => rand(0, 100),
                        'PenaltyArr'                => rand(0, 50),
                        'ChargeArr'                 => rand(0, 50),
                        'DateCol'                   => $dateStr,
                        'TotalCol'                  => rand(100, 1000),
                        'Principal'                 => rand(50, 500),
                        'Interest'                  => rand(10, 100),
                        'Charge'                    => rand(0, 50),
                        'LoanProduct'               => collect(['Micro Loan', 'Medium Loan', 'Small Loan'])->random(),
                        'Note'                      => collect(['Arrears', 'Due Date', 'Prepaid'])->random(),
                        'Agent'                     => collect(['WING', 'TUME', 'AMKR', 'ACLB'])->random(),
                        'Officer'                   => '',
                        'ClientTel'                 => '0' . rand(10000000, 99999999),
                        'created_by'                => 'Test User',
                        'updated_by'                => null,
                        'created_at'                => $dateStr . ' 0' . $upload . ':00:00',
                        'updated_at'                => $dateStr . ' 0' . $upload . ':00:00',
                    ]);
                }
            }

            $currentDate->addDay();
        }

        $this->command->info('✅ Seeded data from 2026-07-14 to 2026-08-14 with 2 uploads per day and 5 details per upload.');
    }
}