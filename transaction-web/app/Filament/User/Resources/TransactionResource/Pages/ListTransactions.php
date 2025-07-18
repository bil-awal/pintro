<?php

namespace App\Filament\User\Resources\TransactionResource\Pages;

use App\Filament\User\Resources\TransactionResource;
use Filament\Resources\Pages\ListRecords;
use Filament\Actions;

class ListTransactions extends ListRecords
{
    protected static string $resource = TransactionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            // Actions are defined in the resource header actions
        ];
    }

    public function getTitle(): string
    {
        return 'Transaction History';
    }

    public function getHeading(): string
    {
        return 'Transaction History';
    }

    public function getSubheading(): string
    {
        return 'View and manage your transaction history';
    }
}
