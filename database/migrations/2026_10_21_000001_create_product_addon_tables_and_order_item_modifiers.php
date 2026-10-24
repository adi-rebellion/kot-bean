<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('product_addon_groups', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('selection_type')->default('single');
            $table->boolean('is_required')->default(false);
            $table->unsignedTinyInteger('max_selections')->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();

            $table->index(['product_id', 'sort_order']);
        });

        Schema::create('product_addon_options', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_addon_group_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->decimal('price', 10, 2)->default(0);
            $table->boolean('is_available')->default(true);
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();

            $table->index(['product_addon_group_id', 'is_available']);
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

        Schema::dropIfExists('product_addon_options');
        Schema::dropIfExists('product_addon_groups');
    }
};
