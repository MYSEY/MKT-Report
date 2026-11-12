<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * php artisan migrate:refresh --path=database/migrations/2026_11_12_040651_create_mkt_tables_table.php
     * 
     * Run the migration with docker
     * docker exec -it mkt_report_app php artisan migrate --path=database/migrations/2026_11_12_040651_create_mkt_tables_table.php
     */
    public function up()
    {
        Schema::create('mkt-table', function (Blueprint $table) {
            $table->id();
            $table->string('table_name', 100)->unique();
            $table->string('display_name', 150);
            $table->string('date_column', 100)->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        $now  = now();
        $rows = [
            ['MKT_PD_DATE', 'PD DATE'],
            ['MKT_LOAN_CONTRACT', 'LOAN CONTRACT'],
            ['MKT_REP_SCHEDULE', 'REP SCHEDULE'],
            ['MKT_REP_SCHEDULE_AS', 'REP SCHEDULE AS'],
            ['MKT_GL_BALANCE_BACKUP', 'GL BALANCE BACKUP'],
            ['MKT_JOURNAL', 'MKT_JOURNAL'],
            ['MKT_COLLATERAL', 'MKT_COLLATERAL'],
            ['MKT_BORROWING_REP_SCHEDULE_AS', 'BORROWING REP SCHEDULE AS'],
        ];

        foreach ($rows as $i => [$name, $label]) {
            DB::table('mkt-table')->insert([
                'table_name'   => $name,
                'display_name' => $label,
                'date_column'  => 'CreatedOn',
                'sort_order'   => $i + 1,
                'is_active'    => true,
                'created_at'   => $now,
                'updated_at'   => $now,
            ]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('mkt_tables');
    }
};
