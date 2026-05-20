<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Customers
        Schema::create('customers', function (Blueprint $table) {
            $table->id();
            $table->string('customer_number', 50)->unique();
            $table->string('name');
            $table->string('email')->nullable();
            $table->string('phone', 50)->nullable();
            $table->text('address')->nullable();
            $table->string('city', 100)->nullable();
            $table->string('state', 100)->nullable();
            $table->string('postal_code', 20)->nullable();
            $table->string('country', 100)->nullable();
            $table->string('tax_id', 100)->nullable();
            $table->enum('customer_type', ['individual', 'business'])->default('individual');
            $table->enum('status', ['active', 'inactive'])->default('active');
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        // Payments
        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained()->onDelete('cascade');
            $table->string('payment_number', 50)->unique();
            $table->enum('payment_method', ['cash', 'card', 'digital_wallet', 'bank_transfer', 'credit', 'gift_card', 'check']);
            $table->decimal('amount', 10, 2);
            $table->string('currency', 3)->default('USD');
            $table->enum('status', ['pending', 'processing', 'completed', 'failed', 'refunded', 'voided'])->default('pending');
            $table->string('transaction_id')->nullable();
            $table->string('gateway', 50)->nullable();
            $table->text('gateway_response')->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->foreignId('user_id')->constrained();
            $table->timestamps();
        });

        // Payment Splits
        Schema::create('payment_splits', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained()->onDelete('cascade');
            $table->foreignId('payment_id')->constrained()->onDelete('cascade');
            $table->integer('split_number');
            $table->decimal('amount', 10, 2);
            $table->string('payment_method', 50);
            $table->enum('status', ['pending', 'completed'])->default('pending');
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        // Refunds
        Schema::create('refunds', function (Blueprint $table) {
            $table->id();
            $table->foreignId('payment_id')->constrained()->onDelete('cascade');
            $table->foreignId('order_id')->constrained()->onDelete('cascade');
            $table->string('refund_number', 50)->unique();
            $table->decimal('amount', 10, 2);
            $table->enum('reason', ['customer_request', 'wrong_order', 'quality_issue', 'cancelled', 'other']);
            $table->text('reason_notes')->nullable();
            $table->enum('refund_method', ['original', 'cash', 'credit', 'gift_card']);
            $table->enum('status', ['pending', 'processing', 'completed', 'failed'])->default('pending');
            $table->string('transaction_id')->nullable();
            $table->text('gateway_response')->nullable();
            $table->timestamp('processed_at')->nullable();
            $table->foreignId('user_id')->constrained();
            $table->timestamps();
        });

        // Tips
        Schema::create('tips', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained()->onDelete('cascade');
            $table->foreignId('payment_id')->nullable()->constrained();
            $table->decimal('amount', 10, 2);
            $table->enum('tip_type', ['cash', 'card', 'digital']);
            $table->foreignId('employee_id')->nullable()->constrained('users');
            $table->enum('distribution_status', ['pending', 'distributed'])->default('pending');
            $table->timestamp('distributed_at')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        // Tip Distributions
        Schema::create('tip_distributions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tip_id')->constrained()->onDelete('cascade');
            $table->foreignId('employee_id')->constrained('users');
            $table->decimal('amount', 10, 2);
            $table->decimal('percentage', 5, 2)->nullable();
            $table->date('distribution_date');
            $table->enum('status', ['pending', 'paid'])->default('pending');
            $table->timestamp('paid_at')->nullable();
            $table->timestamps();
        });

        // Customer Credits
        Schema::create('customer_credits', function (Blueprint $table) {
            $table->id();
            $table->foreignId('customer_id')->constrained()->onDelete('cascade');
            $table->decimal('credit_limit', 10, 2)->default(0);
            $table->decimal('current_balance', 10, 2)->default(0);
            $table->decimal('available_credit', 10, 2)->default(0);
            $table->string('payment_terms', 100)->nullable();
            $table->enum('status', ['active', 'suspended', 'closed'])->default('active');
            $table->timestamps();
        });

        // Credit Transactions
        Schema::create('credit_transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('customer_credit_id')->constrained()->onDelete('cascade');
            $table->enum('transaction_type', ['charge', 'payment', 'adjustment', 'refund']);
            $table->decimal('amount', 10, 2);
            $table->decimal('balance_after', 10, 2);
            $table->string('reference_type', 50)->nullable();
            $table->unsignedBigInteger('reference_id')->nullable();
            $table->text('description')->nullable();
            $table->foreignId('user_id')->constrained();
            $table->timestamps();
        });

        // Payment Gateways
        Schema::create('payment_gateways', function (Blueprint $table) {
            $table->id();
            $table->string('name', 100);
            $table->enum('gateway_type', ['stripe', 'paypal', 'square', 'authorize_net', 'custom']);
            $table->string('api_key')->nullable();
            $table->string('api_secret')->nullable();
            $table->string('webhook_secret')->nullable();
            $table->boolean('is_active')->default(true);
            $table->boolean('is_test_mode')->default(false);
            $table->json('configuration')->nullable();
            $table->timestamps();
        });

        // Invoices
        Schema::create('invoices', function (Blueprint $table) {
            $table->id();
            $table->string('invoice_number', 50)->unique();
            $table->foreignId('order_id')->constrained()->onDelete('cascade');
            $table->foreignId('customer_id')->nullable()->constrained();
            $table->date('issue_date');
            $table->date('due_date')->nullable();
            $table->decimal('subtotal', 10, 2);
            $table->decimal('tax', 10, 2)->default(0);
            $table->decimal('discount', 10, 2)->default(0);
            $table->decimal('total', 10, 2);
            $table->decimal('paid_amount', 10, 2)->default(0);
            $table->decimal('balance', 10, 2);
            $table->enum('status', ['draft', 'sent', 'paid', 'partial', 'overdue', 'cancelled'])->default('draft');
            $table->text('notes')->nullable();
            $table->text('terms')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('invoices');
        Schema::dropIfExists('payment_gateways');
        Schema::dropIfExists('credit_transactions');
        Schema::dropIfExists('customer_credits');
        Schema::dropIfExists('tip_distributions');
        Schema::dropIfExists('tips');
        Schema::dropIfExists('refunds');
        Schema::dropIfExists('payment_splits');
        Schema::dropIfExists('payments');
        Schema::dropIfExists('customers');
    }
};
