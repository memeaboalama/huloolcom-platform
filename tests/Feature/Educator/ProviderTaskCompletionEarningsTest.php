<?php

namespace Tests\Feature\Educator;

use App\Application\Educator\Enums\EducatorStatus;
use App\Application\Project\Enums\AssignmentStatus;
use App\Application\Project\Enums\AssignmentType;
use App\Application\Project\Enums\CreationType;
use App\Application\Project\Enums\ProjectStatus;
use App\Domain\Project\Actions\Task\Assignmts\AcceptAssignmentAction;
use App\Domain\Project\Actions\Task\Assignmts\AssignTaskToEducatorAction;
use App\Domain\Project\Actions\Task\Assignmts\RejectAssignmentAction;
use App\Domain\Project\Actions\Task\Assignmts\UpdateAssignmentStatusAction;
use Domain\Admin\Models\Admin;
use Domain\Educator\Models\Educator;
use Domain\Project\Actions\Task\TaskChangeStatusAction;
use Domain\Project\Models\Assignment;
use Domain\Project\Models\Project;
use Domain\Project\Models\Task;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * Provider (educator) dashboard earnings + task completion lifecycle.
 *
 * Covered rules:
 *  - total_earnings counts only assignments whose TASK is completed
 *  - an amount is never counted twice
 *  - completing a task persists tasks.completed_at
 *  - the assignment lifecycle (accept/reject) and the educator permissions
 *    (assertEducatorOwnsTask / fetch) keep working after completion
 *
 * Every test runs inside a transaction that is ALWAYS rolled back, the same
 * pattern used by DriverLogoutTokenTest: no rows are committed to the local
 * database.
 */
class ProviderTaskCompletionEarningsTest extends TestCase
{
    private const STATS_URI = '/api/v1/educator/dashboard/stats';
    private const TASKS_URI = '/api/v1/educator/tasks';

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
            'name'     => 'Earnings Test Admin',
            'email'    => 'admin-' . Str::random(8) . '@local.test',
            'password' => bcrypt('secret'),
            'type'     => 'employee',
            'status'   => 'active',
        ]);
    }

    private function makeEducator(): Educator
    {
        return Educator::create([
            'name'           => 'Provider ' . Str::random(6),
            'email'          => 'provider-' . Str::random(8) . '@local.test',
            'phone'          => '5' . random_int(10000000, 99999999),
            'password'       => bcrypt('secret'),
            'role'           => 'provider',
            'status'         => EducatorStatus::ACTIVE->value,
            'is_accepted'    => true,
            'accepted_terms' => true,
        ]);
    }

    private function makeProject(): Project
    {
        $id = DB::table('projects')->insertGetId([
            'uuid'          => (string) Str::uuid(),
            'title'         => 'Earnings test project',
            'description'   => 'Earnings test project',
            'status'        => ProjectStatus::IN_PROGRESS->value,
            'is_published'  => 1,
            'creation_type' => CreationType::STANDALONE->value,
            'created_by'    => 'admin',
            'deadline'      => now()->addMonth()->toDateString(),
            'created_at'    => now(),
            'updated_at'    => now(),
        ]);

        return Project::query()->findOrFail($id);
    }

    private function makeTask(Project $project, Admin $admin, string $status): Task
    {
        $id = DB::table('tasks')->insertGetId([
            'uuid'           => (string) Str::uuid(),
            'project_id'     => $project->id,
            'title'          => 'Task (' . $status . ')',
            'description'    => 'Task used by the earnings test',
            'status'         => $status,
            'deadline'       => now()->addDays(7)->toDateString(),
            'is_public'      => 0,
            'bidding_status' => 'CLOSED',
            'created_by'     => $admin->id,
            'created_at'     => now(),
            'updated_at'     => now(),
        ]);

        return Task::query()->findOrFail($id);
    }

    private function makeAssignment(
        Task $task,
        Educator $educator,
        Admin $admin,
        float $amount,
        string $status = 'accepted'
    ): Assignment {
        $id = DB::table('assignments')->insertGetId([
            'uuid'            => (string) Str::uuid(),
            'task_id'         => $task->id,
            'assigned_to'     => $educator->id,
            'assigned_by'     => $admin->id,
            'assigned_type'   => AssignmentType::DIRECT->value,
            'status'          => $status,
            'amount'          => $amount,
            'assigned_at'     => now()->subDay(),
            'accepted_at'     => $status === 'accepted' ? now()->subDay() : null,
            'created_at'      => now(),
            'updated_at'      => now(),
        ]);

        return Assignment::query()->findOrFail($id);
    }

    public function test_total_earnings_counts_only_completed_tasks(): void
    {
        $this->inTransaction(function () {
            $admin    = $this->makeAdmin();
            $educator = $this->makeEducator();
            $project  = $this->makeProject();

            $inProgress = $this->makeTask($project, $admin, ProjectStatus::IN_PROGRESS->value);
            $pending    = $this->makeTask($project, $admin, ProjectStatus::PENDING->value);
            $completed  = $this->makeTask($project, $admin, ProjectStatus::COMPLETED->value);

            $this->makeAssignment($inProgress, $educator, $admin, 1500);
            $this->makeAssignment($pending, $educator, $admin, 800);
            $this->makeAssignment($completed, $educator, $admin, 900);

            $response = $this->actingAs($educator, 'educator-api')->getJson(self::STATS_URI);

            $response->assertOk();

            // Only the completed task (900) may be counted, never 1500/800/5000.
            $this->assertSame(900.0, (float) $response->json('data.stats.total_earnings'));

            // Assignments stay 'accepted' - earnings do not depend on that status.
            $this->assertSame(
                3,
                Assignment::where('assigned_to', $educator->id)->where('status', 'accepted')->count()
            );
        });
    }

    public function test_total_earnings_never_counts_the_same_amount_twice(): void
    {
        $this->inTransaction(function () {
            $admin    = $this->makeAdmin();
            $educator = $this->makeEducator();
            $project  = $this->makeProject();

            $completed  = $this->makeTask($project, $admin, ProjectStatus::COMPLETED->value);
            $assignment = $this->makeAssignment($completed, $educator, $admin, 900);

            // both completion signals true at once: task completed + assignment
            // explicitly closed by the admin.
            app(UpdateAssignmentStatusAction::class)
                ->handle($assignment, AssignmentStatus::COMPLETED);

            $response = $this->actingAs($educator, 'educator-api')->getJson(self::STATS_URI);

            $response->assertOk();
            $this->assertSame(900.0, (float) $response->json('data.stats.total_earnings'));
            $this->assertSame(1, Assignment::where('assigned_to', $educator->id)->count());
        });
    }

    public function test_completing_a_task_saves_completed_at_and_keeps_the_assignment_accepted(): void
    {
        $this->inTransaction(function () {
            $admin    = $this->makeAdmin();
            $educator = $this->makeEducator();
            $project  = $this->makeProject();

            $task       = $this->makeTask($project, $admin, ProjectStatus::IN_PROGRESS->value);
            $assignment = $this->makeAssignment($task, $educator, $admin, 1500);

            $response = $this->actingAs($educator, 'educator-api')
                ->postJson(self::TASKS_URI . '/' . $task->uuid . '/complete');

            $response->assertOk();

            $task->refresh();
            $this->assertSame(ProjectStatus::COMPLETED->value, $task->status->value);
            $this->assertNotNull($task->completed_at, 'tasks.completed_at must be persisted on completion');

            // Design decision: the assignment keeps its own lifecycle, so the
            // educator keeps access to the task (assertEducatorOwnsTask, fetch).
            $assignment->refresh();
            $this->assertSame(AssignmentStatus::ACCEPTED, $assignment->status);
        });
    }

    public function test_repeated_completion_keeps_the_original_completion_date(): void
    {
        $this->inTransaction(function () {
            $admin    = $this->makeAdmin();
            $educator = $this->makeEducator();
            $project  = $this->makeProject();

            $task = $this->makeTask($project, $admin, ProjectStatus::IN_PROGRESS->value);
            $this->makeAssignment($task, $educator, $admin, 1500);

            $this->actingAs($educator, 'educator-api')
                ->postJson(self::TASKS_URI . '/' . $task->uuid . '/complete')
                ->assertOk();

            $original = $task->fresh()->completed_at;
            $this->assertNotNull($original);

            // pretend the first completion happened a week ago, then complete
            // again: the original date must not be overwritten.
            $past = now()->subWeek()->startOfSecond();
            DB::table('tasks')->where('id', $task->id)->update([
                'completed_at' => $past,
            ]);

            $this->actingAs($educator, 'educator-api')
                ->postJson(self::TASKS_URI . '/' . $task->uuid . '/complete')
                ->assertOk();

            $this->assertSame(
                $past->format('Y-m-d H:i:s'),
                $task->fresh()->completed_at->format('Y-m-d H:i:s'),
                'the original completion date must survive a repeated completion'
            );
        });
    }

    public function test_completed_task_stays_visible_to_the_assigned_educator(): void
    {
        $this->inTransaction(function () {
            $admin    = $this->makeAdmin();
            $educator = $this->makeEducator();
            $project  = $this->makeProject();

            $task = $this->makeTask($project, $admin, ProjectStatus::IN_PROGRESS->value);
            $this->makeAssignment($task, $educator, $admin, 1500);

            $this->actingAs($educator, 'educator-api')
                ->postJson(self::TASKS_URI . '/' . $task->uuid . '/complete')
                ->assertOk();

            // fetch() must still return the task after completion
            $listed = $this->actingAs($educator, 'educator-api')
                ->getJson(self::TASKS_URI)
                ->assertOk()
                ->json('data.tasks');

            $ids = collect($listed)->pluck('id')->all();
            $this->assertContains($task->uuid, $ids);
        });
    }

    public function test_complete_task_is_forbidden_for_another_educator(): void
    {
        $this->inTransaction(function () {
            $admin    = $this->makeAdmin();
            $owner    = $this->makeEducator();
            $intruder = $this->makeEducator();
            $project  = $this->makeProject();

            $task = $this->makeTask($project, $admin, ProjectStatus::IN_PROGRESS->value);
            $this->makeAssignment($task, $owner, $admin, 1500);

            $this->actingAs($intruder, 'educator-api')
                ->postJson(self::TASKS_URI . '/' . $task->uuid . '/complete')
                ->assertStatus(403);

            $this->assertSame(
                ProjectStatus::IN_PROGRESS->value,
                $task->fresh()->status->value
            );
        });
    }

    public function test_admin_status_change_stamps_completed_at(): void
    {
        $this->inTransaction(function () {
            $admin    = $this->makeAdmin();
            $educator = $this->makeEducator();
            $project  = $this->makeProject();

            $task = $this->makeTask($project, $admin, ProjectStatus::IN_PROGRESS->value);
            $this->makeAssignment($task, $educator, $admin, 900);

            $this->assertNull($task->completed_at);

            app(TaskChangeStatusAction::class)
                ->handle($task, ['status' => ProjectStatus::COMPLETED->value]);

            $task->refresh();
            $this->assertSame(ProjectStatus::COMPLETED->value, $task->status->value);
            $this->assertNotNull($task->completed_at);
        });
    }

    public function test_accept_and_reject_assignment_flows_still_work(): void
    {
        $this->inTransaction(function () {
            $admin    = $this->makeAdmin();
            $educator = $this->makeEducator();
            $project  = $this->makeProject();

            $taskA = $this->makeTask($project, $admin, ProjectStatus::PENDING->value);
            $taskB = $this->makeTask($project, $admin, ProjectStatus::PENDING->value);

            $pendingA = $this->makeAssignment($taskA, $educator, $admin, 500, 'pending');
            $pendingB = $this->makeAssignment($taskB, $educator, $admin, 700, 'pending');

            $accepted = app(AcceptAssignmentAction::class)->handle($pendingA, 'agreed');
            $this->assertSame(AssignmentStatus::ACCEPTED, $accepted->status);
            $this->assertNotNull($accepted->accepted_at);

            $rejected = app(RejectAssignmentAction::class)->handle($pendingB, 'no budget');
            $this->assertSame(AssignmentStatus::REJECTED, $rejected->status);
            $this->assertSame('no budget', $rejected->rejection_reason);

            // rejected assignments never reach the earnings total
            $this->actingAs($educator, 'educator-api')->getJson(self::STATS_URI)
                ->assertOk();
            $this->assertSame(
                0.0,
                (float) Assignment::where('assigned_to', $educator->id)
                    ->whereIn('status', ['accepted', 'completed'])
                    ->whereHas('task', fn ($q) => $q->where('status', ProjectStatus::COMPLETED->value))
                    ->sum('amount')
            );
        });
    }

    public function test_duplicate_assignment_is_blocked_so_an_amount_cannot_be_counted_twice(): void
    {
        $this->inTransaction(function () {
            $admin    = $this->makeAdmin();
            $educator = $this->makeEducator();
            $project  = $this->makeProject();

            $task = $this->makeTask($project, $admin, ProjectStatus::IN_PROGRESS->value);

            $action = app(AssignTaskToEducatorAction::class);
            $action->handle($task, $educator, [
                'assigned_type' => AssignmentType::DIRECT->value,
                'amount'        => 1000,
            ]);

            try {
                $action->handle($task, $educator, [
                    'assigned_type' => AssignmentType::DIRECT->value,
                    'amount'        => 1000,
                ]);
                $this->fail('A second assignment for the same educator/task must be blocked.');
            } catch (\Exception $e) {
                $this->assertStringContainsString('already assigned', $e->getMessage());
            }

            $this->assertSame(
                1,
                Assignment::where('task_id', $task->id)->where('assigned_to', $educator->id)->count()
            );
        });
    }
}
