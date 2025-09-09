<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Arranjo extends Model
{
    use HasFactory;

    protected $fillable = [
        'projeto_id',
        'modulo_id',
        'inversor_id',
        'nome',
        'descricao',
        'azimute',
        'inclinacao',
        'fator_sombreamento',
    ];

    protected $casts = [
        'azimute' => 'decimal:2',
        'inclinacao' => 'decimal:2',
        'fator_sombreamento' => 'decimal:4',
    ];

        protected static function booted()
    {
        static::created(function (Arranjo $arranjo) {
            $projeto = $arranjo->projeto;
            if ($projeto && $projeto->status === 'rascunho') {
                $projeto->update(['status' => 'em_analise']);
            }
        });
    }
    
    // Relacionamentos
    public function projeto()
    {
        return $this->belongsTo(Projeto::class);
    }

    public function modulo()
    {
        return $this->belongsTo(Modulo::class);
    }

    public function inversor()
    {
        return $this->belongsTo(Inversor::class);
    }

    public function strings()
    {
        return $this->hasMany(StringModel::class, 'arranjo_id');
    }

    public function checagens()
    {
        return $this->hasMany(Checagem::class);
    }

    // Métodos auxiliares
    public function getOrientacaoGrupo()
    {
        return "Az{$this->azimute}_Inc{$this->inclinacao}";
    }
}
