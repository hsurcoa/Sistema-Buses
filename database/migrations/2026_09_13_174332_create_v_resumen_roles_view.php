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
        // Sin calificar `roles`/`rol_permiso` con la BD, ver nota en
        // create_v_permisos_por_rol_view.
        DB::statement("CREATE VIEW `v_resumen_roles` AS select `r`.`id` AS `id`,`r`.`nombre` AS `nombre`,`r`.`descripcion` AS `descripcion`,`r`.`activo` AS `activo`,count(`rp`.`permiso_id`) AS `total_permisos` from (`roles` `r` left join `rol_permiso` `rp` on(`r`.`id` = `rp`.`rol_id`)) group by `r`.`id`,`r`.`nombre`,`r`.`descripcion`,`r`.`activo` order by `r`.`nombre`");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::statement("DROP VIEW IF EXISTS `v_resumen_roles`");
    }
};
