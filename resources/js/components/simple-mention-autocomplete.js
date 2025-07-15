/**
 * Simple Mention Autocomplete for Filament
 * A simplified version that works better with Filament's dynamic content
 */

class SimpleMentionAutocomplete {
    constructor() {
        this.users = [];
        this.loadUsers();
        this.initializeEventListeners();
    }

    async loadUsers() {
        try {
            // Try to load users from API
            const response = await fetch('/api/users/all', {
                headers: {
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                }
            });
            
            if (response.ok) {
                this.users = await response.json();
                console.log('Loaded users for mentions:', this.users.length);
            } else {
                console.warn('Could not load users from API, using fallback');
                // Fallback users for testing
                this.users = [
                    { id: 1, name: 'Thomas', email: 'thomas@example.com' },
                    { id: 2, name: 'Administrator', email: 'admin@example.com' },
                    { id: 3, name: 'Test User', email: 'test@example.com' }
                ];
            }
        } catch (error) {
            console.error('Error loading users:', error);
            // Fallback users
            this.users = [
                { id: 1, name: 'Thomas', email: 'thomas@example.com' },
                { id: 2, name: 'Administrator', email: 'admin@example.com' }
            ];
        }
    }

    initializeEventListeners() {
        // Use event delegation to handle dynamically added textareas
        document.addEventListener('input', (e) => {
            if (e.target.tagName === 'TEXTAREA') {
                this.handleTextareaInput(e.target, e);
            }
        });

        document.addEventListener('keydown', (e) => {
            if (e.target.tagName === 'TEXTAREA') {
                this.handleTextareaKeydown(e.target, e);
            }
        });

        // Clean up dropdowns when clicking outside
        document.addEventListener('click', (e) => {
            if (!e.target.closest('.mention-dropdown')) {
                this.hideAllDropdowns();
            }
        });
    }

    handleTextareaInput(textarea, event) {
        const cursorPos = textarea.selectionStart;
        const text = textarea.value;
        
        // Find the last @ before cursor
        let mentionStart = -1;
        for (let i = cursorPos - 1; i >= 0; i--) {
            if (text[i] === '@') {
                // Check if @ is at start or preceded by whitespace
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
            
            // Only show dropdown if query doesn't contain spaces
            if (!/\s/.test(query)) {
                this.showMentionDropdown(textarea, query, mentionStart);
                return;
            }
        }

        this.hideMentionDropdown(textarea);
    }

    handleTextareaKeydown(textarea, event) {
        const dropdown = textarea.mentionDropdown;
        if (!dropdown || dropdown.style.display === 'none') return;

        const items = dropdown.querySelectorAll('.mention-item');
        let selectedIndex = parseInt(dropdown.dataset.selectedIndex || '0');

        switch (event.key) {
            case 'ArrowDown':
                event.preventDefault();
                selectedIndex = Math.min(selectedIndex + 1, items.length - 1);
                this.updateSelection(dropdown, selectedIndex);
                break;
                
            case 'ArrowUp':
                event.preventDefault();
                selectedIndex = Math.max(selectedIndex - 1, 0);
                this.updateSelection(dropdown, selectedIndex);
                break;
                
            case 'Tab':
            case 'Enter':
                event.preventDefault();
                if (items[selectedIndex]) {
                    this.selectUser(textarea, items[selectedIndex].dataset.user);
                }
                break;
                
            case 'Escape':
                event.preventDefault();
                this.hideMentionDropdown(textarea);
                break;
        }
    }

    showMentionDropdown(textarea, query, mentionStart) {
        const filteredUsers = this.users.filter(user => 
            user.name.toLowerCase().includes(query.toLowerCase())
        ).slice(0, 5);

        if (filteredUsers.length === 0) {
            this.hideMentionDropdown(textarea);
            return;
        }

        let dropdown = textarea.mentionDropdown;
        if (!dropdown) {
            dropdown = this.createDropdown();
            textarea.mentionDropdown = dropdown;
            document.body.appendChild(dropdown);
        }

        dropdown.innerHTML = '';
        dropdown.dataset.mentionStart = mentionStart;
        dropdown.dataset.selectedIndex = '0';

        filteredUsers.forEach((user, index) => {
            const item = document.createElement('div');
            item.className = `mention-item ${index === 0 ? 'selected' : ''}`;
            item.dataset.user = JSON.stringify(user);
            item.innerHTML = `
                <div class="user-avatar">${user.name.charAt(0).toUpperCase()}</div>
                <div class="user-info">
                    <div class="user-name">${user.name}</div>
                    <div class="user-email">${user.email}</div>
                </div>
            `;
            
            item.addEventListener('click', () => {
                this.selectUser(textarea, JSON.stringify(user));
            });
            
            dropdown.appendChild(item);
        });

        this.positionDropdown(textarea, dropdown);
        dropdown.style.display = 'block';
    }

    hideMentionDropdown(textarea) {
        if (textarea.mentionDropdown) {
            textarea.mentionDropdown.style.display = 'none';
        }
    }

    hideAllDropdowns() {
        document.querySelectorAll('.mention-dropdown').forEach(dropdown => {
            dropdown.style.display = 'none';
        });
    }

    createDropdown() {
        const dropdown = document.createElement('div');
        dropdown.className = 'mention-dropdown';
        dropdown.style.cssText = `
            position: absolute;
            background: white;
            border: 1px solid #d1d5db;
            border-radius: 6px;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
            max-height: 200px;
            overflow-y: auto;
            z-index: 9999;
            display: none;
            min-width: 200px;
        `;
        return dropdown;
    }

    updateSelection(dropdown, selectedIndex) {
        const items = dropdown.querySelectorAll('.mention-item');
        items.forEach((item, index) => {
            if (index === selectedIndex) {
                item.classList.add('selected');
                item.style.backgroundColor = '#eff6ff';
                item.scrollIntoView({ block: 'nearest' });
            } else {
                item.classList.remove('selected');
                item.style.backgroundColor = '';
            }
        });
        dropdown.dataset.selectedIndex = selectedIndex;
    }

    selectUser(textarea, userJson) {
        const user = JSON.parse(userJson);
        const dropdown = textarea.mentionDropdown;
        const mentionStart = parseInt(dropdown.dataset.mentionStart);
        
        const text = textarea.value;
        const beforeMention = text.substring(0, mentionStart);
        const afterCursor = text.substring(textarea.selectionStart);
        
        // Replace @query with @username and add space
        const newText = beforeMention + '@' + user.name + ' ' + afterCursor;
        const newCursorPos = beforeMention.length + user.name.length + 2; // +2 for @ and space
        
        textarea.value = newText;
        textarea.setSelectionRange(newCursorPos, newCursorPos);
        
        // Trigger input event to notify form systems
        textarea.dispatchEvent(new Event('input', { bubbles: true }));
        
        this.hideMentionDropdown(textarea);
        textarea.focus();
    }

    positionDropdown(textarea, dropdown) {
        const rect = textarea.getBoundingClientRect();
        dropdown.style.left = `${rect.left}px`;
        dropdown.style.top = `${rect.bottom + 5}px`;
    }
}

// Initialize when DOM is ready
document.addEventListener('DOMContentLoaded', function() {
    console.log('Initializing Simple Mention Autocomplete');
    window.simpleMentionAutocomplete = new SimpleMentionAutocomplete();
});

// Add CSS styles
const style = document.createElement('style');
style.textContent = `
    .mention-dropdown {
        font-family: system-ui, -apple-system, sans-serif;
    }
    
    .mention-item {
        padding: 8px 12px;
        cursor: pointer;
        border-bottom: 1px solid #f3f4f6;
        display: flex;
        align-items: center;
        gap: 8px;
        transition: background-color 0.15s ease-in-out;
    }
    
    .mention-item:last-child {
        border-bottom: none;
    }
    
    .mention-item:hover,
    .mention-item.selected {
        background-color: #eff6ff !important;
    }
    
    .mention-item .user-avatar {
        width: 24px;
        height: 24px;
        border-radius: 50%;
        background-color: #3b82f6;
        color: white;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 10px;
        font-weight: bold;
        flex-shrink: 0;
    }
    
    .mention-item .user-info {
        flex: 1;
        min-width: 0;
    }
    
    .mention-item .user-name {
        font-weight: 500;
        font-size: 14px;
        color: #111827;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }
    
    .mention-item .user-email {
        font-size: 12px;
        color: #6b7280;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }
`;
document.head.appendChild(style);