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
        Schema::table('anexos_03', function (Blueprint $table) {
            // Cambiar nro_modulo de integer a string para almacenar el nombre del módulo
            $table->string('nro_modulo', 255)->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('anexos_03', function (Blueprint $table) {
            // Revertir a integer si es necesario
            $table->integer('nro_modulo')->change();
        });
    }
};
