<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SerieBoleto extends Model
{
    protected $table = 'series_boletos';

    public $timestamps = false;
    const CREATED_AT = 'created_at';

    protected $fillable = ['usuario_id', 'sede_id', 'numero_serie', 'estado'];

    protected function casts(): array
    {
        return ['estado' => 'boolean'];
    }

    public function vendedor()
    {
        return $this->belongsTo(Personal::class, 'usuario_id');
    }

    public function sede()
    {
        return $this->belongsTo(Terminal::class, 'sede_id');
    }
}
