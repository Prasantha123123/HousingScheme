<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('shopPayment', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('shopId'); // References ShopRental.id
            $table->foreign('shopId')->references('id')->on('ShopRental')->cascadeOnDelete();
            
            $table->decimal('paymentmake', 12, 2);
            $table->enum('method', ['cash','card','online']);
            $table->string('recipt')->nullable();
            $table->enum('paymenttype', ['partpayment','fullpayment']);
            $table->timestamp('customerPaidAt')->nullable();
            $table->enum('status', ['approval','pending','inprogress'])->default('pending');
            $table->timestamp('approvedAt')->nullable();
            
            $table->timestamps();
            
            $table->index(['shopId', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('shopPayment');
    }
};