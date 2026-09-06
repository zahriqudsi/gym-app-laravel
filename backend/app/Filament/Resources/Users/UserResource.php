<?php

namespace App\Filament\Resources\Users;

use App\Filament\Resources\Users\Pages\CreateUser;
use App\Filament\Resources\Users\Pages\EditUser;
use App\Filament\Resources\Users\Pages\ListUsers;
use App\Models\User;
use BackedEnum;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class UserResource extends Resource
{
    protected static ?string $model = User::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedUserGroup;

    protected static string|\UnitEnum|null $navigationGroup = 'Settings';

    protected static ?int $navigationSort = 90;

    protected static ?string $navigationLabel = 'Staff';

    public const ROLES = [
        'owner' => 'Owner',
        'manager' => 'Manager',
        'receptionist' => 'Receptionist',
        'trainer' => 'Trainer',
        'accountant' => 'Accountant',
    ];

    /** Only owners and managers manage staff. */
    public static function canAccess(): bool
    {
        return in_array(auth()->user()?->staff_role, ['owner', 'manager'], true);
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make()->columns(2)->schema([
                TextInput::make('name')->required(),
                TextInput::make('email')->email()->required()->unique(ignoreRecord: true),
                TextInput::make('phone')->tel(),
                Select::make('staff_role')->label('Role')->options(self::ROLES)->required(),
                Select::make('default_branch_id')->label('Default branch')->relationship('defaultBranch', 'name'),
                Toggle::make('is_active')->default(true),
                TextInput::make('password')
                    ->password()->revealable()
                    ->required(fn (string $operation) => $operation === 'create')
                    ->dehydrated(fn ($state) => filled($state)) // model cast 'hashed' does the hashing
                    ->helperText('Leave blank to keep the current password.'),
            ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')->searchable()->sortable(),
                TextColumn::make('email')->searchable()->icon(Heroicon::OutlinedEnvelope),
                TextColumn::make('staff_role')->badge()->label('Role'),
                TextColumn::make('defaultBranch.name')->label('Branch')->placeholder('—'),
                IconColumn::make('is_active')->boolean()->label('Active'),
                TextColumn::make('last_login_at')->dateTime()->placeholder('never')->toggleable(),
            ])
            ->recordActions([EditAction::make()])
            ->toolbarActions([BulkActionGroup::make([DeleteBulkAction::make()])]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListUsers::route('/'),
            'create' => CreateUser::route('/create'),
            'edit' => EditUser::route('/{record}/edit'),
        ];
    }
}
