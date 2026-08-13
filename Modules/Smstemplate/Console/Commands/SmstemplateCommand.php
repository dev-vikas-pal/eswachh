<?php

namespace Modules\Smstemplate\Console\Commands;

use Illuminate\Console\Command;

class SmstemplateCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'command:SmstemplateCommand';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Smstemplate Command description';

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
