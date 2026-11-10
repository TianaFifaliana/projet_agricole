<?php

namespace App\Console\Commands;

use App\Jobs\GenerateIrrigationReportJob;
use Illuminate\Console\Command;

class GenerateIrrigationReportCommand extends Command
{
    /**
     * @var string
     */
    protected $signature = 'irrigation:report
                            {campaign : Identifiant de la campagne (cycle de culture)}
                            {--sync : Générer le rapport immédiatement au lieu de le mettre en file}';

    /**
     * @var string
     */
    protected $description = "Génère le rapport de consommation d'eau par rizière et par semaine pour une campagne";

    public function handle(): int
    {
        $campaignId = filter_var($this->argument('campaign'), FILTER_VALIDATE_INT);

        if ($campaignId === false || $campaignId <= 0) {
            $this->error('L\'identifiant de campagne est invalide.');

            return self::FAILURE;
        }

        if ($this->option('sync')) {
            GenerateIrrigationReportJob::dispatchSync($campaignId);

            $this->info("Le rapport d'irrigation de la campagne #{$campaignId} a été généré.");

            return self::SUCCESS;
        }

        GenerateIrrigationReportJob::dispatch($campaignId);

        $this->info("Le rapport d'irrigation de la campagne #{$campaignId} a été mis en file d'attente.");

        return self::SUCCESS;
    }
}
