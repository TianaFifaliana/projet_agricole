<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        if (DB::connection()->getDriverName() === 'sqlite') {
            DB::unprepared(<<<'SQL'
                CREATE TRIGGER IF NOT EXISTS water_need_forecasts_check_coherence_insert
                BEFORE INSERT ON water_need_forecasts
                FOR EACH ROW
                WHEN NEW.besoin_net_total_m3 > NEW.besoin_brut_total_m3
                    OR NEW.besoin_brut_total_m3 > NEW.volume_propose_m3
                BEGIN
                    SELECT RAISE(ABORT, 'La prévision doit être cohérente : besoin net <= besoin brut <= volume proposé.');
                END;
            SQL);

            DB::unprepared(<<<'SQL'
                CREATE TRIGGER IF NOT EXISTS water_need_forecasts_check_coherence_update
                BEFORE UPDATE ON water_need_forecasts
                FOR EACH ROW
                WHEN NEW.besoin_net_total_m3 > NEW.besoin_brut_total_m3
                    OR NEW.besoin_brut_total_m3 > NEW.volume_propose_m3
                BEGIN
                    SELECT RAISE(ABORT, 'La prévision doit être cohérente : besoin net <= besoin brut <= volume proposé.');
                END;
            SQL);

            return;
        }

        DB::statement('ALTER TABLE water_need_forecasts ADD CONSTRAINT chk_water_need_forecasts_coherence CHECK (besoin_net_total_m3 <= besoin_brut_total_m3 AND besoin_brut_total_m3 <= volume_propose_m3)');
    }

    public function down(): void
    {
        if (DB::connection()->getDriverName() === 'sqlite') {
            DB::unprepared('DROP TRIGGER IF EXISTS water_need_forecasts_check_coherence_insert');
            DB::unprepared('DROP TRIGGER IF EXISTS water_need_forecasts_check_coherence_update');

            return;
        }

        DB::statement('ALTER TABLE water_need_forecasts DROP CONSTRAINT chk_water_need_forecasts_coherence');
    }
};
