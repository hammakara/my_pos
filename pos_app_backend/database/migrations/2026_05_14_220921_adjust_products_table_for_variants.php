<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->renameColumn('title', 'name');
            $table->foreignId('category_id')->after('id')->nullable()->constrained('categories')->nullOnDelete();
            $table->enum('status', ['active', 'inactive'])->after('description')->default('active');
            $table->dropColumn(['cost', 'banner_image']);
        });
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->renameColumn('name', 'title');
            $table->dropForeign(['category_id']);
            $table->dropColumn(['category_id', 'status']);
            $table->decimal('cost')->nullable();
            $table->string('banner_image', 220)->nullable();
        });
    }
};
