<?php

namespace App\Filament\Pages;

use Filament\Pages\Page;
use Illuminate\Support\Carbon;

class TrialExpired extends Page
{
    protected static string $view = 'filament.pages.trial-expired';

    protected static ?string $slug = 'trial-expired';

    protected static ?string $title = 'Testphase abgelaufen';

    protected static bool $shouldRegisterNavigation = false;

    public string $endDate = '';
    public bool $manuallyExpired = false;

    public function mount(): void
    {
        $this->endDate = config('trial.popup.end_date', '27.02.2026');
        $this->manuallyExpired = (bool) config('trial.expired', false);
    }

    public static function canAccess(): bool
    {
        return true;
    }
}
