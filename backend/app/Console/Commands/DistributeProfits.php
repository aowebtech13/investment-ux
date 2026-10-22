<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

use App\Models\Investment;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class DistributeProfits extends Command
{
    protected $signature = 'app:distribute-profits';
    protected $description = 'Distribute profits to active investments based on their plan';

    public function handle()
    {
        $this->info("Profit distribution via this command has been disabled. Profits are now distributed via the admin panel on group maturity.");
        return;
    }

    protected function processInvestment($investment)
    {
        // Logic removed in favor of group maturation
        return false;
    }
}
