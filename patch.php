<?php
$_SERVER['REQUEST_METHOD'] = 'GET';
$_SERVER['REQUEST_URI'] = '/';
require 'public/index.php';

$results = [];

// 1. sales_channels
try {
    Database::execute("ALTER TABLE sales_channels ADD COLUMN channel_type ENUM('WHATSAPP', 'WEBSITE', 'OTHER') DEFAULT 'OTHER'");
    $results[] = 'sales_channels.channel_type: added';
} catch (Exception $e) {
    $results[] = 'sales_channels.channel_type: ' . (strpos($e->getMessage(), 'Duplicate') !== false ? 'exists' : $e->getMessage());
}

// 2. qr_generation_items design_name
try {
    Database::execute("ALTER TABLE qr_generation_items ADD COLUMN design_name VARCHAR(150) NULL AFTER color_name");
    $results[] = 'qr_generation_items.design_name: added';
} catch (Exception $e) {
    $results[] = 'qr_generation_items.design_name: ' . (strpos($e->getMessage(), 'Duplicate') !== false ? 'exists' : $e->getMessage());
}

// 3. Backfill color and design
try {
    Database::execute("
        UPDATE qr_generations qrg
        INNER JOIN production_purchase_items ppi ON ppi.id = qrg.source_purchase_item_id
        SET qrg.color_name = COALESCE(NULLIF(qrg.color_name, ''), ppi.color),
            qrg.design_name = COALESCE(NULLIF(qrg.design_name, ''), ppi.design_name)
    ");
    Database::execute("
        UPDATE qr_generation_items qri
        INNER JOIN qr_generations qrg ON qrg.id = qri.generation_id
        INNER JOIN production_purchase_items ppi ON ppi.id = qrg.source_purchase_item_id
        SET qri.color_name = COALESCE(NULLIF(qri.color_name, ''), qrg.color_name, ppi.color),
            qri.design_name = COALESCE(NULLIF(qri.design_name, ''), qrg.design_name, ppi.design_name)
    ");
    $results[] = 'backfill: done';
} catch (Exception $e) {
    $results[] = 'backfill error: ' . $e->getMessage();
}

// 4. Wastage Sales Concern ID columns & Backfill
try {
    Database::execute("ALTER TABLE wastage_sales ADD COLUMN concern_id INT(11) NULL DEFAULT 1 AFTER id, ADD INDEX (concern_id)");
    $results[] = 'wastage_sales.concern_id: added';
} catch (Exception $e) {
    $results[] = 'wastage_sales.concern_id: ' . (strpos($e->getMessage(), 'Duplicate') !== false ? 'exists' : $e->getMessage());
}

try {
    Database::execute("ALTER TABLE stitching_wastage_sales ADD COLUMN concern_id INT(11) NULL DEFAULT 1 AFTER id, ADD INDEX (concern_id)");
    $results[] = 'stitching_wastage_sales.concern_id: added';
} catch (Exception $e) {
    $results[] = 'stitching_wastage_sales.concern_id: ' . (strpos($e->getMessage(), 'Duplicate') !== false ? 'exists' : $e->getMessage());
}

try {
    Database::execute("
        UPDATE wastage_sales ws
        INNER JOIN cutting_batches cb ON ws.cutting_batch_id = cb.id
        SET ws.concern_id = cb.concern_id
        WHERE cb.concern_id IS NOT NULL AND cb.concern_id > 0
    ");
    Database::execute("
        UPDATE wastage_sales ws
        INNER JOIN users u ON ws.created_by = u.id
        SET ws.concern_id = u.default_concern_id
        WHERE (ws.concern_id IS NULL OR ws.concern_id = 1) AND u.default_concern_id IS NOT NULL AND u.default_concern_id > 0
    ");
    $results[] = 'wastage_sales backfill: done';
} catch (Exception $e) {
    $results[] = 'wastage_sales backfill error: ' . $e->getMessage();
}

try {
    Database::execute("
        UPDATE stitching_wastage_sales sws
        INNER JOIN stitching_batches sb ON sws.stitching_batch_id = sb.id
        SET sws.concern_id = sb.concern_id
        WHERE sb.concern_id IS NOT NULL AND sb.concern_id > 0
    ");
    Database::execute("
        UPDATE stitching_wastage_sales SET concern_id = 1 WHERE concern_id IS NULL OR concern_id = 0
    ");
    $results[] = 'stitching_wastage_sales backfill: done';
} catch (Exception $e) {
    $results[] = 'stitching_wastage_sales backfill error: ' . $e->getMessage();
}

try {
    Database::execute("
        UPDATE wastage_sales SET concern_id = 1 WHERE concern_id IS NULL OR concern_id = 0
    ");
} catch (Exception $e) {}

// 5. Grant Wastage & Stitching Wastage Permissions to Roles 2, 3, 4
try {
    $permIds = Database::fetchAll("SELECT id FROM permissions WHERE module IN ('stitching_wastage', 'wastage')");
    $grantedCount = 0;
    foreach ([2, 3, 4] as $roleId) {
        foreach ($permIds as $p) {
            Database::execute("INSERT IGNORE INTO role_permissions (role_id, permission_id, created_at) VALUES (?, ?, NOW())", [$roleId, (int)$p['id']]);
            $grantedCount++;
        }
    }
    $results[] = "Wastage permissions granted to roles (Admin, Production Staff): $grantedCount records";
} catch (Exception $e) {
    $results[] = 'Permissions grant error: ' . $e->getMessage();
}

try {
    $rows = Database::fetchAll("SELECT id, sale_number, concern_id, cutting_batch_id, buyer_name, weight, total_amount FROM wastage_sales");
    $results[] = 'Current wastage_sales: ' . json_encode($rows);
} catch (Exception $e) {
    $results[] = 'Fetch error: ' . $e->getMessage();
}

try {
    $sRows = Database::fetchAll("SELECT id, sale_number, concern_id, stitching_batch_id, buyer_name, quantity, total_amount FROM stitching_wastage_sales");
    $results[] = 'Current stitching_wastage_sales: ' . json_encode($sRows);
} catch (Exception $e) {
    $results[] = 'Fetch error: ' . $e->getMessage();
}

echo implode('<br>', $results);

