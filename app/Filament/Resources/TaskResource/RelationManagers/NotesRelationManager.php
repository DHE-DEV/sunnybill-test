<?php

namespace App\Filament\Resources\TaskResource\RelationManagers;

use App\Models\TaskNote;
use App\Models\User;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\HtmlString;
use Filament\Support\Facades\FilamentView;

class NotesRelationManager extends RelationManager
{
    protected static string $relationship = 'notes';

    protected static ?string $title = 'Notizen';

    protected static ?string $modelLabel = 'Notiz';

    protected static ?string $pluralModelLabel = 'Notizen';

    protected static ?string $icon = 'heroicon-o-chat-bubble-left-ellipsis';

    public function isReadOnly(): bool
    {
        return false;
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Notiz')
                    ->schema([
                        Forms\Components\Textarea::make('content')
                            ->label('Inhalt')
                            ->required()
                            ->rows(8)
                            ->columnSpanFull()
                            ->placeholder('Schreiben Sie Ihre Notiz hier... Verwenden Sie @benutzername um Benutzer zu erwähnen.')
                            ->extraAttributes([
                                'id' => 'task-note-content',
                                'data-mention-enabled' => 'true',
                                'style' => 'font-family: system-ui, -apple-system, sans-serif; font-size: 14px; line-height: 1.5; border: 2px solid #3b82f6; border-radius: 8px; padding: 12px;',
                                'oninput' => 'handleMentionInput(this, event);',
                                'onkeydown' => 'handleMentionKeydown(this, event);',
                                'onfocus' => 'console.log("🎯 Textarea focused:", this.id); initializeMentionSystem();'
                            ])
                            ->hint(new HtmlString('💡 <strong>Erweiterte Notiz-Eingabe</strong> mit @mention Unterstützung. Verwenden Sie <strong>@benutzername</strong> um Benutzer zu erwähnen und zu benachrichtigen.'))
                            ->hintColor('info'),
                        
                        // Benutzer-Auswahl-Komponente
                        Forms\Components\Placeholder::make('user_selector')
                            ->label('Benutzer erwähnen')
                            ->content(new \Illuminate\Support\HtmlString($this->getUserSelectorHtml()))
                            ->columnSpanFull(),
                    ]),
            ])
            ->columns(1);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('content')
            ->defaultSort('created_at', 'desc')
            ->columns([
                Tables\Columns\TextColumn::make('content')
                    ->label('Inhalt')
                    ->searchable()
                    ->limit(100)
                    ->wrap()
                    ->formatStateUsing(function ($state, $record) {
                        // Highlight @mentions in the content
                        $content = e($state);
                        $content = preg_replace(
                            '/@(\w+)/',
                            '<span class="bg-blue-100 text-blue-800 px-1 rounded">@$1</span>',
                            $content
                        );
                        return new HtmlString($content);
                    }),
                Tables\Columns\TextColumn::make('user.name')
                    ->label('Erstellt von')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Erstellt am')
                    ->dateTime('d.m.Y H:i')
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\Filter::make('created_today')
                    ->label('Heute erstellt')
                    ->query(fn (Builder $query): Builder => $query->whereDate('created_at', today())),
                Tables\Filters\Filter::make('created_this_week')
                    ->label('Diese Woche erstellt')
                    ->query(fn (Builder $query): Builder => $query->whereBetween('created_at', [now()->startOfWeek(), now()->endOfWeek()])),
                Tables\Filters\SelectFilter::make('user_id')
                    ->label('Erstellt von')
                    ->relationship('user', 'name')
                    ->searchable()
                    ->preload(),
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make()
                    ->label('Notiz hinzufügen')
                    ->icon('heroicon-o-plus')
                    ->modalWidth('2xl')
                    ->modalHeading('Neue Notiz hinzufügen')
                    ->modalDescription('Verwenden Sie @benutzername um andere Benutzer zu erwähnen')
                    ->extraModalFooterActions([
                        \Filament\Actions\Action::make('debug')
                            ->label('Debug Info')
                            ->color('gray')
                            ->action(function () {
                                // This will be handled by JavaScript
                            })
                            ->extraAttributes([
                                'onclick' => 'console.log("🔧 Debug: Modal ist geöffnet"); console.log("🔧 Verfügbare Funktionen:", Object.keys(window).filter(k => k.includes("mention") || k.includes("Mention"))); return false;'
                            ])
                    ])
                    ->mutateFormDataUsing(function (array $data): array {
                        $data['user_id'] = auth()->id();
                        return $data;
                    })
                    ->after(function (TaskNote $record) {
                        // Process @mentions and send notifications
                        $this->processMentions($record);
                    }),
            ])
            ->actions([
                Tables\Actions\ViewAction::make()
                    ->modalWidth('2xl')
                    ->form([
                        Forms\Components\Section::make('Notiz Details')
                            ->schema([
                                Forms\Components\Textarea::make('content')
                                    ->label('Inhalt')
                                    ->disabled()
                                    ->rows(6),
                                Forms\Components\TextInput::make('user.name')
                                    ->label('Erstellt von')
                                    ->disabled(),
                                Forms\Components\TextInput::make('created_at')
                                    ->label('Erstellt am')
                                    ->disabled()
                                    ->formatStateUsing(fn ($state) => $state?->format('d.m.Y H:i')),
                            ]),
                    ]),
                Tables\Actions\EditAction::make()
                    ->modalWidth('2xl')
                    ->visible(fn (TaskNote $record): bool => $record->user_id === auth()->id() || auth()->user()->isManagerOrAdmin())
                    ->after(function (TaskNote $record) {
                        // Process @mentions and send notifications for edited notes
                        $this->processMentions($record);
                    }),
                Tables\Actions\DeleteAction::make()
                    ->visible(fn (TaskNote $record): bool => $record->user_id === auth()->id() || auth()->user()->isManagerOrAdmin()),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make()
                        ->visible(fn (): bool => auth()->user()->isManagerOrAdmin()),
                ]),
            ])
            ->emptyStateHeading('Keine Notizen vorhanden')
            ->emptyStateDescription('Erstellen Sie die erste Notiz für diese Aufgabe.')
            ->emptyStateIcon('heroicon-o-chat-bubble-left-ellipsis');
    }

    /**
     * Process @mentions in the note content and send email notifications
     */
    protected function processMentions(TaskNote $note): void
    {
        \Log::info('🔍 Verarbeite @mentions in Task-Notiz', [
            'note_id' => $note->id,
            'task_id' => $note->task_id,
            'content' => $note->content,
            'author_id' => auth()->id(),
            'author_name' => auth()->user()->name
        ]);

        // Extract @mentions from content
        preg_match_all('/@(\w+)/', $note->content, $matches);
        $mentionedUsernames = array_unique($matches[1]);

        \Log::info('📝 Gefundene @mentions', [
            'note_id' => $note->id,
            'mentioned_usernames' => $mentionedUsernames,
            'count' => count($mentionedUsernames)
        ]);

        if (empty($mentionedUsernames)) {
            \Log::info('ℹ️ Keine @mentions gefunden in Task-Notiz', [
                'note_id' => $note->id
            ]);
            return;
        }

        // Find users by name (case-insensitive)
        $mentionedUsers = User::whereIn('name', $mentionedUsernames)
            ->orWhere(function ($query) use ($mentionedUsernames) {
                foreach ($mentionedUsernames as $username) {
                    $query->orWhereRaw('LOWER(name) LIKE ?', ['%' . strtolower($username) . '%']);
                }
            })
            ->get();

        \Log::info('👥 Gefundene Benutzer für @mentions', [
            'note_id' => $note->id,
            'mentioned_usernames' => $mentionedUsernames,
            'found_users' => $mentionedUsers->map(function($user) {
                return [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email
                ];
            })->toArray(),
            'found_count' => $mentionedUsers->count()
        ]);

        // Send email notifications to mentioned users
        foreach ($mentionedUsers as $user) {
            if ($user->id !== auth()->id()) { // Don't notify the author
                \Log::info('📧 Sende Mention-Benachrichtigung', [
                    'note_id' => $note->id,
                    'user_id' => $user->id,
                    'user_name' => $user->name,
                    'user_email' => $user->email
                ]);
                $this->sendMentionNotification($user, $note);
            } else {
                \Log::info('⏭️ Überspringe Selbst-Benachrichtigung', [
                    'note_id' => $note->id,
                    'author_id' => auth()->id()
                ]);
            }
        }

        \Log::info('✅ @mentions Verarbeitung abgeschlossen', [
            'note_id' => $note->id,
            'total_mentions' => count($mentionedUsernames),
            'notifications_sent' => $mentionedUsers->where('id', '!=', auth()->id())->count()
        ]);
    }

    /**
     * Get HTML for user selector component
     */
    protected function getUserSelectorHtml(): string
    {
        $users = \App\Models\User::orderBy('name')->get();
        
        $html = '
        <div class="user-mention-selector" style="margin-top: 12px; padding: 16px; background-color: #f9fafb; border: 1px solid #e5e7eb; border-radius: 8px;">
            <div style="margin-bottom: 8px;">
                <span style="font-size: 14px; font-weight: 500; color: #374151;">Klicken Sie auf einen Benutzer, um ihn zu erwähnen:</span>
            </div>
            
            <div style="display: flex; flex-wrap: wrap; gap: 8px;">';
        
        foreach ($users as $user) {
            $initial = strtoupper(substr($user->name, 0, 1));
            $html .= '
                <button
                    type="button"
                    class="user-mention-btn"
                    onclick="insertUserMention(\'' . addslashes($user->name) . '\')"
                    title="@' . htmlspecialchars($user->name) . ' erwähnen"
                    style="display: inline-flex; align-items: center; padding: 8px 12px; border: 1px solid #d1d5db; box-shadow: 0 1px 2px 0 rgba(0, 0, 0, 0.05); font-size: 14px; font-weight: 500; border-radius: 6px; color: #374151; background-color: white; cursor: pointer; transition: all 0.2s;"
                    onmouseover="this.style.backgroundColor=\'#f9fafb\'; this.style.transform=\'translateY(-1px)\'; this.style.boxShadow=\'0 4px 6px -1px rgba(0, 0, 0, 0.1)\';"
                    onmouseout="this.style.backgroundColor=\'white\'; this.style.transform=\'translateY(0)\'; this.style.boxShadow=\'0 1px 2px 0 rgba(0, 0, 0, 0.05)\';"
                >
                    <div style="display: flex; align-items: center; gap: 8px;">
                        <div style="width: 24px; height: 24px; background-color: #3b82f6; border-radius: 50%; display: flex; align-items: center; justify-content: center; color: white; font-size: 12px; font-weight: bold;">
                            ' . $initial . '
                        </div>
                        <span>' . htmlspecialchars($user->name) . '</span>
                    </div>
                </button>';
        }
        
        $html .= '
            </div>
        </div>

        <script>
        // Globale Funktionen definieren
        window.handleMentionInput = function(textarea, event) {
            console.log("📝 handleMentionInput called:", textarea.value);
            
            const cursorPos = textarea.selectionStart;
            const text = textarea.value;
            
            let mentionStart = -1;
            for (let i = cursorPos - 1; i >= 0; i--) {
                if (text[i] === "@") {
                    if (i === 0 || /\s/.test(text[i - 1])) {
                        mentionStart = i;
                        break;
                    }
                } else if (/\s/.test(text[i])) {
                    break;
                }
            }

            if (mentionStart !== -1) {
                const query = text.substring(mentionStart + 1, cursorPos);
                if (!/\s/.test(query)) {
                    console.log("🔍 Zeige Dropdown für:", query);
                    showMentionDropdown(textarea, query, mentionStart);
                    return;
                }
            }
            hideMentionDropdown();
        };

        window.handleMentionKeydown = function(textarea, event) {
            console.log("⌨️ handleMentionKeydown called:", event.key);
            
            const dropdown = document.getElementById("mention-dropdown");
            if (!dropdown || dropdown.style.display === "none") return;
            
            const items = dropdown.querySelectorAll(".mention-item");
            let selectedIndex = Array.from(items).findIndex(item => item.classList.contains("selected"));
            
            switch (event.key) {
                case "ArrowDown":
                    event.preventDefault();
                    selectedIndex = Math.min(selectedIndex + 1, items.length - 1);
                    updateSelection(items, selectedIndex);
                    break;
                case "ArrowUp":
                    event.preventDefault();
                    selectedIndex = Math.max(selectedIndex - 1, 0);
                    updateSelection(items, selectedIndex);
                    break;
                case "Tab":
                case "Enter":
                    event.preventDefault();
                    if (selectedIndex >= 0 && items[selectedIndex]) {
                        selectMention(items[selectedIndex], textarea);
                    }
                    break;
                case "Escape":
                    hideMentionDropdown();
                    break;
            }
        };

        window.initializeMentionSystem = function() {
            console.log("🔧 initializeMentionSystem called");
            
            if (!window.mentionUsers) {
                window.mentionUsers = ' . json_encode(\App\Models\User::select('id', 'name', 'email')->orderBy('name')->get()) . ';
                console.log("👥 Benutzer geladen:", window.mentionUsers);
            }
            
            // CSS hinzufügen
            if (!document.getElementById("mention-styles")) {
                const style = document.createElement("style");
                style.id = "mention-styles";
                style.textContent = `
                    #mention-dropdown {
                        position: absolute;
                        background: white;
                        border: 1px solid #d1d5db;
                        border-radius: 6px;
                        box-shadow: 0 10px 25px rgba(0, 0, 0, 0.15);
                        max-height: 200px;
                        overflow-y: auto;
                        z-index: 99999 !important;
                        display: none;
                        min-width: 250px;
                        font-family: system-ui, -apple-system, sans-serif;
                    }
                    .mention-item {
                        padding: 12px 16px;
                        cursor: pointer;
                        border-bottom: 1px solid #f3f4f6;
                        display: flex;
                        align-items: center;
                        gap: 10px;
                        transition: background-color 0.15s ease;
                    }
                    .mention-item:hover,
                    .mention-item.selected {
                        background-color: #eff6ff !important;
                    }
                    .mention-avatar {
                        width: 28px;
                        height: 28px;
                        border-radius: 50%;
                        background-color: #3b82f6;
                        color: white;
                        display: flex;
                        align-items: center;
                        justify-content: center;
                        font-size: 12px;
                        font-weight: bold;
                    }
                    .mention-name {
                        font-weight: 600;
                        font-size: 14px;
                        color: #111827;
                    }
                `;
                document.head.appendChild(style);
                console.log("✅ Mention Styles hinzugefügt");
            }
        };

        function showMentionDropdown(textarea, query, mentionStart) {
            const filteredUsers = window.mentionUsers.filter(user =>
                user.name.toLowerCase().includes(query.toLowerCase())
            ).slice(0, 5);
            
            if (filteredUsers.length === 0) {
                hideMentionDropdown();
                return;
            }

            hideMentionDropdown();

            const dropdown = document.createElement("div");
            dropdown.id = "mention-dropdown";
            
            filteredUsers.forEach((user, index) => {
                const item = document.createElement("div");
                item.className = "mention-item" + (index === 0 ? " selected" : "");
                item.dataset.userIndex = index;
                item.dataset.mentionStart = mentionStart;
                
                item.innerHTML = `
                    <div class="mention-avatar">${user.name.charAt(0).toUpperCase()}</div>
                    <div class="mention-name">${user.name}</div>
                `;
                
                item.addEventListener("click", () => {
                    selectMention(item, textarea);
                });
                
                dropdown.appendChild(item);
            });

            const rect = textarea.getBoundingClientRect();
            dropdown.style.left = rect.left + "px";
            dropdown.style.top = (rect.bottom + 5) + "px";
            dropdown.style.display = "block";
            
            document.body.appendChild(dropdown);
            console.log("✅ Dropdown angezeigt mit", filteredUsers.length, "Benutzern");
        }

        function hideMentionDropdown() {
            const dropdown = document.getElementById("mention-dropdown");
            if (dropdown) {
                dropdown.remove();
            }
        }

        function updateSelection(items, selectedIndex) {
            items.forEach((item, index) => {
                item.classList.toggle("selected", index === selectedIndex);
            });
        }

        function selectMention(item, textarea) {
            const userIndex = parseInt(item.dataset.userIndex);
            const mentionStart = parseInt(item.dataset.mentionStart);
            const user = window.mentionUsers[userIndex];
            
            if (user) {
                const text = textarea.value;
                const cursorPos = textarea.selectionStart;
                const beforeMention = text.substring(0, mentionStart);
                const afterCursor = text.substring(cursorPos);
                const newText = beforeMention + "@" + user.name + " " + afterCursor;
                
                textarea.value = newText;
                textarea.setSelectionRange(beforeMention.length + user.name.length + 2, beforeMention.length + user.name.length + 2);
                textarea.dispatchEvent(new Event("input", { bubbles: true }));
                
                console.log("👤 Mention eingefügt:", user.name);
            }
            
            hideMentionDropdown();
            textarea.focus();
        }

        function insertUserMention(username) {
            console.log("👤 Benutzer-Button geklickt:", username);
            
            // Finde das Textarea
            const textarea = document.getElementById("task-note-content") ||
                            document.querySelector("textarea[data-mention-enabled=\'true\']") ||
                            document.querySelector("textarea");
            
            if (!textarea) {
                console.error("❌ Textarea nicht gefunden");
                return;
            }
            
            console.log("📝 Textarea gefunden:", textarea.id);
            
            // Aktueller Cursor-Position und Text
            const cursorPos = textarea.selectionStart;
            const currentText = textarea.value;
            
            // Text vor und nach Cursor
            const textBefore = currentText.substring(0, cursorPos);
            const textAfter = currentText.substring(cursorPos);
            
            // Neuen Text zusammensetzen: " @Username "
            const mentionText = " @" + username + " ";
            const newText = textBefore + mentionText + textAfter;
            
            // Text setzen
            textarea.value = newText;
            
            // Cursor nach dem eingefügten Text positionieren
            const newCursorPos = cursorPos + mentionText.length;
            textarea.setSelectionRange(newCursorPos, newCursorPos);
            
            // Focus auf Textarea setzen
            textarea.focus();
            
            // Input Event triggern für Filament
            textarea.dispatchEvent(new Event("input", { bubbles: true }));
            textarea.dispatchEvent(new Event("change", { bubbles: true }));
            
            console.log("✅ Mention eingefügt:", mentionText);
            console.log("📍 Neue Cursor-Position:", newCursorPos);
            
            // Visuelles Feedback
            const button = event.target.closest(".user-mention-btn");
            if (button) {
                const originalBg = button.style.backgroundColor;
                button.style.backgroundColor = "#10b981";
                button.style.color = "white";
                
                setTimeout(() => {
                    button.style.backgroundColor = originalBg;
                    button.style.color = "";
                }, 300);
            }
        }

        // Fallback-Funktion für normale Textareas
        function insertIntoTextarea(textarea, username) {
            console.log("📝 Fallback: Textarea gefunden:", textarea.id);
            
            const cursorPos = textarea.selectionStart;
            const currentText = textarea.value;
            
            const textBefore = currentText.substring(0, cursorPos);
            const textAfter = currentText.substring(cursorPos);
            
            const mentionText = " @" + username + " ";
            const newText = textBefore + mentionText + textAfter;
            
            textarea.value = newText;
            
            const newCursorPos = cursorPos + mentionText.length;
            textarea.setSelectionRange(newCursorPos, newCursorPos);
            
            textarea.focus();
            
            textarea.dispatchEvent(new Event("input", { bubbles: true }));
            textarea.dispatchEvent(new Event("change", { bubbles: true }));
            
            console.log("✅ Fallback: Mention eingefügt:", mentionText);
        }

        // Debug-Funktion
        window.debugUserSelector = function() {
            console.log("🔧 User Selector Debug:");
            console.log("- Textarea gefunden:", !!document.getElementById("task-note-content"));
            console.log("- Benutzer-Buttons:", document.querySelectorAll(".user-mention-btn").length);
            console.log("- Aktueller Textarea-Wert:", document.getElementById("task-note-content")?.value || "Nicht gefunden");
        };

        console.log("✅ User Mention Selector geladen");
        console.log("💡 Verwende window.debugUserSelector() für Debug-Infos");
        </script>';
        
        return $html;
    }

    /**
     * Get direct input handler for immediate execution
     */
    protected function getDirectInputHandler(): string
    {
        return "
        console.log('📝 Direct Input Event:', this.value);
        
        // Initialisiere Mention-System falls noch nicht geschehen
        if (!window.mentionSystemInitialized) {
            " . $this->getInitScript() . "
            window.mentionSystemInitialized = true;
        }
        
        // Handle Mention Input direkt
        const textarea = this;
        const cursorPos = textarea.selectionStart;
        const text = textarea.value;
        
        let mentionStart = -1;
        for (let i = cursorPos - 1; i >= 0; i--) {
            if (text[i] === '@') {
                if (i === 0 || /\s/.test(text[i - 1])) {
                    mentionStart = i;
                    break;
                }
            } else if (/\s/.test(text[i])) {
                break;
            }
        }

        if (mentionStart !== -1) {
            const query = text.substring(mentionStart + 1, cursorPos);
            if (!/\s/.test(query)) {
                console.log('🔍 Zeige Dropdown für:', query);
                window.showDirectMentionDropdown(textarea, query, mentionStart);
                return;
            }
        }
        window.hideDirectMentionDropdown();
        ";
    }

    /**
     * Get direct keydown handler for immediate execution
     */
    protected function getDirectKeydownHandler(): string
    {
        return "
        console.log('⌨️ Direct Keydown:', event.key);
        
        if (!window.currentMentionDropdown) return;
        
        switch (event.key) {
            case 'ArrowDown':
                event.preventDefault();
                window.navigateDirectDropdown('down');
                break;
            case 'ArrowUp':
                event.preventDefault();
                window.navigateDirectDropdown('up');
                break;
            case 'Tab':
            case 'Enter':
                event.preventDefault();
                window.confirmDirectSelection();
                break;
            case 'Escape':
                event.preventDefault();
                window.hideDirectMentionDropdown();
                break;
        }
        ";
    }

    /**
     * Get direct focus handler for immediate execution
     */
    protected function getDirectFocusHandler(): string
    {
        return "
        // Initialisiere Mention-System beim ersten Focus
        if (!window.mentionSystemInitialized) {
            console.log('🔧 Initialisiere Mention System...');
            " . $this->getInitScript() . "
            window.mentionSystemInitialized = true;
            console.log('✅ Mention System initialisiert');
        }
        ";
    }

    /**
     * Get initialization script
     */
    protected function getInitScript(): string
    {
        return "
        // Benutzer-Daten aus der Datenbank laden
        window.mentionUsers = " . json_encode(\App\Models\User::select('id', 'name', 'email')->orderBy('name')->get()) . ";

        window.currentMentionDropdown = null;
        window.currentMentionTextarea = null;

        // CSS Styles hinzufügen
        if (!document.getElementById('direct-mention-styles')) {
            const style = document.createElement('style');
            style.id = 'direct-mention-styles';
            style.textContent = \`
                .direct-mention-dropdown {
                    position: absolute;
                    background: white;
                    border: 1px solid #d1d5db;
                    border-radius: 6px;
                    box-shadow: 0 10px 25px rgba(0, 0, 0, 0.15);
                    max-height: 200px;
                    overflow-y: auto;
                    z-index: 99999 !important;
                    display: none;
                    min-width: 250px;
                    font-family: system-ui, -apple-system, sans-serif;
                }
                .direct-mention-item {
                    padding: 12px 16px;
                    cursor: pointer;
                    border-bottom: 1px solid #f3f4f6;
                    display: flex;
                    align-items: center;
                    gap: 10px;
                    transition: background-color 0.15s ease;
                }
                .direct-mention-item:hover,
                .direct-mention-item.selected {
                    background-color: #eff6ff !important;
                }
                .direct-mention-avatar {
                    width: 28px;
                    height: 28px;
                    border-radius: 50%;
                    background-color: #3b82f6;
                    color: white;
                    display: flex;
                    align-items: center;
                    justify-content: center;
                    font-size: 12px;
                    font-weight: bold;
                }
                .direct-mention-name {
                    font-weight: 600;
                    font-size: 14px;
                    color: #111827;
                }
            \`;
            document.head.appendChild(style);
            console.log('✅ Direct Mention Styles hinzugefügt');
        }

        // Funktionen definieren
        window.showDirectMentionDropdown = function(textarea, query, mentionStart) {
            const filteredUsers = window.mentionUsers.filter(user =>
                user.name.toLowerCase().includes(query.toLowerCase())
            ).slice(0, 5);
            
            if (filteredUsers.length === 0) {
                window.hideDirectMentionDropdown();
                return;
            }

            window.hideDirectMentionDropdown();

            const dropdown = document.createElement('div');
            dropdown.className = 'direct-mention-dropdown';
            window.currentMentionDropdown = dropdown;
            window.currentMentionTextarea = textarea;
            
            filteredUsers.forEach((user, index) => {
                const item = document.createElement('div');
                item.className = 'direct-mention-item' + (index === 0 ? ' selected' : '');
                item.dataset.userIndex = index;
                item.dataset.mentionStart = mentionStart;
                
                item.innerHTML = \`
                    <div class=\"direct-mention-avatar\">\${user.name.charAt(0).toUpperCase()}</div>
                    <div class=\"direct-mention-name\">\${user.name}</div>
                \`;
                
                item.addEventListener('click', () => {
                    window.selectDirectUser(textarea, user, mentionStart);
                });
                
                dropdown.appendChild(item);
            });

            const rect = textarea.getBoundingClientRect();
            dropdown.style.left = rect.left + 'px';
            dropdown.style.top = (rect.bottom + 5) + 'px';
            dropdown.style.display = 'block';
            
            document.body.appendChild(dropdown);
            console.log('✅ Direct Dropdown angezeigt');
        };

        window.hideDirectMentionDropdown = function() {
            if (window.currentMentionDropdown) {
                window.currentMentionDropdown.remove();
                window.currentMentionDropdown = null;
                window.currentMentionTextarea = null;
            }
        };

        window.selectDirectUser = function(textarea, user, mentionStart) {
            const text = textarea.value;
            const cursorPos = textarea.selectionStart;
            const beforeMention = text.substring(0, mentionStart);
            const afterCursor = text.substring(cursorPos);
            const newText = beforeMention + '@' + user.name + ' ' + afterCursor;
            
            textarea.value = newText;
            textarea.setSelectionRange(beforeMention.length + user.name.length + 2, beforeMention.length + user.name.length + 2);
            textarea.dispatchEvent(new Event('input', { bubbles: true }));
            
            window.hideDirectMentionDropdown();
            console.log('👤 Benutzer ausgewählt:', user.name);
        };

        window.navigateDirectDropdown = function(direction) {
            if (!window.currentMentionDropdown) return;
            const items = window.currentMentionDropdown.querySelectorAll('.direct-mention-item');
            const currentSelected = window.currentMentionDropdown.querySelector('.direct-mention-item.selected');
            let newIndex = 0;
            
            if (currentSelected) {
                const currentIndex = parseInt(currentSelected.dataset.userIndex);
                newIndex = direction === 'down' ?
                    Math.min(currentIndex + 1, items.length - 1) :
                    Math.max(currentIndex - 1, 0);
            }
            
            items.forEach((item, index) => {
                if (index === newIndex) {
                    item.classList.add('selected');
                } else {
                    item.classList.remove('selected');
                }
            });
        };

        window.confirmDirectSelection = function() {
            if (!window.currentMentionDropdown || !window.currentMentionTextarea) return;
            const selectedItem = window.currentMentionDropdown.querySelector('.direct-mention-item.selected');
            if (selectedItem) {
                const userIndex = parseInt(selectedItem.dataset.userIndex);
                const mentionStart = parseInt(selectedItem.dataset.mentionStart);
                const user = window.mentionUsers[userIndex];
                if (user) {
                    window.selectDirectUser(window.currentMentionTextarea, user, mentionStart);
                }
            }
        };
        ";
    }

    /**
     * Get the mention script as a string for inline injection
     */
    protected function getMentionScript(): string
    {
        return "
console.log('🚀 Direct Mention Script wird geladen...');

window.mentionUsers = [
    { id: 1, name: 'Thomas', email: 'thomas@example.com' },
    { id: 2, name: 'Administrator', email: 'admin@example.com' },
    { id: 3, name: 'Test User', email: 'test@example.com' },
    { id: 4, name: 'Thomas Kubitzek', email: 'thomas.kubitzek@example.com' }
];

window.currentMentionDropdown = null;
window.currentMentionTextarea = null;

// CSS Styles hinzufügen
if (!document.getElementById('direct-mention-styles')) {
    const style = document.createElement('style');
    style.id = 'direct-mention-styles';
    style.textContent = \`
        .direct-mention-dropdown {
            position: absolute;
            background: white;
            border: 1px solid #d1d5db;
            border-radius: 6px;
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.15);
            max-height: 200px;
            overflow-y: auto;
            z-index: 99999 !important;
            display: none;
            min-width: 250px;
            font-family: system-ui, -apple-system, sans-serif;
        }
        .direct-mention-item {
            padding: 12px 16px;
            cursor: pointer;
            border-bottom: 1px solid #f3f4f6;
            display: flex;
            align-items: center;
            gap: 10px;
            transition: background-color 0.15s ease;
        }
        .direct-mention-item:hover,
        .direct-mention-item.selected {
            background-color: #eff6ff !important;
        }
        .direct-mention-avatar {
            width: 28px;
            height: 28px;
            border-radius: 50%;
            background-color: #3b82f6;
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 12px;
            font-weight: bold;
        }
        .direct-mention-name {
            font-weight: 600;
            font-size: 14px;
            color: #111827;
        }
    \`;
    document.head.appendChild(style);
    console.log('✅ Direct Mention Styles hinzugefügt');
}

window.handleMentionInput = function(textarea, event) {
    console.log('📝 Direct Input Event:', textarea.value);
    
    const cursorPos = textarea.selectionStart;
    const text = textarea.value;
    
    let mentionStart = -1;
    for (let i = cursorPos - 1; i >= 0; i--) {
        if (text[i] === '@') {
            if (i === 0 || /\s/.test(text[i - 1])) {
                mentionStart = i;
                break;
            }
        } else if (/\s/.test(text[i])) {
            break;
        }
    }

    if (mentionStart !== -1) {
        const query = text.substring(mentionStart + 1, cursorPos);
        if (!/\s/.test(query)) {
            console.log('🔍 Zeige Dropdown für:', query);
            showDirectMentionDropdown(textarea, query, mentionStart);
            return;
        }
    }
    hideDirectMentionDropdown();
};

window.handleMentionKeydown = function(textarea, event) {
    console.log('⌨️ Direct Keydown:', event.key);
    if (!window.currentMentionDropdown) return;
    
    switch (event.key) {
        case 'ArrowDown':
            event.preventDefault();
            navigateDirectDropdown('down');
            break;
        case 'ArrowUp':
            event.preventDefault();
            navigateDirectDropdown('up');
            break;
        case 'Tab':
        case 'Enter':
            event.preventDefault();
            confirmDirectSelection();
            break;
        case 'Escape':
            event.preventDefault();
            hideDirectMentionDropdown();
            break;
    }
};

function showDirectMentionDropdown(textarea, query, mentionStart) {
    const filteredUsers = window.mentionUsers.filter(user =>
        user.name.toLowerCase().includes(query.toLowerCase())
    ).slice(0, 5);
    
    if (filteredUsers.length === 0) {
        hideDirectMentionDropdown();
        return;
    }

    hideDirectMentionDropdown();

    const dropdown = document.createElement('div');
    dropdown.className = 'direct-mention-dropdown';
    window.currentMentionDropdown = dropdown;
    window.currentMentionTextarea = textarea;
    
    filteredUsers.forEach((user, index) => {
        const item = document.createElement('div');
        item.className = 'direct-mention-item' + (index === 0 ? ' selected' : '');
        item.dataset.userIndex = index;
        item.dataset.mentionStart = mentionStart;
        
        item.innerHTML = \`
            <div class=\"direct-mention-avatar\">\${user.name.charAt(0).toUpperCase()}</div>
            <div class=\"direct-mention-name\">\${user.name}</div>
        \`;
        
        item.addEventListener('click', () => {
            selectDirectUser(textarea, user, mentionStart);
        });
        
        dropdown.appendChild(item);
    });

    const rect = textarea.getBoundingClientRect();
    dropdown.style.left = rect.left + 'px';
    dropdown.style.top = (rect.bottom + 5) + 'px';
    dropdown.style.display = 'block';
    
    document.body.appendChild(dropdown);
    console.log('✅ Direct Dropdown angezeigt');
}

function hideDirectMentionDropdown() {
    if (window.currentMentionDropdown) {
        window.currentMentionDropdown.remove();
        window.currentMentionDropdown = null;
        window.currentMentionTextarea = null;
    }
}

function selectDirectUser(textarea, user, mentionStart) {
    const text = textarea.value;
    const cursorPos = textarea.selectionStart;
    const beforeMention = text.substring(0, mentionStart);
    const afterCursor = text.substring(cursorPos);
    const newText = beforeMention + '@' + user.name + ' ' + afterCursor;
    
    textarea.value = newText;
    textarea.setSelectionRange(beforeMention.length + user.name.length + 2, beforeMention.length + user.name.length + 2);
    textarea.dispatchEvent(new Event('input', { bubbles: true }));
    
    hideDirectMentionDropdown();
    console.log('👤 Benutzer ausgewählt:', user.name);
}

function navigateDirectDropdown(direction) {
    if (!window.currentMentionDropdown) return;
    const items = window.currentMentionDropdown.querySelectorAll('.direct-mention-item');
    const currentSelected = window.currentMentionDropdown.querySelector('.direct-mention-item.selected');
    let newIndex = 0;
    
    if (currentSelected) {
        const currentIndex = parseInt(currentSelected.dataset.userIndex);
        newIndex = direction === 'down' ?
            Math.min(currentIndex + 1, items.length - 1) :
            Math.max(currentIndex - 1, 0);
    }
    
    items.forEach((item, index) => {
        if (index === newIndex) {
            item.classList.add('selected');
        } else {
            item.classList.remove('selected');
        }
    });
}

function confirmDirectSelection() {
    if (!window.currentMentionDropdown || !window.currentMentionTextarea) return;
    const selectedItem = window.currentMentionDropdown.querySelector('.direct-mention-item.selected');
    if (selectedItem) {
        const userIndex = parseInt(selectedItem.dataset.userIndex);
        const mentionStart = parseInt(selectedItem.dataset.mentionStart);
        const user = window.mentionUsers[userIndex];
        if (user) {
            selectDirectUser(window.currentMentionTextarea, user, mentionStart);
        }
    }
}

console.log('✅ Direct Mention Script geladen');
        ";
    }

    /**
     * Send email notification to mentioned user
     */
    protected function sendMentionNotification(User $user, TaskNote $note): void
    {
        try {
            \Log::info('📧 Versuche E-Mail-Benachrichtigung zu senden', [
                'user_id' => $user->id,
                'user_email' => $user->email,
                'user_name' => $user->name,
                'note_id' => $note->id,
                'task_id' => $note->task_id,
                'task_title' => $note->task->title,
                'author' => auth()->user()->name
            ]);

            // E-Mail-Adresse validieren
            if (!filter_var($user->email, FILTER_VALIDATE_EMAIL)) {
                \Log::warning('❌ Ungültige E-Mail-Adresse für Mention-Benachrichtigung', [
                    'user_id' => $user->id,
                    'email' => $user->email
                ]);
                return;
            }

            // Gmail Service verwenden
            $gmailService = app(\App\Services\GmailService::class);
            
            // E-Mail-Inhalt rendern
            $emailContent = view('emails.task-note-mention', [
                'user' => $user,
                'note' => $note,
                'task' => $note->task,
                'author' => auth()->user(),
                'mentionedUser' => $user,
                'taskUrl' => route('filament.admin.resources.tasks.view', [
                    'record' => $note->task_id,
                    'activeRelationManager' => 'notes'
                ])
            ])->render();

            $subject = "Neue Notiz - Aufgabe - {$note->task->title}";

            // E-Mail über Gmail Service senden
            $result = $gmailService->sendEmail(
                $user->email,
                $subject,
                $emailContent
            );

            if ($result) {
                \Log::info('✅ Task-Notiz Mention E-Mail erfolgreich gesendet', [
                    'user_id' => $user->id,
                    'user_email' => $user->email,
                    'note_id' => $note->id,
                    'task_id' => $note->task_id,
                    'subject' => $subject
                ]);
            } else {
                \Log::error('❌ Task-Notiz Mention E-Mail konnte nicht gesendet werden', [
                    'user_id' => $user->id,
                    'user_email' => $user->email,
                    'note_id' => $note->id,
                    'task_id' => $note->task_id
                ]);
            }

        } catch (\Exception $e) {
            \Log::error('❌ Fehler beim Senden der Task-Notiz Mention E-Mail', [
                'user_id' => $user->id,
                'user_email' => $user->email,
                'note_id' => $note->id,
                'task_id' => $note->task_id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
        }
    }
}