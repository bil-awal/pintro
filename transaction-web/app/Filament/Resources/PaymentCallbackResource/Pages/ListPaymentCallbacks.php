<?php

namespace App\Filament\Resources\PaymentCallbackResource\Pages;

use App\Filament\Resources\PaymentCallbackResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListPaymentCallbacks extends ListRecords
{
    protected static string $resource = PaymentCallbackResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
