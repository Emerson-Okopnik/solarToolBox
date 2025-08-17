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
            $table->foreignId('modulo_id')->constrained('modulos');
            $table->foreignId('inversor_id')->constrained('inversores');
            
            $table->string('nome');
            $table->text('descricao')->nullable();
            
            // Orientação e inclinação
            $table->decimal('azimute', 5, 2); // graus (0-360)
            $table->decimal('inclinacao', 5, 2); // graus (0-90)
            
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
