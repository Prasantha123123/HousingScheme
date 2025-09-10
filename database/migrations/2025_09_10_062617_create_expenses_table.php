<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('expenses', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->date('date');
            $table->string('name');
            $table->decimal('amount', 12, 2);
            $table->text('note')->nullable();
            $table->timestamp('timestamp')->useCurrent();
            $table->index('date');
        });
    }
    public function down(): void {
        Schema::dropIfExists('expenses');
    }
};
