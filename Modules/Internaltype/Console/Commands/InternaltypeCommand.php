<?php

namespace Modules\Internaltype\Console\Commands;

use Illuminate\Console\Command;

class InternaltypeCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'command:InternaltypeCommand';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Internaltype Command description';

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
