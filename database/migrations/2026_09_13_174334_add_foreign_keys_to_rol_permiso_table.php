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
        Schema::table('rol_permiso', function (Blueprint $table) {
            $table->foreign(['permiso_id'], 'fk_rol_permiso_permiso')->references(['id'])->on('permisos')->onUpdate('restrict')->onDelete('cascade');
            $table->foreign(['rol_id'], 'fk_rol_permiso_rol')->references(['id'])->on('roles')->onUpdate('restrict')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('rol_permiso', function (Blueprint $table) {
            $table->dropForeign('fk_rol_permiso_permiso');
            $table->dropForeign('fk_rol_permiso_rol');
        });
    }
};
