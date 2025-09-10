<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('houses', function (Blueprint $table) {
            // No 'id' by request. Primary key by houseNo.
            $table->unsignedBigInteger('HouseOwneId');
            $table->string('houseNo')->primary();
            $table->timestamp('timestamp')->useCurrent();
        });
    }
    public function down(): void {
        Schema::dropIfExists('houses');
    }
};
