<?php

namespace App\Filament\Resources\Members\RelationManagers;

use App\Filament\Actions\SellMembershipAction;
use Filament\Actions\Action;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class MembershipsRelationManager extends RelationManager
{
    protected static string $relationship = 'memberships';

    protected static ?string $title = 'Memberships';

    public function form(Schema $schema): Schema
    {
        return $schema->components([]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('id')
            ->defaultSort('starts_on', 'desc')
            ->columns([
                TextColumn::make('plan.name')->label('Plan')->placeholder('—'),
                TextColumn::make('starts_on')->date(),
                TextColumn::make('ends_on')->date()->placeholder('session pack'),
                TextColumn::make('sessions')->label('Sessions')
                    ->state(fn ($record) => $record->sessions_total === null
                        ? '—'
                        : ($record->sessions_total - $record->sessions_used).' / '.$record->sessions_total),
                TextColumn::make('price_cents')->label('Price')->money('LKR', divideBy: 100),
                TextColumn::make('status')->badge()->color(fn (string $state) => match ($state) {
                    'active' => 'success',
                    'frozen' => 'info',
                    'expired', 'cancelled' => 'danger',
                    default => 'gray',
                }),
                TextColumn::make('invoice.number')->label('Invoice')
                    ->url(fn ($record) => $record->invoice_id ? route('documents.invoice', $record->invoice_id) : null, true)
                    ->placeholder('—'),
            ])
            ->headerActions([
                SellMembershipAction::make()
                    ->record(fn (RelationManager $livewire) => $livewire->getOwnerRecord()),
            ])
            ->recordActions([
                Action::make('freeze')
                    ->icon('heroicon-o-pause')
                    ->color('warning')
                    ->visible(fn ($record) => $record->status === 'active' && $record->ends_on)
                    ->requiresConfirmation()
                    ->schema([
                        \Filament\Forms\Components\TextInput::make('days')->numeric()->required()->minValue(1)->label('Freeze for (days)'),
                        \Filament\Forms\Components\TextInput::make('reason')->maxLength(200),
                    ])
                    ->action(function (array $data, $record) {
                        $days = (int) $data['days'];
                        $record->freezes()->create([
                            'from_date' => today(),
                            'to_date' => today()->addDays($days),
                            'days' => $days,
                            'reason' => $data['reason'] ?? null,
                            'created_by' => auth()->id(),
                        ]);
                        $record->update([
                            'status' => 'frozen',
                            'frozen_days' => $record->frozen_days + $days,
                            'ends_on' => $record->ends_on?->copy()->addDays($days),
                        ]);
                        app(\App\Domain\Members\MembershipStatusService::class)->sync($record->member);
                    }),
                Action::make('unfreeze')
                    ->icon('heroicon-o-play')
                    ->visible(fn ($record) => $record->status === 'frozen')
                    ->action(function ($record) {
                        $record->update(['status' => 'active']);
                        app(\App\Domain\Members\MembershipStatusService::class)->sync($record->member);
                    }),
            ]);
    }
}
