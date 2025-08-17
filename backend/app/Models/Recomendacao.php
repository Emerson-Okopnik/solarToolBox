<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Recomendacao extends Model
{
    use HasFactory;

    protected $table = 'recomendacoes';

    protected $fillable = [
        'execucao_id',
        'checagem_id',
        'prioridade',
        'categoria',
        'titulo',
        'descricao',
        'solucao_sugerida',
        'impacto_estimado',
    ];

    protected $casts = [
        'impacto_estimado' => 'array',
    ];

    // Relacionamentos
    public function execucao()
    {
        return $this->belongsTo(Execucao::class);
    }

    public function checagem()
    {
        return $this->belongsTo(Checagem::class);
    }
}
