<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('mppts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('inversor_id')->constrained('inversores');
            $table->integer('numero'); // 1, 2, 3...
            
            // Janela MPPT
            $table->decimal('tensao_mppt_min', 8, 2); // V
            $table->decimal('tensao_mppt_max', 8, 2); // V
            
            // Limites de corrente
            $table->decimal('corrente_entrada_max', 8, 2); // A
            $table->integer('strings_max')->default(1);
            
            $table->timestamps();
            
            $table->unique(['inversor_id', 'numero']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('mppts');
    }
};
