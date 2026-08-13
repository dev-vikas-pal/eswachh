<?php

namespace Modules\Duration\Console\Commands;

use Illuminate\Console\Command;

class DurationCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'command:DurationCommand';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Duration Command description';

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
