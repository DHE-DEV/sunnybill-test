<div class="w-full">
    @if($hasCoordinates)
        <div class="mb-6">
            <!-- Statische Karte als Fallback -->
            <div class="w-full h-96 rounded-lg border border-gray-300 dark:border-gray-600 bg-gray-100 dark:bg-gray-800 relative overflow-hidden">
                <iframe
                    src="https://www.openstreetmap.org/export/embed.html?bbox={{ $longitude - 0.01 }},{{ $latitude - 0.01 }},{{ $longitude + 0.01 }},{{ $latitude + 0.01 }}&layer=mapnik&marker={{ $latitude }},{{ $longitude }}"
                    width="100%"
                    height="100%"
                    style="border: none;"
                    loading="lazy"
                    title="Standortkarte für {{ $name }}">
                </iframe>
            </div>
        </div>
        
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
            <div class="bg-gray-50 dark:bg-gray-800 p-4 rounded-lg">
                <h3 class="font-semibold text-gray-900 dark:text-gray-100 mb-2">Standortinformationen</h3>
                <div class="space-y-2 text-sm">
                    <div><span class="font-medium">Anlage:</span> {{ $name }}</div>
                    <div><span class="font-medium">Standort:</span> {{ $location }}</div>
                    <div><span class="font-medium">Koordinaten:</span> {{ number_format($latitude, 6, ',', '.') }}°N, {{ number_format($longitude, 6, ',', '.') }}°E</div>
                </div>
            </div>
            
            <div class="bg-gray-50 dark:bg-gray-800 p-4 rounded-lg">
                <h3 class="font-semibold text-gray-900 dark:text-gray-100 mb-2">Externe Karten</h3>
                <div class="space-y-2">
                    <a href="https://www.openstreetmap.org/?mlat={{ $latitude }}&mlon={{ $longitude }}&zoom=15"
                       target="_blank"
                       class="inline-flex items-center px-3 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-md transition-colors w-full justify-center">
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"></path>
                        </svg>
                        OpenStreetMap öffnen
                    </a>
                    
                    <a href="https://www.google.com/maps?q={{ $latitude }},{{ $longitude }}"
                       target="_blank"
                       class="inline-flex items-center px-3 py-2 bg-green-600 hover:bg-green-700 text-white rounded-md transition-colors w-full justify-center">
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"></path>
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"></path>
                        </svg>
                        Google Maps öffnen
                    </a>
                </div>
            </div>
        </div>
    @else
        <div class="w-full h-64 bg-gray-100 dark:bg-gray-800 rounded-lg border border-gray-300 dark:border-gray-600 flex items-center justify-center">
            <div class="text-center text-gray-500 dark:text-gray-400">
                <svg class="w-16 h-16 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"></path>
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"></path>
                </svg>
                <p class="text-lg font-medium">Keine Geokoordinaten hinterlegt</p>
                <p class="text-sm mt-2">Fügen Sie Koordinaten hinzu, um die Karte anzuzeigen</p>
            </div>
        </div>
    @endif
</div>