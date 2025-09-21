<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class StringModel extends Model
{
    use HasFactory;

    protected $table = 'strings';

    protected $fillable = [
        'arranjo_id',
        'mppt_id',
        'modulo_id',
        'azimute',
        'inclinacao',
        'nome',
        'tipo_conexao',
        'num_modulos_serie',
        'num_strings_paralelo',
        'total_modulos',
        'tensao_circuito_aberto',
        'tensao_maxima_potencia',
        'corrente_curto_circuito',
        'corrente_maxima_potencia',
        'potencia_total',
    ];

    protected $casts = [
        'tensao_circuito_aberto' => 'decimal:2',
        'tensao_maxima_potencia' => 'decimal:2',
        'corrente_curto_circuito' => 'decimal:2',
        'corrente_maxima_potencia' => 'decimal:2',
        'potencia_total' => 'decimal:2',
        'azimute' => 'decimal:2',
        'inclinacao' => 'decimal:2',
    ];

    // Relacionamentos
    public function arranjo()
    {
        return $this->belongsTo(Arranjo::class);
    }

    public function mppt()
    {
        return $this->belongsTo(Mppt::class);
    }

    public function modulo()
    {
        return $this->belongsTo(Modulo::class);
    }

    // Accessors
    public function checagens()
    {
        return $this->hasMany(Checagem::class, 'string_id');
    }

    public function getInversorAttribute()
    {
        return $this->arranjo->inversor;
    }

    public function getOrientacaoGrupo(): string
    {
        $azimute = $this->azimute !== null ? number_format((float) $this->azimute, 2, '.', '') : '0.00';
        $inclinacao = $this->inclinacao !== null ? number_format((float) $this->inclinacao, 2, '.', '') : '0.00';

        return "Az{$azimute}_Inc{$inclinacao}";
    }
}
