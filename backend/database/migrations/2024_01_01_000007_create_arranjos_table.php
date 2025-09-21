<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('arranjos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('projeto_id')->constrained('projetos');
            $table->foreignId('inversor_id')->constrained('inversores');
            
            $table->string('nome');
            $table->text('descricao')->nullable();
            
            // Sombreamento
            $table->decimal('fator_sombreamento', 5, 4)->default(1.0); // 0-1
            
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('arranjos');
    }
};
