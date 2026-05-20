<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->enum('payment_method', ['cash', 'card', 'digital_wallet'])->default('cash')->after('status');
            $table->enum('payment_status', ['pending', 'paid', 'refunded', 'cancelled'])->default('pending')->after('payment_method');
            $table->decimal('paid_amount', 10, 2)->nullable()->after('payment_status');
            $table->decimal('change_amount', 10, 2)->nullable()->after('paid_amount');
            $table->timestamp('paid_at')->nullable()->after('change_amount');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn(['payment_method', 'payment_status', 'paid_amount', 'change_amount', 'paid_at']);
        });
    }
};
