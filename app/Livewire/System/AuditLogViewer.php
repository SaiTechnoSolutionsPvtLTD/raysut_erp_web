<?php

namespace App\Livewire\System;

use App\Models\AuditLog;
use Livewire\Component;

use Livewire\WithPagination;

class AuditLogViewer extends Component
{
    use WithPagination;

    public string $search = '';
    public string $searchModule = '';
    public string $searchAction = '';

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function updatingSearchModule(): void
    {
        $this->resetPage();
    }

    public function updatingSearchAction(): void
    {
        $this->resetPage();
    }

    public function render()
    {
        $query = AuditLog::with('user')->orderBy('audit_id', 'desc');

        if ($this->search) {
            $term = '%' . $this->search . '%';
            $query->where(function ($q) use ($term) {
                $q->where('module_name', 'like', $term)
                  ->orWhere('record_table', 'like', $term)
                  ->orWhere('record_id', 'like', $term)
                  ->orWhereHas('user', function ($uq) use ($term) {
                      $uq->where('full_name', 'like', $term)
                        ->orWhere('username', 'like', $term);
                  });
            });
        }

        if ($this->searchModule) {
            $query->where('module_name', 'like', '%' . $this->searchModule . '%');
        }

        if ($this->searchAction) {
            $query->where('action', $this->searchAction);
        }

        return view('livewire.system.audit-log-viewer', [
            'logs' => $query->paginate(20),
        ])->layout('layouts.app');
    }
}
