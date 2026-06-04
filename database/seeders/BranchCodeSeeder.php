<?php

namespace Database\Seeders;
use App\Models\BranchCode;
use Illuminate\Support\Facades\Auth;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class BranchCodeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     * php artisan db:seed --class=BranchCodeSeeder
     */
    public function run(): void
    {
        BranchCode::create([
            'code' => '00',
            'abbreviations' => 'HO',
            'name' => "Operation Unit",
        ]);
        BranchCode::create([
            'code' => '01',
            'abbreviations' => "ANS",
            'name' => "Angsnuol Branch",
        ]);
        BranchCode::create([
            'code' => '02',
            'abbreviations' => "TKM",
            'name' => "Takhmau Branch",
        ]);
        BranchCode::create([
            'code' => '03',
            'abbreviations' => "KPB",
            'name' => "Kong Pisei Branch",
        ]);
        BranchCode::create([
            'code' => '04',
            'abbreviations' => "KPS",
            'name' => "Kampong Speu Branch",
        ]);
        BranchCode::create([
            'code' => '05',
            'abbreviations' => "KTB",
            'name' => "Kampong Trach Branch",
        ]);
        BranchCode::create([
            'code' => '06',
            'abbreviations' => "SAB",
            'name' => "Saang Branch",
        ]);
        BranchCode::create([
            'code' => '07',
            'abbreviations' => "DIG",
            'name' => "Digital Unit",
        ]);
    }
}
