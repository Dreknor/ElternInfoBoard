<?php

namespace App\Livewire\Help;

use App\Services\HelpService;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Str;
use Livewire\Attributes\On;
use Livewire\Component;

class HelpDrawer extends Component
{
    public bool $open = false;
    public string $search = '';
    public ?string $currentRoute = null;
    public ?string $currentUri = null;

    public function mount(): void
    {
        $this->currentRoute = optional(Route::current())->getName();
        $this->currentUri = request()->path();
    }

    #[On('help:open')]
    public function openDrawer(?string $route = null, ?string $uri = null): void
    {
        if ($route) {
            $this->currentRoute = $route;
        }
        if ($uri) {
            $this->currentUri = $uri;
        }
        $this->open = true;
    }

    #[On('help:close')]
    public function closeDrawer(): void
    {
        $this->open = false;
        $this->search = '';
    }

    public function toggle(): void
    {
        $this->open = ! $this->open;
    }

    public function render(HelpService $help)
    {
        $contextual = $help->topicsForRoute($this->currentRoute, $this->currentUri);

        $all = $help->availableTopics();

        if ($this->search !== '') {
            $needle = Str::lower($this->search);
            $all = $all->filter(fn ($t) => Str::contains(Str::lower($t['title'].' '.$t['excerpt']), $needle))->values();
        }

        return view('livewire.help.help-drawer', [
            'contextual' => $contextual,
            'all'        => $all,
            'help'       => $help,
        ]);
    }
}
