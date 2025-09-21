<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('strings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('arranjo_id')->constrained('arranjos');
            $table->foreignId('mppt_id')->nullable()->constrained('mppts');
            
            $table->string('nome');
            $table->enum('tipo_conexao', ['serie'])->default('serie');
            
            // Configuração da string
            $table->integer('num_modulos_serie'); // Ns
            $table->integer('num_strings_paralelo'); // Np
            $table->integer('total_modulos'); // Ns * Np
            
            // Resultados calculados (preenchidos após execução)
            $table->decimal('tensao_circuito_aberto', 8, 2)->nullable(); // V
            $table->decimal('tensao_maxima_potencia', 8, 2)->nullable(); // V
            $table->decimal('corrente_curto_circuito', 8, 2)->nullable(); // A
            $table->decimal('corrente_maxima_potencia', 8, 2)->nullable(); // A
            $table->decimal('potencia_total', 10, 2)->nullable(); // W
            
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('strings');
    }
};
