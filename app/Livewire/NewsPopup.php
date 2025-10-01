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

    public function mount()
    {
        $this->loadUnviewedNews();
    }

    public function loadUnviewedNews()
    {
        if (!Auth::check()) {
            return;
        }

        $user = Auth::user();
        $this->unviewedNews = News::getUnviewedForUser($user)->toArray();

        if (count($this->unviewedNews) > 0) {
            $this->currentNews = $this->unviewedNews[0];
            $this->showPopup = true;
        }
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
        $this->markAsViewed(false);
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
}
