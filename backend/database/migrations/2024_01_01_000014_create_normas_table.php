<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('normas', function (Blueprint $table) {
            $table->id();
            $table->string('codigo')->unique(); // NBR, IEC, etc.
            $table->string('titulo');
            $table->text('descricao');
            $table->string('orgao_emissor'); // ABNT, IEC, etc.
            $table->date('data_publicacao');
            $table->date('data_revisao')->nullable();
            
            $table->enum('status', ['vigente', 'revisada', 'cancelada'])->default('vigente');
            $table->string('pais')->default('Brasil');
            
            // Parâmetros técnicos da norma
            $table->json('parametros_tecnicos')->nullable();
            
            $table->boolean('ativa')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('normas');
    }
};
