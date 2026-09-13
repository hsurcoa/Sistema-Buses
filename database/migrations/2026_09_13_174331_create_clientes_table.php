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
        Schema::create('clientes', function (Blueprint $table) {
            $table->collation = 'utf8mb4_unicode_ci';
            $table->charset = 'utf8mb4';

            $table->integer('id', true);
            $table->enum('tipo_documento', ['CI', 'DNI', 'Pasaporte'])->nullable()->default('CI');
            $table->string('numero_documento', 20)->unique('numero_documento');
            $table->string('nombres', 100);
            $table->string('apellidos', 100);
            $table->string('celular', 20)->nullable();
            $table->string('email', 100)->nullable();
            $table->date('fecha_nacimiento')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('clientes');
    }
};
