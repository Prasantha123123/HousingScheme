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
        Schema::table('ShopRental', function (Blueprint $table) {
            $table->timestamp('customer_paid_at')->nullable()->after('timestamp');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('ShopRental', function (Blueprint $table) {
            $table->dropColumn('customer_paid_at');
        });
    }
};
