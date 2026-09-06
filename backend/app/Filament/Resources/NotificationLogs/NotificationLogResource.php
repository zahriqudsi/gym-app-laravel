<?php

namespace App\Filament\Resources\NotificationLogs;

use App\Filament\Resources\NotificationLogs\Pages\ListNotificationLogs;
use App\Models\NotificationLog;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class NotificationLogResource extends Resource
{
    protected static ?string $model = NotificationLog::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedInboxStack;

    protected static string|\UnitEnum|null $navigationGroup = 'Messaging';

    protected static ?int $navigationSort = 3;

    protected static ?string $navigationLabel = 'Reminder log';

    public static function form(Schema $schema): Schema
    {
        return $schema->components([]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                TextColumn::make('created_at')->dateTime('d M Y, g:i A')->label('Queued')->sortable(),
                TextColumn::make('member.name')->searchable(['members.first_name', 'members.last_name'])->placeholder('—'),
                TextColumn::make('template_key')->badge(),
                TextColumn::make('channel')->badge(),
                TextColumn::make('status')->badge()->color(fn ($state) => match ($state) {
                    'sent', 'delivered' => 'success', 'failed' => 'danger', default => 'gray',
                }),
                TextColumn::make('cost_cents')->label('Cost')->money('LKR', divideBy: 100)->placeholder('—'),
                TextColumn::make('sent_at')->dateTime('g:i A')->placeholder('—'),
                TextColumn::make('error')->limit(40)->placeholder('—')->color('danger')->toggleable(),
            ])
            ->filters([
                SelectFilter::make('status')->options([
                    'queued' => 'Queued', 'sent' => 'Sent', 'delivered' => 'Delivered', 'failed' => 'Failed',
                ]),
                Filter::make('today')->label('Today')->query(fn (Builder $q) => $q->whereDate('created_at', today())),
            ]);
    }

    public static function getPages(): array
    {
        return ['index' => ListNotificationLogs::route('/')];
    }

    public static function canCreate(): bool
    {
        return false;
    }
}
