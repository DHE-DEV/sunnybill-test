<div x-data="{
    show: true,
    countdown: 20,
    finished: false,
    interval: null,
    init() {
        this.interval = setInterval(() => {
            if (this.countdown > 0) {
                this.countdown--;
            }
            if (this.countdown === 0 && !this.finished) {
                this.finished = true;
                clearInterval(this.interval);
            }
        }, 1000);
    },
    close() {
        this.show = false;
        if (this.interval) clearInterval(this.interval);
    }
}" x-show="show" x-cloak style="display: none;">
    {{-- Overlay --}}
    <div class="fixed inset-0" style="z-index: 999998; background-color: rgba(50, 50, 50, 0.92);">
    </div>

    {{-- Modal --}}
    <div class="fixed inset-0 flex items-center justify-center p-4" style="z-index: 999999;">
        <div class="relative w-full max-w-lg">
            <div class="relative overflow-hidden rounded-xl shadow-2xl" style="background-color: #ffffff;">
                {{-- Header --}}
                <div style="background-color: #dc2626; padding: 20px 24px;">
                    <div class="flex items-center gap-3">
                        <svg class="w-7 h-7 text-white flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126ZM12 15.75h.007v.008H12v-.008Z"/>
                        </svg>
                        <div>
                            <h2 class="text-lg font-bold text-white" style="margin: 0;">Testphase endet am 27.02.2026</h2>
                        </div>
                    </div>
                </div>

                {{-- Body --}}
                <div style="padding: 24px;">
                    <div style="font-size: 15px; line-height: 1.7; color: #374151;">
                        <p style="margin-bottom: 14px;">
                            Sie nutzen unsere Software aktuell im Rahmen einer <strong>Testphase</strong>.
                        </p>
                        <p style="margin-bottom: 14px;">
                            Bitte schließen Sie bis zum <strong>27.02.2026</strong> einen Nutzungsvertrag ab oder sichern Sie Ihre Daten.
                        </p>
                        <p style="margin-bottom: 14px;">
                            Nach Ablauf der Frist wird Ihr Zugang <strong>automatisch deaktiviert</strong>.
                        </p>
                        <p style="margin-bottom: 0;">
                            Bei Fragen kontaktieren Sie uns bitte.
                        </p>
                    </div>

                    {{-- Countdown --}}
                    <div style="margin-top: 24px; text-align: center;">
                        <div x-show="!finished" style="padding: 16px; background-color: #fef2f2; border-radius: 8px; border: 1px solid #fecaca;">
                            <span style="font-size: 13px; color: #991b1b; display: block; margin-bottom: 6px;">Bitte lesen Sie den Hinweis aufmerksam durch</span>
                            <span style="font-size: 36px; font-weight: 700; color: #dc2626; font-variant-numeric: tabular-nums;" x-text="countdown"></span>
                            <span style="font-size: 14px; color: #991b1b; margin-left: 4px;">Sekunden</span>
                        </div>

                        {{-- Acknowledgment + Close button (shown after countdown) --}}
                        <div x-show="finished" x-transition:enter="transition ease-out duration-300" x-transition:enter-start="opacity-0 transform translate-y-2" x-transition:enter-end="opacity-100 transform translate-y-0" style="padding: 16px; background-color: #f0fdf4; border-radius: 8px; border: 1px solid #bbf7d0;">
                            <p style="font-size: 14px; color: #166534; margin-bottom: 14px;">
                                Ich habe den Hinweis gelesen und die Frist zum <strong>27.02.2026</strong> zur Kenntnis genommen.
                            </p>
                            <button @click="close()"
                                    type="button"
                                    style="display: inline-flex; align-items: center; gap: 8px; padding: 10px 24px; background-color: #16a34a; color: #ffffff; font-size: 14px; font-weight: 600; border: none; border-radius: 6px; cursor: pointer; transition: background-color 0.15s;"
                                    onmouseover="this.style.backgroundColor='#15803d'"
                                    onmouseout="this.style.backgroundColor='#16a34a'">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5"/>
                                </svg>
                                Text gelesen und Fenster schließen
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
