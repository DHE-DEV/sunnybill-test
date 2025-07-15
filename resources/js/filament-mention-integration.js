/**
 * Filament Mention Integration
 * Integrates mention autocomplete with Filament forms
 */

// Wait for Filament to be ready
document.addEventListener('DOMContentLoaded', function() {
    console.log('Filament Mention Integration loaded');
    
    // Initialize mention autocomplete for existing textareas
    initializeMentionAutocomplete();
    
    // Handle Filament-specific events
    setupFilamentEventHandlers();
});

function initializeMentionAutocomplete() {
    console.log('Initializing mention autocomplete...');
    
    // Look for textareas with mention support
    const textareas = document.querySelectorAll('textarea[data-mention-enabled="true"]');
    console.log('Found textareas with mention support:', textareas.length);
    
    textareas.forEach(textarea => {
        if (!textarea.mentionAutocomplete) {
            console.log('Initializing mention autocomplete for textarea:', textarea);
            textarea.mentionAutocomplete = new window.MentionAutocomplete(textarea);
        }
    });
    
    // Also look for textareas by ID (fallback)
    const taskNoteContent = document.getElementById('task-note-content');
    if (taskNoteContent && !taskNoteContent.mentionAutocomplete) {
        console.log('Initializing mention autocomplete for task-note-content');
        taskNoteContent.setAttribute('data-mention-enabled', 'true');
        taskNoteContent.mentionAutocomplete = new window.MentionAutocomplete(taskNoteContent);
    }
    
    // Look for any textarea in forms that might be for notes
    const allTextareas = document.querySelectorAll('textarea');
    allTextareas.forEach(textarea => {
        // Check if this textarea is likely for notes/content
        const label = textarea.closest('.fi-fo-field-wrp')?.querySelector('label');
        const labelText = label?.textContent?.toLowerCase() || '';
        
        if ((labelText.includes('inhalt') || labelText.includes('notiz') || labelText.includes('content'))
            && !textarea.mentionAutocomplete) {
            console.log('Auto-enabling mention for textarea with label:', labelText);
            textarea.setAttribute('data-mention-enabled', 'true');
            textarea.mentionAutocomplete = new window.MentionAutocomplete(textarea);
        }
    });
}

function setupFilamentEventHandlers() {
    // Handle Filament modal opens
    document.addEventListener('click', function(e) {
        // Check if a modal trigger was clicked
        if (e.target.closest('[data-action="create"]') ||
            e.target.closest('[data-action="edit"]') ||
            e.target.textContent?.includes('Notiz hinzufügen')) {
            
            setTimeout(() => {
                console.log('Modal likely opened, re-initializing mentions');
                initializeMentionAutocomplete();
            }, 500);
        }
    });
    
    // Watch for DOM changes (Filament modals, etc.)
    const observer = new MutationObserver(function(mutations) {
        let shouldReinitialize = false;
        
        mutations.forEach(function(mutation) {
            if (mutation.type === 'childList') {
                mutation.addedNodes.forEach(function(node) {
                    if (node.nodeType === Node.ELEMENT_NODE) {
                        // Check if modal or form was added
                        if (node.classList?.contains('fi-modal') ||
                            node.querySelector?.('.fi-modal') ||
                            node.querySelector?.('textarea')) {
                            shouldReinitialize = true;
                        }
                    }
                });
            }
        });
        
        if (shouldReinitialize) {
            setTimeout(initializeMentionAutocomplete, 200);
        }
    });
    
    // Start observing
    observer.observe(document.body, {
        childList: true,
        subtree: true
    });
}

// Handle Livewire updates
if (window.Livewire) {
    window.Livewire.hook('morph.updated', () => {
        setTimeout(initializeMentionAutocomplete, 100);
    });
    
    window.Livewire.hook('component.initialized', () => {
        setTimeout(initializeMentionAutocomplete, 100);
    });
}

// Handle Alpine.js updates
document.addEventListener('alpine:init', function() {
    setTimeout(initializeMentionAutocomplete, 100);
});

// Export for manual initialization
window.FilamentMentionIntegration = {
    initialize: initializeMentionAutocomplete
};