<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RondaPedido extends Model
{
    protected $table = 'rondas_pedidos';
    protected $primaryKey = 'id_ronda_pedido';
    public const CREATED_AT = 'creado_en';
    public const UPDATED_AT = 'actualizado_en';

    protected $fillable = ['ref_pedido', 'ref_usuario_creacion', 'numero_ronda', 'estado', 'importe_total', 'enviada_en', 'activo'];

    protected function casts(): array
    {
        return ['importe_total' => 'decimal:2', 'enviada_en' => 'datetime', 'activo' => 'boolean'];
    }
}
