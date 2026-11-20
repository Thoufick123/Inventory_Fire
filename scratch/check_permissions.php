<?php
require __DIR__ . '/../config/app.php';
require __DIR__ . '/../app/Helpers/Database.php';

$rows = Database::fetchAll("SELECT DISTINCT module FROM permissions ORDER BY module");
echo "Modules in permissions table (" . count($rows) . "):\n";
foreach ($rows as $r) {
    $actions = Database::fetchAll("SELECT action FROM permissions WHERE module = ?", [$r['module']]);
    $acts = implode(',', array_column($actions, 'action'));
    echo " - {$r['module']}: [$acts]\n";
}
