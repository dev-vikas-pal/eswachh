<?php

namespace Modules\Report\Console\Commands;

use Illuminate\Console\Command;

class ReportCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'command:ReportCommand';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Report Command description';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        return Command::SUCCESS;
    }
}
