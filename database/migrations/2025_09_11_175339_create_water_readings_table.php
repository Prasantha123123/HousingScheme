<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('WaterReadings', function (Blueprint $table) {
            $table->bigIncrements('id');

            // House identifier (matches your 'Houses' table houseNo string PK)
            $table->string('houseNo');

            // Month in YYYY-MM
            $table->string('month', 7);

            // Readings (units)
            $table->unsignedInteger('openingReadingUnit')->default(0);
            $table->unsignedInteger('readingUnit')->default(0); // closing/current reading

            // Optional metadata
            $table->enum('source', ['manual','import','estimated'])->default('manual');
            $table->text('note')->nullable();

            // ✅ NEW: status (default Pending)
            $table->enum('status', ['Pending','Approved'])->default('Pending');

            // Timestamps
            $table->timestamps();

            // Fast lookups & uniqueness
            $table->unique(['houseNo','month']);
            $table->index('houseNo');

            // Optional FK if your Houses table uses string PK 'houseNo'
            // $table->foreign('houseNo')->references('houseNo')->on('Houses')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('WaterReadings');
    }
};
