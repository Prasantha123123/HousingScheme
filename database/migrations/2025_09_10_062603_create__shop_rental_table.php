<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('ShopRental', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('shopNumber');
            $table->decimal('billAmount', 12, 2)->default(0);
            $table->string('month', 7);
            $table->decimal('paidAmount', 12, 2)->default(0);
            $table->decimal('monthly_collection_amount', 12, 2)->default(0);
            $table->string('collection_month', 7)->nullable();
            $table->decimal('original_payment_amount', 10, 2)->nullable();
            $table->string('paymentMethod')->nullable();
            $table->string('recipt')->nullable();
            $table->enum('status', ['Pending', 'InProgress', 'Approved', 'PartPayment'])->default('Pending');
            $table->timestamp('customer_paid_at')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->timestamp('timestamp')->useCurrent();
            $table->index(['shopNumber', 'month']);
        });
    }
    public function down(): void
    {
        Schema::dropIfExists('ShopRental');
    }
};
