<?php

namespace App\Filament\Imports;

use App\Domain\Support\DocumentNumber;
use App\Models\Branch;
use App\Models\Member;
use Filament\Actions\Imports\ImportColumn;
use Filament\Actions\Imports\Importer;
use Filament\Actions\Imports\Models\Import;
use Illuminate\Support\Number;

class MemberImporter extends Importer
{
    protected static ?string $model = Member::class;

    public static function getColumns(): array
    {
        return [
            ImportColumn::make('first_name')->requiredMapping()->rules(['required', 'string', 'max:255']),
            ImportColumn::make('last_name')->rules(['nullable', 'string', 'max:255']),
            ImportColumn::make('phone')->rules(['nullable', 'string', 'max:30']),
            ImportColumn::make('nic')->label('NIC')->rules(['nullable', 'string', 'max:20']),
            ImportColumn::make('email')->rules(['nullable', 'email']),
            ImportColumn::make('gender')->rules(['nullable', 'in:male,female,other']),
            ImportColumn::make('dob')->label('Date of birth')->rules(['nullable', 'date']),
            ImportColumn::make('joined_on')->rules(['nullable', 'date']),
            ImportColumn::make('status')
                ->rules(['nullable', 'in:enquiry,trial,active,due,frozen,expired,cancelled'])
                ->fillRecordUsing(fn (Member $record, ?string $state) => $record->status = $state ?: 'active'),
            ImportColumn::make('current_expiry_on')->label('Current expiry')->rules(['nullable', 'date']),
        ];
    }

    public function resolveRecord(): Member
    {
        // Match on phone or NIC to allow re-running an import without duplicates.
        $member = Member::query()
            ->when($this->data['phone'] ?? null, fn ($q, $p) => $q->orWhere('phone', $p))
            ->when($this->data['nic'] ?? null, fn ($q, $n) => $q->orWhere('nic', $n))
            ->first();

        if ($member) {
            return $member;
        }

        return new Member([
            'member_no' => DocumentNumber::member(),
            'branch_id' => Branch::current()?->id,
            'status' => 'active',
        ]);
    }

    public static function getCompletedNotificationBody(Import $import): string
    {
        $body = 'Member import complete: '.Number::format($import->successful_rows).' '
            .str('row')->plural($import->successful_rows).' imported.';

        if ($failed = $import->getFailedRowsCount()) {
            $body .= ' '.Number::format($failed).' '.str('row')->plural($failed).' failed.';
        }

        return $body;
    }
}
