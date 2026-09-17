<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RecetaProducto extends Model
{
    protected $table = 'recetas_productos';
    protected $primaryKey = 'id_receta_producto';
    public const CREATED_AT = 'creado_en';
    public const UPDATED_AT = 'actualizado_en';

    protected $fillable = ['ref_producto', 'ref_insumo', 'cantidad', 'activo'];

    protected function casts(): array
    {
        return ['cantidad' => 'decimal:4', 'activo' => 'boolean'];
    }
}
