<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * php artisan migrate:refresh --path=database/migrations/2026_08_10_061139_create_verify_repayment_agents_table.php
     */
    public function up(): void
    {
        Schema::create('verify_repayment_agents', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->dateTime('date')->nullable();
            $table->string('branch')->nullable();
            $table->string('memo')->nullable();
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
        Schema::dropIfExists('verify_repayment_agents');
    }
};
