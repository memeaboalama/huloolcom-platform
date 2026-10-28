<?php

namespace Application\Driver\Livewire\Auth;

use App\Domain\Driver\Actions\Auth\LogoutAction;
use App\Domain\MarketPlace\Models\DriverHistory;
use App\Providers\RouteServiceProvider;
use Jantinnerezo\LivewireAlert\LivewireAlert;
use Livewire\Component;

class Logout extends Component
{
    use LivewireAlert;

    public function logout(LogoutAction $logoutAction)
    {
        $driverId = auth('driver')->id();
        $logoutAction->handle();
        if ($driverId !== null) {
            DriverHistory::create(['driver_id' => $driverId, 'action' => 'Exit']);
        }
        return redirect(RouteServiceProvider::DRIVER_LOGIN);
    }

    public function render(): \Illuminate\Contracts\View\View|\Illuminate\Foundation\Application|\Illuminate\Contracts\View\Factory|\Illuminate\Contracts\Foundation\Application
    {
        return view('driver::livewire.auth.logout');
    }
}
