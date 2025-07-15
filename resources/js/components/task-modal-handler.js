/**
 * Task Modal Handler
 * Handles opening task modals with specific relation managers from URL parameters
 */

document.addEventListener('DOMContentLoaded', function() {
    // Check URL parameters for task modal opening
    const urlParams = new URLSearchParams(window.location.search);
    const activeRelationManager = urlParams.get('activeRelationManager');
    
    if (activeRelationManager) {
        // Wait for Filament to be ready
        setTimeout(() => {
            openTaskRelationManager(activeRelationManager);
        }, 500);
    }
});

/**
 * Open specific relation manager tab in task view
 */
function openTaskRelationManager(relationManager) {
    // Find the relation manager tab
    const tabSelector = `[data-tab="${relationManager}"], [aria-controls="${relationManager}"], [href*="${relationManager}"]`;
    const tab = document.querySelector(tabSelector);
    
    if (tab) {
        // Click the tab to activate it
        tab.click();
        
        // Scroll to the tab area
        setTimeout(() => {
            tab.scrollIntoView({ 
                behavior: 'smooth', 
                block: 'center' 
            });
        }, 300);
        
        // Remove the URL parameter to clean up
        const url = new URL(window.location);
        url.searchParams.delete('activeRelationManager');
        window.history.replaceState({}, '', url);
    } else {
        // If direct tab selection doesn't work, try alternative methods
        tryAlternativeTabActivation(relationManager);
    }
}

/**
 * Alternative methods to activate relation manager tabs
 */
function tryAlternativeTabActivation(relationManager) {
    // Method 1: Look for tabs with text content
    const tabs = document.querySelectorAll('[role="tab"], .fi-tabs-tab, .fi-tab');
    
    for (const tab of tabs) {
        const tabText = tab.textContent.toLowerCase();
        if (tabText.includes('notiz') && relationManager === 'notes') {
            tab.click();
            return;
        }
        if (tabText.includes('dokument') && relationManager === 'documents') {
            tab.click();
            return;
        }
        if (tabText.includes('unteraufgab') && relationManager === 'subtasks') {
            tab.click();
            return;
        }
    }
    
    // Method 2: Use Alpine.js or Livewire events if available
    if (window.Alpine) {
        window.Alpine.nextTick(() => {
            const event = new CustomEvent('activate-relation-manager', {
                detail: { relationManager }
            });
            document.dispatchEvent(event);
        });
    }
    
    // Method 3: Try Livewire wire:click if available
    if (window.Livewire) {
        const livewireComponent = window.Livewire.find(
            document.querySelector('[wire\\:id]')?.getAttribute('wire:id')
        );
        
        if (livewireComponent) {
            try {
                livewireComponent.call('mountAction', 'view', {
                    activeRelationManager: relationManager
                });
            } catch (e) {
                console.warn('Could not activate relation manager via Livewire:', e);
            }
        }
    }
}

/**
 * Create a URL that opens a task with specific relation manager
 */
function createTaskUrl(taskId, relationManager = null) {
    const baseUrl = `/admin/tasks/${taskId}`;
    
    if (relationManager) {
        return `${baseUrl}?activeRelationManager=${relationManager}`;
    }
    
    return baseUrl;
}

/**
 * Navigate to task with specific relation manager
 */
function navigateToTask(taskId, relationManager = null) {
    const url = createTaskUrl(taskId, relationManager);
    window.location.href = url;
}

// Export functions for global use
window.TaskModalHandler = {
    openTaskRelationManager,
    createTaskUrl,
    navigateToTask
};

// Listen for custom events to handle modal opening
document.addEventListener('open-task-notes', function(event) {
    const { taskId } = event.detail;
    navigateToTask(taskId, 'notes');
});

// Handle browser back/forward navigation
window.addEventListener('popstate', function(event) {
    const urlParams = new URLSearchParams(window.location.search);
    const activeRelationManager = urlParams.get('activeRelationManager');
    
    if (activeRelationManager) {
        setTimeout(() => {
            openTaskRelationManager(activeRelationManager);
        }, 100);
    }
});