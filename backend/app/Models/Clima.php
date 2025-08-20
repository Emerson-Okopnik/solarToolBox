<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Clima extends Model
{
    use HasFactory;

    protected $fillable = [
        'nome',
        'cidade',
        'estado',
        'pais',
        'latitude',
        'longitude',
        'altitude',
        'temp_min_historica',
        'temp_max_historica',
        'temp_media_anual',
        'irradiacao_global_horizontal',
        'irradiacao_direta_normal',
        'ativo',
    ];

    protected $casts = [
        'latitude' => 'decimal:8',
        'longitude' => 'decimal:8',
        'altitude' => 'decimal:2',
        'temp_min_historica' => 'decimal:2',
        'temp_max_historica' => 'decimal:2',
        'temp_media_anual' => 'decimal:2',
        'irradiacao_global_horizontal' => 'decimal:2',
        'irradiacao_direta_normal' => 'decimal:2',
        'ativo' => 'boolean',
    ];

    // Relacionamentos
    public function projetos()
    {
        return $this->hasMany(Projeto::class);
    }

    // Scopes
    public function scopeAtivos($query)
    {
        return $query->where('ativo', true);
    }
}
