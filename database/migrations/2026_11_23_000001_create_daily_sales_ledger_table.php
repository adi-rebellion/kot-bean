<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('daily_sales_ledgers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('restaurant_id')->constrained()->cascadeOnDelete();
            $table->date('sale_date');
            $table->decimal('cash_amount', 12, 2)->default(0);
            $table->decimal('online_amount', 12, 2)->default(0);
            $table->text('notes')->nullable();
            $table->foreignId('recorded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->unique(['restaurant_id', 'sale_date']);
            $table->index(['restaurant_id', 'sale_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('daily_sales_ledgers');
    }
};