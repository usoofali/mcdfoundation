<?php

namespace App\Console\Commands;

use App\Services\ExpectedContributionService;
use Illuminate\Console\Command;

class ProcessExpectedContributions extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'contributions:process';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Process expected contributions - mark overdue and calculate fines';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $service = app(ExpectedContributionService::class);

        $this->info('Processing expected contributions...');

        // 1. Mark overdue contributions and calculate fines
        $overdueCount = $service->markOverdueContributions();
        $this->info("Marked {$overdueCount} contributions as overdue");

        // 2. Auto-generate for members running low on future contributions
        $this->info('Checking for members needing new contributions...');
        $generatedCount = $service->autoGenerateForAllMembers();
        $this->info("Generated contributions for {$generatedCount} new periods");

        $this->info('Processing complete!');

        return Command::SUCCESS;
    }
}
