<div id="solar-plant-billing-statistics" class="hidden mb-4 p-4 bg-gray-50 border border-gray-200 rounded-lg">
    <div class="flex items-center justify-between mb-3">
        <h3 class="text-lg font-semibold text-gray-800">Ausgewählte Statistiken</h3>
        <button id="close-statistics" class="text-gray-500 hover:text-gray-700">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
            </svg>
        </button>
    </div>
    
    <div class="grid grid-cols-2 md:grid-cols-5 gap-4">
        <div class="bg-white p-3 rounded-lg border">
            <div class="text-sm text-gray-600">Anzahl Datensätze</div>
            <div id="stat-count" class="text-xl font-bold text-gray-900">0</div>
        </div>
        
        <div class="bg-white p-3 rounded-lg border">
            <div class="text-sm text-gray-600">Gesamt kWp</div>
            <div id="stat-kwp" class="text-xl font-bold text-blue-600">0,00 kWp</div>
        </div>
        
        <div class="bg-white p-3 rounded-lg border">
            <div class="text-sm text-gray-600">Gesamt Kosten</div>
            <div id="stat-costs" class="text-xl font-bold text-red-600">0,00 €</div>
        </div>
        
        <div class="bg-white p-3 rounded-lg border">
            <div class="text-sm text-gray-600">Gesamt Gutschriften</div>
            <div id="stat-credits" class="text-xl font-bold text-green-600">0,00 €</div>
        </div>
        
        <div class="bg-white p-3 rounded-lg border">
            <div class="text-sm text-gray-600">Gesamtbetrag</div>
            <div id="stat-total" class="text-xl font-bold">0,00 €</div>
        </div>
    </div>
</div>

<style>
.fi-ta-header-toolbar {
    position: relative;
}
#solar-plant-billing-statistics {
    order: -1;
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    let statisticsVisible = false;
    
    // Button zum Anzeigen/Verstecken der Statistiken hinzufügen
    function addStatisticsToggleButton() {
        const headerToolbar = document.querySelector('.fi-ta-header-toolbar');
        if (headerToolbar && !document.getElementById('statistics-toggle-btn')) {
            const button = document.createElement('button');
            button.id = 'statistics-toggle-btn';
            button.type = 'button';
            button.className = 'fi-btn fi-btn-outlined fi-btn-size-md fi-btn-color-gray relative grid-flow-col items-center justify-center gap-1.5 px-3 py-2 text-sm font-semibold outline-none transition duration-75 focus-visible:ring-2 disabled:pointer-events-none disabled:opacity-70 rounded-lg fi-btn-outlined border border-gray-300 text-gray-950 hover:bg-gray-50 focus-visible:ring-primary-600 dark:border-gray-600 dark:text-white dark:hover:bg-gray-800 dark:focus-visible:ring-primary-500';
            button.innerHTML = `
                <svg class="fi-btn-icon transition duration-75 h-5 w-5 text-gray-400 dark:text-gray-500" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                    <path fill-rule="evenodd" d="M3 3a1 1 0 000 2v8a2 2 0 002 2h2.586l-1.293 1.293a1 1 0 101.414 1.414L10 15.414l2.293 2.293a1 1 0 001.414-1.414L12.414 15H15a2 2 0 002-2V5a1 1 0 100-2H3zm11.707 4.707a1 1 0 00-1.414-1.414L10 9.586 8.707 8.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                </svg>
                <span class="fi-btn-label">Statistiken</span>
            `;
            
            button.addEventListener('click', toggleStatistics);
            headerToolbar.insertBefore(button, headerToolbar.firstChild);
        }
    }
    
    function toggleStatistics() {
        const statsDiv = document.getElementById('solar-plant-billing-statistics');
        if (statsDiv) {
            statisticsVisible = !statisticsVisible;
            if (statisticsVisible) {
                statsDiv.classList.remove('hidden');
                updateStatistics();
            } else {
                statsDiv.classList.add('hidden');
            }
        }
    }
    
    // Statistiken berechnen und aktualisieren
    function updateStatistics() {
        const selectedCheckboxes = document.querySelectorAll('input[name="mountedTableActionsBulkAction[]"]:checked');
        const count = selectedCheckboxes.length;
        
        let totalKwp = 0;
        let totalCosts = 0;
        let totalCredits = 0;
        
        selectedCheckboxes.forEach(checkbox => {
            const row = checkbox.closest('tr');
            if (row) {
                // kWp aus der entsprechenden Spalte extrahieren
                const kwpCell = row.querySelector('td:nth-child(7)'); // Anpassung je nach Spaltenposition
                if (kwpCell) {
                    const kwpText = kwpCell.textContent.replace(/[^\d,.-]/g, '').replace(',', '.');
                    const kwp = parseFloat(kwpText) || 0;
                    totalKwp += kwp;
                }
                
                // Kosten aus der entsprechenden Spalte extrahieren
                const costsCell = row.querySelector('td:nth-child(9)'); // Anpassung je nach Spaltenposition
                if (costsCell) {
                    const costsText = costsCell.textContent.replace(/[^\d,.-]/g, '').replace(/\./g, '').replace(',', '.');
                    const costs = parseFloat(costsText) || 0;
                    totalCosts += costs;
                }
                
                // Gutschriften aus der entsprechenden Spalte extrahieren
                const creditsCell = row.querySelector('td:nth-child(10)'); // Anpassung je nach Spaltenposition
                if (creditsCell) {
                    const creditsText = creditsCell.textContent.replace(/[^\d,.-]/g, '').replace(/\./g, '').replace(',', '.');
                    const credits = parseFloat(creditsText) || 0;
                    totalCredits += credits;
                }
            }
        });
        
        const netAmount = totalCredits - totalCosts;
        
        // Statistiken aktualisieren
        document.getElementById('stat-count').textContent = count;
        document.getElementById('stat-kwp').textContent = totalKwp.toLocaleString('de-DE', { minimumFractionDigits: 2 }) + ' kWp';
        document.getElementById('stat-costs').textContent = totalCosts.toLocaleString('de-DE', { minimumFractionDigits: 2 }) + ' €';
        document.getElementById('stat-credits').textContent = totalCredits.toLocaleString('de-DE', { minimumFractionDigits: 2 }) + ' €';
        
        const totalElement = document.getElementById('stat-total');
        totalElement.textContent = netAmount.toLocaleString('de-DE', { minimumFractionDigits: 2 }) + ' €';
        totalElement.className = `text-xl font-bold ${netAmount >= 0 ? 'text-green-600' : 'text-red-600'}`;
    }
    
    // Event Listener für Checkbox-Änderungen
    function attachCheckboxListeners() {
        const checkboxes = document.querySelectorAll('input[name="mountedTableActionsBulkAction[]"]');
        checkboxes.forEach(checkbox => {
            checkbox.addEventListener('change', () => {
                if (statisticsVisible) {
                    updateStatistics();
                }
            });
        });
        
        // Select All Checkbox
        const selectAllCheckbox = document.querySelector('input[name="mountedTableActionsBulkActionSelectAll"]');
        if (selectAllCheckbox) {
            selectAllCheckbox.addEventListener('change', () => {
                if (statisticsVisible) {
                    setTimeout(updateStatistics, 100); // Kurze Verzögerung für DOM-Update
                }
            });
        }
    }
    
    // Close Button
    document.getElementById('close-statistics')?.addEventListener('click', () => {
        document.getElementById('solar-plant-billing-statistics').classList.add('hidden');
        statisticsVisible = false;
    });
    
    // Observer für dynamisch hinzugefügte Elemente
    const observer = new MutationObserver(function(mutations) {
        mutations.forEach(function(mutation) {
            if (mutation.type === 'childList') {
                addStatisticsToggleButton();
                attachCheckboxListeners();
            }
        });
    });
    
    observer.observe(document.body, {
        childList: true,
        subtree: true
    });
    
    // Initial setup
    addStatisticsToggleButton();
    attachCheckboxListeners();
});
</script>
