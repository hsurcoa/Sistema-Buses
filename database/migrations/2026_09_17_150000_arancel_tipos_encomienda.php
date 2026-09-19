<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * `encomienda_tipos` ya traia un precio_extra fijo por tipo de paquete, pero
 * el peso que se carga en el formulario de "Nueva Encomienda" nunca influia
 * en el precio (el campo estaba ahi solo de adorno). Se agrega un rango de
 * peso incluido y un cargo por kg excedente, para que el arancel realmente
 * cobre distinto segun cuanto pese el envio.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('encomienda_tipos', function (Blueprint $table) {
            $table->decimal('peso_incluido_kg', 8, 2)->nullable()->after('precio_extra')
                ->comment('Peso hasta el cual precio_extra ya cubre todo. NULL = sin limite de peso.');
            $table->decimal('precio_por_kg_excedente', 8, 2)->default(0)->after('peso_incluido_kg')
                ->comment('Se cobra por cada kg que pase de peso_incluido_kg.');
        });

        // Rangos consistentes con lo que ya describian estos 3 tipos sembrados
        // ("Hasta 5kg" / "Más de 5kg"), ahora aplicados de verdad al precio.
        DB::table('encomienda_tipos')->where('nombre', 'Documentos')->update(['peso_incluido_kg' => 1]);
        DB::table('encomienda_tipos')->where('nombre', 'Caja pequeña')->update(['peso_incluido_kg' => 5, 'precio_por_kg_excedente' => 2]);
        DB::table('encomienda_tipos')->where('nombre', 'Paquete grande')->update(['peso_incluido_kg' => 5, 'precio_por_kg_excedente' => 3]);
    }

    public function down(): void
    {
        Schema::table('encomienda_tipos', function (Blueprint $table) {
            $table->dropColumn(['peso_incluido_kg', 'precio_por_kg_excedente']);
        });
    }
};
