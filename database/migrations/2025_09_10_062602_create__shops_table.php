<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('Shops', function (Blueprint $table) {
            $table->string('shopNumber')->primary();
            $table->unsignedBigInteger('MerchantId');
            $table->date('leaseEnd')->nullable();
            $table->decimal('rentalAmount', 12, 2)->default(0);
            $table->timestamp('timestamp')->useCurrent();
        });
    }
    public function down(): void {
        Schema::dropIfExists('Shops');
    }
};
