<?php

namespace App\Filament\Resources\TaskResource\Pages;

use App\Filament\Resources\TaskResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;
use Illuminate\Contracts\View\View;

class ViewTask extends ViewRecord
{
    protected static string $resource = TaskResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make(),
        ];
    }

    public function getFooter(): ?View
    {
        return view('filament.components.mention-script', [
            'script' => $this->getMentionScript()
        ]);
    }

    private function getMentionScript(): string
    {
        return '
        <script>
        console.log("🚀 ViewTask: Mention Script wird geladen...");
        
        // Warten bis DOM geladen ist
        document.addEventListener("DOMContentLoaded", function() {
            console.log("📄 ViewTask: DOM Content Loaded");
            initializeMentionSystem();
        });

        // Auch für dynamisch geladene Inhalte
        if (document.readyState === "loading") {
            document.addEventListener("DOMContentLoaded", initializeMentionSystem);
        } else {
            initializeMentionSystem();
        }

        function initializeMentionSystem() {
            console.log("🔧 ViewTask: Initialisiere Mention System...");
            
            // Observer für dynamisch hinzugefügte Textareas
            const observer = new MutationObserver(function(mutations) {
                mutations.forEach(function(mutation) {
                    if (mutation.type === "childList") {
                        mutation.addedNodes.forEach(function(node) {
                            if (node.nodeType === Node.ELEMENT_NODE) {
                                // Suche nach Textareas in neuen Nodes
                                const textareas = node.querySelectorAll ? node.querySelectorAll("textarea") : [];
                                textareas.forEach(setupMentionForTextarea);
                                
                                // Prüfe auch das Node selbst
                                if (node.tagName === "TEXTAREA") {
                                    setupMentionForTextarea(node);
                                }
                            }
                        });
                    }
                });
            });

            // Observer starten
            observer.observe(document.body, {
                childList: true,
                subtree: true
            });

            // Bereits vorhandene Textareas einrichten
            document.querySelectorAll("textarea").forEach(setupMentionForTextarea);
            
            console.log("✅ ViewTask: Mention System initialisiert");
        }

        function setupMentionForTextarea(textarea) {
            // Prüfe ob es ein Notiz-Textarea ist
            if (!textarea.id || !textarea.id.includes("content")) {
                return;
            }
            
            console.log("🎯 ViewTask: Setup für Textarea:", textarea.id);
            
            // Verhindere doppelte Initialisierung
            if (textarea.hasAttribute("data-mention-initialized")) {
                return;
            }
            textarea.setAttribute("data-mention-initialized", "true");
            
            // Visueller Hinweis
            textarea.style.border = "2px solid #3b82f6";
            textarea.style.borderRadius = "6px";
            
            // Event Listeners
            textarea.addEventListener("input", function(e) {
                console.log("📝 ViewTask Input Event - Value:", e.target.value);
                handleMentionInput(e);
            });
            
            textarea.addEventListener("keydown", function(e) {
                console.log("⌨️ ViewTask KeyDown Event:", e.key);
                handleMentionKeydown(e);
            });
            
            textarea.addEventListener("focus", function(e) {
                console.log("🎯 ViewTask: Textarea focused -", e.target.id);
            });
        }

        function handleMentionInput(event) {
            const textarea = event.target;
            const value = textarea.value;
            const cursorPos = textarea.selectionStart;
            
            console.log("📝 ViewTask: Input Event - Cursor:", cursorPos, "Value:", value);
            
            // Suche nach @ vor dem Cursor
            const textBeforeCursor = value.substring(0, cursorPos);
            const atMatch = textBeforeCursor.match(/@([a-zA-Z0-9]*)$/);
            
            if (atMatch) {
                const searchTerm = atMatch[1];
                console.log("🔍 ViewTask: @ gefunden, Suchbegriff:", searchTerm);
                showMentionDropdown(textarea, searchTerm, cursorPos);
            } else {
                hideMentionDropdown();
            }
        }

        function handleMentionKeydown(event) {
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
                        selectMention(items[selectedIndex], event.target);
                    }
                    break;
                case "Escape":
                    hideMentionDropdown();
                    break;
            }
        }

        function showMentionDropdown(textarea, searchTerm, cursorPos) {
            console.log("🔍 ViewTask: Zeige Dropdown für:", searchTerm);
            
            // Lade Benutzer
            fetch("/api/users/search?q=" + encodeURIComponent(searchTerm))
                .then(response => response.json())
                .then(users => {
                    console.log("👥 ViewTask: Benutzer geladen:", users);
                    createDropdown(textarea, users, cursorPos);
                })
                .catch(error => {
                    console.error("❌ ViewTask: Fehler beim Laden der Benutzer:", error);
                });
        }

        function createDropdown(textarea, users, cursorPos) {
            // Entferne vorhandenes Dropdown
            hideMentionDropdown();
            
            if (users.length === 0) {
                console.log("📭 ViewTask: Keine Benutzer gefunden");
                return;
            }
            
            const dropdown = document.createElement("div");
            dropdown.id = "mention-dropdown";
            dropdown.style.cssText = `
                position: absolute;
                background: white;
                border: 1px solid #d1d5db;
                border-radius: 6px;
                box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
                max-height: 200px;
                overflow-y: auto;
                z-index: 1000;
                min-width: 200px;
            `;
            
            users.forEach((user, index) => {
                const item = document.createElement("div");
                item.className = "mention-item" + (index === 0 ? " selected" : "");
                item.style.cssText = `
                    padding: 8px 12px;
                    cursor: pointer;
                    border-bottom: 1px solid #f3f4f6;
                `;
                item.textContent = user.name;
                item.dataset.username = user.name;
                
                item.addEventListener("mouseenter", () => {
                    document.querySelectorAll(".mention-item").forEach(i => i.classList.remove("selected"));
                    item.classList.add("selected");
                });
                
                item.addEventListener("click", () => {
                    selectMention(item, textarea);
                });
                
                dropdown.appendChild(item);
            });
            
            // Positionierung
            const rect = textarea.getBoundingClientRect();
            dropdown.style.left = rect.left + "px";
            dropdown.style.top = (rect.bottom + 5) + "px";
            
            document.body.appendChild(dropdown);
            
            // Style für selected item
            const style = document.createElement("style");
            style.textContent = `
                .mention-item.selected {
                    background-color: #3b82f6 !important;
                    color: white !important;
                }
                .mention-item:hover {
                    background-color: #f3f4f6;
                }
                .mention-item.selected:hover {
                    background-color: #3b82f6 !important;
                    color: white !important;
                }
            `;
            document.head.appendChild(style);
            
            console.log("✅ ViewTask: Dropdown angezeigt mit", users.length, "Benutzern");
        }

        function updateSelection(items, selectedIndex) {
            items.forEach((item, index) => {
                item.classList.toggle("selected", index === selectedIndex);
            });
        }

        function selectMention(item, textarea) {
            const username = item.dataset.username;
            const value = textarea.value;
            const cursorPos = textarea.selectionStart;
            
            // Finde @ Position
            const textBeforeCursor = value.substring(0, cursorPos);
            const atIndex = textBeforeCursor.lastIndexOf("@");
            
            if (atIndex !== -1) {
                const newValue = value.substring(0, atIndex) + "@" + username + " " + value.substring(cursorPos);
                textarea.value = newValue;
                
                // Cursor nach dem eingefügten Namen setzen
                const newCursorPos = atIndex + username.length + 2;
                textarea.setSelectionRange(newCursorPos, newCursorPos);
                
                // Input Event triggern für Filament
                textarea.dispatchEvent(new Event("input", { bubbles: true }));
                
                console.log("✅ ViewTask: Mention eingefügt:", username);
            }
            
            hideMentionDropdown();
            textarea.focus();
        }

        function hideMentionDropdown() {
            const dropdown = document.getElementById("mention-dropdown");
            if (dropdown) {
                dropdown.remove();
            }
        }

        // Debug-Funktion für die Konsole
        window.debugMentions = function() {
            console.log("🔧 ViewTask Debug Info:");
            console.log("- Textareas gefunden:", document.querySelectorAll("textarea").length);
            console.log("- Mention-initialisierte Textareas:", document.querySelectorAll("textarea[data-mention-initialized]").length);
            console.log("- Dropdown vorhanden:", !!document.getElementById("mention-dropdown"));
        };
        
        console.log("✅ ViewTask: Mention Script vollständig geladen");
        console.log("💡 ViewTask: Verwende window.debugMentions() für Debug-Infos");
        </script>';
    }
}