<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Projeto extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'clima_id',
        'nome',
        'cliente',
        'descricao',
        'endereco',
        'status',
        'limite_compatibilidade_tensao',
        'limite_compatibilidade_corrente',
    ];

    protected $casts = [
        'limite_compatibilidade_tensao' => 'decimal:2',
        'limite_compatibilidade_corrente' => 'decimal:2',
    ];

    // Relacionamentos
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function clima()
    {
        return $this->belongsTo(Clima::class);
    }

    public function arranjos()
    {
        return $this->hasMany(Arranjo::class);
    }

    public function execucoes()
    {
        return $this->hasMany(Execucao::class);
    }

    // Scopes
    public function scopeAtivos($query)
    {
        return $query->whereIn('status', ['rascunho', 'em_analise', 'aprovado']);
    }
}
