<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('coordinadors', function (Blueprint $table) {
            $table->unsignedInteger('id')->primary();
            $table->unsignedInteger('id_programa');

            $table->foreign('id')->references('id')->on('personas')->onDelete('cascade');
            $table->foreign('id_programa')->references('id')->on('programas_estudios')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('coordinadors');
    }
};
