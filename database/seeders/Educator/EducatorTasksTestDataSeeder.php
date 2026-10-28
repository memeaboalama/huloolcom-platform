<?php

namespace Database\Seeders\Educator;

use App\Application\Project\Enums\AssignmentStatus;
use App\Application\Project\Enums\AssignmentType;
use App\Application\Project\Enums\CreationType;
use App\Application\Project\Enums\ProjectStatus;
use Domain\Admin\Models\Admin;
use Domain\Educator\Models\Educator;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * EducatorTasksTestDataSeeder
 * ---------------------------
 * Local-only sample data so the educator tasks page
 * (/educator/tasks) can be reviewed in the browser for the local
 * provider account provider.test@local.test (password: 123456).
 *
 * Seeds 2 projects / 5 tasks covering every list filter:
 *   - IN_PROGRESS with subtasks (progress bar ~50%)
 *   - PENDING not started yet
 *   - OVERDUE (in_progress + deadline in the past)
 *   - COMPLETED (with completed subtasks + completed_at)
 *   - RECENT vs older created_at so the date filters do something
 *
 * Guarantees:
 *   - Additive only: it never truncates, deletes or updates existing rows.
 *   - Idempotent: matched on the project title marker "[LOCAL-TASKS]",
 *     so re-running adds nothing.
 *   - Reuses the existing models/tables (projects, tasks, assignments,
 *     sub_tasks). No new schema.
 *
 * Run with:
 *   php artisan db:seed --class=Database\Seeders\Educator\EducatorTasksTestDataSeeder
 */
class EducatorTasksTestDataSeeder extends Seeder
{
    private const EMAIL = 'provider.test@local.test';
    private const MARKER = '[LOCAL-TASKS]';

    public function run(): void
    {
        if (DB::table('projects')->where('title', 'like', self::MARKER . '%')->exists()) {
            $this->command->info('Local tasks data already exists — nothing added.');
            return;
        }

        $educator = Educator::where('email', self::EMAIL)->first();
        if (!$educator) {
            // Reuse the existing provider seeder so the login account is
            // created first when it is still missing.
            $this->call(ProviderTestDataSeeder::class);
            $educator = Educator::where('email', self::EMAIL)->firstOrFail();
        }

        $admin = Admin::query()->first();
        if (!$admin) {
            $admin = Admin::create([
                'name'     => 'Local Test Admin',
                'email'    => 'admin@local.test',
                'password' => bcrypt('123456'),
                'type'     => 'employee',
                'status'   => 'active',
            ]);
        }

        DB::transaction(function () use ($educator, $admin) {
            $projectA = $this->createProject(
                title: self::MARKER . ' تطوير منهج الرياضيات',
                description: 'مشروع محلي لاختبار شاشة المهام — يحتوي على مهمة منفذة ومهمة لم تبدأ بعد.',
                budget: 4000,
                deadline: now()->addMonth()->toDateString()
            );

            $projectB = $this->createProject(
                title: self::MARKER . ' إعداد محتوى الفيزياء',
                description: 'مشروع محلي لاختبار فلاتر المهام — يحتوي على مهمة متأخرة ومهمة مكتملة.',
                budget: 3500,
                deadline: now()->addDays(20)->toDateString()
            );

            // 1) IN_PROGRESS + subtasks (progress = 50%), deadline in the future.
            $task1 = $this->createTask($projectA->id, $admin->id, [
                'title'       => self::MARKER . ' إعداد الفصل الأول من المقرر',
                'description' => 'كتابة مقدمة الفصل الأول وصياغة أهداف التعلم. (بيانات محلية للاختبار)',
                'status'      => ProjectStatus::IN_PROGRESS->value,
                'budget'      => 1500,
                'deadline'    => now()->addDays(10)->toDateString(),
            ]);
            $this->assign($task1->id, $educator->id, $admin->id, 1500, now()->subDays(3));
            $this->subTasks($task1->id, $admin->id, [
                ['title' => 'كتابة مقدمة الفصل', 'completed' => true],
                ['title' => 'صياغة أهداف التعلم', 'completed' => true],
                ['title' => 'إعداد التدريبات', 'completed' => false],
                ['title' => 'مراجعة المحتوى النهائي', 'completed' => false],
            ]);

            // 2) PENDING, not started, no subtasks, deadline in the future.
            $task2 = $this->createTask($projectA->id, $admin->id, [
                'title'       => self::MARKER . ' تصميم اختبار قصير',
                'description' => 'مهمة لم تبدأ بعد — مناسبة لاختبار الفيلتر "قيد التنفيذ" بعد تغيير الحالة. (بيانات محلية)',
                'status'      => ProjectStatus::PENDING->value,
                'budget'      => 800,
                'deadline'    => now()->addDays(25)->toDateString(),
            ]);
            $this->assign($task2->id, $educator->id, $admin->id, 800, now()->subDays(2));

            // 3) OVERDUE: in_progress with a deadline already in the past.
            $task3 = $this->createTask($projectB->id, $admin->id, [
                'title'       => self::MARKER . ' تلخيص درس الحركة',
                'description' => 'مهمة متأخرة عن قصد لاختبار فيلتر "المتأخرة". (بيانات محلية)',
                'status'      => ProjectStatus::IN_PROGRESS->value,
                'budget'      => 1200,
                'deadline'    => now()->subDays(5)->toDateString(),
            ]);
            $this->assign($task3->id, $educator->id, $admin->id, 1200, now()->subDays(12));
            $this->subTasks($task3->id, $admin->id, [
                ['title' => 'جمع المصادر', 'completed' => true],
                ['title' => 'كتابة الملخص', 'completed' => false],
                ['title' => 'التسليم', 'completed' => false],
            ]);

            // 4) COMPLETED, all subtasks done, completed_at set.
            $task4 = $this->createTask($projectB->id, $admin->id, [
                'title'       => self::MARKER . ' مراجعة تقرير المعمل',
                'description' => 'مهمة مكتملة بالفعل لاختبار فيلتر "المكتملة". (بيانات محلية)',
                'status'      => ProjectStatus::COMPLETED->value,
                'budget'      => 900,
                'deadline'    => now()->subDays(15)->toDateString(),
            ]);
            $assignment4 = $this->assign($task4->id, $educator->id, $admin->id, 900, now()->subDays(20));
            DB::table('assignments')->where('id', $assignment4->id)->update([
                'completed_at' => now()->subDays(10),
            ]);
            DB::table('tasks')->where('id', $task4->id)->update([
                'completed_at' => now()->subDays(10),
            ]);
            $this->subTasks($task4->id, $admin->id, [
                ['title' => 'قراءة التقرير', 'completed' => true],
                ['title' => 'كتابة الملاحظات', 'completed' => true],
            ]);

            // 5) Older IN_PROGRESS (created > 14 days ago) so the
            //    "recently added" filter actually excludes something.
            $task5 = $this->createTask($projectB->id, $admin->id, [
                'title'       => self::MARKER . ' تجهيز عرض تقديمي',
                'description' => 'مهمة قديمة نسبياً لاختبار الفيلتر "المضافة حديثاً". (بيانات محلية)',
                'status'      => ProjectStatus::IN_PROGRESS->value,
                'budget'      => 600,
                'deadline'    => now()->addDays(40)->toDateString(),
            ]);
            $this->assign($task5->id, $educator->id, $admin->id, 600, now()->subDays(30));
            DB::table('tasks')->where('id', $task5->id)->update([
                'created_at' => now()->subDays(30),
            ]);
        });

        $this->report($educator);
    }

    private function createProject(string $title, string $description, float $budget, string $deadline): object
    {
        $id = DB::table('projects')->insertGetId([
            'uuid'           => (string) Str::uuid(),
            'title'          => $title,
            'description'    => $description,
            'tags'           => json_encode(['local', 'test']),
            'status'         => ProjectStatus::IN_PROGRESS->value,
            'budget'         => $budget,
            'is_published'   => 1,
            'creation_type'  => CreationType::STANDALONE->value,
            'created_by'     => 'admin',
            'deadline'       => $deadline,
            'created_at'     => now(),
            'updated_at'     => now(),
        ]);

        return DB::table('projects')->where('id', $id)->first();
    }

    private function createTask(int $projectId, int $adminId, array $attrs): object
    {
        $id = DB::table('tasks')->insertGetId([
            'uuid'           => (string) Str::uuid(),
            'project_id'     => $projectId,
            'title'          => $attrs['title'],
            'description'    => $attrs['description'],
            'tags'           => json_encode(['local', 'test']),
            'status'         => $attrs['status'],
            'budget'         => $attrs['budget'],
            'deadline'       => $attrs['deadline'],
            'is_public'      => 0,
            'bidding_status' => 'CLOSED',
            'created_by'     => $adminId,
            'created_at'     => now(),
            'updated_at'     => now(),
        ]);

        return DB::table('tasks')->where('id', $id)->first();
    }

    private function assign(int $taskId, int $educatorId, int $adminId, float $amount, $assignedAt): object
    {
        $id = DB::table('assignments')->insertGetId([
            'uuid'           => (string) Str::uuid(),
            'task_id'        => $taskId,
            'assigned_to'    => $educatorId,
            'assigned_by'    => $adminId,
            'assigned_type'  => AssignmentType::DIRECT->value,
            'bid_id'         => null,
            'status'         => AssignmentStatus::ACCEPTED->value,
            'amount'         => $amount,
            'agreement_notes' => 'تعيين محلي للاختبار',
            'assigned_at'    => $assignedAt,
            'accepted_at'    => $assignedAt,
            'created_at'     => now(),
            'updated_at'     => now(),
        ]);

        return DB::table('assignments')->where('id', $id)->first();
    }

    /**
     * @param array<int, array{title: string, completed: bool}> $subTasks
     */
    private function subTasks(int $taskId, int $adminId, array $subTasks): void
    {
        foreach ($subTasks as $sub) {
            DB::table('sub_tasks')->insert([
                'uuid'            => (string) Str::uuid(),
                'creatable_type'  => Admin::class,
                'creatable_id'    => $adminId,
                'task_id'         => $taskId,
                'title'           => $sub['title'],
                'completed'       => $sub['completed'] ? 1 : 0,
                'completed_at'    => $sub['completed'] ? now() : null,
                'created_at'      => now(),
                'updated_at'      => now(),
            ]);
        }
    }

    private function report(Educator $educator): void
    {
        $taskCount = DB::table('tasks')
            ->join('assignments', 'assignments.task_id', '=', 'tasks.id')
            ->where('assignments.assigned_to', $educator->id)
            ->where('assignments.status', AssignmentStatus::ACCEPTED->value)
            ->whereNull('tasks.deleted_at')
            ->count();

        $this->command->info("Tasks seeded for: #{$educator->id} — {$educator->email}");
        $this->command->line("  accepted tasks: $taskCount (pending / in progress / overdue / completed)");
        $this->command->line('  list page     : http://localhost:5174/educator/tasks');
        $this->command->line('  login         : provider.test@local.test / 123456');
    }
}
