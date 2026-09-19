<?php

namespace Database\Seeders;

use App\Models\Rol;
use App\Models\Usuario;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

/**
 * `php artisan migrate --seed` debe dejar una instancia usable en cualquier
 * equipo nuevo sin depender de un dump de MySQL: crea el rol Administrador,
 * un usuario para poder loguearse, y los catalogos base (PandoSeeder).
 *
 * `TestDataSeeder` (viajes/boletos/encomiendas de prueba) queda aparte y no
 * se corre aca: asume una caja ya abierta del usuario 1, lo que no es cierto
 * en una base recien creada. Correrlo a mano si hace falta ver datos de
 * ejemplo end-to-end.
 */
class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $rol = Rol::firstOrCreate(
            ['nombre' => 'Administrador'],
            ['descripcion' => 'Acceso total al sistema', 'activo' => 1]
        );

        Usuario::firstOrCreate(
            ['email' => 'admin@sistema.local'],
            [
                'username' => 'admin',
                'nombres' => 'Administrador',
                'apellidos' => 'Sistema',
                'password' => Hash::make('Admin12345!'),
                'rol_id' => $rol->id,
                'estado' => 'activo',
            ]
        );

        $this->call(PandoSeeder::class);
    }
}
