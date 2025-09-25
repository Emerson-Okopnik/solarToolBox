<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * @property-read \App\Models\ProjetoInversor|null $projetoInversor
 */

class Arranjo extends Model
{
    use HasFactory;

    protected $fillable = [
        'projeto_id',
        'projeto_inversor_id',
        'nome',
        'descricao',
        'fator_sombreamento',
    ];

    protected $appends = [
        'inversor',
        'inversor_id',
    ];

    protected $casts = [
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

        static::deleted(function (Arranjo $arranjo) {
            $projetoInversorId = $arranjo->getOriginal('projeto_inversor_id');

            if ($projetoInversorId) {
                ProjetoInversor::where('id', $projetoInversorId)->delete();
            }
        });
    }
    
    // Relacionamentos
    public function projeto()
    {
        return $this->belongsTo(Projeto::class);
    }

    public function projetoInversor()
    {
        return $this->belongsTo(ProjetoInversor::class);
    }

    public function getInversorAttribute()
    {
        return $this->projetoInversor?->inversor;
    }

    public function getInversorIdAttribute()
    {
        return $this->projetoInversor?->inversor_id;
    }

    public function strings()
    {
        return $this->hasMany(StringModel::class, 'arranjo_id');
    }

    public function checagens()
    {
        return $this->hasMany(Checagem::class);
    }
}
