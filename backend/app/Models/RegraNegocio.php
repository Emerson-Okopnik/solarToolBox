<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RegraNegocio extends Model
{
    use HasFactory;

    protected $table = 'regras_negocio';

    protected $fillable = [
        'codigo',
        'nome',
        'descricao',
        'categoria',
        'parametros',
        'ativa',
        'prioridade',
    ];

    protected $casts = [
        'parametros' => 'array',
        'ativa' => 'boolean',
    ];

    // Scopes
    public function scopeAtivas($query)
    {
        return $query->where('ativa', true);
    }

    public function scopePorCategoria($query, $categoria)
    {
        return $query->where('categoria', $categoria);
    }
}
