<?php

namespace App\Filament\Actions;

use App\Domain\Billing\BillingService;
use App\Domain\Members\MembershipStatusService;
use App\Domain\Support\Money;
use App\Models\Member;
use App\Models\Plan;
use Filament\Actions\Action;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Schemas\Components\Section;
use Filament\Support\Icons\Heroicon;

class SellMembershipAction extends Action
{
    public static function getDefaultName(): ?string
    {
        return 'sellMembership';
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->label('Sell / Renew')
            ->icon(Heroicon::OutlinedTicket)
            ->color('primary')
            ->modalWidth('lg')
            ->modalSubmitActionLabel('Sell membership')
            ->schema([
                Select::make('plan_id')
                    ->label('Plan')
                    ->options(fn () => Plan::where('is_active', true)->orderBy('sort')->pluck('name', 'id'))
                    ->required()
                    ->searchable(),
                DatePicker::make('starts_on')
                    ->label('Start date')
                    ->default(now())
                    ->helperText('If the member still has time left, the new term is queued to start after it.'),
                TextInput::make('discount')
                    ->label('Discount')
                    ->numeric()->prefix('Rs')->default(0)->minValue(0),
                Toggle::make('apply_signup_fee')
                    ->label('Charge registration fee')
                    ->default(fn (Member $record) => $record->memberships()->count() === 0),
                Section::make('Payment')->schema([
                    Toggle::make('collect_payment')->label('Collect payment now')->default(true)->live(),
                    Select::make('payment_method')
                        ->options(RecordPaymentAction::METHODS)
                        ->default('cash')
                        ->required(fn ($get) => $get('collect_payment'))
                        ->visible(fn ($get) => $get('collect_payment')),
                    TextInput::make('payment_amount')
                        ->label('Amount received')
                        ->numeric()->prefix('Rs')
                        ->helperText('Leave blank to collect the full invoice amount.')
                        ->visible(fn ($get) => $get('collect_payment')),
                ]),
            ])
            ->action(function (array $data, Member $record) {
                $billing = app(BillingService::class);
                $plan = Plan::findOrFail($data['plan_id']);

                $membership = $billing->sellMembership($record, $plan, [
                    'starts_on' => $data['starts_on'] ?? now(),
                    'discount_cents' => Money::toCents($data['discount'] ?? 0),
                    'apply_signup_fee' => (bool) ($data['apply_signup_fee'] ?? false),
                    'created_by' => auth()->id(),
                ]);

                $invoice = $membership->invoice;
                $receipt = null;

                if ($data['collect_payment'] ?? false) {
                    $amount = filled($data['payment_amount'] ?? null)
                        ? Money::toCents($data['payment_amount'])
                        : $invoice->total_cents;

                    $receipt = $billing->recordPayment($record, [
                        'amount_cents' => $amount,
                        'method' => $data['payment_method'],
                        'created_by' => auth()->id(),
                    ]);
                }

                app(MembershipStatusService::class)->sync($record);

                $body = "Invoice {$invoice->number} — ".Money::format($invoice->total_cents);
                $body .= $receipt
                    ? " — Receipt {$receipt->number}"
                    : ' — unpaid';

                Notification::make()
                    ->success()
                    ->title('Membership sold')
                    ->body($body)
                    ->send();
            });
    }
}
