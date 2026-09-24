<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('vendors', function (Blueprint $table) {
            $table->id();
            $table->foreignId('restaurant_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('phone')->nullable();
            $table->string('email')->nullable();
            $table->text('address')->nullable();
            $table->text('notes')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['restaurant_id', 'name']);
            $table->index(['restaurant_id', 'phone']);
        });

        Schema::table('expenses', function (Blueprint $table) {
            $table->foreignId('vendor_id')->nullable()->after('restaurant_id')->constrained()->nullOnDelete();
            $table->string('payment_status')->default('paid')->after('amount');
            $table->decimal('paid_amount', 12, 2)->default(0)->after('payment_status');
            $table->date('due_date')->nullable()->after('expense_date');
            $table->timestamp('paid_at')->nullable()->after('due_date');

            $table->index(['restaurant_id', 'vendor_id', 'payment_status']);
            $table->index(['restaurant_id', 'payment_status', 'expense_date']);
        });
    }

    public function down(): void
    {
        Schema::table('expenses', function (Blueprint $table) {
            $table->dropConstrainedForeignId('vendor_id');
            $table->dropColumn(['payment_status', 'paid_amount', 'due_date', 'paid_at']);
        });

        Schema::dropIfExists('vendors');
    }
};
