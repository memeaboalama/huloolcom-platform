<?php

namespace Tests\Feature\Project;

use App\Application\Project\Enums\AssignmentStatus;
use App\Application\Project\Enums\AssignmentType;
use App\Application\Project\Enums\BidStatus;
use App\Application\Project\Enums\CreationType;
use App\Application\Project\Enums\ProjectStatus;
use App\Application\Project\Enums\ProjectTab;
use App\Application\Project\Enums\TaskStatus;
use Domain\Admin\Models\Admin;
use Domain\Educator\Models\Educator;
use Domain\Project\Models\Project;
use Domain\Project\Repositories\ProjectRepository;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\TestCase;

class ProjectTabFilterTest extends TestCase
{
    private string $marker;

    protected function setUp(): void
    {
        parent::setUp();

        $this->marker = '[TAB-TEST-'.Str::upper(Str::random(8)).']';
    }

    private function inTransaction(callable $fn): void
    {
        DB::beginTransaction();
        try {
            $fn();
        } finally {
            DB::rollBack();
        }
    }

    private function makeAdmin(): Admin
    {
        return Admin::create([
            'name' => 'Project Tab Test Admin',
            'email' => 'tab-admin-'.Str::random(8).'@local.test',
            'password' => bcrypt('secret'),
            'type' => 'employee',
            'status' => 'active',
        ]);
    }

    private function makeEducator(): Educator
    {
        return Educator::create([
            'name' => 'Project Tab Educator '.Str::random(4),
            'email' => 'tab-educator-'.Str::random(8).'@local.test',
            'phone' => '5'.random_int(10000000, 99999999),
            'password' => bcrypt('secret'),
            'role' => 'provider',
            'status' => 'active',
            'accepted_terms' => true,
        ]);
    }

    private function makeProject(Admin $admin, string $status, string $suffix): Project
    {
        $id = DB::table('projects')->insertGetId([
            'uuid' => (string) Str::uuid(),
            'title' => $this->marker.' '.$suffix,
            'description' => 'Tab filter fixture',
            'status' => $status,
            'is_published' => 1,
            'creation_type' => CreationType::STANDALONE->value,
            'created_by' => (string) $admin->id,
            'deadline' => now()->addMonth()->toDateString(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return Project::query()->findOrFail($id);
    }

    private function makeTask(Project $project, Admin $admin, string $suffix): int
    {
        return DB::table('tasks')->insertGetId([
            'uuid' => (string) Str::uuid(),
            'project_id' => $project->id,
            'title' => 'Task '.$suffix,
            'description' => 'Tab filter fixture task',
            'status' => TaskStatus::PENDING->value,
            'deadline' => now()->addWeek()->toDateString(),
            'is_public' => 0,
            'bidding_status' => 'CLOSED',
            'created_by' => $admin->id,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function addBid(Project $project, Educator $educator, Admin $admin, BidStatus $status): int
    {
        $bidId = DB::table('bids')->insertGetId([
            'uuid' => (string) Str::uuid(),
            'project_id' => $project->id,
            'educator_id' => $educator->id,
            'total_amount' => 1000,
            'proposal' => 'Tab filter fixture bid',
            'delivery_days' => 7,
            'links' => json_encode([]),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('bid_tasks')->insert([
            'uuid' => (string) Str::uuid(),
            'bid_id' => $bidId,
            'task_id' => $this->makeTask($project, $admin, 'bid-'.$bidId),
            'status' => $status->value,
            'amount' => 1000,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return $bidId;
    }

    private function addAcceptedAssignment(Project $project, Educator $educator, Admin $admin): void
    {
        DB::table('assignments')->insert([
            'uuid' => (string) Str::uuid(),
            'task_id' => $this->makeTask($project, $admin, 'assignment'),
            'assigned_to' => $educator->id,
            'assigned_by' => $admin->id,
            'assigned_type' => AssignmentType::DIRECT->value,
            'status' => AssignmentStatus::ACCEPTED->value,
            'amount' => 500,
            'assigned_at' => now()->subDay(),
            'accepted_at' => now()->subDay(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function repository(): ProjectRepository
    {
        return app(ProjectRepository::class);
    }

    private function projectIdsForTab(string $tab): array
    {
        $projects = $this->repository()->filterAndPaginate(
            ['search' => $this->marker, 'tab' => $tab],
            [],
            50
        );

        return collect($projects->items())->pluck('id')->map(fn ($id) => (int) $id)->all();
    }

    private function assertProjectIds(array $expected, array $actual): void
    {
        sort($expected);
        sort($actual);

        $this->assertSame($expected, $actual);
    }

    public function test_tab_counts_use_the_search_filter_and_cover_every_tab(): void
    {
        $this->inTransaction(function () {
            $admin = $this->makeAdmin();
            $educator = $this->makeEducator();
            $secondEducator = $this->makeEducator();

            $this->makeProject($admin, ProjectStatus::PENDING->value, 'no-offers-pending');
            $this->makeProject($admin, ProjectStatus::IN_PROGRESS->value, 'no-offers-in-progress');
            $this->addBid($this->makeProject($admin, ProjectStatus::PENDING->value, 'rejected'), $educator, $admin, BidStatus::REJECTED);
            $this->addBid($this->makeProject($admin, ProjectStatus::PENDING->value, 'pending'), $educator, $admin, BidStatus::PENDING);
            $this->addBid($this->makeProject($admin, ProjectStatus::PENDING->value, 'pending-second'), $secondEducator, $admin, BidStatus::PENDING);
            $this->addBid($this->makeProject($admin, ProjectStatus::PENDING->value, 'approved'), $educator, $admin, BidStatus::APPROVED);
            $this->addBid($this->makeProject($admin, ProjectStatus::PENDING->value, 'assigned'), $educator, $admin, BidStatus::ASSIGNED);
            $this->makeProject($admin, ProjectStatus::COMPLETED->value, 'completed');
            $this->addBid($this->makeProject($admin, ProjectStatus::CANCELED->value, 'canceled'), $educator, $admin, BidStatus::PENDING);
            $this->addAcceptedAssignment($this->makeProject($admin, ProjectStatus::PENDING->value, 'assignment-only'), $educator, $admin);

            $counts = $this->repository()->tabCounts(['search' => $this->marker]);

            $this->assertSame([
                ProjectTab::ALL->value => 10,
                ProjectTab::WITH_OFFERS->value => 6,
                ProjectTab::WITHOUT_OFFERS->value => 4,
                ProjectTab::PENDING_REVIEW->value => 3,
                ProjectTab::IN_PROGRESS->value => 3,
                ProjectTab::COMPLETED->value => 1,
                ProjectTab::CANCELED->value => 1,
            ], $counts);

            $countsWithTabAndPagination = $this->repository()->tabCounts([
                'search' => $this->marker,
                'tab' => ProjectTab::COMPLETED->value,
                'paginated' => 'true',
                'per_page' => 1,
                'page' => 2,
            ]);

            $this->assertSame($counts, $countsWithTabAndPagination);
        });
    }

    public function test_each_tab_returns_only_its_matching_projects_without_duplicates(): void
    {
        $this->inTransaction(function () {
            $admin = $this->makeAdmin();
            $educator = $this->makeEducator();
            $secondEducator = $this->makeEducator();

            $nonePending = $this->makeProject($admin, ProjectStatus::PENDING->value, 'no-offers-pending');
            $noneInProgress = $this->makeProject($admin, ProjectStatus::IN_PROGRESS->value, 'no-offers-in-progress');
            $rejected = $this->makeProject($admin, ProjectStatus::PENDING->value, 'rejected');
            $pending = $this->makeProject($admin, ProjectStatus::PENDING->value, 'pending');
            $approved = $this->makeProject($admin, ProjectStatus::PENDING->value, 'approved');
            $assigned = $this->makeProject($admin, ProjectStatus::PENDING->value, 'assigned');
            $completed = $this->makeProject($admin, ProjectStatus::COMPLETED->value, 'completed');
            $canceled = $this->makeProject($admin, ProjectStatus::CANCELED->value, 'canceled');
            $assignmentOnly = $this->makeProject($admin, ProjectStatus::PENDING->value, 'assignment-only');

            $this->addBid($rejected, $educator, $admin, BidStatus::REJECTED);
            $this->addBid($pending, $educator, $admin, BidStatus::PENDING);
            $this->addBid($pending, $secondEducator, $admin, BidStatus::PENDING);
            $this->addBid($approved, $educator, $admin, BidStatus::APPROVED);
            $this->addBid($assigned, $educator, $admin, BidStatus::ASSIGNED);
            $this->addBid($canceled, $educator, $admin, BidStatus::PENDING);
            $this->addAcceptedAssignment($assignmentOnly, $educator, $admin);

            $all = [
                $nonePending->id,
                $noneInProgress->id,
                $rejected->id,
                $pending->id,
                $approved->id,
                $assigned->id,
                $completed->id,
                $canceled->id,
                $assignmentOnly->id,
            ];

            $withOffers = [$rejected->id, $pending->id, $approved->id, $assigned->id, $canceled->id];
            $withoutOffers = [$nonePending->id, $noneInProgress->id, $completed->id, $assignmentOnly->id];
            $pendingReview = [$pending->id, $canceled->id];
            $inProgress = [$noneInProgress->id, $assigned->id, $assignmentOnly->id];

            $this->assertProjectIds($all, $this->projectIdsForTab(ProjectTab::ALL->value));
            $this->assertProjectIds($withOffers, $this->projectIdsForTab(ProjectTab::WITH_OFFERS->value));
            $this->assertProjectIds($withoutOffers, $this->projectIdsForTab(ProjectTab::WITHOUT_OFFERS->value));
            $this->assertProjectIds($pendingReview, $this->projectIdsForTab(ProjectTab::PENDING_REVIEW->value));
            $this->assertProjectIds($inProgress, $this->projectIdsForTab(ProjectTab::IN_PROGRESS->value));
            $this->assertProjectIds([$completed->id], $this->projectIdsForTab(ProjectTab::COMPLETED->value));
            $this->assertProjectIds([$canceled->id], $this->projectIdsForTab(ProjectTab::CANCELED->value));

            $pendingIds = $this->projectIdsForTab(ProjectTab::PENDING_REVIEW->value);
            $this->assertSame(count($pendingIds), count(array_unique($pendingIds)));
        });
    }

    public function test_tab_counts_endpoint_is_not_captured_by_the_project_show_route(): void
    {
        $this->inTransaction(function () {
            $admin = $this->makeAdmin();
            $this->makeProject($admin, ProjectStatus::PENDING->value, 'endpoint');

            $response = $this->actingAs($admin, 'admin-api')->getJson(
                '/api/v1/admin/projects/tab-counts?search='.urlencode($this->marker)
            );

            $response->assertOk();
            $response->assertJsonPath('data.counts.all', 1);
            $response->assertJsonPath('data.counts.without_offers', 1);
            $response->assertJsonPath('data.counts.with_offers', 0);
        });
    }
}
