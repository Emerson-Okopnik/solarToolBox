<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Relatorio extends Model
{
    use HasFactory;

    protected $fillable = [
        'execucao_id',
        'user_id',
        'tipo',
        'formato',
        'status',
        'nome_arquivo',
        'caminho_arquivo',
        'tamanho_bytes',
        'configuracoes',
    ];

    protected $casts = [
        'configuracoes' => 'array',
    ];

    // Relacionamentos
    public function execucao()
    {
        return $this->belongsTo(Execucao::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
