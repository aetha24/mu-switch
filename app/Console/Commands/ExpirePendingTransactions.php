<?php

namespace App\Console\Commands;

use App\Support\PendingTransactionExpiry;
use Illuminate\Console\Command;

class ExpirePendingTransactions extends Command
{
    protected $signature = 'payments:expire-pending';

    protected $description = 'Mark unapproved mobile-money prompts as failed after two minutes';

    public function handle(): int
    {
        $expired = PendingTransactionExpiry::expireOverdue();
        $this->info("Expired {$expired} pending transaction(s).");

        return self::SUCCESS;
    }
}
