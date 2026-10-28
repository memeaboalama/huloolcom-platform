<?php
$tables = DB::select('SHOW TABLES');
foreach ($tables as $t) {
    $name = array_values((array)$t)[0];
    if (stripos($name, 'user') !== false || stripos($name, 'admin') !== false || stripos($name, 'role') !== false || stripos($name, 'permission') !== false) {
        echo $name . "\n";
    }
}
echo "---ALL TABLES COUNT: " . count($tables) . "\n";
