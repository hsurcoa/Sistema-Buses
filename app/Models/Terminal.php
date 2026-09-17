<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Sucursal/terminal (tabla `terminales`, ya existente).
 *
 * Equivalente Eloquent de las lecturas simples de `legacy/app/core/Sucursal.php`
 * (listar/obtener). La logica de "que sucursal filtra" se resuelve en cada
 * controlador migrado con `auth()->user()`, no aqui (ver Fase 2, Tarea 3).
 */
class Terminal extends Model
{
    protected $table = 'terminales';

    public $timestamps = false;
}
