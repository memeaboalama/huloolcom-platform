<?php

namespace Tests\Feature\Educator;

use App\Application\Educator\Enums\EducatorStatus;
use App\Support\Enum\StatusEnum;
use Domain\AppBase\Models\Edu\Service;
use Domain\Educator\Models\Educator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\TestCase;

class EducatorServiceOptionsTest extends TestCase
{
    public function test_educator_can_fetch_active_services_and_add_one_without_admin_access(): void
    {
        DB::beginTransaction();

        try {
            $educator = Educator::create([
                'name' => 'Service Test Provider',
                'email' => 'service-' . Str::random(8) . '@local.test',
                'phone' => '5' . random_int(10000000, 99999999),
                'password' => bcrypt('secret'),
                'role' => 'provider',
                'status' => EducatorStatus::ACTIVE->value,
                'is_accepted' => true,
                'accepted_terms' => true,
            ]);

            $activeService = Service::create([
                'name' => ['en' => 'Active test service', 'ar' => 'خدمة اختبار متاحة'],
                'description' => ['en' => 'Test service description', 'ar' => 'وصف خدمة اختبار'],
                'status' => StatusEnum::ACTIVE,
            ]);
            $inactiveService = Service::create([
                'name' => ['en' => 'Inactive test service', 'ar' => 'خدمة اختبار غير متاحة'],
                'status' => StatusEnum::INACTIVE,
            ]);

            $this->actingAs($educator, 'educator-api')
                ->getJson('/api/v1/educator/profile/services/options')
                ->assertOk()
                ->assertJsonFragment(['id' => $activeService->uuid])
                ->assertJsonMissing(['id' => $inactiveService->uuid]);

            $this->actingAs($educator, 'educator-api')
                ->postJson('/api/v1/educator/profile/services', [
                    'service_id' => $activeService->uuid,
                    'custom_description' => 'My test service',
                    'is_custom' => false,
                ])
                ->assertOk();

            $this->assertDatabaseHas('educator_services', [
                'educator_id' => $educator->id,
                'service_id' => $activeService->id,
            ]);
        } finally {
            DB::rollBack();
        }
    }
}
