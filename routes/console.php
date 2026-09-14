<?php

use App\Models\Usuario;
use App\Services\LegacyRbacSync;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// RBAC legacy -> spatie (Fase 1, Tarea 9). Re-ejecutar despues de editar
// roles/permisos desde el modulo Admin legacy.
Artisan::command('rbac:sync {--verify : Compara usuario x permiso contra rol_permiso sin sincronizar}', function (LegacyRbacSync $sync) {
    if (! $this->option('verify')) {
        $resumen = $sync->syncAll();
        $this->info('RBAC sincronizado: '.json_encode($resumen));
    }

    // Criterio de aceptacion: can() == logica legacy para cada usuario y permiso.
    $legacy = DB::table('usuarios as u')
        ->join('roles as r', 'r.id', '=', 'u.rol_id')
        ->join('rol_permiso as rp', 'rp.rol_id', '=', 'r.id')
        ->join('permisos as p', 'p.id', '=', 'rp.permiso_id')
        ->where('u.estado', 'activo')->where('r.activo', 1)->where('p.activo', 1)
        ->select('u.id', 'p.clave')->get()
        ->map(fn ($row) => $row->id.'|'.$row->clave)->flip();

    $claves = DB::table('permisos')->pluck('clave');
    $diferencias = [];
    $comprobaciones = 0;
    foreach (Usuario::with('rol')->get() as $usuario) {
        foreach ($claves as $clave) {
            $comprobaciones++;
            $esperado = $legacy->has($usuario->id.'|'.$clave);
            if ($usuario->can($clave) !== $esperado) {
                $diferencias[] = [$usuario->id, $usuario->email, $usuario->rol?->nombre, $clave, $esperado ? 'si' : 'no'];
            }
        }
    }

    if ($diferencias) {
        $this->error(count($diferencias)." diferencias en {$comprobaciones} comprobaciones:");
        $this->table(['id', 'email', 'rol', 'permiso', 'legacy'], $diferencias);

        return 1;
    }

    $this->info("OK: {$comprobaciones} comprobaciones usuario x permiso identicas a rol_permiso.");

    return 0;
})->purpose('Sincroniza roles/permisos legacy hacia spatie y verifica paridad');
