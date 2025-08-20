<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('climas', function (Blueprint $table) {
            $table->id();
            $table->string('nome');
            $table->string('cidade');
            $table->string('estado');
            $table->string('pais')->default('Brasil');
            
            // Coordenadas
            $table->decimal('latitude', 10, 8);
            $table->decimal('longitude', 11, 8);
            $table->decimal('altitude', 8, 2)->nullable(); // m
            
            // Temperaturas extremas
            $table->decimal('temp_min_historica', 8, 2); // °C
            $table->decimal('temp_max_historica', 8, 2); // °C
            $table->decimal('temp_media_anual', 8, 2); // °C
            
            // Irradiação
            $table->decimal('irradiacao_global_horizontal', 8, 2); // kWh/m²/dia
            $table->decimal('irradiacao_direta_normal', 8, 2)->nullable(); // kWh/m²/dia
            
            $table->boolean('ativo')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('climas');
    }
};
