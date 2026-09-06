<?php

namespace App\Filament\Pages;

use App\Models\Setting;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;

/**
 * @property-read Schema $form
 */
class Settings extends Page
{
    protected string $view = 'filament.pages.settings';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCog6Tooth;

    protected static ?int $navigationSort = 99;

    protected static string|\UnitEnum|null $navigationGroup = 'Settings';

    public ?array $data = [];

    /** config('gym.*') keys this page manages. */
    protected const KEYS = [
        'gym.name', 'gym.currency_symbol',
        'gym.tax.enabled', 'gym.tax.label', 'gym.tax.rate', 'gym.tax.inclusive',
        'gym.access.on_expired', 'gym.access.on_dues', 'gym.access.on_frozen',
        'gym.reminders.quiet_hours.start', 'gym.reminders.quiet_hours.end',
        'gym.sms.driver', 'gym.sms.sender_id',
    ];

    public function mount(): void
    {
        $this->form->fill(collect(self::KEYS)->mapWithKeys(
            fn ($k) => [str_replace('.', '__', $k) => config($k)]
        )->all());
    }

    public function form(Schema $schema): Schema
    {
        $policy = ['hard_block' => 'Block entry', 'allow_warn' => 'Allow, warn front desk'];

        return $schema->statePath('data')->components([
            Section::make('Gym')->columns(2)->schema([
                TextInput::make('gym__name')->label('Gym name')->required(),
                TextInput::make('gym__currency_symbol')->label('Currency symbol')->required(),
            ]),
            Section::make('Tax')->columns(2)->schema([
                Toggle::make('gym__tax__enabled')->label('Charge tax'),
                Toggle::make('gym__tax__inclusive')->label('Prices include tax'),
                TextInput::make('gym__tax__label')->label('Tax label')->default('VAT'),
                TextInput::make('gym__tax__rate')->label('Rate %')->numeric()->default(0),
            ]),
            Section::make('Access control at check-in')->columns(3)->schema([
                Select::make('gym__access__on_expired')->label('When expired')->options($policy),
                Select::make('gym__access__on_dues')->label('When member owes')->options($policy),
                Select::make('gym__access__on_frozen')->label('When frozen')->options($policy),
            ]),
            Section::make('Reminders & SMS')->columns(2)->schema([
                TextInput::make('gym__reminders__quiet_hours__start')->label('Quiet hours start')->placeholder('21:00'),
                TextInput::make('gym__reminders__quiet_hours__end')->label('Quiet hours end')->placeholder('08:00'),
                Select::make('gym__sms__driver')->label('SMS driver')
                    ->options(['log' => 'Log only (no send)', 'notifylk' => 'notify.lk']),
                TextInput::make('gym__sms__sender_id')->label('SMS sender ID / mask'),
            ]),
        ]);
    }

    protected function getFormActions(): array
    {
        return [
            Action::make('save')->label('Save settings')->submit('save'),
        ];
    }

    public function save(): void
    {
        $state = $this->form->getState();

        $pairs = [];
        foreach (self::KEYS as $key) {
            $formKey = str_replace('.', '__', $key);
            if (array_key_exists($formKey, $state)) {
                $pairs[$key] = $this->cast($key, $state[$formKey]);
            }
        }

        Setting::putMany($pairs);
        config($pairs); // apply immediately

        Notification::make()->success()->title('Settings saved')->send();
    }

    private function cast(string $key, mixed $value): mixed
    {
        return match ($key) {
            'gym.tax.enabled', 'gym.tax.inclusive' => (bool) $value,
            'gym.tax.rate' => (float) $value,
            default => $value,
        };
    }
}
