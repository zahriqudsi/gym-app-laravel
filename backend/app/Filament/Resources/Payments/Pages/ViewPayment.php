<?php

namespace App\Filament\Resources\Payments\Pages;

use App\Filament\Resources\Payments\PaymentResource;
use Filament\Actions\Action;
use Filament\Resources\Pages\ViewRecord;
use Filament\Support\Icons\Heroicon;

class ViewPayment extends ViewRecord
{
    protected static string $resource = PaymentResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('receipt')->label('Download receipt')->icon(Heroicon::OutlinedDocumentArrowDown)
                ->url(fn () => route('documents.receipt', $this->record), true),
        ];
    }
}
