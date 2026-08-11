<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * php artisan migrate:refresh --path=database/migrations/2026_08_11_144406_create_verify_repayment_agent_branch_details_table.php
     */
    public function up(): void
    {
        Schema::create('verify_repayment_agent_branch_details', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('verify_repayment_agent_id');
            $table->string('Branch')->nullable();
            $table->string('DrAccount')->nullable();
            $table->string('DrCategory')->nullable();
            $table->string('DrCurrency')->nullable();
            $table->string('CrAccount')->nullable();
            $table->string('CrCategory')->nullable();
            $table->string('CrCurrency')->nullable();
            $table->string('Amount')->nullable();
            $table->string('LDNumber')->nullable();
            $table->string('CCY')->nullable();
            $table->string('KHName')->nullable();
            $table->string('Outstanding')->nullable();
            $table->string('TotaArr')->nullable();
            $table->string('PricipalArr')->nullable();
            $table->string('InteresArr')->nullable();
            $table->string('PenaltyArr')->nullable();
            $table->string('ChargeArr')->nullable();
            $table->string('DateCol')->nullable();
            $table->string('TotalCol')->nullable();
            $table->string('Principal')->nullable();
            $table->string('Interest')->nullable();
            $table->string('Charge')->nullable();
            $table->string('LoanProduct')->nullable();
            $table->text('Note')->nullable();
            $table->string('Agent')->nullable();
            $table->string('Officer')->nullable();
            $table->string('ClientTel')->nullable();
            $table->string('created_by')->nullable();
            $table->string('updated_by')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('verify_repayment_agent_branch_details');
    }
};
