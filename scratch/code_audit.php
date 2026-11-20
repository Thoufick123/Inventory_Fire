<?php
$rootDir = dirname(__DIR__);

// 1. Scan app PHP files
$phpFiles = [];
$iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($rootDir));
foreach ($iterator as $file) {
    if (!$file->isFile()) continue;
    $ext = strtolower($file->getExtension());
    $path = $file->getPathname();
    if (strpos($path, 'vendor') !== false || strpos($path, '.git') !== false || strpos($path, 'node_modules') !== false) {
        continue;
    }
    if ($ext === 'php') {
        $phpFiles[] = $path;
    }
}

echo "Total PHP files found: " . count($phpFiles) . "\n";

// Analyze LOC by directory
$locByDir = [];
$totalLoc = 0;
$fileSizes = [];

foreach ($phpFiles as $file) {
    $rel = str_replace($rootDir . DIRECTORY_SEPARATOR, '', $file);
    $lines = file($file);
    $count = count($lines);
    $totalLoc += $count;
    
    $parts = explode(DIRECTORY_SEPARATOR, $rel);
    $topDir = $parts[0];
    if (isset($parts[1]) && ($topDir === 'app' || $topDir === 'views')) {
        $topDir .= '/' . $parts[1];
    }
    $locByDir[$topDir] = ($locByDir[$topDir] ?? 0) + $count;
    $fileSizes[$rel] = $count;
}

arsort($fileSizes);
arsort($locByDir);

echo "\n--- LOC BY DIRECTORY ---\n";
foreach ($locByDir as $dir => $loc) {
    printf("%-25s: %8d lines\n", $dir, $loc);
}
echo "Total LOC: $totalLoc\n";

echo "\n--- TOP 10 LARGEST PHP FILES ---\n";
$i = 0;
foreach ($fileSizes as $file => $loc) {
    printf("%-60s: %6d lines\n", substr($file, 0, 60), $loc);
    if (++$i >= 10) break;
}

// 2. Scan for raw string interpolation / concatenation in SQL
$rawSqlQueries = [];
$preparedCount = 0;
$queryCount = 0;
$transactionCount = 0;
$commitCount = 0;
$rollbackCount = 0;
$forUpdateCount = 0;

foreach ($phpFiles as $file) {
    $rel = str_replace($rootDir . DIRECTORY_SEPARATOR, '', $file);
    $lines = file($file);
    foreach ($lines as $lineNum => $line) {
        if (preg_match('/Database::(beginTransaction|commit|rollback)/i', $line, $m)) {
            if (strtolower($m[1]) === 'begintransaction') $transactionCount++;
            if (strtolower($m[1]) === 'commit') $commitCount++;
            if (strtolower($m[1]) === 'rollback') $rollbackCount++;
        }
        if (preg_match('/FOR\s+UPDATE/i', $line)) {
            $forUpdateCount++;
        }
        if (preg_match('/Database::(query|fetch|fetchAll|fetchOne|fetchColumn|fetchValue|execute)\s*\(/i', $line)) {
            $queryCount++;
            // Check if it has parameter array passed
            if (preg_match('/Database::[a-zA-Z]+\s*\(\s*(["\'].*?["\']|\$[a-zA-Z0-9_]+)\s*,\s*\[(.*?)\]\s*\)/s', $line)) {
                $preparedCount++;
            }
            // Check for direct variable concatenation or interpolation in SQL string
            if (preg_match('/Database::[a-zA-Z]+\s*\(\s*["\'][^"\']*(\$[a-zA-Z0-9_]+)[^"\']*["\']/i', $line, $matches)) {
                // If the variable is just in the string (PHP interpolation)
                $rawSqlQueries[] = [
                    'file' => $rel,
                    'line' => $lineNum + 1,
                    'text' => trim($line),
                    'type' => 'interpolation: ' . $matches[1]
                ];
            }
            if (preg_match('/Database::[a-zA-Z]+\s*\(\s*["\'].*?["\']\s*\.\s*\$[a-zA-Z0-9_]+/i', $line, $matches)) {
                $rawSqlQueries[] = [
                    'file' => $rel,
                    'line' => $lineNum + 1,
                    'text' => trim($line),
                    'type' => 'concatenation'
                ];
            }
        }
    }
}

echo "\n--- SQL & TRANSACTION METRICS ---\n";
echo "Total Database calls detected: $queryCount\n";
echo "Transactions begun: $transactionCount, committed: $commitCount, rolled back: $rollbackCount\n";
echo "'FOR UPDATE' concurrency locks: $forUpdateCount\n";
echo "Potentially unsafe/interpolated SQL calls: " . count($rawSqlQueries) . "\n";
if (count($rawSqlQueries) > 0) {
    echo "Sample raw/interpolated queries:\n";
    foreach (array_slice($rawSqlQueries, 0, 10) as $q) {
        echo "  [{$q['file']}:{$q['line']}] ({$q['type']}) {$q['text']}\n";
    }
}

// 3. Scan Schema for Foreign Keys, Indexes, Data Types
$schemaFile = $rootDir . '/database/schema.sql';
if (file_exists($schemaFile)) {
    $schema = file_get_contents($schemaFile);
    $tables = preg_match_all('/CREATE TABLE `?([a-zA-Z0-9_]+)`?\s*\((.*?)\)\s*ENGINE/s', $schema, $tableMatches);
    echo "\n--- DATABASE SCHEMA METRICS (schema.sql) ---\n";
    echo "Tables defined: " . count($tableMatches[1]) . "\n";
    
    $fkCount = 0;
    $decimalCount = 0;
    $floatCount = 0;
    $tablesWithNoFk = [];
    $totalIndexes = 0;
    
    foreach ($tableMatches[1] as $idx => $tName) {
        $body = $tableMatches[2][$idx];
        $fks = preg_match_all('/FOREIGN KEY/i', $body);
        $fkCount += $fks;
        if ($fks === 0) {
            $tablesWithNoFk[] = $tName;
        }
        $indexes = preg_match_all('/(PRIMARY KEY|KEY|UNIQUE KEY|INDEX)\s+/i', $body);
        $totalIndexes += $indexes;
        $decimalCount += preg_match_all('/DECIMAL\s*\(/i', $body);
        $floatCount += preg_match_all('/(FLOAT|DOUBLE)\s+/i', $body);
    }
    
    echo "Total Foreign Key constraints: $fkCount\n";
    echo "Total Indexes defined: $totalIndexes\n";
    echo "DECIMAL column definitions: $decimalCount\n";
    echo "FLOAT/DOUBLE column definitions: $floatCount\n";
    echo "Tables without foreign keys: " . count($tablesWithNoFk) . " (e.g., " . implode(', ', array_slice($tablesWithNoFk, 0, 8)) . "...)\n";
}

// 4. View Security (XSS / Escaping)
$viewFiles = glob($rootDir . '/views/**/*.php');
$rawEchos = 0;
$escapedEchos = 0;
foreach ($phpFiles as $file) {
    if (strpos($file, DIRECTORY_SEPARATOR . 'views' . DIRECTORY_SEPARATOR) === false) continue;
    $content = file_get_contents($file);
    $escapedEchos += preg_match_all('/htmlspecialchars|htmlentities|e\(/i', $content);
    // Unescaped echoes: <?= $var (not wrapped in htmlspecialchars)
    if (preg_match_all('/<\?=\s*(\$(?!_csrf)[a-zA-Z0-9_>\[\]\']+)\s*;?\s*\?>/i', $content, $m)) {
        $rawEchos += count($m[0]);
    }
}
echo "\n--- VIEW SECURITY (XSS) ---\n";
echo "Escaped echoes (htmlspecialchars / e()): $escapedEchos\n";
echo "Potentially unescaped <?= \$var ?> occurrences: $rawEchos\n";

// 5. CSRF Protection in Routes
$webRoutes = file_get_contents($rootDir . '/routes/web.php');
preg_match_all('/\$router->(post|put|delete|patch)\s*\(\s*[\'"]([^\'"]+)[\'"]\s*,\s*([^\)]+)\)/i', $webRoutes, $postRoutes);
echo "\n--- ROUTES & CSRF ---\n";
echo "Total State-modifying routes (POST/PUT/DELETE/PATCH): " . count($postRoutes[0]) . "\n";
$csrfProtectedInController = 0;
foreach ($phpFiles as $file) {
    if (strpos($file, 'Controllers') === false) continue;
    $content = file_get_contents($file);
    $csrfProtectedInController += preg_match_all('/CSRF::(verify|validate)/i', $content);
}
echo "Explicit CSRF checks in Controllers: $csrfProtectedInController\n";

// 6. Session & Auth Analysis
$sessionFile = $rootDir . '/app/Helpers/Session.php';
$sessionContent = file_get_contents($sessionFile);
$usesStrict = strpos($sessionContent, 'use_strict_mode') !== false;
$usesHttpOnly = strpos($sessionContent, 'cookie_httponly') !== false;
$usesSameSite = strpos($sessionContent, 'cookie_samesite') !== false;
$usesSecure = strpos($sessionContent, 'cookie_secure') !== false;

echo "\n--- SESSION SECURITY ---\n";
echo "session.use_strict_mode: " . ($usesStrict ? "YES" : "NO") . "\n";
echo "session.cookie_httponly: " . ($usesHttpOnly ? "YES" : "NO") . "\n";
echo "session.cookie_samesite: " . ($usesSameSite ? "YES" : "NO") . "\n";
echo "session.cookie_secure (HTTPS only): " . ($usesSecure ? "YES" : "NO") . "\n";


// 7. Route counts
$content = file_get_contents($rootDir . '/routes/web.php');
preg_match_all('/\$this->(get|post)\s*\(/', $content, $m);
$gets = 0; $posts = 0;
foreach ($m[1] as $method) {
    if ($method === 'get') $gets++;
    if ($method === 'post') $posts++;
}
echo "\n--- DETAILED ROUTE STATS ---\n";
echo "Total routes: " . count($m[0]) . " (GET: $gets, POST: $posts)\n";

// 8. Validator usages
$ctrlFiles = glob($rootDir . '/app/Controllers/*.php');
$validatorUsages = 0;
foreach ($ctrlFiles as $f) {
    $content = file_get_contents($f);
    $validatorUsages += preg_match_all('/Validator::make/i', $content);
}
echo "Validator::make usages across controllers: $validatorUsages\n";

// 9. ProductionController analysis
$f = $rootDir . '/app/Controllers/ProductionController.php';
$content = file_get_contents($f);
preg_match_all('/\$_POST\[[\'"][^\'"]+[\'"]\]/i', $content, $mPost);
preg_match_all('/\$_GET\[[\'"][^\'"]+[\'"]\]/i', $content, $mGet);
preg_match_all('/function\s+([a-zA-Z0-9_]+)\s*\(/i', $content, $mFunc);
echo "\n--- PRODUCTION CONTROLLER METRICS ---\n";
echo "Total methods: " . count($mFunc[1]) . "\n";
echo "Direct \$_POST accesses: " . count($mPost[0]) . "\n";
echo "Direct \$_GET accesses: " . count($mGet[0]) . "\n";
