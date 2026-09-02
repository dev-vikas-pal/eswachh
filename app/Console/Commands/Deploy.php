<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Throwable;

/**
 * Bring a database and its caches up to this copy of the code, in one command.
 *
 * Written because the alternative is seven commands typed into a hosting
 * panel's terminal in the right order, and the order is not obvious: clearing
 * the config cache *after* rebuilding it leaves the old values in place, and
 * running `config:cache` before editing `.env` caches the values you were
 * about to change. Both have happened, and both fail quietly - the site keeps
 * working, on yesterday's settings.
 *
 * It is safe to run twice. Migrations that have already run are skipped by
 * Laravel; the seeders it calls create rather than replace.
 */
class Deploy extends Command
{
    protected $signature = 'eswachh:deploy
                            {--seed : Also seed users, roles and permissions. For a blank database.}
                            {--force : Do not ask, even in production.}
                            {--pretend : Say what would happen and change nothing.}';

    protected $description = 'Run pending migrations, seed a blank database, and rebuild the caches';

    public function handle(): int
    {
        /*
         * Clear the config cache before reading a single value from it.
         *
         * Otherwise this reports the database the *last* deploy cached, not the
         * one it is about to touch - `config()` answers from
         * bootstrap/cache/config.php while the queries run against whatever
         * .env says now. Caught here doing exactly that: the header said one
         * database and the row counts underneath came from another.
         *
         * On a deploy that is the worst possible bug, because the whole point
         * of printing the name is to stop somebody migrating the wrong one.
         */
        Artisan::call('config:clear');

        $database = config('database.connections.'.config('database.default').'.database');
        $host = config('database.connections.'.config('database.default').'.host');

        $this->newLine();
        $this->line('  <fg=cyan>Database</> '.$database.'  <fg=gray>on</> '.$host);
        $this->line('  <fg=cyan>Environment</> '.app()->environment());
        $this->newLine();

        try {
            DB::connection()->getPdo();
        } catch (Throwable $e) {
            $this->error('Cannot reach that database: '.$e->getMessage());

            return self::FAILURE;
        }

        $blank = ! Schema::hasTable('migrations');
        $pending = $this->pendingMigrations();

        /*
         * A blank database is built from database/schema/mysql-schema.sql, not
         * from the migrations one at a time - Laravel squashed them, and the
         * old ones are no longer self-consistent without it. Running them
         * individually stops partway with "Unknown column 'user_id' in
         * 'orders'" and leaves about thirty tables behind.
         *
         * Loading that dump shells out to the `mysql` client, so it has to be
         * on the PATH. Checked here, before the site is taken down, because the
         * failure otherwise happens with the site already closed.
         */
        if ($blank && ! $this->canLoadSchemaDump()) {
            $this->error('  This database is empty, and the schema dump cannot be loaded.');
            $this->newLine();
            $this->line('  A blank database is built from <fg=cyan>database/schema/mysql-schema.sql</>,');
            $this->line('  which Laravel loads by running the <fg=cyan>mysql</> client - and that is not');
            $this->line('  reachable from here. Running the migrations one at a time instead does');
            $this->line('  not work: they stop partway and leave a half-built database.');
            $this->newLine();
            $this->line('  Either make the <fg=cyan>mysql</> client available, or - better for a test site -');
            $this->line('  import a dump of the live database through phpMyAdmin first, then run');
            $this->line('  this again. It will apply the pending migrations on top of it.');

            return self::FAILURE;
        }

        if ($blank) {
            $this->warn('  This database is empty. Everything will be created from scratch.');
        } elseif ($pending === []) {
            $this->line('  <fg=green>No pending migrations.</> The schema is already up to date.');
        } else {
            $this->line('  <fg=yellow>'.count($pending).' migration(s) to run:</>');

            foreach ($pending as $migration) {
                $this->line('    '.$migration);
            }
        }

        $this->newLine();

        if ($this->option('pretend')) {
            $this->comment('  Pretending: nothing was changed.');

            return self::SUCCESS;
        }

        /*
         * The one question worth asking.
         *
         * The mistake this catches is pointing a test subdomain's .env at the
         * live database and "trying the migrations out" - which cannot be
         * undone by re-uploading files, unlike every other part of a deploy.
         */
        if (! $this->option('force') && ! $this->confirm("Run against '{$database}'?", false)) {
            $this->comment('  Nothing was changed.');

            return self::SUCCESS;
        }

        $steps = [
            // Down first: it stops an order being written against half-migrated
            // tables. Skipped when there is nothing to migrate, so a cache
            // rebuild does not need a maintenance window.
            'Closing the site' => fn () => $pending || $blank ? Artisan::call('down') : null,
            'Running migrations' => fn () => Artisan::call('migrate', ['--force' => true]),
        ];

        if ($this->option('seed') || $blank) {
            /*
             * A blank database with no users is a site nobody can sign in to,
             * so this is assumed rather than asked for when there is nothing
             * there. On a database that already has people in it, seeding is
             * opt-in.
             */
            $steps['Seeding users, roles and permissions'] = fn () => Artisan::call('db:seed', ['--force' => true]);
        }

        /*
         * Clear, then build. In that order, always.
         *
         * `config:cache` reads .env and freezes it. Building before clearing
         * leaves the previous cache in place on some setups, and clearing after
         * building throws away the thing you just made - so the site runs on
         * whatever was cached last time and nothing says so.
         */
        $steps['Clearing caches'] = function () {
            Artisan::call('config:clear');
            Artisan::call('cache:clear');
            Artisan::call('route:clear');
            Artisan::call('view:clear');
        };

        /*
         * `route:cache` is left out when it cannot succeed.
         *
         * Modules/Order/routes/api.php registers `frontend.orders.index`,
         * `frontend.orders.show` and `backend.orders.renew` - names that
         * web.php has already used - and Laravel refuses to serialise a route
         * table with a name in it twice. It has been that way since the
         * original import, so it is not something this deploy introduced.
         *
         * Detected rather than hard-coded, so this starts caching routes by
         * itself the day the names are made unique, and says what is in the way
         * until then. The site runs perfectly well without it; the only cost is
         * a few milliseconds a request.
         */
        $duplicates = $this->duplicateRouteNames();

        $steps['Rebuilding caches'] = function () use ($duplicates) {
            Artisan::call('config:cache');
            Artisan::call('view:cache');

            if ($duplicates === []) {
                Artisan::call('route:cache');
            }
        };

        // Uploaded pictures are served through it, and it does not survive a
        // fresh checkout. Already-linked is not an error worth stopping for.
        $steps['Linking storage'] = function () {
            try {
                Artisan::call('storage:link');
            } catch (Throwable) {
                // Already there.
            }
        };

        $steps['Opening the site'] = fn () => Artisan::call('up');

        foreach ($steps as $label => $step) {
            try {
                $this->components->task('  '.$label, function () use ($step) {
                    $step();

                    return true;
                });
            } catch (Throwable $e) {
                $this->newLine();
                $this->error('  Stopped at: '.$label);
                $this->error('  '.$e->getMessage());
                $this->newLine();
                $this->warn('  The site is still closed. Fix the above, then run this again.');
                $this->warn('  To reopen it without deploying: php artisan up');

                return self::FAILURE;
            }
        }

        $this->newLine();

        if ($duplicates !== []) {
            $this->warn('  Routes were not cached: '.count($duplicates).' route name(s) are registered twice.');

            foreach ($duplicates as $name) {
                $this->line('    <fg=yellow>'.$name.'</>');
            }

            $this->line('  <fg=gray>Modules/Order/routes/api.php reuses names from web.php. The site works</>');
            $this->line('  <fg=gray>without the route cache; making those names unique turns it back on.</>');
            $this->newLine();
        }

        $this->report();

        return self::SUCCESS;
    }

    /**
     * Route names registered more than once.
     *
     * Laravel refuses to serialise a route table with a repeated name, so this
     * is what decides whether `route:cache` can run at all.
     *
     * @return array<int, string>
     */
    private function duplicateRouteNames(): array
    {
        $seen = [];
        $twice = [];

        foreach (app('router')->getRoutes() as $route) {
            $name = $route->getName();

            if (! $name) {
                continue;
            }

            if (isset($seen[$name])) {
                $twice[$name] = true;
            }

            $seen[$name] = true;
        }

        return array_keys($twice);
    }

    /**
     * Can the schema dump actually be loaded?
     *
     * Only matters for a blank database. Two ways this fails on shared hosting:
     * the `mysql` client is not on the PATH, or PHP is not allowed to start a
     * process at all - and the second one is common enough to be worth naming
     * rather than letting it surface as a confusing shell error.
     */
    private function canLoadSchemaDump(): bool
    {
        if (! file_exists(database_path('schema/mysql-schema.sql'))) {
            // No dump to load; Laravel will run the migrations one by one.
            return true;
        }

        if (! function_exists('proc_open')) {
            return false;
        }

        $process = @proc_open(
            'mysql --version',
            [1 => ['pipe', 'w'], 2 => ['pipe', 'w']],
            $pipes,
        );

        if (! is_resource($process)) {
            return false;
        }

        foreach ($pipes as $pipe) {
            fclose($pipe);
        }

        return proc_close($process) === 0;
    }

    /**
     * What is left to do, without running anything.
     *
     * `migrate:status` is parsed rather than reimplemented so this cannot
     * disagree with what `migrate` will actually do.
     *
     * @return array<int, string>
     */
    private function pendingMigrations(): array
    {
        if (! Schema::hasTable('migrations')) {
            return [];
        }

        Artisan::call('migrate:status');

        $pending = [];

        foreach (preg_split('/\R/', Artisan::output()) as $line) {
            if (str_contains($line, 'Pending') && preg_match('/(\d{4}_\d{2}_\d{2}_\d+_\w+)/', $line, $found)) {
                $pending[] = $found[1];
            }
        }

        return $pending;
    }

    /**
     * What the database holds now, so somebody can see it worked without
     * opening the site.
     */
    private function report(): void
    {
        $counts = [];

        foreach (['users' => 'People', 'orders' => 'Orders', 'payment_history' => 'Payments', 'sectors' => 'Sectors'] as $table => $label) {
            if (Schema::hasTable($table)) {
                $counts[] = [$label, number_format(DB::table($table)->count())];
            }
        }

        if ($counts !== []) {
            $this->table(['In the database', 'Rows'], $counts);
        }

        if (Schema::hasTable('users') && DB::table('users')->count() === 0) {
            $this->warn('  There are no users, so nobody can sign in.');
            $this->warn('  Run it again with --seed, or restore a database dump.');
        }

        $this->line('  <fg=green>Done.</>');
        $this->newLine();
    }
}
