<?php

namespace Tests\Feature\Marketplace;

use App\Domain\MarketPlace\Actions\Dashboard\Auth\Employee\LoginEmployeeAction;
use App\Domain\MarketPlace\Actions\Dashboard\Auth\LoginAction;
use Tests\TestCase;

class MerchantLoginTest extends TestCase
{
    public function test_login_post_route_validates_missing_credentials(): void
    {
        $response = $this->post(route('marketplace:login.submit'));

        $response->assertRedirect();
        $response->assertSessionHasErrors(['email', 'password']);
    }

    public function test_invalid_credentials_redirect_back_with_an_error(): void
    {
        $credentials = [
            'email' => 'wrong@example.com',
            'password' => 'wrong-password',
        ];

        $this->mock(LoginAction::class)
            ->shouldReceive('handle')
            ->once()
            ->with($credentials)
            ->andReturnFalse();
        $this->mock(LoginEmployeeAction::class)
            ->shouldReceive('handle')
            ->once()
            ->with($credentials)
            ->andReturnFalse();

        $response = $this->from('/ar/merchant/login')
            ->post('/ar/merchant/login', $credentials);

        $response->assertRedirect('/ar/merchant/login');
        $response->assertSessionHasErrors('email');
    }
}
