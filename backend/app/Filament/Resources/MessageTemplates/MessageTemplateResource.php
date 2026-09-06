<?php

namespace App\Filament\Resources\MessageTemplates;

use App\Domain\Notifications\TemplateRenderer;
use App\Filament\Resources\MessageTemplates\Pages\CreateMessageTemplate;
use App\Filament\Resources\MessageTemplates\Pages\EditMessageTemplate;
use App\Filament\Resources\MessageTemplates\Pages\ListMessageTemplates;
use App\Models\MessageTemplate;
use App\Support\Sms\SmsGateway;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class MessageTemplateResource extends Resource
{
    protected static ?string $model = MessageTemplate::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedChatBubbleLeftRight;

    protected static string|\UnitEnum|null $navigationGroup = 'Messaging';

    protected static ?int $navigationSort = 2;

    public const MERGE_FIELDS = '{name} {full_name} {member_no} {gym} {branch} {expiry_date} {amount_due} {phone}';

    public static function form(Schema $schema): Schema
    {
        return $schema->columns(2)->components([
            TextInput::make('key')->required()
                ->helperText('e.g. before_expiry, on_expiry, payment_due, welcome, birthday, winback'),
            TextInput::make('name')->required(),
            Select::make('channel')->options(['sms' => 'SMS', 'whatsapp' => 'WhatsApp', 'email' => 'Email'])
                ->default('sms')->required(),
            Select::make('language')->options(['en' => 'English', 'si' => 'Sinhala', 'ta' => 'Tamil'])
                ->default('en')->required(),
            TextInput::make('subject')->visible(fn ($get) => $get('channel') === 'email')->columnSpanFull(),
            Textarea::make('body')->required()->rows(4)->columnSpanFull()
                ->helperText('Merge fields: '.self::MERGE_FIELDS),
            Toggle::make('is_active')->default(true),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('key')->searchable()->sortable(),
                TextColumn::make('name')->searchable(),
                TextColumn::make('channel')->badge(),
                TextColumn::make('language')->badge(),
                TextColumn::make('body')->limit(60)->wrap(),
                IconColumn::make('is_active')->boolean()->label('Active'),
            ])
            ->recordActions([
                EditAction::make(),
                Action::make('sendTest')
                    ->label('Send test')
                    ->icon(Heroicon::OutlinedPaperAirplane)
                    ->schema([
                        TextInput::make('to')->label('Test phone number')->tel()->required(),
                    ])
                    ->action(function (array $data, MessageTemplate $record) {
                        $renderer = app(TemplateRenderer::class);
                        $body = $renderer->render($record->body, [
                            'name' => 'Test', 'full_name' => 'Test Member', 'member_no' => 'M00000',
                            'gym' => config('gym.name'), 'branch' => config('gym.name'),
                            'expiry_date' => today()->addDays(7)->toDateString(),
                            'amount_due' => '1,500.00', 'phone' => $data['to'],
                        ]);

                        $result = app(SmsGateway::class)->send($data['to'], $body);

                        $result->ok
                            ? Notification::make()->success()->title('Test sent')->body($body)->send()
                            : Notification::make()->danger()->title('Send failed')->body($result->error)->send();
                    }),
            ])
            ->toolbarActions([BulkActionGroup::make([DeleteBulkAction::make()])]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListMessageTemplates::route('/'),
            'create' => CreateMessageTemplate::route('/create'),
            'edit' => EditMessageTemplate::route('/{record}/edit'),
        ];
    }
}
