<?php

namespace App\Filament\Resources\ReminderRules;

use App\Domain\Notifications\ReminderRunner;
use App\Filament\Resources\ReminderRules\Pages\CreateReminderRule;
use App\Filament\Resources\ReminderRules\Pages\EditReminderRule;
use App\Filament\Resources\ReminderRules\Pages\ListReminderRules;
use App\Models\ReminderRule;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class ReminderRuleResource extends Resource
{
    protected static ?string $model = ReminderRule::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBellAlert;

    protected static string|\UnitEnum|null $navigationGroup = 'Messaging';

    protected static ?int $navigationSort = 1;

    public const EVENTS = [
        'before_expiry' => 'Before expiry',
        'on_expiry' => 'On expiry day',
        'after_expiry' => 'After expiry (dunning)',
        'payment_due' => 'Payment due',
        'welcome' => 'Welcome',
        'birthday' => 'Birthday',
        'winback' => 'Win-back (lapsed)',
    ];

    public static function form(Schema $schema): Schema
    {
        return $schema->columns(2)->components([
            TextInput::make('name')->required(),
            Select::make('event')->options(self::EVENTS)->required()->live(),
            TextInput::make('offset_days')->numeric()->default(0)->required()
                ->helperText('Negative = days before (e.g. -7). Positive = days after.')
                ->visible(fn ($get) => in_array($get('event'), ['before_expiry', 'on_expiry', 'after_expiry', 'welcome', 'winback'])),
            Select::make('channel')->options(['sms' => 'SMS', 'whatsapp' => 'WhatsApp', 'email' => 'Email'])
                ->default('sms')->required(),
            TextInput::make('template_key')->helperText('Matches a Message Template key. Blank = use the event name / built-in default.'),
            Select::make('language')->options(['en' => 'English', 'si' => 'Sinhala', 'ta' => 'Tamil'])->default('en')->required(),
            Toggle::make('respect_quiet_hours')->default(true),
            Toggle::make('is_active')->default(true),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')->searchable()->sortable(),
                TextColumn::make('event')->badge()->formatStateUsing(fn ($state) => self::EVENTS[$state] ?? $state),
                TextColumn::make('offset_days')->label('Offset')->badge()
                    ->formatStateUsing(fn ($state) => $state == 0 ? 'same day' : ($state < 0 ? abs($state).'d before' : $state.'d after')),
                TextColumn::make('channel')->badge(),
                TextColumn::make('template_key')->placeholder('default'),
                IconColumn::make('is_active')->boolean()->label('Active'),
            ])
            ->recordActions([EditAction::make()])
            ->toolbarActions([BulkActionGroup::make([DeleteBulkAction::make()])])
            ->headerActions([
                Action::make('runNow')
                    ->label('Run reminders now')
                    ->icon(Heroicon::OutlinedPlay)
                    ->requiresConfirmation()
                    ->modalDescription('Evaluate all active rules for today and queue messages.')
                    ->action(function () {
                        $summary = app(ReminderRunner::class)->run();
                        Notification::make()->success()
                            ->title('Reminder run complete')
                            ->body("{$summary['rules']} rules · {$summary['queued']} queued · {$summary['skipped']} skipped")
                            ->send();
                    }),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListReminderRules::route('/'),
            'create' => CreateReminderRule::route('/create'),
            'edit' => EditReminderRule::route('/{record}/edit'),
        ];
    }
}
