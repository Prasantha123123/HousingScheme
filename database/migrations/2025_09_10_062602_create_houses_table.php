<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
Schema::create('houses', function (Blueprint $table) {
    $table->string('houseNo')->primary();
    $table->unsignedBigInteger('HouseOwneId')->nullable();
    $table->string('house_password'); // hashed house password
    $table->timestamp('timestamp')->useCurrent();
    // optional FK if you want:
    // $table->foreign('HouseOwneId')->references('id')->on('users')->nullOnDelete();
});
    }

    public function down(): void {
        Schema::dropIfExists('houses');
    }
};
