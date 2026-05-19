<?php

namespace App\Livewire;

use App\Models\Treatment;
use App\Services\NotificationScheduler;
use Livewire\Component;

class Treatments extends Component
{
    public ?int $pendingArchiveId = null;
    public bool $showArchiveModal = false;

    public array $orderedIds = [];
    public bool $isDirty = false;

    public function archive(int $id): void
    {
        $this->pendingArchiveId = $id;
        $this->showArchiveModal = true;
    }

    public function confirmArchive(): void
    {
        if ($this->pendingArchiveId !== null) {
            $treatment = Treatment::findOrFail($this->pendingArchiveId);
            app(NotificationScheduler::class)->cancelForTreatment($treatment);
            $treatment->archive();
        }
        $this->pendingArchiveId = null;
        $this->showArchiveModal = false;
    }

    public function cancelArchive(): void
    {
        $this->pendingArchiveId = null;
        $this->showArchiveModal = false;
    }

    public function unarchive(int $id): void
    {
        Treatment::findOrFail($id)->unarchive();
    }

    public function setOrder(array $ids): void
    {
        $this->orderedIds = $ids;
        $this->isDirty = true;
    }

    public function saveOrder(): void
    {
        foreach ($this->orderedIds as $index => $id) {
            Treatment::where('id', (int) $id)->update(['sort_order' => $index]);
        }
        $this->isDirty = false;
    }

    public function render(): \Illuminate\View\View
    {
        return view('livewire.treatments', [
            'treatments' => Treatment::active()
                ->with('posologyHistory')
                ->orderBy('sort_order')
                ->orderBy('id')
                ->get(),
            'archived' => Treatment::archived()
                ->orderBy('name')
                ->get(),
        ])->layout('layouts.app', ['title' => 'Traitements']);
    }
}
