<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('relatorios', function (Blueprint $table) {
            $table->id();
            $table->foreignId('execucao_id')->constrained('execucoes');
            $table->foreignId('user_id')->constrained('users');
            
            $table->enum('tipo', ['completo', 'resumo', 'tecnico']);
            $table->enum('formato', ['pdf', 'html', 'json']);
            $table->enum('status', ['gerando', 'concluido', 'erro'])->default('gerando');
            
            $table->string('nome_arquivo')->nullable();
            $table->string('caminho_arquivo')->nullable();
            $table->integer('tamanho_bytes')->nullable();
            
            // Configurações do relatório
            $table->json('configuracoes')->nullable();
            
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('relatorios');
    }
};
