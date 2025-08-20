<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('regras_negocio', function (Blueprint $table) {
            $table->id();
            $table->string('codigo')->unique();
            $table->string('nome');
            $table->text('descricao');
            
            $table->enum('categoria', [
                'compatibilidade',
                'seguranca',
                'performance',
                'instalacao'
            ]);
            
            $table->json('parametros'); // Valores configuráveis da regra
            $table->boolean('ativa')->default(true);
            $table->integer('prioridade')->default(1); // 1=alta, 5=baixa
            
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('regras_negocio');
    }
};
