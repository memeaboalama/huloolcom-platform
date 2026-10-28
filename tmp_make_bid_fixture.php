<?php

/**
 * TEMPORARY test fixture for the "bid edit lock" fix.
 *
 * Creates 3 throwaway projects (each with one OPEN task) and 3 bids for the
 * existing local educator id=19, so the three states can be checked in the UI:
 *
 *   A) bid task = pending  -> must remain editable
 *   B) bid task = approved -> must be locked (agreement done)
 *   C) bid task = assigned -> must be locked (execution started)
 *
 * Safety rules honoured here:
 *  - no seeder, no migration, no migrate:fresh/refresh/truncate
 *  - no existing row is read for update or modified (all inserts are new rows)
 *  - the educator's password is NOT touched (the existing account already works)
 *  - every created id is written to tmp_bidfixture_manifest.json so rollback is exact
 *
 * Run:  php tmp_make_bid_fixture.php
 * Undo: php tmp_rollback_bid_fixture.php
 */

require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

$educatorId = 19;
$adminId = 1;           // tasks.created_by -> admins(id) restrict
$tag = 'ZZ-TEMP-BID-GUARD-TEST';
define('TAG', $tag);

$manifestPath = __DIR__.'/tmp_bidfixture_manifest.json';

if (file_exists($manifestPath)) {
    echo "REFUSING TO RUN: a manifest already exists at tmp_bidfixture_manifest.json.".PHP_EOL;
    echo "Run 'php tmp_rollback_bid_fixture.php' first if you want a clean slate.".PHP_EOL;
    exit(1);
}

$created = ['projects' => [], 'tasks' => [], 'bids' => [], 'bid_tasks' => [], 'educator_id' => $educatorId];

$cases = [
    ['suffix' => 'A-EDITABLE', 'title' => 'مشروع اختباري A - قابل للتعديل', 'status' => 'pending', 'amount' => 1000],
    ['suffix' => 'B-APPROVED', 'title' => 'مشروع اختباري B - بعد الاتفاق', 'status' => 'approved', 'amount' => 2000],
    ['suffix' => 'C-ASSIGNED', 'title' => 'مشروع اختباري C - بعد بدء التنفيذ', 'status' => 'assigned', 'amount' => 3000],
];

DB::transaction(function () use ($cases, &$created, $manifestPath, $tag, $educatorId, $adminId) {
    foreach ($cases as $case) {
        $projectId = DB::table('projects')->insertGetId([
            'uuid' => (string) Str::uuid(),
            'title' => $tag.' '.$case['title'],
            'description' => 'Temporary row created to verify the bid edit lock. Safe to delete.',
            'tags' => json_encode(['temp']),
            // ProjectStatus enum has no 'published' value; is_published is the
            // separate boolean the bid validation actually checks.
            'status' => 'pending',
            'budget' => 9000,
            'is_published' => 1,
            'creation_type' => 'public',
            'created_by' => 'admin',
            'deadline' => now()->addMonth()->toDateString(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $created['projects'][] = $projectId;

        $taskId = DB::table('tasks')->insertGetId([
            'uuid' => (string) Str::uuid(),
            'project_id' => $projectId,
            'title' => $tag.' مهمة '.$case['suffix'],
            'description' => 'Temporary task for the bid edit lock check.',
            'tags' => json_encode(['temp']),
            'status' => 'pending',
            'budget' => 5000,
            'deadline' => now()->addWeek()->toDateString(),
            'is_public' => 1,
            'bidding_status' => 'OPEN',
            'created_by' => $adminId,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $created['tasks'][] = $taskId;

        $bidId = DB::table('bids')->insertGetId([
            'uuid' => (string) Str::uuid(),
            'project_id' => $projectId,
            'educator_id' => $educatorId,
            'total_amount' => 0,
            'proposal' => $tag.' مقترح العرض '.$case['suffix'],
            'delivery_days' => 7,
            'links' => '[]',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $created['bids'][] = $bidId;

        $bidTaskId = DB::table('bid_tasks')->insertGetId([
            'uuid' => (string) Str::uuid(),
            'bid_id' => $bidId,
            'task_id' => $taskId,
            'status' => $case['status'],
            'amount' => $case['amount'],
            'approval_message' => $case['status'] === 'pending' ? null : 'temp fixture',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $created['bid_tasks'][] = $bidTaskId;
    }

    file_put_contents($manifestPath, json_encode($created, JSON_PRETTY_PRINT));
});

echo 'FIXTURE CREATED'.PHP_EOL.PHP_EOL;
printf("projects created : %d\ntasks created    : %d\nbids created     : %d\nbid_tasks created: %d\n\n",
    count($created['projects']), count($created['tasks']),
    count($created['bids']), count($created['bid_tasks']));

$rows = DB::table('bids')
    ->join('bid_tasks', 'bid_tasks.bid_id', '=', 'bids.id')
    ->join('projects', 'projects.id', '=', 'bids.project_id')
    ->whereIn('bids.id', $created['bids'])
    ->orderBy('bids.id')
    ->get(['bids.id as bid_id', 'bids.uuid as bid_uuid', 'bid_tasks.status',
        'projects.title as project_title']);

printf("%-9s %-36s %-10s %s\n", 'BID_ID', 'BID_UUID (for the direct URL)', 'STATUS', 'PROJECT');
foreach ($rows as $r) {
    printf("%-9s %-36s %-10s %s\n", $r->bid_id, $r->bid_uuid, $r->status, $r->project_title);
}

echo PHP_EOL.'manifest: '.basename($manifestPath).PHP_EOL;
echo 'rollback: php tmp_rollback_bid_fixture.php'.PHP_EOL;