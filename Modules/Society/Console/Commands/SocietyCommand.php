<?php

namespace Modules\Society\Console\Commands;

use Illuminate\Console\Command;

class SocietyCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'command:SocietyCommand';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Society Command description';

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
