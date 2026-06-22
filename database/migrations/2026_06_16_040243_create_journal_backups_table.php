<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * php artisan migrate:refresh --path=database/migrations/2026_06_16_040243_create_journal_backups_table.php
     */
    public function up(): void
    {
        Schema::create('journal_backups', function (Blueprint $table) {
            $table->id();
            $table->string('TransactionMonth', 7)->nullable(); 
            $table->string('GLAcc', 50)->nullable();     
            $table->string('CategoryID', 50)->nullable(); 
            $table->string('Currency', 10)->nullable();  
            $table->string('Reference', 100)->nullable(); 
            $table->string('Module', 20)->nullable();     
            
            $table->string('KhName', 255)->nullable();        
            $table->string('EnName', 255)->nullable();        
            $table->text('Transaction')->nullable();      
            $table->string('UserReference', 100)->nullable();
            
            // 💡 កែសម្រួល៖ ប្តូរពី null ទៅជា (20, 4) ដើម្បីឱ្យ MySQL ស្គាល់ប្រវែងច្បាស់លាស់
            $table->decimal('Amount_KHR', 30, 9)->default(0.000000000);
            $table->decimal('Amount_USD', 30, 9)->default(0.000000000);
            $table->decimal('Total_Amount_KHR', 30, 9)->default(0.000000000); 
            $table->decimal('Income_Tax', 30, 9)->default(0.000000000);

            $table->decimal('TotalLCYAmount', 30, 9)->default(0.000000000);       
            $table->decimal('TotalLCYPrevBalance', 30, 9)->default(0.000000000);
            
            $table->timestamps(); 
            
            $table->index('TransactionMonth');
            $table->index('GLAcc');
            $table->index('Reference');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('journal_backups');
    }
};
