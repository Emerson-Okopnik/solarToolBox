<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('modulos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('fabricante_id')->constrained('fabricantes');
            $table->string('modelo');
            $table->string('tecnologia'); // mono, poly, thin-film
            
            // Especificações elétricas STC
            $table->decimal('potencia_nominal', 8, 2); // Watts
            $table->decimal('voc', 8, 2); // Tensão circuito aberto (V)
            $table->decimal('vmp', 8, 2); // Tensão máxima potência (V)
            $table->decimal('isc', 8, 2); // Corrente curto-circuito (A)
            $table->decimal('imp', 8, 2); // Corrente máxima potência (A)
            
            // Coeficientes de temperatura
            $table->decimal('coef_temp_voc', 8, 6); // %/°C (negativo)
            $table->decimal('coef_temp_vmp', 8, 6); // %/°C (negativo)
            $table->decimal('coef_temp_isc', 8, 6); // %/°C (positivo)
            $table->decimal('coef_temp_imp', 8, 6); // %/°C (positivo)
            $table->decimal('coef_temp_potencia', 8, 6); // %/°C (negativo)
            
            // Especificações físicas
            $table->decimal('comprimento', 8, 2); // mm
            $table->decimal('largura', 8, 2); // mm
            $table->decimal('espessura', 8, 2); // mm
            $table->decimal('peso', 8, 2); // kg
            
            // Limites operacionais
            $table->decimal('temp_operacao_min', 8, 2); // °C
            $table->decimal('temp_operacao_max', 8, 2); // °C
            $table->decimal('tensao_maxima_sistema', 8, 2); // V
            
            $table->boolean('ativo')->default(true);
            $table->timestamps();
            
            $table->unique(['fabricante_id', 'modelo']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('modulos');
    }
};
