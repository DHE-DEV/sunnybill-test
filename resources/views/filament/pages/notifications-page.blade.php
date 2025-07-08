<x-filament-panels::page>
    <div class="space-y-6">
        <!-- Header mit Statistiken -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <div class="bg-white rounded-lg shadow p-6">
                <div class="flex items-center">
                    <div class="flex-shrink-0">
                        <x-heroicon-o-bell class="h-8 w-8 text-blue-500" />
                    </div>
                    <div class="ml-4">
                        <p class="text-sm font-medium text-gray-500">Gesamt</p>
                        <p class="text-2xl font-semibold text-gray-900">
                            {{ auth()->user()->notifications()->count() }}
                        </p>
                    </div>
                </div>
            </div>

            <div class="bg-white rounded-lg shadow p-6">
                <div class="flex items-center">
                    <div class="flex-shrink-0">
                        <x-heroicon-o-eye-slash class="h-8 w-8 text-orange-500" />
                    </div>
                    <div class="ml-4">
                        <p class="text-sm font-medium text-gray-500">Ungelesen</p>
                        <p class="text-2xl font-semibold text-gray-900">
                            {{ auth()->user()->unread_notifications_count }}
                        </p>
                    </div>
                </div>
            </div>

            <div class="bg-white rounded-lg shadow p-6">
                <div class="flex items-center">
                    <div class="flex-shrink-0">
                        <x-heroicon-o-clock class="h-8 w-8 text-green-500" />
                    </div>
                    <div class="ml-4">
                        <p class="text-sm font-medium text-gray-500">Heute</p>
                        <p class="text-2xl font-semibold text-gray-900">
                            {{ auth()->user()->notifications()->whereDate('created_at', today())->count() }}
                        </p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Benachrichtigungstabelle -->
        {{ $this->table }}
    </div>

    @push('scripts')
    <script>
        // Auto-refresh für Benachrichtigungen
        document.addEventListener('livewire:init', () => {
            Livewire.on('refresh-notifications', () => {
                // Aktualisiere die Seite nach einer kurzen Verzögerung
                setTimeout(() => {
                    window.location.reload();
                }, 500);
            });
        });

        // Markiere Benachrichtigung als gelesen beim Klick auf Zeile
        document.addEventListener('click', function(e) {
            const row = e.target.closest('[data-notification-id]');
            if (row && !e.target.closest('button') && !e.target.closest('a')) {
                const notificationId = row.dataset.notificationId;
                const isRead = row.dataset.isRead === '1';
                
                if (!isRead) {
                    // Markiere als gelesen
                    Livewire.dispatch('markNotificationAsRead', { id: notificationId });
                }
            }
        });
    </script>
    @endpush
</x-filament-panels::page>
