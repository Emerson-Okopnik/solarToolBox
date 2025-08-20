<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Execucao extends Model
{
    use HasFactory;

    protected $table = 'execucoes';

    protected $fillable = [
        'projeto_id',
        'user_id',
        'status',
        'iniciada_em',
        'concluida_em',
        'total_checagens',
        'checagens_aprovadas',
        'checagens_reprovadas',
        'total_recomendacoes',
        'configuracoes',
    ];

    protected $casts = [
        'iniciada_em' => 'datetime',
        'concluida_em' => 'datetime',
        'configuracoes' => 'array',
    ];

    // Relacionamentos
    public function projeto()
    {
        return $this->belongsTo(Projeto::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function checagens()
    {
        return $this->hasMany(Checagem::class);
    }

    public function recomendacoes()
    {
        return $this->hasMany(Recomendacao::class);
    }

    public function relatorios()
    {
        return $this->hasMany(Relatorio::class);
    }
}
