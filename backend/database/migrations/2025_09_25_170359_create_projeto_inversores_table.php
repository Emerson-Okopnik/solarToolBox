<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('projeto_inversores', function (Blueprint $table) {
            $table->id();
            $table->foreignId('projeto_id')->constrained('projetos')->cascadeOnDelete();
            $table->foreignId('inversor_id')->constrained('inversores');
            $table->timestamps();
        });

        Schema::table('arranjos', function (Blueprint $table) {
            $table->foreignId('projeto_inversor_id')
                ->nullable()
                ->after('projeto_id')
                ->constrained('projeto_inversores');
        });

        $arranjos = DB::table('arranjos')->orderBy('id')->get();

        foreach ($arranjos as $arranjo) {
            $projetoInversorId = DB::table('projeto_inversores')->insertGetId([
                'projeto_id' => $arranjo->projeto_id,
                'inversor_id' => $arranjo->inversor_id,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            DB::table('arranjos')
                ->where('id', $arranjo->id)
                ->update(['projeto_inversor_id' => $projetoInversorId]);
        }

        Schema::table('arranjos', function (Blueprint $table) {
            $table->dropConstrainedForeignId('inversor_id');
        });
    }

    public function down(): void
    {
        Schema::table('arranjos', function (Blueprint $table) {
            $table->foreignId('inversor_id')
                ->nullable()
                ->after('projeto_id')
                ->constrained('inversores');
        });

        $arranjos = DB::table('arranjos')->orderBy('id')->get();

        foreach ($arranjos as $arranjo) {
            $projetoInversor = DB::table('projeto_inversores')
                ->where('id', $arranjo->projeto_inversor_id)
                ->first();

            if ($projetoInversor) {
                DB::table('arranjos')
                    ->where('id', $arranjo->id)
                    ->update(['inversor_id' => $projetoInversor->inversor_id]);
            }
        }

        Schema::table('arranjos', function (Blueprint $table) {
            $table->dropConstrainedForeignId('projeto_inversor_id');
        });

        Schema::dropIfExists('projeto_inversores');
    }
};