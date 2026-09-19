<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Sin calificar `roles`/`rol_permiso`/`permisos` con la BD: el nombre real de la
        // BD varia por entorno (kitloong/laravel-migrations-generator lo dejaba fijo a
        // "sistema_transportes", rompiendo la vista en cualquier otra instancia).
        DB::statement("CREATE VIEW `v_permisos_por_rol` AS select `r`.`id` AS `rol_id`,`r`.`nombre` AS `rol_nombre`,`p`.`id` AS `permiso_id`,`p`.`grupo` AS `grupo`,`p`.`vista` AS `vista`,`p`.`clave` AS `clave`,`rp`.`fecha_asignacion` AS `fecha_asignacion` from ((`roles` `r` join `rol_permiso` `rp` on(`r`.`id` = `rp`.`rol_id`)) join `permisos` `p` on(`rp`.`permiso_id` = `p`.`id`)) where `r`.`activo` = 1 and `p`.`activo` = 1 order by `r`.`nombre`,`p`.`grupo`,`p`.`orden`");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::statement("DROP VIEW IF EXISTS `v_permisos_por_rol`");
    }
};
