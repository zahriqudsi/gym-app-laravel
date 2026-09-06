<?php

namespace App\Filament\Resources\Members;

use App\Domain\Support\DocumentNumber;
use App\Filament\Actions\BroadcastSmsBulkAction;
use App\Filament\Actions\RecordPaymentAction;
use App\Filament\Actions\SellMembershipAction;
use App\Filament\Resources\Members\Pages\CreateMember;
use App\Filament\Resources\Members\Pages\EditMember;
use App\Filament\Resources\Members\Pages\ListMembers;
use App\Filament\Resources\Members\RelationManagers\MembershipsRelationManager;
use App\Models\Member;
use BackedEnum;
use Filament\Actions\ActionGroup;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ForceDeleteBulkAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class MemberResource extends Resource
{
    protected static ?string $model = Member::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedUsers;

    protected static ?string $recordTitleAttribute = 'member_no';

    protected static ?int $navigationSort = 1;

    public const STATUSES = [
        'enquiry' => 'Enquiry',
        'trial' => 'Trial',
        'active' => 'Active',
        'due' => 'Due',
        'frozen' => 'Frozen',
        'expired' => 'Expired',
        'cancelled' => 'Cancelled',
    ];

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Identity')->columns(2)->schema([
                TextInput::make('member_no')->required()->maxLength(30)
                    ->default(fn () => DocumentNumber::member())
                    ->helperText('Auto-filled with the next number — change it if your gym uses its own scheme.'),
                Select::make('status')->options(self::STATUSES)->required()->default('enquiry'),
                TextInput::make('first_name')->required(),
                TextInput::make('last_name'),
                TextInput::make('nic')->label('NIC')->maxLength(20),
                Select::make('gender')->options(['male' => 'Male', 'female' => 'Female', 'other' => 'Other']),
                DatePicker::make('dob')->label('Date of birth')->maxDate(now()),
                DatePicker::make('joined_on')->default(now()),
            ]),
            Section::make('Contact')->columns(2)->schema([
                TextInput::make('phone')->tel(),
                TextInput::make('email')->email(),
                Textarea::make('address')->columnSpanFull(),
                TextInput::make('emergency_name'),
                TextInput::make('emergency_phone')->tel(),
            ]),
            Section::make('Membership & health')->columns(2)->schema([
                Select::make('branch_id')->relationship('branch', 'name')->label('Home branch'),
                Select::make('assigned_trainer_id')->relationship('assignedTrainer', 'name')->label('Assigned trainer')->searchable(),
                TextInput::make('referral_source'),
                DatePicker::make('current_expiry_on')->label('Current expiry')->disabled()
                    ->helperText('Set automatically from the active membership.'),
                Textarea::make('health_notes')->columnSpanFull(),
                Textarea::make('goals')->columnSpanFull(),
                Textarea::make('notes')->columnSpanFull(),
            ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                TextColumn::make('member_no')->label('No.')->searchable()->sortable(),
                TextColumn::make('name')->searchable(['first_name', 'last_name'])->sortable('first_name'),
                TextColumn::make('phone')->searchable()->icon(Heroicon::OutlinedPhone),
                TextColumn::make('status')->badge()->searchable()->color(fn (string $state) => match ($state) {
                    'active' => 'success',
                    'due' => 'warning',
                    'frozen' => 'info',
                    'expired', 'cancelled' => 'danger',
                    default => 'gray',
                }),
                TextColumn::make('current_expiry_on')->label('Expires')->date()->sortable()
                    ->color(fn ($state) => $state && $state->isPast() ? 'danger' : null),
                TextColumn::make('branch.name')->label('Branch')->toggleable(),
                TextColumn::make('joined_on')->date()->sortable()->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('status')->options(self::STATUSES),
                SelectFilter::make('branch_id')->relationship('branch', 'name')->label('Branch'),
                TrashedFilter::make(),
            ])
            ->recordActions([
                ActionGroup::make([
                    EditAction::make(),
                    SellMembershipAction::make(),
                    RecordPaymentAction::make(),
                ]),
            ])
            ->toolbarActions([
                BroadcastSmsBulkAction::make(),
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                    ForceDeleteBulkAction::make(),
                    RestoreBulkAction::make(),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            MembershipsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListMembers::route('/'),
            'create' => CreateMember::route('/create'),
            'edit' => EditMember::route('/{record}/edit'),
        ];
    }

    public static function getRecordRouteBindingEloquentQuery(): Builder
    {
        return parent::getRecordRouteBindingEloquentQuery()
            ->withoutGlobalScopes([SoftDeletingScope::class]);
    }
}
