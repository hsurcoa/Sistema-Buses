<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Trazabilidad de los cobros con QR delegados a la pasarela Libélula
 * (ver docs/superpowers/... integracion Libelula): una fila por cada
 * "deuda" registrada en su plataforma para un boleto reservado con
 * metodo_pago=QR. Separado de `boletos` para no ensuciar esa tabla con
 * campos que solo aplican cuando la pasarela esta activa.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('libelula_cobros', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('boleto_id');
            $table->string('identificador_deuda', 60);
            $table->string('id_transaccion', 100)->nullable();
            $table->string('codigo_recaudacion', 60)->nullable();
            $table->text('url_pasarela_pagos')->nullable();
            $table->text('qr_simple_url')->nullable();
            $table->decimal('monto', 10, 2);
            $table->enum('estado', ['pendiente', 'pagado', 'pagado_sin_aplicar', 'anulado', 'error'])->default('pendiente');
            $table->text('respuesta_registro')->nullable()->comment('JSON crudo de la respuesta de REGISTRAR DEUDA, para depurar');
            $table->timestamp('fecha_creacion')->useCurrent();
            $table->timestamp('fecha_pago')->nullable();

            $table->index('boleto_id');
            $table->index('identificador_deuda');
            $table->index('id_transaccion');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('libelula_cobros');
    }
};
