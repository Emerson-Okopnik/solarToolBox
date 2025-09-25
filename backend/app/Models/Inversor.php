<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Inversor extends Model
{
    use HasFactory;

    protected $table = 'inversores';

    protected $fillable = [
        'fabricante_id',
        'modelo',
        'tipo',
        'potencia_dc_max',
        'tensao_dc_max',
        'tensao_dc_min',
        'corrente_dc_max',
        'num_mppts',
        'potencia_ac_nominal',
        'tensao_ac_nominal',
        'corrente_ac_max',
        'frequencia_nominal',
        'eficiencia_max',
        'temp_operacao_min',
        'temp_operacao_max',
        'altitude_max',
        'umidade_max',
        'ativo',
    ];

    protected $casts = [
        'potencia_dc_max' => 'decimal:2',
        'tensao_dc_max' => 'decimal:2',
        'tensao_dc_min' => 'decimal:2',
        'corrente_dc_max' => 'decimal:2',
        'potencia_ac_nominal' => 'decimal:2',
        'tensao_ac_nominal' => 'decimal:2',
        'corrente_ac_max' => 'decimal:2',
        'frequencia_nominal' => 'decimal:2',
        'eficiencia_max' => 'decimal:2',
        'ativo' => 'boolean',
    ];

    // Relacionamentos
    public function fabricante()
    {
        return $this->belongsTo(Fabricante::class);
    }

    public function mppts()
    {
        return $this->hasMany(Mppt::class);
    }

    public function arranjos()
    {
        return $this->hasMany(Arranjo::class);
    }

    public function projetoInversores()
    {
        return $this->hasMany(ProjetoInversor::class);
    }

    // Scopes
    public function scopeAtivos($query)
    {
        return $query->where('ativo', true);
    }
}
