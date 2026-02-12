<x-filament-panels::page>
    <div class="flex items-center justify-center" style="min-height: 60vh;">
        <div class="w-full max-w-lg">
            <div class="overflow-hidden rounded-xl shadow-2xl" style="background-color: #ffffff;">
                {{-- Header --}}
                <div style="background-color: #dc2626; padding: 24px;">
                    <div class="flex items-center gap-3">
                        <svg class="w-8 h-8 text-white flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126ZM12 15.75h.007v.008H12v-.008Z"/>
                        </svg>
                        <h2 class="text-xl font-bold text-white" style="margin: 0;">Testphase abgelaufen</h2>
                    </div>
                </div>

                {{-- Body --}}
                <div style="padding: 28px 24px;">
                    <div style="font-size: 15px; line-height: 1.7; color: #374151;">
                        <p style="margin-bottom: 16px;">
                            Die Testphase ist am <strong>{{ $endDate }}</strong> abgelaufen.
                        </p>
                        <p style="margin-bottom: 16px;">
                            Zur weiteren Nutzung der Software schließen Sie bitte einen <strong>Nutzungsvertrag</strong> ab.
                        </p>
                        <p style="margin-bottom: 0;">
                            Bei Fragen kontaktieren Sie uns bitte.
                        </p>
                    </div>

                    {{-- Logout Button --}}
                    <div style="margin-top: 28px; text-align: center;">
                        <form method="POST" action="{{ filament()->getLogoutUrl() }}">
                            @csrf
                            <button type="submit"
                                    style="display: inline-flex; align-items: center; gap: 8px; padding: 12px 32px; background-color: #dc2626; color: #ffffff; font-size: 14px; font-weight: 600; border: none; border-radius: 8px; cursor: pointer; transition: background-color 0.15s;"
                                    onmouseover="this.style.backgroundColor='#b91c1c'"
                                    onmouseout="this.style.backgroundColor='#dc2626'">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 9V5.25A2.25 2.25 0 0 0 13.5 3h-6a2.25 2.25 0 0 0-2.25 2.25v13.5A2.25 2.25 0 0 0 7.5 21h6a2.25 2.25 0 0 0 2.25-2.25V15m3 0 3-3m0 0-3-3m3 3H9"/>
                                </svg>
                                Abmelden
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-filament-panels::page>
