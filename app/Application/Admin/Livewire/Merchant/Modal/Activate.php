<?php

namespace Application\Admin\Livewire\Merchant\Modal;

use App\Domain\Admin\Actions\Dashboard\Merchant\ActivateMerchantAction;
use Illuminate\Support\Facades\DB;
use Jantinnerezo\LivewireAlert\LivewireAlert;
use Livewire\Component;

class Activate extends Component
{
    use LivewireAlert;

    public int|null $element_id = null;
    protected $listeners = ['activateMerchant'];

    public function activateMerchant($id): void
    {
        $this->element_id = $id;
        $this->emit('openModal', '#activateMerchant');
    }

    public function activate(ActivateMerchantAction $activateMerchantAction): void
    {
        DB::beginTransaction();
        $activateMerchantAction->activate($this->element_id);
        $this->emit('closeModal', '#activateMerchant');
        $this->emit('refreshDatatable');
        $this->alert('success', __('admin::home.activated_successfully'));
        DB::commit();
    }


    public function render(): \Illuminate\Contracts\View\View|\Illuminate\Foundation\Application|\Illuminate\Contracts\View\Factory|\Illuminate\Contracts\Foundation\Application
    {
        return view('admin::livewire.merchant.modal.activate');
    }
}
