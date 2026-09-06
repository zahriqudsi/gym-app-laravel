<?php

namespace App\Filament\Actions;

use App\Domain\Billing\BillingService;
use App\Domain\Members\MembershipStatusService;
use App\Domain\Support\Money;
use App\Models\Member;
use Filament\Actions\Action;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Support\Icons\Heroicon;

class RecordPaymentAction extends Action
{
    public const METHODS = [
        'cash' => 'Cash',
        'card' => 'Card',
        'lankaqr' => 'LankaQR',
        'bank_transfer' => 'Bank transfer',
        'wallet' => 'Mobile wallet',
        'cheque' => 'Cheque',
        'online' => 'Online',
    ];

    public static function getDefaultName(): ?string
    {
        return 'recordPayment';
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->label('Record payment')
            ->icon(Heroicon::OutlinedBanknotes)
            ->color('success')
            ->modalWidth('md')
            ->schema([
                TextInput::make('amount')
                    ->label('Amount received')
                    ->numeric()->prefix('Rs')->required()->minValue(1)
                    ->default(fn (Member $record) => app(BillingService::class)->memberDuesCents($record) / 100 ?: null)
                    ->helperText(fn (Member $record) => 'Outstanding: '.Money::format(app(BillingService::class)->memberDuesCents($record))),
                Select::make('method')->options(self::METHODS)->default('cash')->required(),
                TextInput::make('reference')->label('Reference / cheque no.')->maxLength(100),
                DateTimePicker::make('paid_at')->label('Received at')->default(now())->seconds(false),
            ])
            ->action(function (array $data, Member $record) {
                $billing = app(BillingService::class);

                $payment = $billing->recordPayment($record, [
                    'amount_cents' => Money::toCents($data['amount']),
                    'method' => $data['method'],
                    'reference' => $data['reference'] ?? null,
                    'paid_at' => $data['paid_at'] ?? now(),
                    'created_by' => auth()->id(),
                ]);

                app(MembershipStatusService::class)->sync($record);

                $remaining = $billing->memberDuesCents($record);

                Notification::make()
                    ->success()
                    ->title('Payment recorded — '.$payment->number)
                    ->body(Money::format($payment->amount_cents).' received. Outstanding now '.Money::format($remaining).'.')
                    ->send();
            });
    }
}
