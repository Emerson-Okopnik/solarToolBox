<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProjetoInversor extends Model
{
    use HasFactory;

    protected $table = 'projeto_inversores';

    protected $fillable = [
        'projeto_id',
        'inversor_id',
    ];

    public function projeto()
    {
        return $this->belongsTo(Projeto::class);
    }

    public function inversor()
    {
        return $this->belongsTo(Inversor::class);
    }

    public function arranjos()
    {
        return $this->hasMany(Arranjo::class);
    }
}