<?php

namespace App\Filament\Resources\ReminderRules\Pages;

use App\Filament\Resources\ReminderRules\ReminderRuleResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListReminderRules extends ListRecords
{
    protected static string $resource = ReminderRuleResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
