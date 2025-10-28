<?php

namespace App\Livewire;

use App\Models\BillingNote;
use Livewire\Component;
use Illuminate\Support\Facades\Auth;

class BillingNoteManager extends Component
{
    public $solarPlantId;
    public $supplierContractId = null;
    public $billingYear;
    public $billingMonth;
    public $note = '';
    public $notes = [];
    public $showAddForm = false;

    public function mount($solarPlantId, $billingYear, $billingMonth, $supplierContractId = null)
    {
        $this->solarPlantId = $solarPlantId;
        $this->billingYear = $billingYear;
        $this->billingMonth = $billingMonth;
        $this->supplierContractId = $supplierContractId;
        $this->loadNotes();
    }

    public function loadNotes()
    {
        $query = BillingNote::forSolarPlant($this->solarPlantId)
            ->forMonth($this->billingYear, $this->billingMonth)
            ->with(['creator', 'supplierContract']);

        if ($this->supplierContractId) {
            $query->forContract($this->supplierContractId);
        }

        $this->notes = $query->orderBy('created_at', 'desc')->get()->toArray();
    }

    public function toggleAddForm()
    {
        $this->showAddForm = !$this->showAddForm;
        if (!$this->showAddForm) {
            $this->note = '';
            $this->resetErrorBag();
        }
    }

    public function addNote()
    {
        $this->validate([
            'note' => 'required|string|max:1000',
        ]);

        BillingNote::create([
            'solar_plant_id' => $this->solarPlantId,
            'supplier_contract_id' => $this->supplierContractId,
            'billing_year' => $this->billingYear,
            'billing_month' => $this->billingMonth,
            'note' => $this->note,
            'created_by' => Auth::id(),
        ]);

        $this->note = '';
        $this->showAddForm = false;
        $this->loadNotes();

        $this->dispatch('noteAdded');
    }

    public function deleteNote($noteId)
    {
        BillingNote::findOrFail($noteId)->delete();
        $this->loadNotes();
    }

    public function render()
    {
        return view('livewire.billing-note-manager');
    }
}
