<?php

namespace App\Filament\Resources\ReminderRules\Pages;

use App\Filament\Resources\ReminderRules\ReminderRuleResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditReminderRule extends EditRecord
{
    protected static string $resource = ReminderRuleResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
