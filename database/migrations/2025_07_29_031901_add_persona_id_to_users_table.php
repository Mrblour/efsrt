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
        Schema::table('users', function (Blueprint $table) {
            // Añade la columna id_persona
            $table->unsignedBigInteger('id_persona')->nullable()->after('id'); // O después de 'email' si prefieres

            // Añade la clave foránea
            $table->foreign('id_persona')->references('id')->on('personas')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // Elimina la clave foránea primero
            $table->dropForeign(['id_persona']);
            // Luego elimina la columna
            $table->dropColumn('id_persona');
        });
    }
};
