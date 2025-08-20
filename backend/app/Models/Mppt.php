<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Mppt extends Model
{
    use HasFactory;

    protected $fillable = [
        'inversor_id',
        'numero',
        'tensao_mppt_min',
        'tensao_mppt_max',
        'corrente_entrada_max',
        'strings_max',
    ];

    protected $casts = [
        'tensao_mppt_min' => 'decimal:2',
        'tensao_mppt_max' => 'decimal:2',
        'corrente_entrada_max' => 'decimal:2',
    ];

    // Relacionamentos
    public function inversor()
    {
        return $this->belongsTo(Inversor::class);
    }

    public function strings()
    {
        return $this->hasMany(StringModel::class, 'mppt_id');
    }

    // Métodos de validação
    public function validarTensao($tensao)
    {
        return $tensao >= $this->tensao_mppt_min && $tensao <= $this->tensao_mppt_max;
    }

    public function validarCorrente($corrente)
    {
        return $corrente <= $this->corrente_entrada_max;
    }
}
