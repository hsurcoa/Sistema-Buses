<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Cliente extends Model
{
    protected $table = 'clientes';

    public $timestamps = false;

    protected $fillable = [
        'tipo_documento', 'numero_documento', 'nombres', 'apellidos', 'celular', 'email', 'fecha_nacimiento',
    ];

    protected function casts(): array
    {
        return ['fecha_nacimiento' => 'date'];
    }

    public function boletos()
    {
        return $this->hasMany(Boleto::class, 'cliente_id');
    }
}
