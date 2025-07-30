<div id="solar-plant-billing-statistics" class="hidden mb-6">
    <div class="flex items-center justify-between mb-4">
        <h2 class="text-xl font-semibold text-gray-900">Statistiken der ausgewählten Abrechnungen</h2>
        <button id="close-statistics" class="text-gray-400 hover:text-gray-600 transition-colors">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
            </svg>
        </button>
    </div>
    
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-5 gap-6">
        <!-- Anzahl Datensätze -->
        <div class="fi-wi-stats-overview-stat relative rounded-xl bg-white p-6 shadow-sm ring-1 ring-gray-950/5">
            <div class="grid gap-y-2">
                <div class="flex items-center gap-x-3">
                    <div class="fi-wi-stats-overview-stat-icon flex h-12 w-12 items-center justify-center rounded-xl bg-primary-50 text-primary-600">
                        <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                        </svg>
                    </div>
                    <div class="grid flex-1 gap-y-1">
                        <div class="fi-wi-stats-overview-stat-label text-sm font-medium text-gray-500">
                            Ausgewählte Datensätze
                        </div>
                        <div id="stat-count" class="fi-wi-stats-overview-stat-value text-3xl font-semibold tracking-tight text-gray-950">
                            0
                        </div>
                    </div>
                </div>
                <div class="fi-wi-stats-overview-stat-description text-sm text-gray-500">
                    <span id="stat-description">Alle Abrechnungen</span>
                    <svg class="inline-block ml-1 h-4 w-4 text-gray-400" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M3 4a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1zm0 4a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1zm0 4a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1z" clip-rule="evenodd"></path>
                    </svg>
                </div>
            </div>
        </div>

        <!-- Gesamt kWp -->
        <div class="fi-wi-stats-overview-stat relative rounded-xl bg-white p-6 shadow-sm ring-1 ring-gray-950/5">
            <div class="grid gap-y-2">
                <div class="flex items-center gap-x-3">
                    <div class="fi-wi-stats-overview-stat-icon flex h-12 w-12 items-center justify-center rounded-xl bg-blue-50 text-blue-600">
                        <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path>
                        </svg>
                    </div>
                    <div class="grid flex-1 gap-y-1">
                        <div class="fi-wi-stats-overview-stat-label text-sm font-medium text-gray-500">
                            Gesamt kWp (Beteiligungen)
                        </div>
                        <div id="stat-kwp" class="fi-wi-stats-overview-stat-value text-3xl font-semibold tracking-tight text-gray-950">
                            0,00 kWp
                        </div>
                    </div>
                </div>
                <div class="fi-wi-stats-overview-stat-description text-sm text-blue-600">
                    <span>Summierte Beteiligungen</span>
                    <svg class="inline-block ml-1 h-4 w-4" fill="currentColor" viewBox="0 0 20 20">
                        <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"></path>
                    </svg>
                </div>
            </div>
        </div>

        <!-- Gesamt Kosten -->
        <div class="fi-wi-stats-overview-stat relative rounded-xl bg-white p-6 shadow-sm ring-1 ring-gray-950/5">
            <div class="grid gap-y-2">
                <div class="flex items-center gap-x-3">
                    <div class="fi-wi-stats-overview-stat-icon flex h-12 w-12 items-center justify-center rounded-xl bg-red-50 text-red-600">
                        <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                    </div>
                    <div class="grid flex-1 gap-y-1">
                        <div class="fi-wi-stats-overview-stat-label text-sm font-medium text-gray-500">
                            Gesamt Kosten
                        </div>
                        <div id="stat-costs" class="fi-wi-stats-overview-stat-value text-3xl font-semibold tracking-tight text-gray-950">
                            0,00 €
                        </div>
                    </div>
                </div>
                <div class="fi-wi-stats-overview-stat-description text-sm text-red-600">
                    <span>Betriebskosten</span>
                    <svg class="inline-block ml-1 h-4 w-4" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M3 17a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1zm3.293-7.707a1 1 0 011.414 0L9 10.586V3a1 1 0 112 0v7.586l1.293-1.293a1 1 0 111.414 1.414l-3 3a1 1 0 01-1.414 0l-3-3a1 1 0 010-1.414z" clip-rule="evenodd"></path>
                    </svg>
                </div>
            </div>
        </div>

        <!-- Gesamt Gutschriften -->
        <div class="fi-wi-stats-overview-stat relative rounded-xl bg-white p-6 shadow-sm ring-1 ring-gray-950/5">
            <div class="grid gap-y-2">
                <div class="flex items-center gap-x-3">
                    <div class="fi-wi-stats-overview-stat-icon flex h-12 w-12 items-center justify-center rounded-xl bg-green-50 text-green-600">
                        <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                    </div>
                    <div class="grid flex-1 gap-y-1">
                        <div class="fi-wi-stats-overview-stat-label text-sm font-medium text-gray-500">
                            Gesamt Gutschriften
                        </div>
                        <div id="stat-credits" class="fi-wi-stats-overview-stat-value text-3xl font-semibold tracking-tight text-gray-950">
                            0,00 €
                        </div>
                    </div>
                </div>
                <div class="fi-wi-stats-overview-stat-description text-sm text-green-600">
                    <span>Einnahmen/Erlöse</span>
                    <svg class="inline-block ml-1 h-4 w-4" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M3 3a1 1 0 000 2v8a2 2 0 002 2h2.586l-1.293 1.293a1 1 0 101.414 1.414L10 15.414l2.293 2.293a1 1 0 001.414-1.414L12.414 15H15a2 2 0 002-2V5a1 1 0 100-2H3zm11.707 4.707a1 1 0 00-1.414-1.414L10 9.586 8.707 8.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path>
                    </svg>
                </div>
            </div>
        </div>

        <!-- Gesamtbetrag -->
        <div class="fi-wi-stats-overview-stat relative rounded-xl bg-white p-6 shadow-sm ring-1 ring-gray-950/5">
            <div class="grid gap-y-2">
                <div class="flex items-center gap-x-3">
                    <div id="stat-total-icon" class="fi-wi-stats-overview-stat-icon flex h-12 w-12 items-center justify-center rounded-xl bg-gray-50 text-gray-600">
                        <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 7h6m0 10v-3m-3 3h.01M9 17h.01M9 14h.01M12 14h.01M15 11h.01M12 11h.01M9 11h.01M7 21h10a2 2 0 002-2V5a2 2 0 00-2-2H7a2 2 0 00-2 2v14a2 2 0 002 2z"></path>
                        </svg>
                    </div>
                    <div class="grid flex-1 gap-y-1">
                        <div class="fi-wi-stats-overview-stat-label text-sm font-medium text-gray-500">
                            Netto-Gesamtbetrag
                        </div>
                        <div id="stat-total" class="fi-wi-stats-overview-stat-value text-3xl font-semibold tracking-tight text-gray-950">
                            0,00 €
                        </div>
                    </div>
                </div>
                <div id="stat-total-description" class="fi-wi-stats-overview-stat-description text-sm text-gray-500">
                    <span>Saldo (Gutschriften - Kosten)</span>
                    <svg class="inline-block ml-1 h-4 w-4" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M6 2a2 2 0 00-2 2v12a2 2 0 002 2h8a2 2 0 002-2V4a2 2 0 00-2-2H6zm1 2a1 1 0 000 2h6a1 1 0 100-2H7zm6 7a1 1 0 011 1v3a1 1 0 11-2 0v-3a1 1 0 011-1zm-3 3a1 1 0 100 2h.01a1 1 0 100-2H10zm-4 1a1 1 0 011-1h.01a1 1 0 110 2H7a1 1 0 01-1-1zm1-4a1 1 0 100 2h.01a1 1 0 100-2H7zm2 0a1 1 0 100 2h.01a1 1 0 100-2H9zm2 0a1 1 0 100 2h.01a1 1 0 100-2H11z" clip-rule="evenodd"></path>
                    </svg>
                </div>
            </div>
        </div>
    </div>
</div>

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
                const cells = row.querySelectorAll('td');
                
                // kWp aus der entsprechenden Spalte extrahieren (normalerweise Spalte mit kWp-Daten)
                cells.forEach((cell, index) => {
                    const text = cell.textContent.trim();
                    
                    // Suche nach kWp-Werten
                    if (text.includes('kWp')) {
                        const kwpMatch = text.match(/(\d+(?:,\d+)?)\s*kWp/);
                        if (kwpMatch) {
                            const kwp = parseFloat(kwpMatch[1].replace(',', '.')) || 0;
                            totalKwp += kwp;
                        }
                    }
                    
                    // Suche nach Geldbeträgen (€)
                    if (text.includes('€')) {
                        const amountMatch = text.match(/(-?\d+(?:\.\d{3})*(?:,\d{2})?)\s*€/);
                        if (amountMatch) {
                            let amount = amountMatch[1].replace(/\./g, '').replace(',', '.');
                            amount = parseFloat(amount) || 0;
                            
                            // Unterscheide zwischen Kosten (negative Werte oder rote Spalten) und Gutschriften
                            if (amount < 0 || cell.classList.contains('text-red-600') || text.includes('-')) {
                                totalCosts += Math.abs(amount);
                            } else if (amount > 0) {
                                totalCredits += amount;
                            }
                        }
                    }
                });
            }
        });
        
        const netAmount = totalCredits - totalCosts;
        
        // Statistiken aktualisieren
        document.getElementById('stat-count').textContent = count.toLocaleString('de-DE');
        document.getElementById('stat-kwp').textContent = totalKwp.toLocaleString('de-DE', { minimumFractionDigits: 2 }) + ' kWp';
        document.getElementById('stat-costs').textContent = totalCosts.toLocaleString('de-DE', { minimumFractionDigits: 2 }) + ' €';
        document.getElementById('stat-credits').textContent = totalCredits.toLocaleString('de-DE', { minimumFractionDigits: 2 }) + ' €';
        
        // Gesamtbetrag mit Farbe und Icon
        const totalElement = document.getElementById('stat-total');
        const totalIcon = document.getElementById('stat-total-icon');
        const totalDescription = document.getElementById('stat-total-description');
        
        totalElement.textContent = Math.abs(netAmount).toLocaleString('de-DE', { minimumFractionDigits: 2 }) + ' €';
        
        if (netAmount >= 0) {
            totalElement.className = 'fi-wi-stats-overview-stat-value text-3xl font-semibold tracking-tight text-green-600';
            totalIcon.className = 'fi-wi-stats-overview-stat-icon flex h-12 w-12 items-center justify-center rounded-xl bg-green-50 text-green-600';
            totalDescription.className = 'fi-wi-stats-overview-stat-description text-sm text-green-600';
            totalDescription.innerHTML = '<span>Positiver Saldo (Gewinn)</span><svg class="inline-block ml-1 h-4 w-4" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M3 17a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1zM6.293 6.707a1 1 0 010-1.414l3-3a1 1 0 011.414 0l3 3a1 1 0 01-1.414 1.414L11 5.414V13a1 1 0 11-2 0V5.414L7.707 6.707a1 1 0 01-1.414 0z" clip-rule="evenodd"></path></svg>';
        } else {
            totalElement.className = 'fi-wi-stats-overview-stat-value text-3xl font-semibold tracking-tight text-red-600';
            totalIcon.className = 'fi-wi-stats-overview-stat-icon flex h-12 w-12 items-center justify-center rounded-xl bg-red-50 text-red-600';
            totalDescription.className = 'fi-wi-stats-overview-stat-description text-sm text-red-600';
            totalDescription.innerHTML = '<span>Negativer Saldo (Verlust)</span><svg class="inline-block ml-1 h-4 w-4" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M3 3a1 1 0 000 2h12a1 1 0 110 2H4a1 1 0 01-1-1zm3.293 7.707a1 1 0 011.414 0L9 12.586V5a1 1 0 112 0v7.586l1.293-1.293a1 1 0 111.414 1.414l-3 3a1 1 0 01-1.414 0l-3-3a1 1 0 010-1.414z" clip-rule="evenodd"></path></svg>';
        }
        
        // Beschreibung aktualisieren
        const description = count === 0 ? 'Keine Auswahl' : 
                          count === 1 ? '1 Datensatz ausgewählt' : 
                          `${count} Datensätze ausgewählt`;
        document.getElementById('stat-description').textContent = description;
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
