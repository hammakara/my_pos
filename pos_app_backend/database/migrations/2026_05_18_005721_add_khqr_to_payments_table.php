<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (DB::getDriverName() !== 'sqlite') {
            DB::statement("ALTER TABLE payments MODIFY COLUMN payment_method ENUM('cash', 'card', 'digital_wallet', 'bank_transfer', 'credit', 'gift_card', 'check', 'khqr') NOT NULL");
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (DB::getDriverName() !== 'sqlite') {
            DB::statement("ALTER TABLE payments MODIFY COLUMN payment_method ENUM('cash', 'card', 'digital_wallet', 'bank_transfer', 'credit', 'gift_card', 'check') NOT NULL");
        }
    }
};
