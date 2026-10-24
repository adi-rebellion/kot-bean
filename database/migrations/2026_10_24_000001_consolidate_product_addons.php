<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('product_addons')) {
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
        }

        if (Schema::hasTable('product_addon_options') && Schema::hasTable('product_addon_groups')) {
            $options = DB::table('product_addon_options')
                ->join('product_addon_groups', 'product_addon_groups.id', '=', 'product_addon_options.product_addon_group_id')
                ->select(
                    'product_addon_groups.product_id',
                    'product_addon_options.name',
                    'product_addon_options.price',
                    'product_addon_options.is_available',
                    'product_addon_options.sort_order',
                    'product_addon_options.created_at',
                    'product_addon_options.updated_at',
                )
                ->orderBy('product_addon_options.id')
                ->get();

            foreach ($options as $row) {
                DB::table('product_addons')->insert([
                    'product_id' => $row->product_id,
                    'name' => $row->name,
                    'price' => $row->price,
                    'is_available' => $row->is_available,
                    'sort_order' => $row->sort_order,
                    'created_at' => $row->created_at,
                    'updated_at' => $row->updated_at,
                ]);
            }

            Schema::dropIfExists('product_addon_options');
            Schema::dropIfExists('product_addon_groups');
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('product_addons');
    }
};
