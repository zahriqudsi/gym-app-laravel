<?php

namespace App\Filament\Resources\Plans;

use App\Filament\Resources\Plans\Pages\CreatePlan;
use App\Filament\Resources\Plans\Pages\EditPlan;
use App\Filament\Resources\Plans\Pages\ListPlans;
use App\Models\Plan;
use BackedEnum;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class PlanResource extends Resource
{
    protected static ?string $model = Plan::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedTag;

    protected static ?int $navigationSort = 2;

    /** Rupee <-> integer-cents helpers for money inputs. */
    private static function rupeeInput(string $field, string $label): TextInput
    {
        return TextInput::make($field)
            ->label($label)
            ->numeric()
            ->prefix('Rs')
            ->required()
            ->default(0)
            ->formatStateUsing(fn ($state) => $state === null ? 0 : $state / 100)
            ->dehydrateStateUsing(fn ($state) => (int) round(((float) $state) * 100));
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->columns(2)->components([
            TextInput::make('name')->required(),
            Select::make('category')->options([
                'gym' => 'Gym only',
                'gym_classes' => 'Gym + classes',
                'pt' => 'Personal training',
                'couple' => 'Couple',
                'student' => 'Student',
                'corporate' => 'Corporate',
            ])->required()->default('gym'),
            Select::make('billing_type')->options([
                'duration' => 'Duration (days)',
                'sessions' => 'Session pack',
            ])->required()->default('duration')->live(),
            TextInput::make('duration_days')->numeric()->suffix('days')
                ->visible(fn ($get) => $get('billing_type') === 'duration')
                ->required(fn ($get) => $get('billing_type') === 'duration'),
            TextInput::make('session_count')->numeric()->suffix('sessions')
                ->visible(fn ($get) => $get('billing_type') === 'sessions')
                ->required(fn ($get) => $get('billing_type') === 'sessions'),
            self::rupeeInput('price_cents', 'Price'),
            self::rupeeInput('signup_fee_cents', 'Registration fee'),
            TextInput::make('tax_rate')->numeric()->suffix('%')->default(0)->required(),
            TextInput::make('grace_days')->numeric()->suffix('days')->default(0)->required()
                ->helperText('Days after expiry that access is still allowed.'),
            TextInput::make('sort')->numeric()->default(0),
            Toggle::make('is_active')->default(true),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('sort')
            ->columns([
                TextColumn::make('name')->searchable()->sortable(),
                TextColumn::make('category')->badge(),
                TextColumn::make('billing_type')->badge()->label('Type'),
                TextColumn::make('term')->label('Term')->state(fn (Plan $r) => $r->billing_type === 'sessions'
                    ? "{$r->session_count} sessions"
                    : "{$r->duration_days} days"),
                TextColumn::make('price_cents')->label('Price')->money('LKR', divideBy: 100)->sortable(),
                TextColumn::make('signup_fee_cents')->label('Reg. fee')->money('LKR', divideBy: 100),
                TextColumn::make('tax_rate')->suffix('%'),
                IconColumn::make('is_active')->boolean()->label('Active'),
            ])
            ->recordActions([EditAction::make()])
            ->toolbarActions([
                BulkActionGroup::make([DeleteBulkAction::make()]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListPlans::route('/'),
            'create' => CreatePlan::route('/create'),
            'edit' => EditPlan::route('/{record}/edit'),
        ];
    }
}
