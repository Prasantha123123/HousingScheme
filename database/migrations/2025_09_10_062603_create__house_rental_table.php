<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('HouseRental', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('houseNo');
            $table->unsignedInteger('readingUnit')->default(0);
            $table->string('month', 7); // YYYY-MM
            $table->unsignedInteger('openingReadingUnit')->default(0);

            $table->decimal('billAmount', 12, 2)->default(0);
            $table->decimal('paidAmount', 12, 2)->default(0);

            $table->enum('paymentMethod', ['cash','card','online'])->nullable();
            $table->string('recipt')->nullable();

            // 👇 NEW status + timestamps
            $table->enum('status', ['Pending','InProgress','Approved','Rejected'])->default('Pending');
            $table->timestamp('customer_paid_at')->nullable(); // when customer submits payment (receipt/card)
            $table->timestamp('approved_at')->nullable();      // when admin approves

            $table->timestamp('timestamp')->useCurrent();

            $table->index(['houseNo','month']);
        });
    }

    public function down(): void {
        Schema::dropIfExists('HouseRental');
    }
};
