document.addEventListener('DOMContentLoaded', function() {
    const tableName = document.querySelector('[data-table-name]')?.getAttribute('data-table-name');
    
    if (!tableName) return;

    // Beobachte Änderungen an Section-Zuständen
    const observer = new MutationObserver(function(mutations) {
        mutations.forEach(function(mutation) {
            if (mutation.type === 'attributes' && mutation.attributeName === 'data-collapsed') {
                const section = mutation.target;
                const sectionId = section.getAttribute('data-section-id');
                const collapsed = section.getAttribute('data-collapsed') === 'true';
                
                if (sectionId) {
                    saveInfolistState(tableName, sectionId, collapsed);
                }
            }
        });
    });

    // Beobachte alle Sections
    const sections = document.querySelectorAll('[data-section-id]');
    sections.forEach(function(section) {
        observer.observe(section, {
            attributes: true,
            attributeFilter: ['data-collapsed']
        });
    });

    // Höre auf Klicks auf Collapse/Expand-Buttons
    document.addEventListener('click', function(event) {
        const button = event.target.closest('[data-collapse-button]');
        if (button) {
            // Warte kurz, damit der DOM-Update stattfindet
            setTimeout(function() {
                const section = button.closest('[data-section-id]');
                if (section) {
                    const sectionId = section.getAttribute('data-section-id');
                    const isCollapsed = section.classList.contains('fi-collapsed') || 
                                      section.querySelector('.fi-section-content').style.display === 'none';
                    
                    if (sectionId) {
                        saveInfolistState(tableName, sectionId, isCollapsed);
                    }
                }
            }, 100);
        }
    });
});

function saveInfolistState(tableName, sectionId, collapsed) {
    fetch('/api/infolist-state', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
        },
        body: JSON.stringify({
            table_name: tableName,
            section_id: sectionId,
            collapsed: collapsed
        })
    })
    .catch(function(error) {
        console.error('Fehler beim Speichern des Infolist-Status:', error);
    });
}
