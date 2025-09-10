<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('inventory_sales', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->date('date');
            $table->string('item');
            $table->unsignedInteger('qty');
            $table->decimal('unit_price', 12, 2);
            $table->decimal('total', 12, 2);
            $table->text('note')->nullable();
            $table->timestamp('timestamp')->useCurrent();
            $table->index('date');
        });
    }
    public function down(): void {
        Schema::dropIfExists('inventory_sales');
    }
};
