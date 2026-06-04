<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * php artisan migrate:refresh --path=database/migrations/2026_06_04_043840_create_branch_codes_table.php
     */
    public function up(): void
    {
        Schema::create('branch_codes', function (Blueprint $table) {
            $table->id();
            $table->string('code');
            $table->string('abbreviations')->nullable();
            $table->string('name');
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
        Schema::dropIfExists('branch_codes');
    }
};
