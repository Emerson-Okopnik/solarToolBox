<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Modulo extends Model
{
    use HasFactory;

    protected $fillable = [
        'fabricante_id',
        'modelo',
        'tecnologia',
        'potencia_nominal',
        'voc',
        'vmp',
        'isc',
        'imp',
        'coef_temp_voc',
        'coef_temp_vmp',
        'coef_temp_isc',
        'coef_temp_imp',
        'coef_temp_potencia',
        'comprimento',
        'largura',
        'espessura',
        'peso',
        'temp_operacao_min',
        'temp_operacao_max',
        'tensao_maxima_sistema',
        'ativo',
    ];

    protected $casts = [
        'potencia_nominal' => 'decimal:2',
        'voc' => 'decimal:2',
        'vmp' => 'decimal:2',
        'isc' => 'decimal:2',
        'imp' => 'decimal:2',
        'coef_temp_voc' => 'decimal:6',
        'coef_temp_vmp' => 'decimal:6',
        'coef_temp_isc' => 'decimal:6',
        'coef_temp_imp' => 'decimal:6',
        'coef_temp_potencia' => 'decimal:6',
        'ativo' => 'boolean',
    ];

    // Relacionamentos
    public function fabricante()
    {
        return $this->belongsTo(Fabricante::class);
    }

    public function arranjos()
    {
        return $this->hasMany(Arranjo::class);
    }

    // Scopes
    public function scopeAtivos($query)
    {
        return $query->where('ativo', true);
    }

    // Métodos de cálculo
    public function calcularVocFrio($temperaturaMin)
    {
        $deltaT = $temperaturaMin - 25; // STC = 25°C
        return $this->voc * (1 + ($this->coef_temp_voc / 100) * $deltaT);
    }

    public function calcularVmpOperacao($temperaturaOperacao)
    {
        $deltaT = $temperaturaOperacao - 25; // STC = 25°C
        return $this->vmp * (1 + ($this->coef_temp_vmp / 100) * $deltaT);
    }
}
