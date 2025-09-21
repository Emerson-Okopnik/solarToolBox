<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('strings', function (Blueprint $table) {
            $table->foreignId('modulo_id')->nullable()->constrained('modulos');
        });

        match (DB::getDriverName()) {
            'pgsql' => DB::statement(
                'UPDATE strings SET modulo_id = arranjos.modulo_id FROM arranjos WHERE arranjos.id = strings.arranjo_id'
            ),
            'mysql', 'mariadb' => DB::statement(
                'UPDATE strings INNER JOIN arranjos ON arranjos.id = strings.arranjo_id SET strings.modulo_id = arranjos.modulo_id'
            ),
            default => throw new \RuntimeException('Unsupported database driver: '.DB::getDriverName()),
        };

        match (DB::getDriverName()) {
            'pgsql' => DB::statement('ALTER TABLE strings ALTER COLUMN modulo_id SET NOT NULL'),
            'mysql', 'mariadb' => DB::statement('ALTER TABLE strings MODIFY modulo_id BIGINT UNSIGNED NOT NULL'),
            default => throw new \RuntimeException('Unsupported database driver: '.DB::getDriverName()),
        };

        Schema::table('arranjos', function (Blueprint $table) {
            $table->dropForeign(['modulo_id']);
            $table->dropColumn('modulo_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('arranjos', function (Blueprint $table) {
            $table->foreignId('modulo_id')->nullable()->constrained('modulos');
        });

        match (DB::getDriverName()) {
            'pgsql' => DB::statement(
                'UPDATE arranjos SET modulo_id = strings.modulo_id FROM strings WHERE strings.arranjo_id = arranjos.id'
            ),
            'mysql', 'mariadb' => DB::statement(
                'UPDATE arranjos INNER JOIN strings ON strings.arranjo_id = arranjos.id SET arranjos.modulo_id = strings.modulo_id'
            ),
            default => throw new \RuntimeException('Unsupported database driver: '.DB::getDriverName()),
        };

        match (DB::getDriverName()) {
            'pgsql' => DB::statement('ALTER TABLE arranjos ALTER COLUMN modulo_id SET NOT NULL'),
            'mysql', 'mariadb' => DB::statement('ALTER TABLE arranjos MODIFY modulo_id BIGINT UNSIGNED NOT NULL'),
            default => throw new \RuntimeException('Unsupported database driver: '.DB::getDriverName()),
        };

        Schema::table('strings', function (Blueprint $table) {
            $table->dropForeign(['modulo_id']);
            $table->dropColumn('modulo_id');
        });
    }
};