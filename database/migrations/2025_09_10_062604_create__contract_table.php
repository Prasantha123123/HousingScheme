<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('Contract', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('EmployeeId');
            $table->enum('contractType', ['dailysallary','monthlysalary']);
            $table->decimal('waheAmount', 12, 2)->default(0); // exact name
            $table->timestamp('timestamp')->useCurrent();
        });
    }
    public function down(): void {
        Schema::dropIfExists('Contract');
    }
};
