<?php

namespace App\Console\Commands;

use App\Jobs\ExpireHolds as JobsExpireHolds;
use Illuminate\Console\Command;

class ExpireHolds extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'holds:expire';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Expire holds that have expired';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Starting hold expiration job...');

        JobsExpireHolds::dispatch();

        $this->info('Hold expiration job completed.');
    }
}
