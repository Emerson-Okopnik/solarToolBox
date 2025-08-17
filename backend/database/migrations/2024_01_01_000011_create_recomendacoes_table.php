<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('recomendacoes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('execucao_id')->constrained('execucoes');
            $table->foreignId('checagem_id')->nullable()->constrained('checagens');
            
            $table->enum('prioridade', ['baixa', 'media', 'alta', 'critica']);
            $table->enum('categoria', [
                'configuracao_string',
                'selecao_equipamento',
                'distribuicao_mppt',
                'orientacao_modulos',
                'seguranca_eletrica'
            ]);
            
            $table->string('titulo');
            $table->text('descricao');
            $table->text('solucao_sugerida');
            
            // Impacto estimado
            $table->json('impacto_estimado')->nullable();
            
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('recomendacoes');
    }
};
