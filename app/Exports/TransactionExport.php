<?php

namespace App\Exports;

use App\Models\Transaction;
use Carbon\Carbon;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;

class TransactionExport implements FromCollection, WithHeadings
{
    private $transactions;

    public function __construct($transactions)
    {
        $this->transactions = $transactions;
    }

    /**
    * @return \Illuminate\Support\Collection
    */
    public function collection()
    {
        $records = $this->transactions->get();
        $result = [];

        foreach ($records as $record) {
            // Check if $record is an array and has the necessary properties

            $result[] = [
                'name' => $record->name,
                'email' => $record->email,
                'created_at' => Carbon::parse($record->created_at)->format('Y-m-d'),
                'first_leader' => $record->top_leader->name ?? '',
                'wallets_sum_balance' => $record->wallets->sum('balance'),
                'country' => Country::find($record->country)->name,
                'rank' => $record->rank->name,
                'kyc_approval' => $record->kyc_approval,
            ];
        }

        return collect($result);
    }

    public function headings(): array
    {
        return [
            'Name',
            'Email',
            'Joining Date',
            'First Leader',
            'Wallet Balance',
            'Country',
            'Rank',
            'Status',
        ];
    }
}
