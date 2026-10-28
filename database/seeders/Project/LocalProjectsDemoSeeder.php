<?php

namespace Database\Seeders\Project;

use App\Application\Project\Enums\AssignmentStatus;
use App\Application\Project\Enums\AssignmentType;
use App\Application\Project\Enums\BidStatus;
use App\Application\Project\Enums\CreationType;
use App\Application\Project\Enums\ProjectStatus;
use App\Application\Project\Enums\TaskStatus;
use Domain\Admin\Models\Admin;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * LocalProjectsDemoSeeder
 * -----------------------
 * Additive sample data for the admin projects screen
 * (/admin/projects) so both cases can be reviewed:
 *
 *   - a project that HAS offers (pending + approved bids / bid tasks)
 *   - a project that HAS NO offers (open bidding, zero bids)
 *   - an IN_PROGRESS project whose task is already assigned and started
 *
 * Guarantees:
 *   - Additive only: no truncate, no deletes, no updates to existing rows.
 *   - Idempotent: matched on the project title marker "[LOCAL-PROJECTS]".
 *   - Reuses the existing tables (projects, tasks, bids, bid_tasks,
 *     assignments, sub_tasks, project_activity_logs, educators). No new schema.
 *
 * The two bidder accounts are created only because bids.educator_id has a
 * foreign key to educators, and the local educators table starts empty.
 *
 * Run with:
 *   php artisan db:seed --class=Database\Seeders\Project\LocalProjectsDemoSeeder
 */
class LocalProjectsDemoSeeder extends Seeder
{
    private const MARKER = '[LOCAL-PROJECTS]';

    public function run(): void
    {
        if (DB::table('projects')->where('title', 'like', self::MARKER.'%')->exists()) {
            $this->command->info('Local projects demo data already exists - nothing added.');

            return;
        }

        $admin = Admin::query()->first();
        if (! $admin) {
            $admin = Admin::create([
                'name' => 'Local Test Admin',
                'email' => 'admin@local.test',
                'password' => Hash::make('123456'),
                'type' => 'employee',
                'status' => 'active',
            ]);
        }

        DB::transaction(function () use ($admin) {
            $ahmedId = $this->ensureEducator(
                email: 'bidder.ahmed@local.test',
                name: 'أحمد العتيبي',
                phone: '500000101',
                gender: 'male',
                birthDate: '1990-04-12',
                rating: 4.80,
                specialization: 'تطوير المناهج والرياضيات'
            );

            $saraId = $this->ensureEducator(
                email: 'bidder.sara@local.test',
                name: 'سارة القحطاني',
                phone: '500000102',
                gender: 'female',
                birthDate: '1992-09-23',
                rating: 4.30,
                specialization: 'التصميم التعليمي والحقائب التدريبية'
            );

            // ---------------------------------------------------------------
            // 1) Project WITH offers: published, two open tasks, three bids.
            // ---------------------------------------------------------------
            $withOffers = $this->createProject($admin->id, [
                'title' => self::MARKER.' تطوير مناهج الرياضيات للصفوف الابتدائية',
                'description' => 'مشروع متكامل لإعادة بناء مناهج الرياضيات للمرحلة الابتدائية، يشمل تحليل الوضع الحالي، إعداد الوحدات التعليمية، وبناء أدلة المعلم.',
                'status' => ProjectStatus::PENDING->value,
                'budget' => 18000,
                'is_published' => 1,
                'creation_type' => CreationType::PUBLIC->value,
                'requester_name' => 'مدرسة الأفق الأهلية',
                'deadline' => now()->addDays(45)->toDateString(),
            ]);

            $taskAnalysis = $this->createTask($withOffers, $admin->id, [
                'title' => 'تحليل الوضع الحالي لمناهج الرياضيات',
                'description' => 'مراجعة المناهج الحالية وتحديد الفجوات ونقاط التحسين مع تقرير تفصيلي.',
                'status' => TaskStatus::PENDING->value,
                'budget' => 7000,
                'deadline' => now()->addDays(20)->toDateString(),
                'is_public' => 1,
                'bidding_status' => 'OPEN',
            ]);

            $taskUnits = $this->createTask($withOffers, $admin->id, [
                'title' => 'إعداد الوحدات التعليمية وأدلة المعلم',
                'description' => 'بناء ثماني وحدات تعليمية متكاملة مع أدلة المعلم والأنشطة الصفية.',
                'status' => TaskStatus::PENDING->value,
                'budget' => 9000,
                'deadline' => now()->addDays(40)->toDateString(),
                'is_public' => 1,
                'bidding_status' => 'OPEN',
            ]);

            // Offer 1: Ahmed offers on both tasks, still pending review.
            $this->createBid($withOffers, $ahmedId, [
                'proposal' => 'أعمل على تطوير مناهج الرياضيات منذ أكثر من 8 سنوات، وسأبدأ بتحليل الفجوات ثم تسليم الوحدات على مراحل أسبوعية مع مراجعة مستمرة.',
                'delivery_days' => 35,
            ], [
                [$taskAnalysis, BidStatus::PENDING->value, 6500],
                [$taskUnits, BidStatus::PENDING->value, 8500],
            ]);

            // Offer 2: Sara offers on the analysis task with an approved price.
            $this->createBid($withOffers, $saraId, [
                'proposal' => 'سأقدم تحليلاً مقارناً لأربعة مناهج دولية مع مصفوفة فجوات واضحة وتوصيات قابلة للتنفيذ.',
                'delivery_days' => 14,
            ], [
                [$taskAnalysis, BidStatus::APPROVED->value, 6800],
            ]);

            $this->log($withOffers, 'project.created', ['title' => 'تطوير مناهج الرياضيات للصفوف الابتدائية'], 'admin');
            $this->log($withOffers, 'project.published', ['title' => 'تطوير مناهج الرياضيات للصفوف الابتدائية'], 'admin');
            $this->log($withOffers, 'task.bidding_opened', ['title' => 'تحليل الوضع الحالي لمناهج الرياضيات'], 'admin');
            $this->log($withOffers, 'bid.approved', [
                'educator' => 'سارة القحطاني',
                'title' => 'تحليل الوضع الحالي لمناهج الرياضيات',
            ], 'admin');

            // ---------------------------------------------------------------
            // 2) Project WITHOUT offers: published, open task, zero bids.
            // ---------------------------------------------------------------
            $withoutOffers = $this->createProject($admin->id, [
                'title' => self::MARKER.' إعداد حقائب تدريبية للمعلمين الجدد',
                'description' => 'إعداد حقائب تدريبية تفاعلية للمعلمين الجدد تشمل خطط الجلسات، أوراق العمل، وأدوات التقييم.',
                'status' => ProjectStatus::PENDING->value,
                'budget' => 9500,
                'is_published' => 1,
                'creation_type' => CreationType::PUBLIC->value,
                'requester_name' => 'مركز التطوير المهني',
                'deadline' => now()->addDays(30)->toDateString(),
            ]);

            $this->createTask($withoutOffers, $admin->id, [
                'title' => 'تصميم حقيبة تدريبية لبرنامج إعداد المعلمين',
                'description' => 'حقيبة تدريبية لمدة خمسة أيام تشمل العروض والأنشطة وأدوات القياس.',
                'status' => TaskStatus::PENDING->value,
                'budget' => 9500,
                'deadline' => now()->addDays(30)->toDateString(),
                'is_public' => 1,
                'bidding_status' => 'OPEN',
            ]);

            $this->log($withoutOffers, 'project.created', ['title' => 'إعداد حقائب تدريبية للمعلمين الجدد'], 'admin');
            $this->log($withoutOffers, 'task.bidding_opened', ['title' => 'تصميم حقيبة تدريبية لبرنامج إعداد المعلمين'], 'admin');

            // ---------------------------------------------------------------
            // 3) IN_PROGRESS project: one task already started/assigned, and a
            //    second open task still collecting offers.
            // ---------------------------------------------------------------
            $inProgress = $this->createProject($admin->id, [
                'title' => self::MARKER.' تحليل نتائج الاختبارات الوطنية',
                'description' => 'تحليل نتائج الاختبارات الوطنية وبناء لوحة مؤشرات لدعم قرارات المدرسة، مع خطة تحسين تنفيذية.',
                'status' => ProjectStatus::IN_PROGRESS->value,
                'budget' => 22000,
                'is_published' => 1,
                'creation_type' => CreationType::PUBLIC->value,
                'requester_name' => 'إدارة التعليم - قطاع الشمال',
                'deadline' => now()->addDays(60)->toDateString(),
            ]);

            $taskStarted = $this->createTask($inProgress, $admin->id, [
                'title' => 'تحليل بيانات الاختبارات وبناء لوحة المؤشرات',
                'description' => 'تنظيف البيانات، تحليل النتائج حسب المادة والصف، وبناء لوحة مؤشرات تفاعلية.',
                'status' => TaskStatus::IN_PROGRESS->value,
                'budget' => 12000,
                'deadline' => now()->addDays(25)->toDateString(),
                'is_public' => 1,
                'bidding_status' => 'CLOSED',
            ]);

            $taskPlan = $this->createTask($inProgress, $admin->id, [
                'title' => 'إعداد خطة تحسين تنفيذية',
                'description' => 'خطة تحسين مبنية على نتائج التحليل مع مؤشرات متابعة ومسؤوليات واضحة.',
                'status' => TaskStatus::PENDING->value,
                'budget' => 4000,
                'deadline' => now()->addDays(50)->toDateString(),
                'is_public' => 1,
                'bidding_status' => 'OPEN',
            ]);

            // The assigned offer for the started task.
            $assignedBid = $this->createBid($inProgress, $ahmedId, [
                'proposal' => 'سأبدأ التحليل فوراً وأسلم نسخة أولية من لوحة المؤشرات خلال أسبوعين، مع جلسة مراجعة مع الفريق.',
                'delivery_days' => 24,
            ], [
                [$taskStarted, BidStatus::ASSIGNED->value, 11800],
            ]);

            $this->assign($taskStarted, $ahmedId, $admin->id, $assignedBid, 11800);

            $this->subTasks($taskStarted, $admin->id, [
                ['title' => 'تنظيف ودمج ملفات النتائج', 'completed' => true],
                ['title' => 'تحليل النتائج حسب المادة', 'completed' => true],
                ['title' => 'بناء لوحة المؤشرات', 'completed' => false],
                ['title' => 'مراجعة اللوحة مع الإدارة', 'completed' => false],
            ]);

            // A second offer still waiting for review on the open task.
            $this->createBid($inProgress, $saraId, [
                'proposal' => 'سأربط خطة التحسين بمؤشرات قابلة للقياس وأقدم نموذج متابعة ربع سنوي.',
                'delivery_days' => 18,
            ], [
                [$taskPlan, BidStatus::PENDING->value, 3800],
            ]);

            $this->log($inProgress, 'project.created', ['title' => 'تحليل نتائج الاختبارات الوطنية'], 'admin');
            $this->log($inProgress, 'project.status_changed', [
                'from' => ProjectStatus::PENDING->value,
                'to' => ProjectStatus::IN_PROGRESS->value,
            ], 'admin');
            $this->log($inProgress, 'task.educator_assigned', [
                'educator' => 'أحمد العتيبي',
                'title' => 'تحليل بيانات الاختبارات وبناء لوحة المؤشرات',
            ], 'admin');
            $this->log($inProgress, 'task.assignment_accepted', [
                'educator' => 'أحمد العتيبي',
                'title' => 'تحليل بيانات الاختبارات وبناء لوحة المؤشرات',
            ], 'أحمد العتيبي');
        });

        $this->report();
    }

    private function ensureEducator(
        string $email,
        string $name,
        string $phone,
        string $gender,
        string $birthDate,
        float $rating,
        string $specialization
    ): int {
        $existing = DB::table('educators')->where('email', $email)->first();
        if ($existing) {
            return (int) $existing->id;
        }

        $id = DB::table('educators')->insertGetId([
            'uuid' => (string) Str::uuid(),
            'name' => $name,
            'email' => $email,
            'phone' => $phone,
            'password' => Hash::make('123456789'),
            'role' => 'provider',
            'discipline' => $specialization,
            'school_name' => 'Local Demo Academy',
            'dialcode' => '966',
            'country_name' => 'Saudi Arabia',
            'country_code' => 'sa',
            'phone_verified_at' => now(),
            'email_verified_at' => now(),
            'rating' => $rating,
            'availability_status' => 'active',
            'status' => 'active',
            'is_accepted' => 1,
            'accepted_terms' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('educator_profiles')->insert([
            'uuid' => (string) Str::uuid(),
            'educator_id' => $id,
            'identity_status' => 'verified',
            'gender' => $gender,
            'birth_date' => $birthDate,
            'education_level' => 'master',
            'experience_years' => 8,
            'specialization' => $specialization,
            'employment_status' => 'employed',
            'completion_step' => 6,
            'is_complete' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return $id;
    }

    private function createProject(int $adminId, array $data): int
    {
        return DB::table('projects')->insertGetId([
            'uuid' => (string) Str::uuid(),
            'title' => $data['title'],
            'description' => $data['description'] ?? null,
            'tags' => json_encode(['local', 'demo']),
            'status' => $data['status'],
            'budget' => $data['budget'] ?? null,
            'is_published' => $data['is_published'] ?? 0,
            'creation_type' => $data['creation_type'],
            'created_by' => (string) $adminId,
            'deadline' => $data['deadline'] ?? null,
            'requester_name' => $data['requester_name'] ?? null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function createTask(int $projectId, int $adminId, array $data): int
    {
        return DB::table('tasks')->insertGetId([
            'uuid' => (string) Str::uuid(),
            'project_id' => $projectId,
            'title' => $data['title'],
            'description' => $data['description'] ?? null,
            'tags' => json_encode(['local', 'demo']),
            'status' => $data['status'],
            'budget' => $data['budget'] ?? null,
            'deadline' => $data['deadline'],
            'is_public' => $data['is_public'] ?? 0,
            'bidding_status' => $data['bidding_status'] ?? 'CLOSED',
            'created_by' => $adminId,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    /**
     * @param  array<int, array{0:int,1:string,2:float}>  $tasks  [task_id, status, amount]
     */
    private function createBid(int $projectId, int $educatorId, array $bid, array $tasks): int
    {
        $total = array_reduce($tasks, fn (float $sum, array $row) => $sum + (float) $row[2], 0.0);

        $bidId = DB::table('bids')->insertGetId([
            'uuid' => (string) Str::uuid(),
            'project_id' => $projectId,
            'educator_id' => $educatorId,
            'total_amount' => $total,
            'proposal' => $bid['proposal'],
            'delivery_days' => $bid['delivery_days'],
            'links' => json_encode([]),
            'created_at' => now()->subDays(2),
            'updated_at' => now(),
        ]);

        foreach ($tasks as [$taskId, $status, $amount]) {
            DB::table('bid_tasks')->insert([
                'uuid' => (string) Str::uuid(),
                'bid_id' => $bidId,
                'task_id' => $taskId,
                'status' => $status,
                'amount' => $amount,
                'rejection_reason' => null,
                'approval_message' => $status === BidStatus::APPROVED->value ? 'تمت الموافقة على العرض.' : null,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        return $bidId;
    }

    private function assign(int $taskId, int $educatorId, int $adminId, int $bidId, float $amount): int
    {
        return DB::table('assignments')->insertGetId([
            'uuid' => (string) Str::uuid(),
            'task_id' => $taskId,
            'assigned_to' => $educatorId,
            'assigned_by' => $adminId,
            'assigned_type' => AssignmentType::FROM_BID->value,
            'bid_id' => $bidId,
            'status' => AssignmentStatus::ACCEPTED->value,
            'amount' => $amount,
            'agreement_notes' => 'تم الاتفاق على التسليم على مراحل مع مراجعة أسبوعية.',
            'assigned_at' => now()->subDays(5),
            'accepted_at' => now()->subDays(4),
            'completed_at' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    /**
     * @param  array<int, array{title:string, completed:bool}>  $items
     */
    private function subTasks(int $taskId, int $adminId, array $items): void
    {
        foreach ($items as $item) {
            DB::table('sub_tasks')->insert([
                'uuid' => (string) Str::uuid(),
                'creatable_type' => Admin::class,
                'creatable_id' => $adminId,
                'task_id' => $taskId,
                'title' => $item['title'],
                'completed' => $item['completed'] ? 1 : 0,
                'completed_at' => $item['completed'] ? now()->subDays(2) : null,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    private function log(int $projectId, string $messageKey, array $payload, ?string $actor): void
    {
        DB::table('project_activity_logs')->insert([
            'uuid' => (string) Str::uuid(),
            'project_id' => $projectId,
            'message_key' => $messageKey,
            'message_payload' => json_encode($payload, JSON_UNESCAPED_UNICODE),
            'actor' => $actor,
            'timestamp' => now()->subMinutes(random_int(5, 300)),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function report(): void
    {
        $projects = DB::table('projects')->where('title', 'like', self::MARKER.'%')->count();
        $bids = DB::table('bids')
            ->join('projects', 'projects.id', '=', 'bids.project_id')
            ->where('projects.title', 'like', self::MARKER.'%')
            ->count();

        $this->command->info("Local projects demo data ready: {$projects} projects, {$bids} bids.");
        $this->command->line('  with offers   : '.self::MARKER.' تطوير مناهج الرياضيات للصفوف الابتدائية');
        $this->command->line('  without offers: '.self::MARKER.' إعداد حقائب تدريبية للمعلمين الجدد');
        $this->command->line('  in progress   : '.self::MARKER.' تحليل نتائج الاختبارات الوطنية');
        $this->command->line('  page          : http://localhost:5173/admin/projects');
    }
}
