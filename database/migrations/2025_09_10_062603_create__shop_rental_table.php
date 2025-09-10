<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('ShopRental', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('shopNumber');
            $table->decimal('billAmount', 12, 2)->default(0);
            $table->string('month', 7);
            $table->decimal('paidAmount', 12, 2)->default(0);
            $table->string('paymentMethod')->nullable();
            $table->string('recipt')->nullable();
            $table->enum('status', ['Pending','Approved','Rejected'])->default('Pending');
            $table->timestamp('timestamp')->useCurrent();
            $table->index(['shopNumber','month']);
        });
    }
    public function down(): void {
        Schema::dropIfExists('ShopRental');
    }
};
