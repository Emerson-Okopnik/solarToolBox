<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('checagens', function (Blueprint $table) {
            $table->id();
            $table->foreignId('execucao_id')->constrained('execucoes');
            $table->foreignId('string_id')->nullable()->constrained('strings');
            $table->foreignId('arranjo_id')->nullable()->constrained('arranjos');
            
            $table->enum('tipo', [
                'compatibilidade_modulos',
                'capacidade_mppt',
                'tensao_maxima',
                'corrente_maxima',
                'janela_mppt',
                'distribuicao_orientacao'
            ]);
            
            $table->enum('resultado', ['aprovado', 'reprovado', 'aviso']);
            $table->string('titulo');
            $table->text('descricao');
            
            // Valores calculados
            $table->json('valores_calculados')->nullable();
            $table->json('limites_referencia')->nullable();
            
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('checagens');
    }
};
