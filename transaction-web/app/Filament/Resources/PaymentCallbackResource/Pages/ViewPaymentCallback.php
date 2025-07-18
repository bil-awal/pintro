<?php

namespace App\Filament\Resources\PaymentCallbackResource\Pages;

use App\Filament\Resources\PaymentCallbackResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewPaymentCallback extends ViewRecord
{
    protected static string $resource = PaymentCallbackResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make(),
        ];
    }
}
