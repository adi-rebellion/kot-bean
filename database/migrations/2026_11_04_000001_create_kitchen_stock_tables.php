<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('stock_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('restaurant_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('unit')->default('units');
            $table->decimal('current_quantity', 12, 2)->default(0);
            $table->decimal('min_quantity', 12, 2)->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();

            $table->index(['restaurant_id', 'name']);
        });

        Schema::create('stock_daily_records', function (Blueprint $table) {
            $table->id();
            $table->foreignId('stock_item_id')->constrained()->cascadeOnDelete();
            $table->date('record_date');
            $table->decimal('opening_quantity', 12, 2);
            $table->decimal('restock_quantity', 12, 2)->default(0);
            $table->decimal('closing_quantity', 12, 2)->nullable();
            $table->decimal('used_quantity', 12, 2)->nullable();
            $table->foreignId('recorded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->unique(['stock_item_id', 'record_date']);
            $table->index(['stock_item_id', 'record_date']);
        });

        Schema::create('stock_item_vendor_prices', function (Blueprint $table) {
            $table->id();
            $table->foreignId('stock_item_id')->constrained()->cascadeOnDelete();
            $table->foreignId('vendor_id')->constrained()->cascadeOnDelete();
            $table->decimal('unit_price', 12, 2);
            $table->string('price_unit')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->unique(['stock_item_id', 'vendor_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('stock_item_vendor_prices');
        Schema::dropIfExists('stock_daily_records');
        Schema::dropIfExists('stock_items');
    }
};
