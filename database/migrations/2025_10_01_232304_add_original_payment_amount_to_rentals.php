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
        Schema::table('HouseRental', function (Blueprint $table) {
            $table->decimal('original_payment_amount', 10, 2)->nullable()->after('paidAmount');
        });
        
        Schema::table('ShopRental', function (Blueprint $table) {
            $table->decimal('original_payment_amount', 10, 2)->nullable()->after('paidAmount');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('HouseRental', function (Blueprint $table) {
            $table->dropColumn('original_payment_amount');
        });
        
        Schema::table('ShopRental', function (Blueprint $table) {
            $table->dropColumn('original_payment_amount');
        });
    }
};
