<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Tymon\JWTAuth\Contracts\JWTSubject;

class User extends Authenticatable implements JWTSubject
{
    use HasFactory, Notifiable;

    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
        'company',
        'phone',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
    ];

    // Relacionamentos
    public function projetos()
    {
        return $this->hasMany(Projeto::class);
    }

    public function execucoes()
    {
        return $this->hasMany(Execucao::class);
    }

    public function relatorios()
    {
        return $this->hasMany(Relatorio::class);
    }

    // Scopes
    public function scopeEngineers($query)
    {
        return $query->whereIn('role', ['admin', 'engineer']);
    }

    // Helpers
    public function isAdmin()
    {
        return $this->role === 'admin';
    }

    public function isEngineer()
    {
        return in_array($this->role, ['admin', 'engineer']);
    }

    // Métodos obrigatórios do JWT
    public function getJWTIdentifier()
    {
        return $this->getKey();
    }

    public function getJWTCustomClaims()
    {
        return [
            'role' => $this->role,
            'company' => $this->company,
        ];
    }
}
