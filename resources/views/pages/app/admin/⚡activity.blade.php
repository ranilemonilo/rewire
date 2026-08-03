<?php

use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;
use Spatie\Activitylog\Models\Activity;

new #[Title('Activity log')] class extends Component
{
    use WithPagination;

    public string $search = '';

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    /**
     * A short label for the activity's subject: the model's class name, plus whatever
     * identifying field it happens to expose, or "(deleted)" if the record is gone.
     */
    public function subjectLabel(Activity $activity): ?string
    {
        if ($activity->subject_type === null) {
            return null;
        }

        $type = class_basename($activity->subject_type);

        if ($activity->subject === null) {
            return "{$type} (deleted)";
        }

        $identifier = $activity->subject->title ?? $activity->subject->name ?? $activity->subject->key ?? null;

        return $identifier ? "{$type}: {$identifier}" : $type;
    }

    #[Computed]
    public function activities()
    {
        return Activity::query()
            ->when($this->search, fn ($query) => $query->where(fn ($q) => $q
                ->where('description', 'like', "%{$this->search}%")
                ->orWhere('log_name', 'like', "%{$this->search}%")
                ->orWhereHas('causer', fn ($causerQuery) => $causerQuery->where('name', 'like', "%{$this->search}%"))))
            ->with(['causer', 'subject'])
            ->latest()
            ->paginate(20);
    }
}; ?>

<div class="w-full space-y-6">
    <div>
        <flux:heading size="xl">Activity log</flux:heading>
        <flux:subheading>Audit trail of admin actions across the app.</flux:subheading>
    </div>

    <flux:input wire:model.live.debounce.300ms="search" placeholder="Search by description, log name, or user" icon="magnifying-glass" class="max-w-sm" />

    <flux:card class="w-full">
        <flux:table :paginate="$this->activities">
            <flux:table.columns>
                <flux:table.column>Description</flux:table.column>
                <flux:table.column>Log name</flux:table.column>
                <flux:table.column>Event</flux:table.column>
                <flux:table.column>Subject</flux:table.column>
                <flux:table.column>By</flux:table.column>
                <flux:table.column>Date</flux:table.column>
            </flux:table.columns>

            <flux:table.rows>
                @foreach ($this->activities as $item)
                    <flux:table.row :key="$item->id">
                        <flux:table.cell variant="strong">{{ $item->description }}</flux:table.cell>
                        <flux:table.cell class="whitespace-nowrap">
                            @if ($item->log_name)
                                <flux:badge size="sm" color="zinc">{{ ucfirst($item->log_name) }}</flux:badge>
                            @else
                                —
                            @endif
                        </flux:table.cell>
                        <flux:table.cell class="whitespace-nowrap">
                            @if ($item->event)
                                <flux:badge size="sm" color="zinc">{{ ucfirst($item->event) }}</flux:badge>
                            @else
                                —
                            @endif
                        </flux:table.cell>
                        <flux:table.cell class="whitespace-nowrap">{{ $this->subjectLabel($item) ?? '—' }}</flux:table.cell>
                        <flux:table.cell class="whitespace-nowrap">{{ $item->causer?->name ?? 'System' }}</flux:table.cell>
                        <flux:table.cell class="whitespace-nowrap">{{ $item->created_at->translatedFormat('l, j F Y H:i') }}</flux:table.cell>
                    </flux:table.row>
                @endforeach
            </flux:table.rows>
        </flux:table>
    </flux:card>
</div>
