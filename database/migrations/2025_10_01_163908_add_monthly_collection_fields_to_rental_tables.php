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
        // Add monthly collection tracking fields
        Schema::table('HouseRental', function (Blueprint $table) {
            // Track what was collected in this specific month for this bill (for display purposes)
            $table->decimal('monthly_collection_amount', 12, 2)->default(0)->after('paidAmount');
            // Track which calendar month this collection occurred in
            $table->string('collection_month', 7)->nullable()->after('monthly_collection_amount');
        });

        Schema::table('ShopRental', function (Blueprint $table) {
            // Track what was collected in this specific month for this bill (for display purposes)
            $table->decimal('monthly_collection_amount', 12, 2)->default(0)->after('paidAmount');
            // Track which calendar month this collection occurred in
            $table->string('collection_month', 7)->nullable()->after('monthly_collection_amount');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('HouseRental', function (Blueprint $table) {
            $table->dropColumn(['monthly_collection_amount', 'collection_month']);
        });

        Schema::table('ShopRental', function (Blueprint $table) {
            $table->dropColumn(['monthly_collection_amount', 'collection_month']);
        });
    }
};
