<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('projetos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users');
            $table->foreignId('clima_id')->constrained('climas');
            
            $table->string('nome');
            $table->string('cliente');
            $table->text('descricao')->nullable();
            $table->string('endereco')->nullable();
            
            // Status do projeto
            $table->enum('status', ['rascunho', 'em_analise', 'aprovado', 'rejeitado'])->default('rascunho');
            
            // Configurações específicas do projeto
            $table->decimal('limite_compatibilidade_tensao', 5, 2)->default(5.0); // %
            $table->decimal('limite_compatibilidade_corrente', 5, 2)->default(5.0); // %
            
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('projetos');
    }
};
