<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * php artisan migrate:refresh --path=database/migrations/2026_04_22_070845_create_interest_incomes_table.php
     */ 
    public function up(): void
    {
        Schema::create('interest_incomes', function (Blueprint $table) {
            $table->id();
            $table->string('account_number');
            $table->string('account_name')->nullable();
            $table->string('type');
            $table->string('created_by')->nullable();
            $table->string('updated_by')->nullable();
            $table->dateTime('deleted_at')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('interest_incomes');
    }
};
