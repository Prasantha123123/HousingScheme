<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('Payroll', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('EmployeeId');
            $table->unsignedInteger('workdays')->nullable();
            $table->decimal('wage_net', 12, 2)->default(0);
            $table->decimal('deduction', 12, 2)->default(0);
            $table->string('files')->nullable();
            $table->string('paidType')->default('cash');
            $table->enum('status', ['Paid','Pending'])->default('Paid');
            $table->timestamp('timestamp')->useCurrent();
        });
    }
    public function down(): void {
        Schema::dropIfExists('Payroll');
    }
};
