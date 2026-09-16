<?php

namespace App\Livewire\System;

use App\Models\Branch;
use App\Models\Counter;
use Livewire\Component;

class CounterManagement extends Component
{
    public bool $isModalOpen = false;
    public ?int $editingCounterId = null;

    public int $branch_id = 0;
    public string $counter_code = '';
    public string $counter_name = '';
    public string $terminal_id = '';
    public bool $is_active = true;

    // Delete Modal Properties
    public bool $isDeleteModalOpen = false;
    public ?int $deletingCounterId = null;
    public string $deletingCounterName = '';

    protected function generateCounterCode(): string
    {
        $nextId = (Counter::max('counter_id') ?? 0) + 1;
        do {
            $code = 'CNT-' . str_pad((string)$nextId, 3, '0', STR_PAD_LEFT);
            $exists = Counter::where('counter_code', $code)->exists();
            if ($exists) {
                $nextId++;
            }
        } while ($exists);

        return $code;
    }

    protected $rules = [
        'branch_id' => 'required|exists:branches,branch_id',
        'counter_code' => 'required|string|max:20',
        'counter_name' => 'required|string|max:100',
        'terminal_id' => 'nullable|string|max:50',
        'is_active' => 'boolean',
    ];

    public function create(): void
    {
        if (! auth()->user()?->hasPermission('Branch & Shift Operations', 'CREATE')) {
            abort(403, 'Unauthorized action: You do not have permission to CREATE POS counters.');
        }

        $this->reset(['editingCounterId', 'counter_name', 'terminal_id']);
        $this->counter_code = $this->generateCounterCode();
        $firstBranch = Branch::first();
        $this->branch_id = $firstBranch?->branch_id ?? 0;
        $this->is_active = true;
        $this->isModalOpen = true;
    }

    public function edit(int $counterId): void
    {
        if (! auth()->user()?->hasPermission('Branch & Shift Operations', 'EDIT')) {
            abort(403, 'Unauthorized action: You do not have permission to EDIT POS counters.');
        }

        $cnt = Counter::findOrFail($counterId);
        $this->editingCounterId = $cnt->counter_id;
        $this->branch_id = $cnt->branch_id;
        $this->counter_code = $cnt->counter_code;
        $this->counter_name = $cnt->counter_name;
        $this->terminal_id = $cnt->terminal_id ?? '';
        $this->is_active = (bool)$cnt->is_active;

        $this->isModalOpen = true;
    }

    public function save(): void
    {
        $requiredAction = $this->editingCounterId ? 'EDIT' : 'CREATE';
        if (! auth()->user()?->hasPermission('Branch & Shift Operations', $requiredAction)) {
            abort(403, "Unauthorized action: You do not have permission to {$requiredAction} POS counters.");
        }

        $this->validate();

        if ($this->editingCounterId) {
            $cnt = Counter::findOrFail($this->editingCounterId);
        } else {
            $cnt = new Counter();
            if (empty($this->counter_code)) {
                $this->counter_code = $this->generateCounterCode();
            }
        }

        $cnt->fill([
            'branch_id' => $this->branch_id,
            'counter_code' => $this->counter_code,
            'counter_name' => $this->counter_name,
            'terminal_id' => $this->terminal_id,
            'is_active' => $this->is_active,
        ]);

        $cnt->save();

        session()->flash('message', 'POS Counter ' . ($this->editingCounterId ? 'updated' : 'created') . ' successfully.');
        $this->dispatch('notify', type: 'success', title: 'POS Counter Saved', message: 'POS Counter ' . ($this->editingCounterId ? 'updated' : 'created') . ' successfully.');
        $this->isModalOpen = false;
    }

    public function confirmDelete(int $counterId): void
    {
        if (! auth()->user()?->hasPermission('Branch & Shift Operations', 'DELETE')) {
            abort(403, 'Unauthorized action: You do not have permission to DELETE POS counters.');
        }

        $cnt = Counter::findOrFail($counterId);
        $this->deletingCounterId = $cnt->counter_id;
        $this->deletingCounterName = $cnt->counter_name;
        $this->isDeleteModalOpen = true;
    }

    public function deleteCounter(): void
    {
        if (! auth()->user()?->hasPermission('Branch & Shift Operations', 'DELETE')) {
            abort(403, 'Unauthorized action: You do not have permission to DELETE POS counters.');
        }

        if ($this->deletingCounterId) {
            $name = $this->deletingCounterName ?: 'Counter';
            Counter::destroy($this->deletingCounterId);
            session()->flash('message', "POS Counter '{$name}' deleted successfully.");
            $this->dispatch('notify', type: 'warning', title: 'POS Counter Removed', message: "POS Counter '{$name}' deleted successfully.");
            $this->isDeleteModalOpen = false;
        }
    }

    public function render()
    {
        return view('livewire.system.counter-management', [
            'counters' => Counter::with('branch')->get(),
            'branches' => Branch::where('is_active', true)->get(),
        ])->layout('layouts.app');
    }
}
