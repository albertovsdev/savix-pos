<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Insumo extends Model
{
    protected $table = 'insumos';
    protected $primaryKey = 'id_insumo';
    public const CREATED_AT = 'creado_en';
    public const UPDATED_AT = 'actualizado_en';

    protected $fillable = ['ref_negocio', 'ref_unidad_medida', 'codigo', 'nombre', 'costo_unitario', 'activo'];

    protected function casts(): array
    {
        return ['costo_unitario' => 'decimal:4', 'activo' => 'boolean'];
    }
}
