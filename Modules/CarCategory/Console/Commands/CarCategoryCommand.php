<?php

namespace Modules\CarCategory\Console\Commands;

use Illuminate\Console\Command;

class CarCategoryCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'command:CarCategoryCommand';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'CarCategory Command description';

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
