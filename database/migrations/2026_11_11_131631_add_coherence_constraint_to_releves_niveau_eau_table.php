<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        if (DB::connection()->getDriverName() === 'sqlite') {
            DB::unprepared(<<<'SQL'
                CREATE TRIGGER IF NOT EXISTS releves_niveau_eau_check_coherence_insert
                BEFORE INSERT ON releves_niveau_eau
                FOR EACH ROW
                WHEN NEW.niveau_constate_mm < 0
                    OR NEW.precipitations_mm < 0
                    OR (NEW.pertes_anormales_m3 IS NOT NULL AND NEW.pertes_anormales_m3 < 0)
                BEGIN
                    SELECT RAISE(ABORT, 'Le relevé doit être cohérent : niveaux et pertes non négatifs.');
                END;
            SQL);

            DB::unprepared(<<<'SQL'
                CREATE TRIGGER IF NOT EXISTS releves_niveau_eau_check_coherence_update
                BEFORE UPDATE ON releves_niveau_eau
                FOR EACH ROW
                WHEN NEW.niveau_constate_mm < 0
                    OR NEW.precipitations_mm < 0
                    OR (NEW.pertes_anormales_m3 IS NOT NULL AND NEW.pertes_anormales_m3 < 0)
                BEGIN
                    SELECT RAISE(ABORT, 'Le relevé doit être cohérent : niveaux et pertes non négatifs.');
                END;
            SQL);

            return;
        }

        DB::statement('ALTER TABLE releves_niveau_eau ADD CONSTRAINT chk_releves_niveau_eau_coherence CHECK (niveau_constate_mm >= 0 AND precipitations_mm >= 0 AND (pertes_anormales_m3 IS NULL OR pertes_anormales_m3 >= 0))');
    }

    public function down(): void
    {
        if (DB::connection()->getDriverName() === 'sqlite') {
            DB::unprepared('DROP TRIGGER IF EXISTS releves_niveau_eau_check_coherence_insert');
            DB::unprepared('DROP TRIGGER IF EXISTS releves_niveau_eau_check_coherence_update');

            return;
        }

        DB::statement('ALTER TABLE releves_niveau_eau DROP CONSTRAINT chk_releves_niveau_eau_coherence');
    }
};
