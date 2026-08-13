<?php

namespace Modules\Cloth\Console\Commands;

use Illuminate\Console\Command;

class ClothCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'command:ClothCommand';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Cloth Command description';

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
