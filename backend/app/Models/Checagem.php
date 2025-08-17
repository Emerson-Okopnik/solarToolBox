<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Checagem extends Model
{
    use HasFactory;

    protected $table = 'checagens';

    protected $fillable = [
        'execucao_id',
        'string_id',
        'arranjo_id',
        'tipo',
        'resultado',
        'titulo',
        'descricao',
        'valores_calculados',
        'limites_referencia',
    ];

    protected $casts = [
        'valores_calculados' => 'array',
        'limites_referencia' => 'array',
    ];

    // Relacionamentos
    public function execucao()
    {
        return $this->belongsTo(Execucao::class);
    }

    public function string()
    {
        return $this->belongsTo(StringModel::class, 'string_id');
    }

    public function arranjo()
    {
        return $this->belongsTo(Arranjo::class);
    }

    public function recomendacoes()
    {
        return $this->hasMany(Recomendacao::class);
    }
}
