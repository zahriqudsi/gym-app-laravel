<?php

namespace App\Filament\Resources\Payments;

use App\Filament\Actions\RecordPaymentAction;
use App\Filament\Resources\Payments\Pages\ListPayments;
use App\Filament\Resources\Payments\Pages\ViewPayment;
use App\Models\Payment;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Infolists\Components\RepeatableEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class PaymentResource extends Resource
{
    protected static ?string $model = Payment::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBanknotes;

    protected static string|\UnitEnum|null $navigationGroup = 'Billing';

    protected static ?int $navigationSort = 1;

    public static function form(Schema $schema): Schema
    {
        return $schema->components([]); // payments are created via the billing flow
    }

    public static function infolist(Schema $schema): Schema
    {
        return $schema->components([
            Section::make()->columns(3)->schema([
                TextEntry::make('number')->label('Receipt'),
                TextEntry::make('member.name')->label('Member')->placeholder('Walk-in'),
                TextEntry::make('amount_cents')->label('Amount')->money('LKR', divideBy: 100)->weight('bold'),
                TextEntry::make('method')->badge(),
                TextEntry::make('paid_at')->dateTime(),
                TextEntry::make('reference')->placeholder('—'),
                TextEntry::make('gateway')->placeholder('—'),
                TextEntry::make('status')->badge(),
                TextEntry::make('creator.name')->label('Taken by')->placeholder('—'),
            ]),
            Section::make('Applied to invoices')->schema([
                RepeatableEntry::make('allocations')->hiddenLabel()->schema([
                    TextEntry::make('invoice.number')->label('Invoice')->columnSpan(2),
                    TextEntry::make('amount_cents')->label('Amount')->money('LKR', divideBy: 100),
                ])->columns(3),
            ])->visible(fn ($record) => $record->allocations->isNotEmpty()),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('paid_at', 'desc')
            ->columns([
                TextColumn::make('number')->label('Receipt')->searchable()->sortable(),
                TextColumn::make('member.name')->searchable(['members.first_name', 'members.last_name'])->placeholder('Walk-in'),
                TextColumn::make('paid_at')->dateTime('d M Y, g:i A')->sortable(),
                TextColumn::make('method')->badge(),
                TextColumn::make('amount_cents')->label('Amount')->money('LKR', divideBy: 100)->sortable(),
                TextColumn::make('reference')->placeholder('—')->toggleable(),
                TextColumn::make('status')->badge()->color(fn ($state) => match ($state) {
                    'completed' => 'success', 'refunded' => 'warning', 'failed' => 'danger', default => 'gray',
                }),
                TextColumn::make('creator.name')->label('By')->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('method')->options(RecordPaymentAction::METHODS),
                SelectFilter::make('status')->options([
                    'completed' => 'Completed', 'pending' => 'Pending', 'failed' => 'Failed', 'refunded' => 'Refunded',
                ]),
                Filter::make('today')->label('Today only')
                    ->query(fn (Builder $q) => $q->whereDate('paid_at', today())),
            ])
            ->recordActions([
                Action::make('receipt')->label('Receipt')->icon(Heroicon::OutlinedDocumentArrowDown)
                    ->url(fn (Payment $r) => route('documents.receipt', $r), true),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListPayments::route('/'),
            'view' => ViewPayment::route('/{record}'),
        ];
    }

    public static function canCreate(): bool
    {
        return false;
    }
}
