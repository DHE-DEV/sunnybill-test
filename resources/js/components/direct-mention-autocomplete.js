/**
 * Direct Mention Autocomplete - Direkte Implementation ohne Klassen
 * Funktioniert direkt im Browser ohne komplexe Initialisierung
 */

console.log('🚀 Direct Mention Autocomplete wird geladen...');

// Globale Variablen
let mentionUsers = [
    { id: 1, name: 'Thomas', email: 'thomas@example.com' },
    { id: 2, name: 'Administrator', email: 'admin@example.com' },
    { id: 3, name: 'Test User', email: 'test@example.com' },
    { id: 4, name: 'Thomas Kubitzek', email: 'thomas.kubitzek@example.com' }
];

let currentDropdown = null;
let currentTextarea = null;

// CSS Styles hinzufügen
function addMentionStyles() {
    if (document.getElementById('mention-styles')) return;
    
    const style = document.createElement('style');
    style.id = 'mention-styles';
    style.textContent = `
        .mention-dropdown-direct {
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
        
        .mention-item-direct {
            padding: 12px 16px;
            cursor: pointer;
            border-bottom: 1px solid #f3f4f6;
            display: flex;
            align-items: center;
            gap: 10px;
            transition: background-color 0.15s ease;
        }
        
        .mention-item-direct:last-child {
            border-bottom: none;
        }
        
        .mention-item-direct:hover,
        .mention-item-direct.selected {
            background-color: #eff6ff !important;
        }
        
        .mention-avatar-direct {
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
            flex-shrink: 0;
        }
        
        .mention-info-direct {
            flex: 1;
            min-width: 0;
        }
        
        .mention-name-direct {
            font-weight: 600;
            font-size: 14px;
            color: #111827;
            margin-bottom: 2px;
        }
        
        .mention-email-direct {
            font-size: 12px;
            color: #6b7280;
        }
    `;
    document.head.appendChild(style);
    console.log('✅ Mention Styles hinzugefügt');
}

// Dropdown erstellen
function createMentionDropdown() {
    const dropdown = document.createElement('div');
    dropdown.className = 'mention-dropdown-direct';
    dropdown.id = 'mention-dropdown-' + Date.now();
    document.body.appendChild(dropdown);
    return dropdown;
}

// Dropdown positionieren
function positionDropdown(textarea, dropdown) {
    const rect = textarea.getBoundingClientRect();
    const scrollTop = window.pageYOffset || document.documentElement.scrollTop;
    const scrollLeft = window.pageXOffset || document.documentElement.scrollLeft;
    
    dropdown.style.left = (rect.left + scrollLeft) + 'px';
    dropdown.style.top = (rect.bottom + scrollTop + 5) + 'px';
    dropdown.style.width = Math.max(250, rect.width) + 'px';
}

// Benutzer filtern
function filterUsers(query) {
    return mentionUsers.filter(user => 
        user.name.toLowerCase().includes(query.toLowerCase())
    ).slice(0, 5);
}

// Dropdown anzeigen
function showMentionDropdown(textarea, query, mentionStart) {
    console.log('🔍 Zeige Dropdown für Query:', query);
    
    const filteredUsers = filterUsers(query);
    if (filteredUsers.length === 0) {
        hideMentionDropdown();
        return;
    }

    // Alten Dropdown entfernen
    hideMentionDropdown();

    // Neuen Dropdown erstellen
    currentDropdown = createMentionDropdown();
    currentTextarea = textarea;
    
    // Dropdown füllen
    filteredUsers.forEach((user, index) => {
        const item = document.createElement('div');
        item.className = 'mention-item-direct' + (index === 0 ? ' selected' : '');
        item.dataset.userIndex = index;
        item.dataset.mentionStart = mentionStart;
        
        item.innerHTML = `
            <div class="mention-avatar-direct">${user.name.charAt(0).toUpperCase()}</div>
            <div class="mention-info-direct">
                <div class="mention-name-direct">${user.name}</div>
                <div class="mention-email-direct">${user.email}</div>
            </div>
        `;
        
        // Click Handler
        item.addEventListener('click', () => {
            selectUser(textarea, user, mentionStart);
        });
        
        currentDropdown.appendChild(item);
    });

    // Dropdown positionieren und anzeigen
    positionDropdown(textarea, currentDropdown);
    currentDropdown.style.display = 'block';
    
    console.log('✅ Dropdown angezeigt mit', filteredUsers.length, 'Benutzern');
}

// Dropdown verstecken
function hideMentionDropdown() {
    if (currentDropdown) {
        currentDropdown.remove();
        currentDropdown = null;
        currentTextarea = null;
    }
}

// Benutzer auswählen
function selectUser(textarea, user, mentionStart) {
    console.log('👤 Benutzer ausgewählt:', user.name);
    
    const text = textarea.value;
    const cursorPos = textarea.selectionStart;
    
    const beforeMention = text.substring(0, mentionStart);
    const afterCursor = text.substring(cursorPos);
    
    const newText = beforeMention + '@' + user.name + ' ' + afterCursor;
    const newCursorPos = beforeMention.length + user.name.length + 2;
    
    textarea.value = newText;
    textarea.setSelectionRange(newCursorPos, newCursorPos);
    
    // Events triggern
    textarea.dispatchEvent(new Event('input', { bubbles: true }));
    textarea.dispatchEvent(new Event('change', { bubbles: true }));
    
    hideMentionDropdown();
    textarea.focus();
}

// Navigation im Dropdown
function navigateDropdown(direction) {
    if (!currentDropdown) return;
    
    const items = currentDropdown.querySelectorAll('.mention-item-direct');
    const currentSelected = currentDropdown.querySelector('.mention-item-direct.selected');
    let newIndex = 0;
    
    if (currentSelected) {
        const currentIndex = parseInt(currentSelected.dataset.userIndex);
        if (direction === 'down') {
            newIndex = Math.min(currentIndex + 1, items.length - 1);
        } else {
            newIndex = Math.max(currentIndex - 1, 0);
        }
    }
    
    // Auswahl aktualisieren
    items.forEach((item, index) => {
        if (index === newIndex) {
            item.classList.add('selected');
            item.scrollIntoView({ block: 'nearest' });
        } else {
            item.classList.remove('selected');
        }
    });
}

// Ausgewählten Benutzer bestätigen
function confirmSelection() {
    if (!currentDropdown || !currentTextarea) return;
    
    const selectedItem = currentDropdown.querySelector('.mention-item-direct.selected');
    if (selectedItem) {
        const userIndex = parseInt(selectedItem.dataset.userIndex);
        const mentionStart = parseInt(selectedItem.dataset.mentionStart);
        const filteredUsers = filterUsers(''); // Alle Benutzer für Index
        
        if (filteredUsers[userIndex]) {
            selectUser(currentTextarea, filteredUsers[userIndex], mentionStart);
        }
    }
}

// Input Handler
function handleTextareaInput(e) {
    const textarea = e.target;
    if (textarea.tagName !== 'TEXTAREA') return;
    
    const cursorPos = textarea.selectionStart;
    const text = textarea.value;
    
    // @ suchen
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
        if (!/\s/.test(query) && query.length >= 0) {
            showMentionDropdown(textarea, query, mentionStart);
            return;
        }
    }

    hideMentionDropdown();
}

// Keyboard Handler
function handleTextareaKeydown(e) {
    if (!currentDropdown) return;
    
    switch (e.key) {
        case 'ArrowDown':
            e.preventDefault();
            navigateDropdown('down');
            break;
        case 'ArrowUp':
            e.preventDefault();
            navigateDropdown('up');
            break;
        case 'Tab':
        case 'Enter':
            e.preventDefault();
            confirmSelection();
            break;
        case 'Escape':
            e.preventDefault();
            hideMentionDropdown();
            break;
    }
}

// Event Listeners hinzufügen
function initializeEventListeners() {
    console.log('🎯 Event Listeners werden hinzugefügt...');
    
    // Input Events
    document.addEventListener('input', handleTextareaInput, true);
    document.addEventListener('keydown', handleTextareaKeydown, true);
    
    // Click außerhalb
    document.addEventListener('click', (e) => {
        if (!e.target.closest('.mention-dropdown-direct')) {
            hideMentionDropdown();
        }
    }, true);
    
    console.log('✅ Event Listeners hinzugefügt');
}

// Initialisierung
function initializeMentionAutocomplete() {
    console.log('🚀 Initialisiere Direct Mention Autocomplete...');
    
    addMentionStyles();
    initializeEventListeners();
    
    console.log('✅ Direct Mention Autocomplete initialisiert');
    console.log('📝 Verfügbare Benutzer:', mentionUsers.map(u => u.name));
    
    // Test-Funktion für Debugging
    window.testMentions = function() {
        console.log('🧪 Mention Test - Verfügbare Benutzer:', mentionUsers);
        console.log('🧪 Aktueller Dropdown:', currentDropdown);
        console.log('🧪 Styles vorhanden:', !!document.getElementById('mention-styles'));
    };
}

// Sofort initialisieren
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initializeMentionAutocomplete);
} else {
    initializeMentionAutocomplete();
}

// Auch bei späteren Ladungen
setTimeout(initializeMentionAutocomplete, 1000);
setTimeout(initializeMentionAutocomplete, 3000);

console.log('📦 Direct Mention Autocomplete Modul geladen');