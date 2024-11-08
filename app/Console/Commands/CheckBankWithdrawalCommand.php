<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;

class CheckBankWithdrawalCommand extends Command
{
    protected $signature = 'check:bank-withdrawal';

    protected $description = 'Check new user bank withdrawal status';

    public function handle(): void
    {
        $leaders = User::whereIn('id', [793, 1382])
            ->get();

        foreach ($leaders as $leader) {
            $childrenIds = $leader->getChildrenIds();

            User::whereIn('id', $childrenIds)->update([
                'enable_bank_withdrawal' => $leader->enable_bank_withdrawal,
            ]);
        }
    }
}
