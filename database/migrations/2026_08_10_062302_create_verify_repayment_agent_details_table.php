<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * php artisan migrate:refresh --path=database/migrations/2026_08_10_062302_create_verify_repayment_agent_details_table.php
     */
    public function up(): void
    {
        Schema::create('verify_repayment_agent_details', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('verify_repayment_agent_id');
            $table->string('Branch')->nullable();
            $table->string('DrAccount')->nullable();
            $table->string('DrCategory')->nullable();
            $table->string('DrCurrency')->nullable();
            $table->string('CrAccount');
            $table->string('CrCategory')->nullable();
            $table->string('CrCurrency')->nullable();
            $table->string('Amount');
            $table->string('LCYAmount')->nullable();
            $table->string('ExchangeRate')->nullable();
            $table->string('Transaction');
            $table->date('TranDate');
            $table->string('Reference');
            $table->string('Note')->nullable();
            $table->string('DrGLKey')->nullable();
            $table->string('CrGLKey')->nullable();
            $table->string('Module')->nullable();
            $table->string('Officer')->nullable();
            $table->string('DisbursementList')->nullable();
            $table->string('TargetBranch')->nullable();
            $table->string('TargetBranchDrCr')->nullable();
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
        Schema::dropIfExists('verify_repayment_agent_details');
    }
};
