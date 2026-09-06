<?php

namespace App\Filament\Actions;

use App\Domain\Support\Money;
use App\Models\CashSession;
use Filament\Actions\Action;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;
use Filament\Support\Icons\Heroicon;

class CloseCashSessionAction extends Action
{
    public static function getDefaultName(): ?string
    {
        return 'closeCashSession';
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->label('Close & reconcile')
            ->icon(Heroicon::OutlinedLockClosed)
            ->color('warning')
            ->visible(fn (CashSession $record) => $record->status === 'open')
            ->fillForm(fn (CashSession $record) => [
                'expected' => Money::format($record->expectedCashCents()),
            ])
            ->schema([
                Placeholder::make('opening')
                    ->label('Opening float')
                    ->content(fn (CashSession $record) => Money::format($record->opening_float_cents)),
                Placeholder::make('cash_in')
                    ->label('Cash collected this session')
                    ->content(fn (CashSession $record) => Money::format($record->cashCollectedCents())),
                Placeholder::make('expected')
                    ->label('Expected in drawer')
                    ->content(fn (CashSession $record) => Money::format($record->expectedCashCents())),
                TextInput::make('counted_cash')
                    ->label('Counted cash in drawer')
                    ->numeric()->prefix('Rs')->required()->minValue(0),
                Textarea::make('notes')->label('Notes (reason for any variance)')->rows(2),
            ])
            ->action(function (array $data, CashSession $record) {
                $expected = $record->expectedCashCents();
                $counted = Money::toCents($data['counted_cash']);

                $record->update([
                    'closed_by' => auth()->id(),
                    'closed_at' => now(),
                    'expected_cash_cents' => $expected,
                    'counted_cash_cents' => $counted,
                    'variance_cents' => $counted - $expected,
                    'notes' => $data['notes'] ?? null,
                    'status' => 'closed',
                ]);

                $variance = $counted - $expected;
                $note = $variance === 0
                    ? 'Balanced.'
                    : ($variance > 0 ? 'Over by ' : 'Short by ').Money::format(abs($variance)).'.';

                Notification::make()
                    ->title('Session closed — '.$note)
                    ->color($variance === 0 ? 'success' : 'warning')
                    ->send();
            });
    }
}
