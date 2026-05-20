<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Theme Settings
        Schema::create('theme_settings', function (Blueprint $table) {
            $table->id();
            $table->string('setting_key', 100)->unique();
            $table->text('setting_value')->nullable();
            $table->enum('setting_type', ['color', 'font', 'image', 'text', 'number', 'boolean', 'json']);
            $table->string('category', 50)->nullable();
            $table->text('description')->nullable();
            $table->timestamps();
        });

        // Layout Templates
        Schema::create('layout_templates', function (Blueprint $table) {
            $table->id();
            $table->string('name', 100);
            $table->enum('template_type', ['pos', 'kitchen', 'receipt', 'invoice', 'report']);
            $table->json('layout_config');
            $table->boolean('is_default')->default(false);
            $table->boolean('is_active')->default(true);
            $table->string('preview_image')->nullable();
            $table->timestamps();
        });

        // Custom Fields
        Schema::create('custom_fields', function (Blueprint $table) {
            $table->id();
            $table->enum('entity_type', ['product', 'order', 'customer', 'supplier', 'employee']);
            $table->string('field_name', 100);
            $table->string('field_label');
            $table->enum('field_type', ['text', 'number', 'date', 'datetime', 'select', 'multiselect', 'checkbox', 'textarea', 'file']);
            $table->json('field_options')->nullable();
            $table->boolean('is_required')->default(false);
            $table->boolean('is_searchable')->default(false);
            $table->integer('display_order')->default(0);
            $table->json('validation_rules')->nullable();
            $table->text('default_value')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        // Custom Field Values
        Schema::create('custom_field_values', function (Blueprint $table) {
            $table->id();
            $table->foreignId('custom_field_id')->constrained()->onDelete('cascade');
            $table->string('entity_type', 50);
            $table->unsignedBigInteger('entity_id');
            $table->text('field_value')->nullable();
            $table->timestamps();
            
            $table->index(['entity_type', 'entity_id']);
        });

        // Tax Rates
        Schema::create('tax_rates', function (Blueprint $table) {
            $table->id();
            $table->string('name', 100);
            $table->decimal('rate', 5, 2);
            $table->enum('tax_type', ['percentage', 'fixed'])->default('percentage');
            $table->boolean('is_compound')->default(false);
            $table->boolean('is_inclusive')->default(false);
            $table->enum('applies_to', ['all', 'products', 'services', 'shipping'])->default('all');
            $table->integer('priority')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        // Tax Groups
        Schema::create('tax_groups', function (Blueprint $table) {
            $table->id();
            $table->string('name', 100);
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        // Tax Group Rates
        Schema::create('tax_group_rates', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tax_group_id')->constrained()->onDelete('cascade');
            $table->foreignId('tax_rate_id')->constrained()->onDelete('cascade');
            $table->timestamps();
        });

        // Discount Rules
        Schema::create('discount_rules', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->text('description')->nullable();
            $table->enum('discount_type', ['percentage', 'fixed', 'buy_x_get_y', 'bundle']);
            $table->decimal('discount_value', 10, 2);
            $table->integer('min_quantity')->nullable();
            $table->decimal('min_amount', 10, 2)->nullable();
            $table->decimal('max_discount', 10, 2)->nullable();
            $table->enum('applies_to', ['all', 'categories', 'products', 'order']);
            $table->date('start_date')->nullable();
            $table->date('end_date')->nullable();
            $table->time('start_time')->nullable();
            $table->time('end_time')->nullable();
            $table->string('days_of_week', 50)->nullable();
            $table->enum('customer_type', ['all', 'new', 'returning', 'vip'])->nullable();
            $table->integer('usage_limit')->nullable();
            $table->integer('usage_count')->default(0);
            $table->boolean('is_combinable')->default(false);
            $table->integer('priority')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        // Discount Rule Items
        Schema::create('discount_rule_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('discount_rule_id')->constrained()->onDelete('cascade');
            $table->enum('item_type', ['category', 'product']);
            $table->unsignedBigInteger('item_id');
            $table->timestamps();
        });

        // Receipt Templates
        Schema::create('receipt_templates', function (Blueprint $table) {
            $table->id();
            $table->string('name', 100);
            $table->enum('template_type', ['thermal', 'a4', 'email']);
            $table->text('header_content')->nullable();
            $table->text('footer_content')->nullable();
            $table->boolean('show_logo')->default(true);
            $table->boolean('show_barcode')->default(false);
            $table->boolean('show_qr_code')->default(false);
            $table->integer('paper_width')->nullable();
            $table->integer('font_size')->default(12);
            $table->text('template_html')->nullable();
            $table->text('template_css')->nullable();
            $table->boolean('is_default')->default(false);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        // Email Templates
        Schema::create('email_templates', function (Blueprint $table) {
            $table->id();
            $table->string('name', 100);
            $table->enum('template_type', ['order_confirmation', 'invoice', 'receipt', 'welcome', 'password_reset', 'custom']);
            $table->string('subject');
            $table->text('body_html');
            $table->text('body_text')->nullable();
            $table->json('variables')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        // Business Rules
        Schema::create('business_rules', function (Blueprint $table) {
            $table->id();
            $table->string('rule_name', 100);
            $table->enum('rule_type', ['validation', 'automation', 'notification', 'workflow']);
            $table->string('entity_type', 50);
            $table->string('trigger_event', 50);
            $table->json('conditions');
            $table->json('actions');
            $table->integer('priority')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        // Languages
        Schema::create('languages', function (Blueprint $table) {
            $table->id();
            $table->string('code', 10)->unique();
            $table->string('name', 100);
            $table->string('native_name', 100)->nullable();
            $table->boolean('is_rtl')->default(false);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        // Translations
        Schema::create('translations', function (Blueprint $table) {
            $table->id();
            $table->string('language_code', 10);
            $table->string('translation_key');
            $table->text('translation_value');
            $table->string('context', 100)->nullable();
            $table->timestamps();
            
            $table->foreign('language_code')->references('code')->on('languages')->onDelete('cascade');
            $table->unique(['language_code', 'translation_key']);
        });

        // Currency Settings
        Schema::create('currency_settings', function (Blueprint $table) {
            $table->id();
            $table->string('currency_code', 3)->unique();
            $table->string('currency_name', 100);
            $table->string('symbol', 10);
            $table->decimal('exchange_rate', 10, 6)->default(1.000000);
            $table->boolean('is_base_currency')->default(false);
            $table->boolean('is_active')->default(true);
            $table->integer('decimal_places')->default(2);
            $table->string('thousand_separator', 5)->default(',');
            $table->string('decimal_separator', 5)->default('.');
            $table->enum('symbol_position', ['before', 'after'])->default('before');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('currency_settings');
        Schema::dropIfExists('translations');
        Schema::dropIfExists('languages');
        Schema::dropIfExists('business_rules');
        Schema::dropIfExists('email_templates');
        Schema::dropIfExists('receipt_templates');
        Schema::dropIfExists('discount_rule_items');
        Schema::dropIfExists('discount_rules');
        Schema::dropIfExists('tax_group_rates');
        Schema::dropIfExists('tax_groups');
        Schema::dropIfExists('tax_rates');
        Schema::dropIfExists('custom_field_values');
        Schema::dropIfExists('custom_fields');
        Schema::dropIfExists('layout_templates');
        Schema::dropIfExists('theme_settings');
    }
};
