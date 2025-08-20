<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('inversores', function (Blueprint $table) {
            $table->id();
            $table->foreignId('fabricante_id')->constrained('fabricantes');
            $table->string('modelo');
            $table->enum('tipo', ['string', 'central', 'micro', 'otimizador']);
            
            // Especificações DC
            $table->decimal('potencia_dc_max', 10, 2); // W
            $table->decimal('tensao_dc_max', 8, 2); // V
            $table->decimal('tensao_dc_min', 8, 2); // V
            $table->decimal('corrente_dc_max', 8, 2); // A
            $table->integer('num_mppts');
            
            // Especificações AC
            $table->decimal('potencia_ac_nominal', 10, 2); // W
            $table->decimal('tensao_ac_nominal', 8, 2); // V
            $table->decimal('corrente_ac_max', 8, 2); // A
            $table->decimal('frequencia_nominal', 8, 2); // Hz
            $table->decimal('eficiencia_max', 5, 2); // %
            
            // Limites operacionais
            $table->decimal('temp_operacao_min', 8, 2); // °C
            $table->decimal('temp_operacao_max', 8, 2); // °C
            $table->decimal('altitude_max', 8, 2)->nullable(); // m
            $table->decimal('umidade_max', 5, 2)->nullable(); // %
            
            $table->boolean('ativo')->default(true);
            $table->timestamps();
            
            $table->unique(['fabricante_id', 'modelo']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('inversores');
    }
};
