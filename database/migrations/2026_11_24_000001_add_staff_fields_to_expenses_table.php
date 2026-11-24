<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('expenses', function (Blueprint $table) {
            $table->foreignId('paid_by_user_id')
                ->nullable()
                ->after('created_by')
                ->constrained('users')
                ->nullOnDelete();
            $table->foreignId('pending_on_user_id')
                ->nullable()
                ->after('paid_by_user_id')
                ->constrained('users')
                ->nullOnDelete();

            $table->index(['restaurant_id', 'paid_by_user_id']);
            $table->index(['restaurant_id', 'pending_on_user_id']);
        });
    }

    public function down(): void
    {
        Schema::table('expenses', function (Blueprint $table) {
            $table->dropForeign(['paid_by_user_id']);
            $table->dropForeign(['pending_on_user_id']);
            $table->dropColumn(['paid_by_user_id', 'pending_on_user_id']);
        });
    }
};