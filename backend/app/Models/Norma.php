<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Norma extends Model
{
    use HasFactory;

    protected $fillable = [
        'codigo',
        'titulo',
        'descricao',
        'orgao_emissor',
        'data_publicacao',
        'data_revisao',
        'status',
        'pais',
        'parametros_tecnicos',
        'ativa',
    ];

    protected $casts = [
        'data_publicacao' => 'date',
        'data_revisao' => 'date',
        'parametros_tecnicos' => 'array',
        'ativa' => 'boolean',
    ];

    // Scopes
    public function scopeVigentes($query)
    {
        return $query->where('status', 'vigente')->where('ativa', true);
    }

    public function scopePorPais($query, $pais)
    {
        return $query->where('pais', $pais);
    }
}
