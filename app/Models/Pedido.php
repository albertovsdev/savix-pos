<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Pedido extends Model
{
    protected $table = 'pedidos';
    protected $primaryKey = 'id_pedido';
    public const CREATED_AT = 'creado_en';
    public const UPDATED_AT = 'actualizado_en';

    protected $fillable = ['ref_sucursal', 'ref_mesa', 'ref_usuario_apertura', 'tipo_servicio', 'estado', 'referencia_mostrador', 'nota', 'abierto_en', 'cerrado_en', 'activo'];

    protected function casts(): array
    {
        return ['abierto_en' => 'datetime', 'cerrado_en' => 'datetime', 'activo' => 'boolean'];
    }
}
