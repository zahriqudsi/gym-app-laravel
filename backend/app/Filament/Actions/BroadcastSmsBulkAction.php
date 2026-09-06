<?php

namespace App\Filament\Actions;

use App\Domain\Notifications\TemplateRenderer;
use App\Jobs\SendAdhocSms;
use App\Models\MessageTemplate;
use Filament\Actions\BulkAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;
use Filament\Support\Icons\Heroicon;
use Illuminate\Database\Eloquent\Collection;

class BroadcastSmsBulkAction extends BulkAction
{
    public static function getDefaultName(): ?string
    {
        return 'broadcastSms';
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->label('Send SMS')
            ->icon(Heroicon::OutlinedChatBubbleLeftRight)
            ->color('primary')
            ->deselectRecordsAfterCompletion()
            ->schema([
                Select::make('template_id')
                    ->label('Start from a template (optional)')
                    ->options(fn () => MessageTemplate::where('channel', 'sms')->where('is_active', true)
                        ->get()->mapWithKeys(fn ($t) => [$t->id => "{$t->name} ({$t->language})"]))
                    ->live()
                    ->afterStateUpdated(function ($state, callable $set) {
                        if ($state && $t = MessageTemplate::find($state)) {
                            $set('body', $t->body);
                        }
                    }),
                Textarea::make('body')
                    ->required()->rows(4)
                    ->helperText('Merge fields: {name} {member_no} {gym} {expiry_date} {amount_due}'),
            ])
            ->action(function (array $data, Collection $records) {
                $renderer = app(TemplateRenderer::class);
                $sent = 0;
                $skipped = 0;

                foreach ($records as $member) {
                    if (blank($member->phone)) {
                        $skipped++;

                        continue;
                    }

                    $body = $renderer->render($data['body'], $renderer->memberVars($member, [
                        'amount_due' => $renderer->duesVar(app(\App\Domain\Billing\BillingService::class)->memberDuesCents($member)),
                    ]));

                    SendAdhocSms::dispatch($member->id, (string) $member->phone, $body, 'campaign');
                    $sent++;
                }

                Notification::make()->success()
                    ->title('Broadcast queued')
                    ->body("{$sent} messages queued".($skipped ? ", {$skipped} skipped (no phone)" : ''))
                    ->send();
            });
    }
}
