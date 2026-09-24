<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('restaurant_user', function (Blueprint $table) {
            $table->id();
            $table->foreignId('restaurant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('role_id')->constrained()->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['restaurant_id', 'user_id']);
            $table->index(['user_id', 'restaurant_id']);
        });

        $existing = DB::table('users')
            ->whereNotNull('restaurant_id')
            ->whereNotNull('role_id')
            ->get(['id', 'restaurant_id', 'role_id', 'created_at', 'updated_at']);

        foreach ($existing as $user) {
            DB::table('restaurant_user')->insert([
                'restaurant_id' => $user->restaurant_id,
                'user_id' => $user->id,
                'role_id' => $user->role_id,
                'created_at' => $user->created_at,
                'updated_at' => $user->updated_at,
            ]);
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('restaurant_user');
    }
};
