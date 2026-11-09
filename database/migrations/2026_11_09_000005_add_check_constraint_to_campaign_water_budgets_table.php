<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        if (DB::connection()->getDriverName() === 'sqlite') {
            DB::unprepared(<<<'SQL'
                CREATE TRIGGER IF NOT EXISTS campaign_water_budgets_check_consomme_insert
                BEFORE INSERT ON campaign_water_budgets
                FOR EACH ROW
                WHEN NEW.volume_consomme_m3 > NEW.volume_alloue_m3
                BEGIN
                    SELECT RAISE(ABORT, 'Le volume consommé ne peut pas dépasser le volume alloué.');
                END;
            SQL);

            DB::unprepared(<<<'SQL'
                CREATE TRIGGER IF NOT EXISTS campaign_water_budgets_check_consomme_update
                BEFORE UPDATE ON campaign_water_budgets
                FOR EACH ROW
                WHEN NEW.volume_consomme_m3 > NEW.volume_alloue_m3
                BEGIN
                    SELECT RAISE(ABORT, 'Le volume consommé ne peut pas dépasser le volume alloué.');
                END;
            SQL);

            return;
        }

        DB::statement('ALTER TABLE campaign_water_budgets ADD CONSTRAINT chk_campaign_water_budgets_consomme CHECK (volume_consomme_m3 <= volume_alloue_m3)');
    }

    public function down(): void
    {
        if (DB::connection()->getDriverName() === 'sqlite') {
            DB::unprepared('DROP TRIGGER IF EXISTS campaign_water_budgets_check_consomme_insert');
            DB::unprepared('DROP TRIGGER IF EXISTS campaign_water_budgets_check_consomme_update');

            return;
        }

        DB::statement('ALTER TABLE campaign_water_budgets DROP CONSTRAINT chk_campaign_water_budgets_consomme');
    }
};
