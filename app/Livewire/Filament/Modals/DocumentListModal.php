<?php

namespace App\Livewire\Filament\Modals;

use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use LivewireUI\Modal\ModalComponent;

class DocumentListModal extends ModalComponent implements HasForms
{
    use InteractsWithForms;

    public $documents = [];

    public function mount(array $documents = [])
    {
        $this->documents = $documents;
    }

    public function render()
    {
        return view('livewire.filament.modals.document-list-modal');
    }

    public static function modalMaxWidth(): string
    {
        return '2xl';
    }
}
