<?php

namespace Modules\Sector\Console\Commands;

use Illuminate\Console\Command;

class SectorCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'command:SectorCommand';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Sector Command description';

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
