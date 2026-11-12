<?php

namespace App\Console\Commands;

use Database\Seeders\DemoInvitationSeeder;
use Illuminate\Console\Command;

/**
 * P3-T7: restore the demo invitation to its factory state.
 */
class ResetDemo extends Command
{
    protected $signature = 'demo:reset';

    protected $description = 'Rebuild the demo invitation (fake data, local assets)';

    public function handle(): int
    {
        $this->call('db:seed', [
            '--class' => DemoInvitationSeeder::class,
            '--force' => true,
        ]);

        $this->info('Demo invitation restored at /'.DemoInvitationSeeder::SLUG);

        return self::SUCCESS;
    }
}
