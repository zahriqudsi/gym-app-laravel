@php
    $member = $this->member;
    $decision = $this->decision;
    $open = $this->openAttendance;
    $bannerColor = match ($decision?->level) {
        'ok' => ['bg' => '#dcfce7', 'fg' => '#166534'],
        'warn' => ['bg' => '#fef9c3', 'fg' => '#854d0e'],
        'block' => ['bg' => '#fee2e2', 'fg' => '#991b1b'],
        default => ['bg' => '#f3f4f6', 'fg' => '#374151'],
    };
@endphp

<x-filament-panels::page>
    <div style="display:grid;grid-template-columns:1fr;gap:1.5rem;max-width:960px;">

        <x-filament::section>
            <x-slot name="heading">Find a member</x-slot>

            <x-filament::input.wrapper>
                <x-filament::input
                    type="text"
                    wire:model.live.debounce.400ms="q"
                    placeholder="Scan card / type member no, phone, or name"
                    autofocus
                />
            </x-filament::input.wrapper>

            @if ($member)
                <div style="margin-top:1rem;display:flex;gap:1rem;align-items:center;">
                    <div style="width:56px;height:56px;border-radius:9999px;background:#e5e7eb;display:flex;align-items:center;justify-content:center;font-weight:700;font-size:1.1rem;color:#6b7280;">
                        {{ strtoupper(substr($member->first_name, 0, 1).substr($member->last_name ?? '', 0, 1)) }}
                    </div>
                    <div style="flex:1;">
                        <div style="font-size:1.15rem;font-weight:600;">{{ $member->name }}</div>
                        <div style="opacity:.6;font-size:.85rem;">
                            {{ $member->member_no }} · {{ $member->phone }}
                            @if ($member->current_expiry_on) · expires {{ $member->current_expiry_on->format('d M Y') }} @endif
                        </div>
                    </div>
                </div>

                <div style="margin-top:1rem;border-radius:.75rem;padding:1rem 1.25rem;background:{{ $bannerColor['bg'] }};color:{{ $bannerColor['fg'] }};">
                    <div style="font-weight:700;text-transform:uppercase;letter-spacing:.03em;font-size:.8rem;">
                        {{ $decision->granted ? ($decision->level === 'warn' ? 'Allow — check' : 'Allowed') : 'Denied' }}
                    </div>
                    <div style="margin-top:.15rem;">{{ $decision->message }}</div>
                </div>

                <div style="margin-top:1rem;display:flex;gap:.75rem;">
                    @if ($open)
                        <x-filament::button color="gray" wire:click="checkOut({{ $open->id }})">
                            Check out ({{ $open->checked_in_at->diffForHumans(null, true) }})
                        </x-filament::button>
                    @else
                        <x-filament::button
                            size="lg"
                            :color="$decision->granted ? ($decision->level === 'warn' ? 'warning' : 'success') : 'danger'"
                            wire:click="checkIn"
                        >
                            {{ $decision->granted ? 'Check in' : 'Override & check in' }}
                        </x-filament::button>
                    @endif
                    <x-filament::button color="gray" outlined wire:click="$set('q', '')">Clear</x-filament::button>
                </div>
            @elseif (strlen($q) >= 2)
                <p style="margin-top:1rem;opacity:.6;">No member matches “{{ $q }}”.</p>
            @endif
        </x-filament::section>

        <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(280px,1fr));gap:1.5rem;">
            <x-filament::section>
                <x-slot name="heading">In the gym now ({{ $this->inside->count() }})</x-slot>
                @forelse ($this->inside as $a)
                    <div style="display:flex;justify-content:space-between;align-items:center;padding:.4rem 0;border-bottom:1px solid rgba(128,128,128,.15);">
                        <span>{{ $a->member?->name }} <span style="opacity:.5;font-size:.8rem;">{{ $a->checked_in_at->format('g:i A') }}</span></span>
                        <x-filament::link tag="button" wire:click="checkOut({{ $a->id }})">out</x-filament::link>
                    </div>
                @empty
                    <p style="opacity:.6;">Nobody checked in right now.</p>
                @endforelse
            </x-filament::section>

            <x-filament::section>
                <x-slot name="heading">Recent check-ins today</x-slot>
                @forelse ($this->recent as $a)
                    <div style="display:flex;justify-content:space-between;padding:.4rem 0;border-bottom:1px solid rgba(128,128,128,.15);">
                        <span>{{ $a->member?->name }}</span>
                        <span style="opacity:.5;font-size:.8rem;">
                            {{ $a->checked_in_at->format('g:i A') }}
                            @if (! $a->access_granted) · <span style="color:#dc2626;">denied</span> @endif
                        </span>
                    </div>
                @empty
                    <p style="opacity:.6;">No check-ins yet today.</p>
                @endforelse
            </x-filament::section>
        </div>
    </div>
</x-filament-panels::page>
