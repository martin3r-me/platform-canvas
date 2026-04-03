<?php

namespace Platform\Canvas\Livewire;

use Livewire\Component;
use Illuminate\Support\Facades\Auth;
use Platform\Canvas\Services\CanvasSidebarService;
use Livewire\Attributes\On;

class Sidebar extends Component
{
    public bool $showAllCanvases = false;

    public function mount()
    {
        $this->showAllCanvases = false;
    }

    #[On('updateSidebar')]
    public function updateSidebar()
    {

    }

    public function toggleShowAllCanvases()
    {
        $this->showAllCanvases = !$this->showAllCanvases;
    }

    public function createCanvas()
    {
        $user = Auth::user();
        $service = new CanvasSidebarService();
        $canvas = $service->createQuickCanvas($user);

        return redirect()->route('canvas.canvases.show', ['canvas' => $canvas]);
    }

    public function render()
    {
        $user = auth()->user();

        if (!$user || !$user->currentTeam) {
            return view('canvas::livewire.sidebar', [
                'entityTypeGroups' => collect(),
                'unlinkedCanvases' => collect(),
                'hasMoreCanvases' => false,
            ]);
        }

        $service = new CanvasSidebarService();
        $data = $service->getCanvasesForUser($user, $this->showAllCanvases);

        return view('canvas::livewire.sidebar', $data);
    }
}
