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
        Schema::create('usuarios', function (Blueprint $table) {
            $table->collation = 'utf8mb4_unicode_ci';
            $table->charset = 'utf8mb4';

            $table->integer('id', true);
            $table->string('username', 50)->nullable();
            $table->string('nombres', 100);
            $table->string('apellidos', 100);
            $table->string('email', 100)->unique('email');
            $table->string('password');
            $table->integer('rol_id')->index('rol_id');
            $table->enum('estado', ['activo', 'inactivo'])->nullable()->default('activo');
            $table->string('sucursal_asignada', 100)->nullable();
            $table->timestamp('created_at')->nullable()->useCurrent();
            $table->enum('tipo_documento', ['DNI', 'CI', 'Pasaporte'])->nullable()->default('DNI');
            $table->string('nro_documento', 20)->nullable()->unique('nro_documento');
            $table->enum('genero', ['M', 'F'])->nullable()->default('M');
            $table->date('fecha_nacimiento')->nullable();
            $table->string('celular', 20)->nullable();
            $table->text('direccion_domicilio')->nullable();
            $table->string('foto')->nullable()->default('default.png');
            $table->string('departamento', 50)->nullable();
            $table->string('provincia', 50)->nullable();
            $table->string('distrito', 50)->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('usuarios');
    }
};
