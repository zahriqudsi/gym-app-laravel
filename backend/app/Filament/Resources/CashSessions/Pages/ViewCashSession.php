<?php

namespace App\Filament\Resources\CashSessions\Pages;

use App\Filament\Actions\CloseCashSessionAction;
use App\Filament\Resources\CashSessions\CashSessionResource;
use Filament\Resources\Pages\ViewRecord;

class ViewCashSession extends ViewRecord
{
    protected static string $resource = CashSessionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CloseCashSessionAction::make(),
        ];
    }
}
