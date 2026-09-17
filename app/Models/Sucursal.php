<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Sucursal extends Model
{
    protected $table = 'sucursales';
    protected $primaryKey = 'id_sucursal';
    public const CREATED_AT = 'creado_en';
    public const UPDATED_AT = 'actualizado_en';

    protected $fillable = ['ref_negocio', 'clave', 'nombre', 'telefono', 'correo', 'direccion', 'ultimo_folio_ticket', 'activo'];

    protected function casts(): array
    {
        return ['activo' => 'boolean'];
    }

    public function negocio(): BelongsTo
    {
        return $this->belongsTo(Negocio::class, 'ref_negocio', 'id_negocio');
    }
}
