<?php

namespace App\Filament\Actions;

use App\Domain\Support\Money;
use App\Models\Branch;
use App\Models\CashSession;
use Filament\Actions\Action;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Support\Icons\Heroicon;

class OpenCashSessionAction extends Action
{
    public static function getDefaultName(): ?string
    {
        return 'openCashSession';
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->label('Open session')
            ->icon(Heroicon::OutlinedLockOpen)
            ->color('success')
            ->visible(fn () => CashSession::currentOpen(auth()->user()?->default_branch_id) === null)
            ->schema([
                Select::make('branch_id')
                    ->label('Branch')
                    ->options(fn () => Branch::where('is_active', true)->pluck('name', 'id'))
                    ->default(fn () => auth()->user()?->default_branch_id ?? Branch::current()?->id)
                    ->required(),
                TextInput::make('opening_float')
                    ->label('Opening float in drawer')
                    ->numeric()->prefix('Rs')->default(0)->required()->minValue(0),
            ])
            ->action(function (array $data) {
                if (CashSession::currentOpen($data['branch_id'])) {
                    Notification::make()->warning()->title('A session is already open for this branch.')->send();

                    return;
                }

                $session = CashSession::create([
                    'branch_id' => $data['branch_id'],
                    'opened_by' => auth()->id(),
                    'opened_at' => now(),
                    'opening_float_cents' => Money::toCents($data['opening_float']),
                    'status' => 'open',
                ]);

                Notification::make()->success()
                    ->title('Cash session opened')
                    ->body('Float '.Money::format($session->opening_float_cents))
                    ->send();
            });
    }
}
