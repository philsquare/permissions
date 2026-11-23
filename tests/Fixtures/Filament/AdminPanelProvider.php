<?php

namespace Philsquare\Permissions\Tests\Fixtures\Filament;

use Filament\Panel;
use Filament\PanelProvider;

class AdminPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel->id('admin')->default()->path('admin');
    }
}
