<?php

namespace App\Livewire;

use App\Models\News;
use App\Models\NewsUserView;
use Livewire\Component;
use Illuminate\Support\Facades\Auth;

class NewsPopup extends Component
{
    public $currentNews = null;
    public $showPopup = false;
    public $unviewedNews = [];
    public $currentIndex = 0;
    public $lastCheckedAt = null;

    public function mount()
    {
        $this->loadUnviewedNews();
        $this->lastCheckedAt = now();
    }

    public function loadUnviewedNews()
    {
        if (!Auth::check()) {
            \Log::info('NewsPopup: User not authenticated');
            return;
        }

        // Prüfe ob News bereits in dieser Session angezeigt wurden
        if (session()->has('news_shown_in_session')) {
            \Log::info('NewsPopup: News already shown in this session');
            return;
        }

        $user = Auth::user();
        $this->unviewedNews = News::getUnviewedForUser($user)->toArray();

        \Log::info('NewsPopup loaded for user: ' . $user->email, [
            'user_id' => $user->id,
            'unviewed_count' => count($this->unviewedNews)
        ]);

        if (count($this->unviewedNews) > 0) {
            $this->currentNews = $this->unviewedNews[0];
            $this->showPopup = true;
            // Markiere, dass News in dieser Session angezeigt wurden
            session()->put('news_shown_in_session', true);
            \Log::info('NewsPopup: Showing popup with news: ' . $this->currentNews['title']);
        } else {
            \Log::info('NewsPopup: No unviewed news to show');
        }
    }

    public function checkForNewNews()
    {
        if (!Auth::check() || $this->showPopup) {
            return;
        }

        $user = Auth::user();

        // Hole alle ungesehenen News, die nach dem letzten Check veröffentlicht wurden
        $newUnviewedNews = News::getUnviewedForUser($user)
            ->filter(function ($news) {
                return $news->published_at > $this->lastCheckedAt;
            })
            ->toArray();

        if (count($newUnviewedNews) > 0) {
            \Log::info('NewsPopup: New news detected', [
                'user_id' => $user->id,
                'new_count' => count($newUnviewedNews)
            ]);

            // Entferne Session-Flag, damit neue News angezeigt werden können
            session()->forget('news_shown_in_session');

            // Lade alle ungesehenen News neu
            $this->unviewedNews = News::getUnviewedForUser($user)->toArray();
            $this->currentIndex = 0;
            $this->currentNews = $this->unviewedNews[0];
            $this->showPopup = true;

            // Setze Session-Flag wieder
            session()->put('news_shown_in_session', true);
        }

        $this->lastCheckedAt = now();
    }

    public function markAsViewed($dontShowAgain = false)
    {
        if (!$this->currentNews || !Auth::check()) {
            return;
        }

        NewsUserView::updateOrCreate(
            [
                'news_id' => $this->currentNews['id'],
                'user_id' => Auth::id(),
            ],
            [
                'dont_show_again' => $dontShowAgain,
                'viewed_at' => now(),
            ]
        );

        // Move to next news or close
        $this->currentIndex++;

        if ($this->currentIndex < count($this->unviewedNews)) {
            $this->currentNews = $this->unviewedNews[$this->currentIndex];
        } else {
            $this->closePopup();
        }
    }

    public function nextNews()
    {
        // Move to next news without marking as viewed
        $this->currentIndex++;

        if ($this->currentIndex < count($this->unviewedNews)) {
            $this->currentNews = $this->unviewedNews[$this->currentIndex];
        } else {
            $this->closePopup();
        }
    }

    public function dontShowAgain()
    {
        $this->markAsViewed(true);
    }

    public function closePopup()
    {
        $this->showPopup = false;
        $this->currentNews = null;
    }

    public function render()
    {
        return view('livewire.news-popup');
    }

    public function getListeners()
    {
        return [
            'escapePressed' => 'closePopup',
        ];
    }
}
