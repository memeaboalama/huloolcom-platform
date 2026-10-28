<?php

/**
 * READ-ONLY: prints the CURRENT fixture UUIDs so the manual-test links can be
 * read without recreating anything. Opens no transaction and writes nothing.
 */

require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\DB;

$created = json_decode(file_get_contents(__DIR__.'/tmp_bidfixture_manifest.json'), true);

$rows = DB::table('bids')
    ->join('bid_tasks', 'bid_tasks.bid_id', '=', 'bids.id')
    ->join('projects', 'projects.id', '=', 'bids.project_id')
    ->whereIn('bids.id', $created['bids'])
    ->orderBy('bids.id')
    ->get([
        'bids.uuid as bid_uuid',
        'projects.uuid as project_uuid',
        'projects.title as project_title',
        'bid_tasks.status as status',
        'bid_tasks.amount as amount',
    ]);

$label = [
    'pending' => ['A', 'PENDING  - EDITABLE'],
    'approved' => ['B', 'APPROVED - LOCKED'],
    'assigned' => ['C', 'ASSIGNED - LOCKED'],
];

echo 'FIXTURE IS LIVE - '.count($rows).' bids found'.PHP_EOL.PHP_EOL;

foreach ($rows as $r) {
    [$letter, $desc] = $label[$r->status] ?? ['?', strtoupper($r->status)];

    echo '===== CASE '.$letter.' : '.$desc.' ====='.PHP_EOL;
    echo 'project title : '.$r->project_title.PHP_EOL;
    echo 'bid uuid      : '.$r->bid_uuid.PHP_EOL;
    echo 'project uuid  : '.$r->project_uuid.PHP_EOL;
    echo 'task status   : '.$r->status.PHP_EOL;
    echo 'amount        : '.$r->amount.PHP_EOL;
    echo 'EDIT URL      : http://localhost:5173/educator/createBid'
        .'?projectId='.$r->project_uuid.'&bidId='.$r->bid_uuid.PHP_EOL;
    echo 'DETAIL URL    : http://localhost:5173/educator/bidDetail?bidId='.$r->bid_uuid.PHP_EOL;
    echo PHP_EOL;
}