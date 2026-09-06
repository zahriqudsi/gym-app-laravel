<?php

namespace App\Filament\Resources\CashSessions;

use App\Domain\Support\Money;
use App\Filament\Resources\CashSessions\Pages\ListCashSessions;
use App\Filament\Resources\CashSessions\Pages\ViewCashSession;
use App\Models\CashSession;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class CashSessionResource extends Resource
{
    protected static ?string $model = CashSession::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCalculator;

    protected static string|\UnitEnum|null $navigationGroup = 'Billing';

    protected static ?int $navigationSort = 3;

    protected static ?string $navigationLabel = 'Cash sessions';

    public static function form(Schema $schema): Schema
    {
        return $schema->components([]);
    }

    public static function infolist(Schema $schema): Schema
    {
        return $schema->components([
            Section::make()->columns(3)->schema([
                TextEntry::make('branch.name')->label('Branch')->placeholder('—'),
                TextEntry::make('status')->badge()->color(fn ($state) => $state === 'open' ? 'success' : 'gray'),
                TextEntry::make('openedBy.name')->label('Opened by')->placeholder('—'),
                TextEntry::make('opened_at')->dateTime(),
                TextEntry::make('closed_at')->dateTime()->placeholder('—'),
                TextEntry::make('closedBy.name')->label('Closed by')->placeholder('—'),
            ]),
            Section::make('Reconciliation')->columns(2)->schema([
                TextEntry::make('opening_float_cents')->label('Opening float')->money('LKR', divideBy: 100),
                TextEntry::make('cash_collected')->label('Cash collected')
                    ->state(fn (CashSession $r) => $r->cashCollectedCents())->money('LKR', divideBy: 100),
                TextEntry::make('expected')->label('Expected in drawer')
                    ->state(fn (CashSession $r) => $r->expected_cash_cents ?? $r->expectedCashCents())
                    ->money('LKR', divideBy: 100)->weight('bold'),
                TextEntry::make('counted_cash_cents')->label('Counted')->money('LKR', divideBy: 100)->placeholder('not closed'),
                TextEntry::make('variance_cents')->label('Variance')->money('LKR', divideBy: 100)
                    ->placeholder('—')
                    ->color(fn ($state) => $state === null ? 'gray' : ((int) $state === 0 ? 'success' : 'danger')),
                TextEntry::make('notes')->placeholder('—')->columnSpanFull(),
            ]),
            Section::make('Collections by method')->schema([
                TextEntry::make('breakdown')->hiddenLabel()
                    ->state(function (CashSession $r) {
                        $b = $r->breakdownByMethod();
                        if (! $b) {
                            return 'No payments recorded in this session.';
                        }

                        return collect($b)
                            ->map(fn ($cents, $method) => ucfirst(str_replace('_', ' ', $method)).': '.Money::format($cents))
                            ->implode('   ·   ');
                    }),
            ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('opened_at', 'desc')
            ->columns([
                TextColumn::make('opened_at')->dateTime('d M Y, g:i A')->sortable(),
                TextColumn::make('branch.name')->label('Branch')->placeholder('—'),
                TextColumn::make('openedBy.name')->label('Opened by')->placeholder('—'),
                TextColumn::make('opening_float_cents')->label('Float')->money('LKR', divideBy: 100),
                TextColumn::make('status')->badge()->color(fn ($state) => $state === 'open' ? 'success' : 'gray'),
                TextColumn::make('expected_cash_cents')->label('Expected')->money('LKR', divideBy: 100)->placeholder('—'),
                TextColumn::make('counted_cash_cents')->label('Counted')->money('LKR', divideBy: 100)->placeholder('—'),
                TextColumn::make('variance_cents')->label('Variance')->money('LKR', divideBy: 100)->placeholder('—')
                    ->color(fn ($state) => $state === null ? 'gray' : ((int) $state === 0 ? 'success' : 'danger')),
            ])
            ->recordActions([
                \App\Filament\Actions\CloseCashSessionAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListCashSessions::route('/'),
            'view' => ViewCashSession::route('/{record}'),
        ];
    }

    public static function canCreate(): bool
    {
        return false;
    }
}
