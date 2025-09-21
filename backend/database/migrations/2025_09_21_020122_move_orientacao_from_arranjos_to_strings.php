<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasColumn('strings', 'azimute')) {
            Schema::table('strings', function (Blueprint $table) {
                $table->decimal('azimute', 5, 2)->nullable()->after('modulo_id');
            });
        }

        if (!Schema::hasColumn('strings', 'inclinacao')) {
            Schema::table('strings', function (Blueprint $table) {
                $table->decimal('inclinacao', 5, 2)->nullable()->after('azimute');
            });
        }

        DB::statement('
            UPDATE strings
            SET azimute = (
                SELECT arranjos.azimute
                FROM arranjos
                WHERE arranjos.id = strings.arranjo_id
            )
        ');

        DB::statement('
            UPDATE strings
            SET inclinacao = (
                SELECT arranjos.inclinacao
                FROM arranjos
                WHERE arranjos.id = strings.arranjo_id
            )
        ');

        DB::table('strings')->whereNull('azimute')->update(['azimute' => 0]);
        DB::table('strings')->whereNull('inclinacao')->update(['inclinacao' => 0]);

        $this->setStringsOrientationNotNull();

        if (Schema::hasColumn('arranjos', 'azimute')) {
            Schema::table('arranjos', function (Blueprint $table) {
                $table->dropColumn('azimute');
            });
        }

        if (Schema::hasColumn('arranjos', 'inclinacao')) {
            Schema::table('arranjos', function (Blueprint $table) {
                $table->dropColumn('inclinacao');
            });
        }
    }

    public function down(): void
    {
        if (!Schema::hasColumn('arranjos', 'azimute')) {
            Schema::table('arranjos', function (Blueprint $table) {
                $table->decimal('azimute', 5, 2)->nullable()->after('descricao');
            });
        }

        if (!Schema::hasColumn('arranjos', 'inclinacao')) {
            Schema::table('arranjos', function (Blueprint $table) {
                $table->decimal('inclinacao', 5, 2)->nullable()->after('azimute');
            });
        }

        DB::statement('
            UPDATE arranjos
            SET azimute = (
                SELECT strings.azimute
                FROM strings
                WHERE strings.arranjo_id = arranjos.id
                ORDER BY strings.id
                LIMIT 1
            )
        ');

        DB::statement('
            UPDATE arranjos
            SET inclinacao = (
                SELECT strings.inclinacao
                FROM strings
                WHERE strings.arranjo_id = arranjos.id
                ORDER BY strings.id
                LIMIT 1
            )
        ');

        DB::table('arranjos')->whereNull('azimute')->update(['azimute' => 0]);
        DB::table('arranjos')->whereNull('inclinacao')->update(['inclinacao' => 0]);

        $this->setArranjosOrientationNotNull();

        if (Schema::hasColumn('strings', 'azimute')) {
            Schema::table('strings', function (Blueprint $table) {
                $table->dropColumn('azimute');
            });
        }

        if (Schema::hasColumn('strings', 'inclinacao')) {
            Schema::table('strings', function (Blueprint $table) {
                $table->dropColumn('inclinacao');
            });
        }
    }

    private function setStringsOrientationNotNull(): void
    {
        $driver = DB::getDriverName();

        if ($driver === 'pgsql') {
            DB::statement('ALTER TABLE strings ALTER COLUMN azimute SET NOT NULL');
            DB::statement('ALTER TABLE strings ALTER COLUMN inclinacao SET NOT NULL');
        } elseif ($driver === 'mysql') {
            DB::statement('ALTER TABLE strings MODIFY azimute DECIMAL(5, 2) NOT NULL');
            DB::statement('ALTER TABLE strings MODIFY inclinacao DECIMAL(5, 2) NOT NULL');
        }
    }

    private function setArranjosOrientationNotNull(): void
    {
        $driver = DB::getDriverName();

        if ($driver === 'pgsql') {
            DB::statement('ALTER TABLE arranjos ALTER COLUMN azimute SET NOT NULL');
            DB::statement('ALTER TABLE arranjos ALTER COLUMN inclinacao SET NOT NULL');
        } elseif ($driver === 'mysql') {
            DB::statement('ALTER TABLE arranjos MODIFY azimute DECIMAL(5, 2) NOT NULL');
            DB::statement('ALTER TABLE arranjos MODIFY inclinacao DECIMAL(5, 2) NOT NULL');
        }
    }
};