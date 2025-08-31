<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class ResetDemo extends Command
{
    protected $signature = 'demo:reset';

    protected $description = 'Wipe the database and reseed the demo organization (demo mode only)';

    public function handle(): int
    {
        if (! config('delego.demo')) {
            $this->error('Demo mode is off (DELEGO_DEMO=false): refusing to wipe the database.');

            return self::FAILURE;
        }

        $this->call('migrate:fresh', ['--seed' => true, '--force' => true]);
        $this->info('Demo reset.');

        return self::SUCCESS;
    }
}
