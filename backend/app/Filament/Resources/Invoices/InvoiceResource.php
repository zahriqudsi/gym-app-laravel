<?php

namespace App\Filament\Resources\Invoices;

use App\Domain\Billing\BillingService;
use App\Domain\Members\MembershipStatusService;
use App\Domain\Support\Money;
use App\Filament\Actions\RecordPaymentAction;
use App\Filament\Resources\Invoices\Pages\ListInvoices;
use App\Filament\Resources\Invoices\Pages\ViewInvoice;
use App\Models\Invoice;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Infolists\Components\RepeatableEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\Summarizers\Sum;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class InvoiceResource extends Resource
{
    protected static ?string $model = Invoice::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedDocumentText;

    protected static string|\UnitEnum|null $navigationGroup = 'Billing';

    protected static ?int $navigationSort = 2;

    public static function form(Schema $schema): Schema
    {
        return $schema->components([]); // invoices are created via the billing flow
    }

    public static function infolist(Schema $schema): Schema
    {
        return $schema->components([
            Section::make()->columns(3)->schema([
                TextEntry::make('number'),
                TextEntry::make('member.name')->label('Member')->placeholder('—'),
                TextEntry::make('status')->badge(),
                TextEntry::make('issued_on')->date(),
                TextEntry::make('due_on')->date()->placeholder('—'),
                TextEntry::make('creator.name')->label('Created by')->placeholder('—'),
            ]),
            Section::make('Lines')->schema([
                RepeatableEntry::make('items')->hiddenLabel()->schema([
                    TextEntry::make('description')->columnSpan(3),
                    TextEntry::make('qty'),
                    TextEntry::make('unit_price_cents')->label('Unit')->money('LKR', divideBy: 100),
                    TextEntry::make('line_total_cents')->label('Total')->money('LKR', divideBy: 100),
                ])->columns(6),
            ]),
            Section::make()->columns(4)->schema([
                TextEntry::make('subtotal_cents')->label('Subtotal')->money('LKR', divideBy: 100),
                TextEntry::make('tax_cents')->label('Tax')->money('LKR', divideBy: 100),
                TextEntry::make('total_cents')->label('Total')->money('LKR', divideBy: 100)->weight('bold'),
                TextEntry::make('amount_paid_cents')->label('Paid')->money('LKR', divideBy: 100),
            ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('issued_on', 'desc')
            ->columns([
                TextColumn::make('number')->searchable()->sortable(),
                TextColumn::make('member.name')->searchable(['members.first_name', 'members.last_name'])->placeholder('—'),
                TextColumn::make('issued_on')->date()->sortable(),
                TextColumn::make('total_cents')->label('Total')->money('LKR', divideBy: 100)->sortable(),
                TextColumn::make('amount_paid_cents')->label('Paid')->money('LKR', divideBy: 100),
                TextColumn::make('balance')->label('Balance')
                    ->state(fn (Invoice $r) => $r->balanceCents())
                    ->money('LKR', divideBy: 100)
                    ->color(fn ($state) => $state > 0 ? 'danger' : 'gray'),
                TextColumn::make('age')->label('Age')
                    ->state(fn (Invoice $r) => $r->balanceCents() > 0 ? $r->issued_on->diffInDays(today()).'d' : '—')
                    ->badge()
                    ->color(fn (Invoice $r) => match (true) {
                        $r->balanceCents() <= 0 => 'gray',
                        $r->issued_on->diffInDays(today()) > 60 => 'danger',
                        $r->issued_on->diffInDays(today()) > 30 => 'warning',
                        default => 'info',
                    }),
                TextColumn::make('status')->badge()->color(fn ($state) => match ($state) {
                    'paid' => 'success', 'part_paid' => 'warning', 'unpaid' => 'danger', default => 'gray',
                }),
            ])
            ->filters([
                SelectFilter::make('status')->options([
                    'unpaid' => 'Unpaid', 'part_paid' => 'Part paid', 'paid' => 'Paid', 'void' => 'Void',
                ]),
                Filter::make('outstanding')->label('Outstanding only')->default()
                    ->query(fn (Builder $q) => $q->whereColumn('amount_paid_cents', '<', 'total_cents')->where('status', '!=', 'void')),
            ])
            ->recordActions([
                Action::make('pdf')->label('PDF')->icon(Heroicon::OutlinedDocumentArrowDown)
                    ->url(fn (Invoice $r) => route('documents.invoice', $r), true),
                Action::make('pay')->label('Record payment')->icon(Heroicon::OutlinedBanknotes)->color('success')
                    ->visible(fn (Invoice $r) => $r->balanceCents() > 0 && $r->member_id)
                    ->schema([
                        TextInput::make('amount')->numeric()->prefix('Rs')->required()
                            ->default(fn (Invoice $r) => $r->balanceCents() / 100)
                            ->helperText(fn (Invoice $r) => 'Balance: '.Money::format($r->balanceCents())),
                        Select::make('method')->options(RecordPaymentAction::METHODS)->default('cash')->required(),
                        TextInput::make('reference')->maxLength(100),
                    ])
                    ->action(function (array $data, Invoice $record) {
                        $billing = app(BillingService::class);
                        $payment = $billing->recordPayment($record->member, [
                            'amount_cents' => Money::toCents($data['amount']),
                            'method' => $data['method'],
                            'reference' => $data['reference'] ?? null,
                            'invoice_id' => $record->id,
                            'created_by' => auth()->id(),
                        ]);
                        app(MembershipStatusService::class)->sync($record->member);
                        Notification::make()->success()
                            ->title('Payment recorded — '.$payment->number)
                            ->body(Money::format($payment->amount_cents).' applied to '.$record->number)
                            ->send();
                    }),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListInvoices::route('/'),
            'view' => ViewInvoice::route('/{record}'),
        ];
    }

    public static function canCreate(): bool
    {
        return false;
    }
}
