<?php

namespace App\Livewire;

use App\Models\TrialPopupAcknowledgment;
use Livewire\Component;
use Illuminate\Support\Facades\Auth;

class TrialPopup extends Component
{
    public bool $showPopup = false;
    public int $countdownSeconds = 20;
    public string $endDate = '';
    public ?int $acknowledgmentId = null;

    public function mount(): void
    {
        if (!config('trial.popup.enabled')) {
            return;
        }

        if (!Auth::check()) {
            return;
        }

        $endDateString = config('trial.popup.end_date', '27.02.2026');
        $endDate = \Illuminate\Support\Carbon::createFromFormat('d.m.Y', $endDateString)->endOfDay();

        if (now()->greaterThan($endDate)) {
            return;
        }

        $this->countdownSeconds = config('trial.popup.countdown_seconds', 20);
        $this->endDate = $endDateString;

        if ($this->shouldShowBasedOnFrequency()) {
            $this->showPopup = true;
            $this->logDisplay();
        }
    }

    private function shouldShowBasedOnFrequency(): bool
    {
        $frequency = config('trial.popup.frequency', 'once_per_session');
        $userId = Auth::id();

        return match ($frequency) {
            'every_page_load' => true,
            'once_per_session' => !session()->has('trial_popup_shown'),
            'once_per_day' => !TrialPopupAcknowledgment::where('user_id', $userId)
                ->whereDate('displayed_at', today())
                ->exists(),
            'once_per_login' => !TrialPopupAcknowledgment::where('user_id', $userId)
                ->where('displayed_at', '>=', Auth::user()->last_login_at)
                ->exists(),
            default => true,
        };
    }

    private function logDisplay(): void
    {
        $acknowledgment = TrialPopupAcknowledgment::create([
            'user_id' => Auth::id(),
            'displayed_at' => now(),
        ]);

        $this->acknowledgmentId = $acknowledgment->id;

        if (config('trial.popup.frequency') === 'once_per_session') {
            session()->put('trial_popup_shown', true);
        }
    }

    public function acknowledge(): void
    {
        if ($this->acknowledgmentId) {
            TrialPopupAcknowledgment::where('id', $this->acknowledgmentId)
                ->update(['acknowledged_at' => now()]);
        }

        $this->showPopup = false;
    }

    public function render()
    {
        return view('livewire.trial-popup');
    }
}
