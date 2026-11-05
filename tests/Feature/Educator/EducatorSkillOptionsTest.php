<?php

namespace Tests\Feature\Educator;

use App\Application\Educator\Enums\EducatorStatus;
use App\Support\Enum\StatusEnum;
use Domain\AppBase\Models\Edu\Skill;
use Domain\Educator\Models\Educator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\TestCase;

class EducatorSkillOptionsTest extends TestCase
{
    public function test_educator_can_fetch_active_skills_and_add_one_without_admin_access(): void
    {
        DB::beginTransaction();

        try {
            $educator = Educator::create([
                'name' => 'Skill Test Provider',
                'email' => 'skill-' . Str::random(8) . '@local.test',
                'phone' => '5' . random_int(10000000, 99999999),
                'password' => bcrypt('secret'),
                'role' => 'provider',
                'status' => EducatorStatus::ACTIVE->value,
                'is_accepted' => true,
                'accepted_terms' => true,
            ]);

            $activeSkill = Skill::create([
                'name' => ['en' => 'Active test skill', 'ar' => 'مهارة اختبار متاحة'],
                'status' => StatusEnum::ACTIVE,
            ]);
            $inactiveSkill = Skill::create([
                'name' => ['en' => 'Inactive test skill', 'ar' => 'مهارة اختبار غير متاحة'],
                'status' => StatusEnum::INACTIVE,
            ]);

            $this->actingAs($educator, 'educator-api')
                ->getJson('/api/v1/educator/profile/skills/options')
                ->assertOk()
                ->assertJsonFragment(['id' => $activeSkill->uuid])
                ->assertJsonMissing(['id' => $inactiveSkill->uuid]);

            $this->actingAs($educator, 'educator-api')
                ->postJson('/api/v1/educator/profile/skills', [
                    'skill_id' => $activeSkill->uuid,
                    'is_custom' => false,
                    'level' => 3,
                ])
                ->assertOk();

            $this->assertDatabaseHas('educator_skills', [
                'educator_id' => $educator->id,
                'skill_id' => $activeSkill->id,
            ]);
        } finally {
            DB::rollBack();
        }
    }
}
