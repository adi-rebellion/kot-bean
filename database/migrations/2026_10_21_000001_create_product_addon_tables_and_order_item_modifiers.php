<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('product_addons', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->decimal('price', 10, 2)->default(0);
            $table->boolean('is_available')->default(true);
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();

            $table->index(['product_id', 'is_available', 'sort_order']);
        });

        Schema::table('order_items', function (Blueprint $table) {
            $table->json('modifiers')->nullable()->after('notes');
            $table->string('modifiers_fingerprint', 64)->nullable()->after('modifiers');
        });
    }

    public function down(): void
    {
        Schema::table('order_items', function (Blueprint $table) {
            $table->dropColumn(['modifiers', 'modifiers_fingerprint']);
        });

        Schema::dropIfExists('product_addons');
    }
};
