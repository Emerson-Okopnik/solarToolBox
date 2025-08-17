<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('execucoes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('projeto_id')->constrained('projetos');
            $table->foreignId('user_id')->constrained('users');
            
            $table->enum('status', ['executando', 'concluida', 'erro'])->default('executando');
            $table->timestamp('iniciada_em');
            $table->timestamp('concluida_em')->nullable();
            
            // Resumo dos resultados
            $table->integer('total_checagens')->default(0);
            $table->integer('checagens_aprovadas')->default(0);
            $table->integer('checagens_reprovadas')->default(0);
            $table->integer('total_recomendacoes')->default(0);
            
            // Configurações usadas na execução
            $table->json('configuracoes')->nullable();
            
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('execucoes');
    }
};
