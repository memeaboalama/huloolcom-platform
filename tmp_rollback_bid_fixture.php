<?php

/**
 * EXACT rollback of the temporary bid fixture created by tmp_make_bid_fixture.php.
 *
 * Deletes only the ids recorded in tmp_bidfixture_manifest.json, children first.
 * It never touches projects/tasks/bids created by anyone else, and it refuses to
 * run if the manifest is missing or a recorded row is not a fixture row (the ZZ-TEMP
 * marker is checked before deleting).
 *
 * Run: php tmp_rollback_bid_fixture.php
 */

require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\DB;

const TAG = 'ZZ-TEMP-BID-GUARD-TEST';
$manifestPath = __DIR__.'/tmp_bidfixture_manifest.json';

if (! file_exists($manifestPath)) {
    echo 'Nothing to roll back: manifest not found.'.PHP_EOL;
    exit(0);
}

$created = json_decode(file_get_contents($manifestPath), true);

$guard = function (string $table, string $column, array $ids): int {
    if ($ids === []) {
        return 0;
    }
    $marked = DB::table($table)->whereIn($column, $ids)->where($column, '>=', 1)->count();
    $existing = DB::table($table)->whereIn($column, $ids)->count();
    if ($existing !== $marked) {
        echo 'ABORT: not all recorded '.$table.' rows exist; refusing partial delete.'.PHP_EOL;
        exit(1);
    }
    return $existing;
};

// Verify every recorded row really belongs to the fixture before deleting anything.
$bad = DB::table('projects')->whereIn('id', $created['projects'])
    ->where('title', 'not like', TAG.'%')->count();
if ($bad > 0) {
    echo 'ABORT: a recorded project is not a fixture row. Refusing to delete.'.PHP_EOL;
    exit(1);
}
$bad = DB::table('bids')->whereIn('id', $created['bids'])
    ->where('proposal', 'not like', TAG.'%')->count();
if ($bad > 0) {
    echo 'ABORT: a recorded bid is not a fixture row. Refusing to delete.'.PHP_EOL;
    exit(1);
}

$deleted = [];

DB::transaction(function () use ($created, &$deleted) {
    $deleted['bid_tasks'] = DB::table('bid_tasks')->whereIn('id', $created['bid_tasks'])->delete();
    $deleted['bids'] = DB::table('bids')->whereIn('id', $created['bids'])->delete();
    $deleted['tasks'] = DB::table('tasks')->whereIn('id', $created['tasks'])->delete();
    $deleted['projects'] = DB::table('projects')->whereIn('id', $created['projects'])->delete();
});

foreach ($deleted as $table => $n) {
    echo str_pad($table, 12).' deleted: '.$n.PHP_EOL;
}

@unlink($manifestPath);
echo PHP_EOL.'manifest removed.'.PHP_EOL;

echo PHP_EOL.'=== VERIFY NOTHING IS LEFT ==='.PHP_EOL;
foreach (['projects', 'tasks', 'bids', 'bid_tasks'] as $table) {
    $leftoverTag = $table === 'projects'
        ? DB::table($table)->where('title', 'like', TAG.'%')->count()
        : ($table === 'bids'
            ? DB::table($table)->where('proposal', 'like', TAG.'%')->count()
            : DB::table($table)->whereIn('id', $created[$table])->count());
    $total = DB::table($table)->count();
    echo str_pad($table, 12).' fixture rows left: '.$leftoverTag
        .'   | total rows now: '.$total.PHP_EOL;
}

echo PHP_EOL.'educators row count (must be unchanged at 4): '
    .DB::table('educators')->count().PHP_EOL;