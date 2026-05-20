<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('customer_loyalties', function (Blueprint $table) {
            $table->id();
            $table->foreignId('customer_id')->constrained()->onDelete('cascade');
            $table->foreignId('loyalty_program_id')->constrained()->onDelete('cascade');
            $table->integer('points_balance')->default(0);
            $table->enum('tier_level', ['bronze', 'silver', 'gold', 'platinum'])->default('bronze');
            $table->decimal('total_spent', 10, 2)->default(0);
            $table->date('joined_date');
            $table->date('last_activity_date')->nullable();
            $table->timestamps();
            
            $table->unique(['customer_id', 'loyalty_program_id']);
            $table->index(['customer_id', 'points_balance']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('customer_loyalties');
    }
};
