<?php

namespace App\Filament\Resources\PaymentCallbackResource\Pages;

use App\Filament\Resources\PaymentCallbackResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditPaymentCallback extends EditRecord
{
    protected static string $resource = PaymentCallbackResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\ViewAction::make(),
            Actions\DeleteAction::make(),
        ];
    }
}
