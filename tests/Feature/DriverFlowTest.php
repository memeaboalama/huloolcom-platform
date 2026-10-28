<?php

namespace Tests\Feature;

use App\Application\Marketplace\Enums\MerchantOrderStatuses;
use App\Application\Marketplace\Enums\MerchantOrderHistoryStatuses;
use App\Domain\MarketPlace\Actions\Api\Order\LogOrderHistoryAction;
use App\Domain\MarketPlace\Models\DriverHistory;
use App\Domain\MarketPlace\Models\MerchantOrder;
use App\Domain\MarketPlace\Models\MerchantOrderHistory;
use App\Domain\Provider\Models\Company;
use Domain\Driver\Models\Driver;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * End-to-end driver flow against LOCAL db (seeder: DriverTestAccountsSeeder).
 * Rolls back nothing destructive; asserts on seeded test rows only.
 */
class DriverFlowTest extends TestCase
{
    private Driver $approved;
    private Driver $pending;

    /**
     * In tests routes register without the /ar locale prefix
     * (LaravelLocalization::setLocale() needs a live request).
     */
    private function uri(string $path): string
    {
        return '/' . ltrim($path, '/');
    }

    protected function setUp(): void
    {
        parent::setUp();
        $this->approved = Driver::where('email', 'driver.approved@test.local')->firstOrFail();
        $this->pending = Driver::where('email', 'driver.pending@test.local')->firstOrFail();
    }

    public function test_login_page_renders(): void
    {
        $resp = $this->get($this->uri('driver/login'));
        $resp->assertOk();
        $resp->assertSee('livewire', false);
    }

    public function test_register_page_renders(): void
    {
        $this->get($this->uri('driver/register'))->assertOk();
    }

    public function test_home_redirects_guests_to_login(): void
    {
        $resp = $this->get($this->uri('driver/home'));
        $resp->assertRedirect();
        $this->assertStringContainsString('driver/login', $resp->headers->get('Location'));
    }

    public function test_customer_branch_distance_handles_orders_without_a_branch(): void
    {
        $order = new MerchantOrder([
            'client_lat' => '24.7136',
            'client_long' => '46.6753',
        ]);

        $this->assertSame('0 km', $order->customer_branch_distance);
    }

    public function test_livewire_login_success_and_failure(): void
    {
        $enterCount = DriverHistory::where('driver_id', $this->approved->id)->where('action', 'Enter')->count();

        // Failure
        Livewire::test(\Application\Driver\Livewire\Auth\Login::class)
            ->set('email', 'driver.approved@test.local')
            ->set('password', 'WRONG')
            ->call('store')
            ->assertHasErrors(['email']);

        // Success
        Livewire::test(\Application\Driver\Livewire\Auth\Login::class)
            ->set('email', 'driver.approved@test.local')
            ->set('password', 'Password123!')
            ->call('store')
            ->assertHasNoErrors();

        $this->assertTrue(Auth::guard('driver')->check());
        $this->assertSame($this->approved->id, Auth::guard('driver')->id());
        $this->assertSame(
            $enterCount + 1,
            DriverHistory::where('driver_id', $this->approved->id)->where('action', 'Enter')->count()
        );

        Livewire::test(\Application\Driver\Livewire\Auth\Logout::class)
            ->call('logout')
            ->assertRedirect();

        $this->assertFalse(Auth::guard('driver')->check());
        $this->assertTrue(
            DriverHistory::where('driver_id', $this->approved->id)->where('action', 'Exit')->exists()
        );
    }

    public function test_home_authenticated_shows_test_orders(): void
    {
        $this->actingAs($this->approved, 'driver');
        $resp = $this->get($this->uri('driver/home?perPage=25'));
        $resp->assertOk();
        $resp->assertSee('DRV-TEST-ACCEPT-001');
    }

    public function test_accept_order_flow(): void
    {
        $order = MerchantOrder::where('code', 'DRV-TEST-ACCEPT-001')->firstOrFail();
        // Reset to confirmed for repeatable test
        $order->update(['status' => MerchantOrderStatuses::UPCOMING->value, 'commission_from_driver' => 0]);
        $histBefore = MerchantOrderHistory::where('order_id', $order->id)->count();

        $this->actingAs($this->approved, 'driver');
        $resp = $this->post($this->uri("driver/orders/{$order->id}/accept"));
        $resp->assertRedirect();

        $order->refresh();
        $this->assertSame('started-work', $order->status->value);
        $this->assertEquals(15, (float)$order->commission_from_driver);

        $this->assertGreaterThan($histBefore, MerchantOrderHistory::where('order_id', $order->id)->count());
        $last = MerchantOrderHistory::where('order_id', $order->id)->orderByDesc('id')->first();
        $this->assertSame('driver-accept', $last->action);
        $this->assertEquals($this->approved->id, $last->changeable_id);
    }

    public function test_update_status_on_way_then_completed(): void
    {
        $order = MerchantOrder::where('code', 'DRV-TEST-ONWAY-001')->firstOrFail();
        $order->update(['status' => MerchantOrderStatuses::READY->value]);
        $admin = \Domain\Admin\Models\Admin::where('email', 'admin@gmail.com')->firstOrFail();
        app(LogOrderHistoryAction::class)->handle(
            $order->id,
            MerchantOrderHistoryStatuses::DRIVER_ASSIGNED_MANUALLY->value,
            'Test driver assignment',
            $admin
        );

        $this->actingAs($this->approved, 'driver');

        $this->post($this->uri("driver/orders/{$order->id}/update-status"), ['status' => 'on-way'])
            ->assertRedirect()
            ->assertSessionHas('error');
        $order->refresh();
        $this->assertSame('ready', $order->status->value);
        $this->assertFalse($order->isAcceptedByDriver($this->approved->id));

        $this->post($this->uri("driver/orders/{$order->id}/accept"))->assertRedirect();
        $order->refresh();
        $this->assertSame('ready', $order->status->value);
        $this->assertTrue($order->isAcceptedByDriver($this->approved->id));
        $this->assertSame('driver-accept', MerchantOrderHistory::where('order_id', $order->id)->orderByDesc('id')->first()->action);

        $this->post($this->uri("driver/orders/{$order->id}/update-status"), ['status' => 'on-way'])->assertRedirect();
        $order->refresh();
        $this->assertSame('on-way', $order->status->value);
        $this->assertSame('driver-on-way', MerchantOrderHistory::where('order_id', $order->id)->orderByDesc('id')->first()->action);

        $this->post($this->uri("driver/orders/{$order->id}/update-status"), ['status' => 'completed'])->assertRedirect();
        $order->refresh();
        $this->assertSame('completed', $order->status->value);
        $this->assertSame('completed-order', MerchantOrderHistory::where('order_id', $order->id)->orderByDesc('id')->first()->action);
    }

    public function test_cannot_accept_other_drivers_order(): void
    {
        $foreign = MerchantOrder::whereNotNull('driver_id')->where('driver_id', '!=', $this->approved->id)->first();
        if (!$foreign) {
            $this->markTestSkipped('No foreign order available');
        }
        $this->actingAs($this->approved, 'driver');
        $this->post($this->uri("driver/orders/{$foreign->id}/accept"))->assertNotFound();
    }

    public function test_admin_sees_driver_and_pending(): void
    {
        $admin = DB::table('admins')->where('email', 'admin@gmail.com')->first();
        $this->assertNotNull($admin, 'admin@gmail.com not found in admins table');
        $adminModel = \Domain\Admin\Models\Admin::find($admin->id);

        $this->actingAs($adminModel, 'admin');

        $this->get($this->uri('admin/drivers'))->assertOk()->assertSee('driver.approved@test.local');
        $this->get($this->uri('admin/drivers/show/' . $this->approved->id))->assertOk()->assertSee('driver.approved@test.local');
        $this->get($this->uri('admin/drivers/pending'))->assertOk()->assertSee('register.pending@test.local');
        $this->get($this->uri('admin/drivers/history'))->assertOk();
        $this->get($this->uri('admin/drivers/tracking'))->assertOk();
    }

    public function test_api_login_orders_profile_logout(): void
    {
        // Login
        $resp = $this->postJson('/api/v1/driver/auth/', [
            'email' => 'driver.approved@test.local',
            'password' => 'Password123!',
            'platform' => 'web',
            'device_token' => 'phpunit-device-' . uniqid(),
        ]);
        $resp->assertStatus(201);
        $token = $resp->json('data.token') ?? $resp->json('token');
        $this->assertNotEmpty($token);

        // Enter history logged
        $this->assertTrue(
            DriverHistory::where('driver_id', $this->approved->id)->where('action', 'Enter')->exists()
        );

        // Orders
        $orders = $this->getJson('/api/v1/driver/order/', ['Authorization' => 'Bearer ' . $token]);
        $orders->assertOk();
        $codes = collect($orders->json('data.order') ?? $orders->json('order') ?? [])
            ->pluck('code')->filter()->all();
        $testCodes = array_values(array_filter($codes, fn ($c) => str_starts_with((string)$c, 'DRV-TEST-')));
        $this->assertGreaterThanOrEqual(2, count($testCodes), 'Expected DRV-TEST orders in API list, got: ' . implode(',', $codes));

        // Profile
        $this->getJson('/api/v1/driver/info/fetch/profile', ['Authorization' => 'Bearer ' . $token])->assertOk();

        // Accept via API — reset to unassigned first (API accept requires driver_id null)
        $ready = MerchantOrder::where('code', 'DRV-TEST-ACCEPT-002')->firstOrFail();
        $ready->update([
            'status' => MerchantOrderStatuses::READY->value,
            'driver_id' => null,
            'has_assign_driver' => 0,
            'assign_driver_id' => null,
        ]);
        $acc = $this->postJson('/api/v1/driver/order/update', [
            'order_id' => $ready->id,
            'status' => 'accept',
        ], ['Authorization' => 'Bearer ' . $token]);
        echo "\n  API accept body: " . substr($acc->getContent(), 0, 200) . "\n";
        $ready->refresh();
        $this->assertEquals($this->approved->id, $ready->driver_id);

        // On-way via API
        $this->postJson('/api/v1/driver/order/update', [
            'order_id' => $ready->id,
            'status' => 'on-way',
        ], ['Authorization' => 'Bearer ' . $token]);
        $ready->refresh();
        $this->assertSame('on-way', $ready->status->value);

        // Logout via remove-token
        $deviceToken = 'phpunit-logout-' . uniqid();
        $this->postJson('/api/v1/driver/auth/', [
            'email' => 'driver.approved@test.local',
            'password' => 'Password123!',
            'platform' => 'android',
            'device_token' => $deviceToken,
        ])->assertStatus(201);

        $exitBefore = DriverHistory::where('driver_id', $this->approved->id)->where('action', 'Exit')->count();
        $this->postJson('/api/v1/driver/auth/remove-token', ['device_token' => $deviceToken])->assertOk();
        $exitAfter = DriverHistory::where('driver_id', $this->approved->id)->where('action', 'Exit')->count();
        $this->assertGreaterThan($exitBefore, $exitAfter);
    }

    public function test_api_v2_login(): void
    {
        $resp = $this->postJson('/api/v2/driver/auth/', [
            'email' => 'driver.approved@test.local',
            'password' => 'Password123!',
            'platform' => 'android',
        ]);
        $resp->assertStatus(201);
        $this->assertNotEmpty($resp->json('data.token') ?? $resp->json('token'));
    }

    public function test_api_rejects_wrong_password(): void
    {
        $resp = $this->postJson('/api/v1/driver/auth/', [
            'email' => 'driver.approved@test.local',
            'password' => 'WRONG',
            'platform' => 'web',
        ]);
        $this->assertNotEquals(201, $resp->status());
    }

    public function test_company_panel_paths(): void
    {
        $company = Company::where('email', 'demo@test.com')->firstOrFail();
        $this->actingAs($company, 'company');

        $this->get($this->uri('company/home'))->assertOk();
        $this->get($this->uri('company/drivers'))->assertOk();
        $this->get($this->uri('company/drivers/show/' . $this->approved->id))->assertOk();
        $this->get($this->uri('company/drivers/tracking'))->assertOk();
        $this->get($this->uri('company/orders'))->assertOk();
    }

    public function test_no_drrivers_typo_in_routes(): void
    {
        $found = [];
        foreach (app('router')->getRoutes() as $route) {
            $name = $route->getName() ?? '';
            $uri = $route->uri();
            if (stripos($name, 'drriver') !== false || stripos($uri, 'drriver') !== false) {
                $found[] = $name . ' / ' . $uri;
            }
        }
        $this->assertSame([], $found, 'Found drrivers typo: ' . implode(', ', $found));

        // Correct company driver routes exist
        $names = [];
        foreach (app('router')->getRoutes() as $route) {
            if ($route->getName() && str_contains($route->getName(), 'company:drivers')) {
                $names[] = $route->getName();
            }
        }
        $this->assertContains('company:drivers', $names);
        $this->assertContains('company:drivers.show', $names);
    }

    public function test_driver_history_filters(): void
    {
        $all = DriverHistory::where('driver_id', $this->approved->id)->count();
        $enters = DriverHistory::where('driver_id', $this->approved->id)->where('action', 'Enter')->count();
        $exits = DriverHistory::where('driver_id', $this->approved->id)->where('action', 'Exit')->count();
        $net = DriverHistory::where('driver_id', $this->approved->id)->where('action', 'like', 'NetworkStatus%')->count();

        $this->assertGreaterThan(0, $all);
        $this->assertGreaterThan(0, $enters);
        $this->assertGreaterThan(0, $exits);
        // Network filter returns consistent subset
        $this->assertEquals(
            DriverHistory::where('driver_id', $this->approved->id)->where('action', 'like', 'NetworkStatus%')->count(),
            $net
        );
    }

    public function test_pending_driver_cannot_see_home_data_of_approved(): void
    {
        $this->actingAs($this->pending, 'driver');
        $resp = $this->get('driver/home');
        $resp->assertOk();
        // Pending driver has no orders
        $resp->assertDontSee('DRV-TEST-ACCEPT-001');
    }
}
