<div class="w-full">
    @if($hasCoordinates)
        <div class="mb-4">
            <div id="map-{{ $name }}" class="w-full h-96 rounded-lg border border-gray-300 dark:border-gray-600"></div>
        </div>
        
        <div class="flex flex-wrap gap-2 text-sm text-gray-600 dark:text-gray-400">
            <a href="https://www.openstreetmap.org/?mlat={{ $latitude }}&mlon={{ $longitude }}&zoom=15" 
               target="_blank" 
               class="inline-flex items-center px-3 py-1 bg-blue-100 dark:bg-blue-900 text-blue-800 dark:text-blue-200 rounded-full hover:bg-blue-200 dark:hover:bg-blue-800 transition-colors">
                <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"></path>
                </svg>
                OpenStreetMap öffnen
            </a>
            
            <a href="https://www.google.com/maps?q={{ $latitude }},{{ $longitude }}" 
               target="_blank" 
               class="inline-flex items-center px-3 py-1 bg-green-100 dark:bg-green-900 text-green-800 dark:text-green-200 rounded-full hover:bg-green-200 dark:hover:bg-green-800 transition-colors">
                <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"></path>
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"></path>
                </svg>
                Google Maps öffnen
            </a>
        </div>

        <script>
            document.addEventListener('DOMContentLoaded', function() {
                // Prüfe ob Leaflet bereits geladen ist
                if (typeof L === 'undefined') {
                    // Lade Leaflet CSS
                    const leafletCSS = document.createElement('link');
                    leafletCSS.rel = 'stylesheet';
                    leafletCSS.href = 'https://unpkg.com/leaflet@1.9.4/dist/leaflet.css';
                    leafletCSS.integrity = 'sha256-p4NxAoJBhIIN+hmNHrzRCf9tD/miZyoHS5obTRR9BMY=';
                    leafletCSS.crossOrigin = '';
                    document.head.appendChild(leafletCSS);

                    // Lade Leaflet JS
                    const leafletJS = document.createElement('script');
                    leafletJS.src = 'https://unpkg.com/leaflet@1.9.4/dist/leaflet.js';
                    leafletJS.integrity = 'sha256-20nQCchB9co0qIjJZRGuk2/Z9VM+kNiyxNV1lvTlZBo=';
                    leafletJS.crossOrigin = '';
                    leafletJS.onload = function() {
                        initMap();
                    };
                    document.head.appendChild(leafletJS);
                } else {
                    initMap();
                }

                function initMap() {
                    const mapId = 'map-{{ $name }}';
                    const mapElement = document.getElementById(mapId);
                    
                    if (!mapElement) return;

                    // Prüfe ob Karte bereits initialisiert wurde
                    if (mapElement._leaflet_id) {
                        return;
                    }

                    const lat = {{ $latitude }};
                    const lng = {{ $longitude }};
                    
                    // Erstelle Karte
                    const map = L.map(mapId).setView([lat, lng], 15);
                    
                    // Füge OpenStreetMap Tiles hinzu
                    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                        attribution: '© <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors',
                        maxZoom: 19
                    }).addTo(map);
                    
                    // Füge Marker hinzu
                    const marker = L.marker([lat, lng]).addTo(map);
                    
                    // Popup mit Informationen
                    marker.bindPopup(`
                        <div class="text-center">
                            <strong>{{ $name }}</strong><br>
                            <small>{{ $location }}</small><br>
                            <small class="text-gray-600">{{ number_format($latitude, 6, ',', '.') }}°N, {{ number_format($longitude, 6, ',', '.') }}°E</small>
                        </div>
                    `).openPopup();
                    
                    // Responsive Verhalten
                    setTimeout(() => {
                        map.invalidateSize();
                    }, 100);
                }
            });
        </script>
    @else
        <div class="w-full h-48 bg-gray-100 dark:bg-gray-800 rounded-lg border border-gray-300 dark:border-gray-600 flex items-center justify-center">
            <div class="text-center text-gray-500 dark:text-gray-400">
                <svg class="w-12 h-12 mx-auto mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"></path>
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"></path>
                </svg>
                <p class="text-sm">Keine Geokoordinaten hinterlegt</p>
                <p class="text-xs mt-1">Fügen Sie Koordinaten hinzu, um die Karte anzuzeigen</p>
            </div>
        </div>
    @endif
</div>