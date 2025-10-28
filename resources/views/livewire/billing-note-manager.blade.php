<div style="text-align: left;">
    <!-- Bestehende Notizen -->
    @if (count($notes) > 0)
        <div style="margin-bottom: 0.75rem;">
            <div style="display: flex; flex-direction: column; gap: 0.5rem; max-height: 200px; overflow-y: auto;">
                @foreach ($notes as $noteData)
                    <div style="padding: 0.5rem; background-color: #f9fafb; border: 1px solid #e5e7eb; border-radius: 4px; font-size: 0.75rem;">
                        <div style="display: flex; align-items: flex-start; gap: 0.5rem;">
                            <div style="flex: 1; min-width: 0;">
                                <p style="color: #111827; margin-bottom: 0.25rem; text-align: left;">{{ $noteData['note'] }}</p>
                                <div style="display: flex; gap: 0.5rem; color: #6b7280; font-size: 0.625rem; text-align: left;">
                                    <span>{{ \Carbon\Carbon::parse($noteData['created_at'])->format('d.m.Y H:i') }}</span>
                                    @if (isset($noteData['creator']['name']))
                                        <span>• {{ $noteData['creator']['name'] }}</span>
                                    @endif
                                </div>
                            </div>
                            <button
                                wire:click="deleteNote('{{ $noteData['id'] }}')"
                                wire:confirm="Notiz wirklich löschen?"
                                style="border: none; background: none; cursor: pointer; color: #ef4444; padding: 0; flex-shrink: 0;"
                                title="Notiz löschen"
                            >
                                <svg style="width: 14px; height: 14px;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                </svg>
                            </button>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    @endif

    <!-- Neue Notiz hinzufügen -->
    <div>
        @if (!$showAddForm)
            <button
                wire:click="toggleAddForm"
                style="display: inline-flex; align-items: center; padding: 0.25rem 0.5rem; background-color: white; border: 1px solid #d1d5db; border-radius: 4px; text-decoration: none; color: #374151; font-size: 0.625rem; cursor: pointer; font-weight: 500; transition: background-color 0.2s;"
                onmouseover="this.style.backgroundColor='#f9fafb';"
                onmouseout="this.style.backgroundColor='#ffffff';"
            >
                <svg style="width: 10px; height: 10px; margin-right: 0.25rem;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                </svg>
                Notiz
            </button>
        @else
            <div>
                <form wire:submit.prevent="addNote">
                    <textarea
                        wire:model="note"
                        placeholder="Notiz eingeben..."
                        style="width: 100%; padding: 0.5rem; border: 1px solid #d1d5db; border-radius: 4px; font-size: 0.75rem; resize: vertical; min-height: 60px;"
                        maxlength="1000"
                        autofocus
                    ></textarea>
                    @error('note')
                        <p style="color: #ef4444; font-size: 0.625rem; margin-top: 0.25rem;">{{ $message }}</p>
                    @enderror
                    <div style="display: flex; justify-content: flex-start; gap: 0.5rem; margin-top: 0.5rem;">
                        <button
                            type="submit"
                            style="padding: 0.375rem 0.75rem; background-color: #3b82f6; color: white; border: none; border-radius: 4px; font-size: 0.75rem; cursor: pointer; font-weight: 500;"
                        >
                            Speichern
                        </button>
                        <button
                            type="button"
                            wire:click="toggleAddForm"
                            style="padding: 0.375rem 0.75rem; background-color: white; color: #374151; border: 1px solid #d1d5db; border-radius: 4px; font-size: 0.75rem; cursor: pointer; font-weight: 500;"
                        >
                            Abbrechen
                        </button>
                    </div>
                </form>
            </div>
        @endif
    </div>
</div>
