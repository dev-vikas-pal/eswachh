<?php

namespace Modules\Team\Console\Commands;

use Illuminate\Console\Command;

class TeamCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'command:TeamCommand';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Team Command description';

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
