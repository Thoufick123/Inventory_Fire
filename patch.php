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

echo implode('<br>', $results);

