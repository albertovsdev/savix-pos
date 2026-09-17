<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Negocio extends Model
{
    protected $table = 'negocios';
    protected $primaryKey = 'id_negocio';
    public const CREATED_AT = 'creado_en';
    public const UPDATED_AT = 'actualizado_en';

    protected $fillable = ['nombre', 'nombre_comercial', 'clave_folio', 'rfc', 'correo', 'telefono', 'ruta_logo', 'color_primario', 'color_secundario', 'color_acento', 'tema_predeterminado', 'porcentaje_iva', 'precios_incluyen_iva', 'ancho_ticket_mm', 'direccion_ticket', 'pie_ticket', 'activo'];

    protected function casts(): array
    {
        return ['porcentaje_iva' => 'decimal:2', 'precios_incluyen_iva' => 'boolean', 'activo' => 'boolean'];
    }

    public function sucursales(): HasMany
    {
        return $this->hasMany(Sucursal::class, 'ref_negocio', 'id_negocio');
    }
}
