<div>
<script>
console.log('🚀 Inline Mention Script wird geladen...');

// Globale Variablen für Mention-Funktionalität
window.mentionUsers = [
    { id: 1, name: 'Thomas', email: 'thomas@example.com' },
    { id: 2, name: 'Administrator', email: 'admin@example.com' },
    { id: 3, name: 'Test User', email: 'test@example.com' },
    { id: 4, name: 'Thomas Kubitzek', email: 'thomas.kubitzek@example.com' }
];

window.currentMentionDropdown = null;
window.currentMentionTextarea = null;

// CSS Styles hinzufügen
function addMentionStyles() {
    if (document.getElementById('inline-mention-styles')) return;
    
    const style = document.createElement('style');
    style.id = 'inline-mention-styles';
    style.textContent = `
        .inline-mention-dropdown {
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
        
        .inline-mention-item {
            padding: 12px 16px;
            cursor: pointer;
            border-bottom: 1px solid #f3f4f6;
            display: flex;
            align-items: center;
            gap: 10px;
            transition: background-color 0.15s ease;
        }
        
        .inline-mention-item:last-child {
            border-bottom: none;
        }
        
        .inline-mention-item:hover,
        .inline-mention-item.selected {
            background-color: #eff6ff !important;
        }
        
        .inline-mention-avatar {
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
        
        .inline-mention-info {
            flex: 1;
            min-width: 0;
        }
        
        .inline-mention-name {
            font-weight: 600;
            font-size: 14px;
            color: #111827;
            margin-bottom: 2px;
        }
        
        .inline-mention-email {
            font-size: 12px;
            color: #6b7280;
        }
    `;
    document.head.appendChild(style);
    console.log('✅ Inline Mention Styles hinzugefügt');
}

// Dropdown erstellen
function createInlineMentionDropdown() {
    const dropdown = document.createElement('div');
    dropdown.className = 'inline-mention-dropdown';
    dropdown.id = 'inline-mention-dropdown-' + Date.now();
    document.body.appendChild(dropdown);
    return dropdown;
}

// Dropdown positionieren
function positionInlineDropdown(textarea, dropdown) {
    const rect = textarea.getBoundingClientRect();
    const scrollTop = window.pageYOffset || document.documentElement.scrollTop;
    const scrollLeft = window.pageXOffset || document.documentElement.scrollLeft;
    
    dropdown.style.left = (rect.left + scrollLeft) + 'px';
    dropdown.style.top = (rect.bottom + scrollTop + 5) + 'px';
    dropdown.style.width = Math.max(250, rect.width) + 'px';
}

// Benutzer filtern
function filterInlineUsers(query) {
    return window.mentionUsers.filter(user => 
        user.name.toLowerCase().includes(query.toLowerCase())
    ).slice(0, 5);
}

// Dropdown anzeigen
function showInlineMentionDropdown(textarea, query, mentionStart) {
    console.log('🔍 Zeige Inline Dropdown für Query:', query);
    
    const filteredUsers = filterInlineUsers(query);
    if (filteredUsers.length === 0) {
        hideInlineMentionDropdown();
        return;
    }

    // Alten Dropdown entfernen
    hideInlineMentionDropdown();

    // Neuen Dropdown erstellen
    window.currentMentionDropdown = createInlineMentionDropdown();
    window.currentMentionTextarea = textarea;
    
    // Dropdown füllen
    filteredUsers.forEach((user, index) => {
        const item = document.createElement('div');
        item.className = 'inline-mention-item' + (index === 0 ? ' selected' : '');
        item.dataset.userIndex = index;
        item.dataset.mentionStart = mentionStart;
        
        item.innerHTML = `
            <div class="inline-mention-avatar">${user.name.charAt(0).toUpperCase()}</div>
            <div class="inline-mention-info">
                <div class="inline-mention-name">${user.name}</div>
                <div class="inline-mention-email">${user.email}</div>
            </div>
        `;
        
        // Click Handler
        item.addEventListener('click', () => {
            selectInlineUser(textarea, user, mentionStart);
        });
        
        window.currentMentionDropdown.appendChild(item);
    });

    // Dropdown positionieren und anzeigen
    positionInlineDropdown(textarea, window.currentMentionDropdown);
    window.currentMentionDropdown.style.display = 'block';
    
    console.log('✅ Inline Dropdown angezeigt mit', filteredUsers.length, 'Benutzern');
}

// Dropdown verstecken
function hideInlineMentionDropdown() {
    if (window.currentMentionDropdown) {
        window.currentMentionDropdown.remove();
        window.currentMentionDropdown = null;
        window.currentMentionTextarea = null;
    }
}

// Benutzer auswählen
function selectInlineUser(textarea, user, mentionStart) {
    console.log('👤 Inline Benutzer ausgewählt:', user.name);
    
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
    
    hideInlineMentionDropdown();
    textarea.focus();
}

// Navigation im Dropdown
function navigateInlineDropdown(direction) {
    if (!window.currentMentionDropdown) return;
    
    const items = window.currentMentionDropdown.querySelectorAll('.inline-mention-item');
    const currentSelected = window.currentMentionDropdown.querySelector('.inline-mention-item.selected');
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
function confirmInlineSelection() {
    if (!window.currentMentionDropdown || !window.currentMentionTextarea) return;
    
    const selectedItem = window.currentMentionDropdown.querySelector('.inline-mention-item.selected');
    if (selectedItem) {
        const userIndex = parseInt(selectedItem.dataset.userIndex);
        const mentionStart = parseInt(selectedItem.dataset.mentionStart);
        const filteredUsers = filterInlineUsers(''); // Alle Benutzer für Index
        
        if (filteredUsers[userIndex]) {
            selectInlineUser(window.currentMentionTextarea, filteredUsers[userIndex], mentionStart);
        }
    }
}

// Globale Handler-Funktionen
window.handleMentionInput = function(textarea, event) {
    console.log('📝 Input Event auf Textarea:', textarea.id);
    
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
            showInlineMentionDropdown(textarea, query, mentionStart);
            return;
        }
    }

    hideInlineMentionDropdown();
};

window.handleMentionKeydown = function(textarea, event) {
    console.log('⌨️ Keydown Event:', event.key);
    
    if (!window.currentMentionDropdown) return;
    
    switch (event.key) {
        case 'ArrowDown':
            event.preventDefault();
            navigateInlineDropdown('down');
            break;
        case 'ArrowUp':
            event.preventDefault();
            navigateInlineDropdown('up');
            break;
        case 'Tab':
        case 'Enter':
            event.preventDefault();
            confirmInlineSelection();
            break;
        case 'Escape':
            event.preventDefault();
            hideInlineMentionDropdown();
            break;
    }
};

// Initialisierung
function initializeInlineMentions() {
    console.log('🚀 Initialisiere Inline Mentions...');
    
    addMentionStyles();
    
    // Click außerhalb Handler
    document.addEventListener('click', (e) => {
        if (!e.target.closest('.inline-mention-dropdown')) {
            hideInlineMentionDropdown();
        }
    }, true);
    
    console.log('✅ Inline Mentions initialisiert');
    console.log('📝 Verfügbare Benutzer:', window.mentionUsers.map(u => u.name));
    
    // Test-Funktion
    window.testInlineMentions = function() {
        console.log('🧪 Inline Mention Test - Verfügbare Benutzer:', window.mentionUsers);
        console.log('🧪 Aktueller Dropdown:', window.currentMentionDropdown);
        console.log('🧪 Styles vorhanden:', !!document.getElementById('inline-mention-styles'));
    };
}

// Sofort initialisieren
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initializeInlineMentions);
} else {
    initializeInlineMentions();
}

// Auch bei späteren Ladungen
setTimeout(initializeInlineMentions, 500);
setTimeout(initializeInlineMentions, 2000);

console.log('📦 Inline Mention Script geladen');
</script>
</div>