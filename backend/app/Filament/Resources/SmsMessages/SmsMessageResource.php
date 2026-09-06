<?php

namespace App\Filament\Resources\SmsMessages;

use App\Filament\Resources\SmsMessages\Pages\ListSmsMessages;
use App\Models\SmsMessage;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class SmsMessageResource extends Resource
{
    protected static ?string $model = SmsMessage::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedDevicePhoneMobile;

    protected static string|\UnitEnum|null $navigationGroup = 'Messaging';

    protected static ?int $navigationSort = 4;

    protected static ?string $navigationLabel = 'SMS log';

    public static function form(Schema $schema): Schema
    {
        return $schema->components([]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                TextColumn::make('created_at')->dateTime('d M Y, g:i A')->sortable(),
                TextColumn::make('to')->searchable(),
                TextColumn::make('member.name')->placeholder('—')->toggleable(),
                TextColumn::make('body')->limit(50)->wrap(),
                TextColumn::make('purpose')->badge(),
                TextColumn::make('segments')->label('Seg'),
                TextColumn::make('cost_cents')->label('Cost')->money('LKR', divideBy: 100),
                TextColumn::make('status')->badge()->color(fn ($state) => match ($state) {
                    'sent', 'delivered' => 'success', 'failed' => 'danger', default => 'gray',
                }),
                TextColumn::make('provider')->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('purpose')->options([
                    'reminder' => 'Reminder', 'otp' => 'OTP', 'campaign' => 'Campaign', 'manual' => 'Manual',
                ]),
                SelectFilter::make('status')->options([
                    'queued' => 'Queued', 'sent' => 'Sent', 'delivered' => 'Delivered', 'failed' => 'Failed',
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return ['index' => ListSmsMessages::route('/')];
    }

    public static function canCreate(): bool
    {
        return false;
    }
}
