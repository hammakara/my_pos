<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('cafe_tables', function (Blueprint $table) {
            $table->integer('floor')->default(1)->after('capacity');
        });
    }

    public function down(): void
    {
        Schema::table('cafe_tables', function (Blueprint $table) {
            $table->dropColumn('floor');
        });
    }
};
