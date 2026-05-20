<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Order Modifications
        Schema::create('order_modifications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained()->onDelete('cascade');
            $table->enum('modification_type', ['item_added', 'item_removed', 'quantity_changed', 'price_changed', 'discount_applied', 'note_added']);
            $table->text('old_value')->nullable();
            $table->text('new_value')->nullable();
            $table->string('reason')->nullable();
            $table->foreignId('user_id')->constrained();
            $table->timestamps();
        });

        // Order Splits
        Schema::create('order_splits', function (Blueprint $table) {
            $table->id();
            $table->foreignId('parent_order_id')->constrained('orders')->onDelete('cascade');
            $table->integer('split_number');
            $table->decimal('amount', 10, 2);
            $table->string('payment_method', 50)->nullable();
            $table->enum('payment_status', ['pending', 'paid'])->default('pending');
            $table->string('customer_name')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        // Order Timeline
        Schema::create('order_timeline', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained()->onDelete('cascade');
            $table->enum('event_type', ['created', 'status_changed', 'modified', 'payment_received', 'cancelled', 'completed']);
            $table->text('description');
            $table->string('old_value')->nullable();
            $table->string('new_value')->nullable();
            $table->foreignId('user_id')->nullable()->constrained();
            $table->timestamps();
            
            $table->index(['order_id', 'created_at']);
        });

        // Kitchen Orders
        Schema::create('kitchen_orders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained()->onDelete('cascade');
            $table->enum('station', ['grill', 'fryer', 'salad', 'dessert', 'drinks', 'general']);
            $table->enum('priority', ['normal', 'high', 'urgent'])->default('normal');
            $table->integer('preparation_time')->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->enum('status', ['pending', 'preparing', 'ready', 'served'])->default('pending');
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        // Deliveries
        Schema::create('deliveries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained()->onDelete('cascade');
            $table->string('customer_name');
            $table->string('customer_phone', 50);
            $table->text('delivery_address');
            $table->text('delivery_instructions')->nullable();
            $table->foreignId('driver_id')->nullable()->constrained('users');
            $table->timestamp('estimated_time')->nullable();
            $table->timestamp('actual_delivery_time')->nullable();
            $table->enum('status', ['pending', 'assigned', 'picked_up', 'in_transit', 'delivered', 'failed'])->default('pending');
            $table->decimal('delivery_fee', 10, 2)->default(0);
            $table->string('tracking_url')->nullable();
            $table->timestamps();
        });

        // Order Notes
        Schema::create('order_notes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained()->onDelete('cascade');
            $table->enum('note_type', ['customer', 'kitchen', 'internal', 'delivery']);
            $table->text('note');
            $table->boolean('is_important')->default(false);
            $table->foreignId('user_id')->constrained();
            $table->timestamps();
        });

        // Scheduled Orders
        Schema::create('scheduled_orders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained()->onDelete('cascade');
            $table->date('scheduled_date');
            $table->time('scheduled_time');
            $table->string('customer_name')->nullable();
            $table->string('customer_phone', 50)->nullable();
            $table->string('customer_email')->nullable();
            $table->boolean('reminder_sent')->default(false);
            $table->enum('status', ['scheduled', 'confirmed', 'cancelled', 'completed'])->default('scheduled');
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('scheduled_orders');
        Schema::dropIfExists('order_notes');
        Schema::dropIfExists('deliveries');
        Schema::dropIfExists('kitchen_orders');
        Schema::dropIfExists('order_timeline');
        Schema::dropIfExists('order_splits');
        Schema::dropIfExists('order_modifications');
    }
};
