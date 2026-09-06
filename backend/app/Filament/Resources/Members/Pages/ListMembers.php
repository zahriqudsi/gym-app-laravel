<?php

namespace App\Filament\Resources\Members\Pages;

use App\Filament\Imports\MemberImporter;
use App\Filament\Resources\Members\MemberResource;
use Filament\Actions\CreateAction;
use Filament\Actions\ImportAction;
use Filament\Resources\Pages\ListRecords;

class ListMembers extends ListRecords
{
    protected static string $resource = MemberResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ImportAction::make()
                ->importer(MemberImporter::class)
                ->label('Import CSV'),
            CreateAction::make(),
        ];
    }
}
