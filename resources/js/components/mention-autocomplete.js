/**
 * Mention Autocomplete Component
 * Provides @mention functionality with keyboard navigation
 */
class MentionAutocomplete {
    constructor(textarea) {
        this.textarea = textarea;
        this.dropdown = null;
        this.users = [];
        this.filteredUsers = [];
        this.selectedIndex = -1;
        this.mentionStart = -1;
        this.mentionQuery = '';
        this.isVisible = false;
        
        this.init();
    }

    init() {
        this.loadUsers();
        this.bindEvents();
        this.createDropdown();
    }

    async loadUsers() {
        try {
            // Load users from API endpoint
            const response = await fetch('/api/users/search');
            this.users = await response.json();
        } catch (error) {
            console.error('Failed to load users:', error);
            // Fallback: try to get users from a global variable if available
            this.users = window.appUsers || [];
        }
    }

    createDropdown() {
        this.dropdown = document.createElement('div');
        this.dropdown.className = 'mention-dropdown';
        this.dropdown.style.cssText = `
            position: absolute;
            background: white;
            border: 1px solid #d1d5db;
            border-radius: 6px;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
            max-height: 200px;
            overflow-y: auto;
            z-index: 1000;
            display: none;
            min-width: 200px;
        `;
        document.body.appendChild(this.dropdown);
    }

    bindEvents() {
        this.textarea.addEventListener('input', (e) => this.handleInput(e));
        this.textarea.addEventListener('keydown', (e) => this.handleKeydown(e));
        this.textarea.addEventListener('blur', (e) => this.handleBlur(e));
        
        // Close dropdown when clicking outside
        document.addEventListener('click', (e) => {
            if (!this.dropdown.contains(e.target) && e.target !== this.textarea) {
                this.hideDropdown();
            }
        });
    }

    handleInput(e) {
        const cursorPos = this.textarea.selectionStart;
        const text = this.textarea.value;
        
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
                this.mentionStart = mentionStart;
                this.mentionQuery = query;
                this.filterUsers(query);
                this.showDropdown();
                return;
            }
        }

        this.hideDropdown();
    }

    handleKeydown(e) {
        if (!this.isVisible) return;

        switch (e.key) {
            case 'ArrowDown':
                e.preventDefault();
                this.selectedIndex = Math.min(this.selectedIndex + 1, this.filteredUsers.length - 1);
                this.updateSelection();
                break;
                
            case 'ArrowUp':
                e.preventDefault();
                this.selectedIndex = Math.max(this.selectedIndex - 1, -1);
                this.updateSelection();
                break;
                
            case 'Tab':
            case 'Enter':
                e.preventDefault();
                if (this.selectedIndex >= 0) {
                    this.selectUser(this.filteredUsers[this.selectedIndex]);
                }
                break;
                
            case 'Escape':
                e.preventDefault();
                this.hideDropdown();
                break;
        }
    }

    handleBlur(e) {
        // Delay hiding to allow clicking on dropdown items
        setTimeout(() => {
            if (!this.dropdown.matches(':hover')) {
                this.hideDropdown();
            }
        }, 150);
    }

    filterUsers(query) {
        const lowerQuery = query.toLowerCase();
        this.filteredUsers = this.users.filter(user => 
            user.name.toLowerCase().includes(lowerQuery) ||
            (user.email && user.email.toLowerCase().includes(lowerQuery))
        ).slice(0, 10); // Limit to 10 results
        
        this.selectedIndex = this.filteredUsers.length > 0 ? 0 : -1;
    }

    showDropdown() {
        if (this.filteredUsers.length === 0) {
            this.hideDropdown();
            return;
        }

        this.renderDropdown();
        this.positionDropdown();
        this.dropdown.style.display = 'block';
        this.isVisible = true;
    }

    hideDropdown() {
        this.dropdown.style.display = 'none';
        this.isVisible = false;
        this.selectedIndex = -1;
    }

    renderDropdown() {
        this.dropdown.innerHTML = '';
        
        this.filteredUsers.forEach((user, index) => {
            const item = document.createElement('div');
            item.className = `mention-item ${index === this.selectedIndex ? 'selected' : ''}`;
            item.style.cssText = `
                padding: 8px 12px;
                cursor: pointer;
                border-bottom: 1px solid #f3f4f6;
                display: flex;
                align-items: center;
                gap: 8px;
            `;
            
            if (index === this.selectedIndex) {
                item.style.backgroundColor = '#eff6ff';
            }
            
            // Create user avatar (initials)
            const avatar = document.createElement('div');
            avatar.style.cssText = `
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
            `;
            avatar.textContent = user.name.charAt(0).toUpperCase();
            
            const userInfo = document.createElement('div');
            userInfo.innerHTML = `
                <div style="font-weight: 500; font-size: 14px;">${this.escapeHtml(user.name)}</div>
                ${user.email ? `<div style="font-size: 12px; color: #6b7280;">${this.escapeHtml(user.email)}</div>` : ''}
            `;
            
            item.appendChild(avatar);
            item.appendChild(userInfo);
            
            item.addEventListener('click', () => this.selectUser(user));
            item.addEventListener('mouseenter', () => {
                this.selectedIndex = index;
                this.updateSelection();
            });
            
            this.dropdown.appendChild(item);
        });
    }

    updateSelection() {
        const items = this.dropdown.querySelectorAll('.mention-item');
        items.forEach((item, index) => {
            if (index === this.selectedIndex) {
                item.classList.add('selected');
                item.style.backgroundColor = '#eff6ff';
                item.scrollIntoView({ block: 'nearest' });
            } else {
                item.classList.remove('selected');
                item.style.backgroundColor = '';
            }
        });
    }

    selectUser(user) {
        const text = this.textarea.value;
        const beforeMention = text.substring(0, this.mentionStart);
        const afterCursor = text.substring(this.textarea.selectionStart);
        
        // Replace @query with @username and add space
        const newText = beforeMention + '@' + user.name + ' ' + afterCursor;
        const newCursorPos = beforeMention.length + user.name.length + 2; // +2 for @ and space
        
        this.textarea.value = newText;
        this.textarea.setSelectionRange(newCursorPos, newCursorPos);
        
        // Trigger input event to notify form systems
        this.textarea.dispatchEvent(new Event('input', { bubbles: true }));
        
        this.hideDropdown();
        this.textarea.focus();
    }

    positionDropdown() {
        const textareaRect = this.textarea.getBoundingClientRect();
        const cursorPos = this.getCursorPosition();
        
        this.dropdown.style.left = `${textareaRect.left + cursorPos.x}px`;
        this.dropdown.style.top = `${textareaRect.top + cursorPos.y + 20}px`;
    }

    getCursorPosition() {
        // Create a temporary div to measure text
        const div = document.createElement('div');
        const style = window.getComputedStyle(this.textarea);
        
        div.style.cssText = `
            position: absolute;
            visibility: hidden;
            white-space: pre-wrap;
            word-wrap: break-word;
            font-family: ${style.fontFamily};
            font-size: ${style.fontSize};
            font-weight: ${style.fontWeight};
            line-height: ${style.lineHeight};
            letter-spacing: ${style.letterSpacing};
            padding: ${style.padding};
            border: ${style.border};
            width: ${this.textarea.offsetWidth}px;
        `;
        
        document.body.appendChild(div);
        
        const textBeforeCursor = this.textarea.value.substring(0, this.mentionStart);
        div.textContent = textBeforeCursor;
        
        const span = document.createElement('span');
        span.textContent = '@';
        div.appendChild(span);
        
        const rect = span.getBoundingClientRect();
        const textareaRect = this.textarea.getBoundingClientRect();
        
        document.body.removeChild(div);
        
        return {
            x: rect.left - textareaRect.left,
            y: rect.top - textareaRect.top
        };
    }

    escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }

    destroy() {
        if (this.dropdown && this.dropdown.parentNode) {
            this.dropdown.parentNode.removeChild(this.dropdown);
        }
    }
}

// Auto-initialize for textareas with mention support
document.addEventListener('DOMContentLoaded', function() {
    initializeMentionTextareas();
});

// Function to initialize mention textareas
function initializeMentionTextareas() {
    const textareas = document.querySelectorAll('textarea[data-mention-enabled="true"]');
    textareas.forEach(textarea => {
        if (!textarea.mentionAutocomplete) {
            textarea.mentionAutocomplete = new MentionAutocomplete(textarea);
        }
    });
}

// Watch for dynamically added textareas (Filament modals)
const observer = new MutationObserver(function(mutations) {
    let shouldCheck = false;
    
    mutations.forEach(function(mutation) {
        if (mutation.type === 'childList') {
            mutation.addedNodes.forEach(function(node) {
                if (node.nodeType === Node.ELEMENT_NODE) {
                    // Check if the added node contains textareas or is a textarea itself
                    if (node.tagName === 'TEXTAREA' || node.querySelector) {
                        shouldCheck = true;
                    }
                }
            });
        }
    });
    
    if (shouldCheck) {
        // Delay to ensure DOM is ready
        setTimeout(initializeMentionTextareas, 100);
    }
});

// Start observing
observer.observe(document.body, {
    childList: true,
    subtree: true
});

// Export for manual initialization
window.MentionAutocomplete = MentionAutocomplete;