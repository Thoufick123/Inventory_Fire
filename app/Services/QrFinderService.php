<?php
/**
 * QrFinderService — Universal QR & Barcode Status Engine
 * Scans, analyzes, and traces the complete lifecycle history of ANY QR code,
 * barcode, piece tag, batch, or transaction number across all inventory modules.
 */

class QrFinderService {

    /**
     * Trace any QR code / barcode across the entire system.
     *
     * @param string $rawCode
     * @return array
     */
    public static function trace(string $rawCode): array {
        $cleanRaw = trim($rawCode);

        // 1. Support JSON payloads from 2D camera or smart barcode readers
        if (str_starts_with($cleanRaw, '{') && str_ends_with($cleanRaw, '}')) {
            $json = json_decode($cleanRaw, true);
            if (is_array($json)) {
                $cleanRaw = $json['qr_code'] ?? $json['code'] ?? $json['batch_code'] ?? $json['tag_code'] ?? $cleanRaw;
            }
        }

        // 2. Support URL parameters (e.g. https://.../qr?code=PRD2611-0001)
        if (str_contains($cleanRaw, '?code=')) {
            $parsedQuery = parse_url($cleanRaw, PHP_URL_QUERY);
            if ($parsedQuery) {
                parse_str($parsedQuery, $urlParams);
                if (!empty($urlParams['code'])) {
                    $cleanRaw = $urlParams['code'];
                }
            }
        }

        $code = strtoupper(trim(explode('|', $cleanRaw, 2)[0]));
        $code = ltrim($code, '#');
        $code = trim($code);

        if ($code === '') {
            return [
                'success' => false,
                'message' => 'Please provide a valid QR code or barcode to scan.',
            ];
        }

        try {
            $timeline = [];
            $matched = false;
            $itemType = 'UNKNOWN';
            $itemTitle = 'Item ' . $code;
            $currentStatus = 'UNKNOWN';
            $statusColor = 'secondary';
            $currentLocation = 'System Archive / Unspecified';
            $details = [];

        // =========================================================================
        // 0. ACTIVE QR GENERATIONS ENGINE (qr_generation_items & qr_generations)
        // Checks all manual, catalog-variant, stitching, fabric & third-party QR stickers
        // =========================================================================
        $qri = Database::fetchOne("
            SELECT qri.id as qr_item_id, qri.qr_code, qri.sequence_num, qri.status as item_status,
                   qri.variant_id as item_variant_id, qri.size_name as item_size_name, qri.color_name as item_color_name,
                   qri.mrp as item_mrp, qri.selling_price as item_selling_price, qri.created_at as item_created_at,
                   qrg.id as generation_id, qrg.batch_code, qrg.generation_type, qrg.qr_source,
                   qrg.brand_name, qrg.category_name, qrg.subcategory_name, qrg.sub_subcategory_name,
                   qrg.color_name as gen_color_name, qrg.design_name, qrg.size_name as gen_size_name,
                   qrg.mrp as gen_mrp, qrg.selling_price as gen_selling_price, qrg.quantity as batch_qty,
                   qrg.qr_prefix, qrg.notes as batch_notes, qrg.stock_added, qrg.stock_added_at,
                   qrg.source_purchase_id, qrg.source_purchase_item_id, qrg.stitching_batch_id, qrg.program_id, qrg.qc_batch_id,
                   qrg.concern_id, qrg.created_at as batch_created_at,
                   p.name as product_name,
                   pv.variant_code,
                   c.name as concern_name, c.code as concern_code,
                   u.full_name as creator_name
            FROM qr_generation_items qri
            JOIN qr_generations qrg ON qrg.id = qri.generation_id
            LEFT JOIN products p ON p.id = qrg.product_id
            LEFT JOIN product_variants pv ON pv.id = COALESCE(qri.variant_id, qrg.variant_id)
            LEFT JOIN concerns c ON c.id = qrg.concern_id
            LEFT JOIN users u ON u.id = qrg.created_by
            WHERE UPPER(TRIM(qri.qr_code)) = ?
            LIMIT 1
        ", [$code]);

        if ($qri) {
            $matched = true;
            $sourceLabel = match(strtoupper((string)($qri['qr_source'] ?? ''))) {
                'FABRIC_ROLL'        => 'Fabric Roll QR Tag',
                'RAW_MATERIAL'       => 'Raw Material QR Tag',
                'PRODUCTION_PRODUCT' => 'In-House Production Garment',
                'THIRD_PARTY'        => 'Third-Party Product QR',
                default              => 'Active Inventory QR Item'
            };
            $itemType = $sourceLabel;

            $prodTitle = $qri['product_name']
                ?: trim(($qri['brand_name'] ?? 'GRM') . ' ' . ($qri['category_name'] ?? '') . ' ' . ($qri['subcategory_name'] ?? ''));
            if (empty($prodTitle)) {
                $prodTitle = 'Inventory Garment Item';
            }

            $size = $qri['item_size_name'] ?: ($qri['gen_size_name'] ?? '');
            $color = $qri['item_color_name'] ?: ($qri['gen_color_name'] ?? '');
            $variantCode = $qri['variant_code'] ?: $qri['qr_prefix'];

            $itemTitle = $prodTitle . ($size || $color ? " ({$size}" . ($color ? " / {$color}" : '') . ")" : '');
            $price = (float)(($qri['item_selling_price'] ?? 0) > 0 ? $qri['item_selling_price'] : ($qri['gen_selling_price'] ?? 0));
            $mrp = (float)(($qri['item_mrp'] ?? 0) > 0 ? $qri['item_mrp'] : ($qri['gen_mrp'] ?? 0));

            $details = [
                'QR Code / Tag'     => $qri['qr_code'],
                'Brand'             => $qri['brand_name'] ?: 'GRM',
                'QR Batch Code'     => $qri['batch_code'],
                'Category / Type'   => trim(($qri['category_name'] ?? '') . ' ' . ($qri['subcategory_name'] ?? '')),
                'Variant ID'        => $variantCode ?: 'Standard',
                'Size & Color'      => trim(($size ?: 'Standard') . ' ' . ($color ? " ({$color})" : '')),
                'Selling Price'     => '₹ ' . number_format($price, 2) . ($mrp > 0 ? ' (MRP: ₹' . number_format($mrp, 2) . ')' : ''),
                'Facility / Concern'=> $qri['concern_name'] ? "{$qri['concern_name']} ({$qri['concern_code']})" : 'Production Facility',
            ];

            // 1. Generation Record
            $timeline[] = [
                'stage'       => 'QR_GENERATION',
                'title'       => 'QR Label Generated (' . $qri['batch_code'] . ')',
                'timestamp'   => $qri['batch_created_at'] ?: $qri['item_created_at'],
                'badge'       => 'GENERATED',
                'badge_color' => 'primary',
                'icon'        => 'bi-qr-code-scan',
                'location'    => ($qri['concern_name'] ?: 'Production') . ' QR Printing Section',
                'description' => "Sticker {$qri['qr_code']} generated under Batch {$qri['batch_code']} ({$qri['batch_qty']} total stickers) by " . ($qri['creator_name'] ?: 'Staff') . ".",
            ];
            $currentStatus = 'GENERATED';
            $statusColor = 'primary';
            $currentLocation = ($qri['concern_name'] ?: 'Production') . ' Tagging Section';

            // 2. Trace Supplier Purchase if linked
            if (!empty($qri['source_purchase_id'])) {
                $po = Database::fetchOne("
                    SELECT pp.purchase_number, pp.invoice_number, pp.purchase_date, pp.created_at,
                           s.name as seller_name, s.company as seller_company
                    FROM production_purchases pp
                    LEFT JOIN sellers s ON s.id = pp.seller_id
                    WHERE pp.id = ?
                ", [(int)$qri['source_purchase_id']]);
                if ($po) {
                    $supp = $po['seller_company'] ?: $po['seller_name'];
                    $timeline[] = [
                        'stage'       => 'MATERIAL_PURCHASE',
                        'title'       => 'Procured from Mill/Supplier (PO #' . $po['purchase_number'] . ')',
                        'timestamp'   => $po['purchase_date'] ?: $po['created_at'],
                        'badge'       => 'PROCURED',
                        'badge_color' => 'success',
                        'icon'        => 'bi-truck',
                        'location'    => 'Raw Material Store / Inward Bay',
                        'description' => "Procured from {$supp} (Invoice: " . ($po['invoice_number'] ?: 'N/A') . ").",
                    ];
                }
            }

            // 3. Trace Stitching & Cutting if linked
            if (!empty($qri['stitching_batch_id'])) {
                $st = Database::fetchOne("
                    SELECT sb.batch_number, sb.stitched_qty, sb.created_at,
                           cb.batch_number as cutting_batch_number, pu.name as unit_name
                    FROM stitching_batches sb
                    LEFT JOIN cutting_batches cb ON cb.id = sb.cutting_batch_id
                    LEFT JOIN production_units pu ON pu.id = sb.production_unit_id
                    WHERE sb.id = ?
                ", [(int)$qri['stitching_batch_id']]);
                if ($st) {
                    $timeline[] = [
                        'stage'       => 'STITCHING',
                        'title'       => 'Stitching Completed #' . $st['batch_number'],
                        'timestamp'   => $st['created_at'],
                        'badge'       => 'STITCHED',
                        'badge_color' => 'info',
                        'icon'        => 'bi-threads',
                        'location'    => ($st['unit_name'] ?: 'Production') . ' Stitching Floor',
                        'description' => "Stitched under Batch {$st['batch_number']}." . ($st['cutting_batch_number'] ? " Cut from Batch {$st['cutting_batch_number']}." : ''),
                    ];
                }
            }

            // 4. Trace QC Inspection
            if (!empty($qri['qc_batch_id'])) {
                $qc = Database::fetchOne("
                    SELECT qb.*, u.full_name as inspector_name
                    FROM qc_batches qb
                    LEFT JOIN users u ON u.id = qb.inspector_id
                    WHERE qb.id = ?
                ", [(int)$qri['qc_batch_id']]);
                if ($qc) {
                    $timeline[] = [
                        'stage'       => 'QC_INSPECTION',
                        'title'       => 'Quality Control Inspection #' . $qc['batch_number'],
                        'timestamp'   => $qc['created_at'],
                        'badge'       => 'QC_PASSED',
                        'badge_color' => 'success',
                        'icon'        => 'bi-shield-check',
                        'location'    => 'QC & Finishing Floor',
                        'description' => "Quality inspected and approved under QC Batch {$qc['batch_number']} by " . ($qc['inspector_name'] ?: 'Inspector') . ".",
                    ];
                }
            }

            // 5. Trace Stock Addition to Inventory
            if (!empty($qri['stock_added'])) {
                $timeline[] = [
                    'stage'       => 'STOCK_ADDED',
                    'title'       => 'Added to Active Inventory Stock',
                    'timestamp'   => $qri['stock_added_at'] ?: $qri['batch_created_at'],
                    'badge'       => 'IN_STOCK',
                    'badge_color' => 'success',
                    'icon'        => 'bi-boxes',
                    'location'    => ($qri['concern_name'] ?: 'Warehouse') . ' Inventory Store',
                    'description' => "Inventory stock balance confirmed and registered for active sales / allocation.",
                ];
                $currentStatus = 'IN_STOCK';
                $statusColor = 'success';
                $currentLocation = ($qri['concern_name'] ?: 'Warehouse') . ' Active Stock Bin';
            }

            // 7. Check Packing Batches
            $batchItem = Database::fetchOne("
                SELECT pbi.*, pb.batch_number, pb.created_at as batch_date, pb.status as batch_status,
                       w.name as dest_warehouse_name
                FROM production_batch_items pbi
                JOIN production_batches pb ON pb.id = pbi.production_batch_id
                LEFT JOIN warehouses w ON w.id = pb.destination_warehouse_id
                WHERE pbi.piece_tags LIKE ? OR pbi.received_qr_codes LIKE ?
                LIMIT 1
            ", ['%' . $code . '%', '%' . $code . '%']);
            if ($batchItem) {
                $timeline[] = [
                    'stage'       => 'PACKING_BATCH',
                    'title'       => 'Packed in Batch #' . $batchItem['batch_number'],
                    'timestamp'   => $batchItem['batch_date'],
                    'badge'       => $batchItem['batch_status'] ?: 'PACKED',
                    'badge_color' => 'primary',
                    'icon'        => 'bi-box-seam',
                    'location'    => 'Packing & Dispatch Section',
                    'description' => "Batched for transfer to " . ($batchItem['dest_warehouse_name'] ?: 'Warehouse') . ".",
                ];
                $currentStatus = 'BATCH_PACKED';
                $statusColor = 'primary';
                $currentLocation = 'Production Dispatch / Packing Room';

                // Check Outward
                $outward = Database::fetchOne("
                    SELECT po.*, w.name as warehouse_name
                    FROM production_outwards po
                    LEFT JOIN warehouses w ON w.id = po.warehouse_id
                    WHERE po.production_batch_id = ? OR po.notes LIKE ?
                    ORDER BY po.id DESC LIMIT 1
                ", [(int)$batchItem['production_batch_id'], '%' . $code . '%']);
                if ($outward) {
                    $isRcv = ($outward['status'] === 'RECEIVED');
                    $timeline[] = [
                        'stage'       => 'DISPATCH_TRANSIT',
                        'title'       => 'Outward Dispatch #' . $outward['outward_number'],
                        'timestamp'   => $outward['dispatch_date'] ?: $outward['created_at'],
                        'badge'       => $outward['status'] ?: 'DISPATCHED',
                        'badge_color' => $isRcv ? 'success' : 'warning',
                        'icon'        => 'bi-truck-flatbed',
                        'location'    => $isRcv ? ($outward['warehouse_name'] ?: 'Warehouse') : 'In Transit',
                        'description' => "Dispatched to {$outward['warehouse_name']}. Vehicle: " . ($outward['vehicle_number'] ?: 'Direct') . ".",
                    ];
                    if ($isRcv) {
                        $currentLocation = ($outward['warehouse_name'] ?: 'Warehouse') . ' Inward Floor';
                        $currentStatus = 'AT_WAREHOUSE';
                        $statusColor = 'success';
                    } else {
                        $currentLocation = 'In Transit to ' . ($outward['warehouse_name'] ?: 'Warehouse');
                        $currentStatus = 'IN_TRANSIT';
                        $statusColor = 'warning';
                    }
                }
            }

            // 8. Check Damages
            $dmg = Database::fetchOne("
                SELECT rd.*, u.full_name as reporter_name
                FROM roll_damages rd
                LEFT JOIN users u ON u.id = rd.reported_by
                WHERE UPPER(TRIM(rd.qr_code)) = ? LIMIT 1
            ", [$code]);
            if (!$dmg) {
                $dmg = Database::fetchOne("
                    SELECT wd.*, u.full_name as reporter_name
                    FROM warehouse_damages wd
                    LEFT JOIN users u ON u.id = wd.reported_by
                    WHERE UPPER(TRIM(wd.tag_code)) = ? LIMIT 1
                ", [$code]);
            }
            if ($dmg) {
                $dmgNum = $dmg['damage_number'] ?? 'DMG';
                $lossVal = $dmg['loss_amount'] ?? $dmg['total_loss'] ?? 0;
                $timeline[] = [
                    'stage'       => 'DAMAGE',
                    'title'       => 'Damage Logged #' . $dmgNum,
                    'timestamp'   => $dmg['created_at'],
                    'badge'       => $dmg['status'] ?? 'DAMAGED',
                    'badge_color' => 'danger',
                    'icon'        => 'bi-exclamation-octagon-fill',
                    'location'    => 'Defect Quarantine',
                    'description' => "Defect: " . ($dmg['damage_reason'] ?? 'Damaged') . ". Loss Valuation: ₹" . number_format((float)$lossVal, 2),
                ];
                $currentLocation = "Damage Quarantine ({$dmgNum})";
                $currentStatus = 'DAMAGED';
                $statusColor = 'danger';
            }

            // 9. Check Billing / Sales
            $bill = Database::fetchOne("
                SELECT pb.*, pbit.tag_code
                FROM production_bill_item_tags pbit
                JOIN production_bills pb ON pb.id = pbit.bill_id
                WHERE UPPER(TRIM(pbit.tag_code)) = ?
                LIMIT 1
            ", [$code]);
            if ($bill) {
                $cust = $bill['customer_name'] ?: $bill['company_name'] ?: 'Customer';
                $timeline[] = [
                    'stage'       => 'BILLED',
                    'title'       => 'Sold / Invoiced #' . $bill['bill_number'],
                    'timestamp'   => $bill['created_at'],
                    'badge'       => 'Sold',
                    'badge_color' => 'dark',
                    'icon'        => 'bi-receipt',
                    'location'    => 'Customer Handover (Sold)',
                    'description' => "Sold to {$cust} under Invoice #{$bill['bill_number']}.",
                ];
                $currentLocation = "Sold to Customer (Invoice #{$bill['bill_number']})";
                $currentStatus = 'SOLD';
                $statusColor = 'dark';
            }

            // 10. Check Price Revision Logs & Old QR Protection
            $vId = (int)($qri['item_variant_id'] ?: ($qri['variant_id'] ?? 0));
            if ($vId > 0) {
                $priceLog = Database::fetchOne("
                    SELECT ppl.*, u.full_name as editor_name
                    FROM product_price_logs ppl
                    LEFT JOIN users u ON u.id = ppl.changed_by
                    WHERE ppl.variant_id = ?
                    ORDER BY ppl.id DESC LIMIT 1
                ", [$vId]);
                if ($priceLog) {
                    $isProtected = !empty($priceLog['dont_affect_old_qr']);
                    $timeline[] = [
                        'stage'       => 'PRICE_REVISION',
                        'title'       => 'Price Revision: ₹' . number_format((float)$priceLog['old_selling_price'], 2) . ' ➔ ₹' . number_format((float)$priceLog['new_selling_price'], 2),
                        'timestamp'   => $priceLog['created_at'],
                        'badge'       => 'PRICE_REVISED',
                        'badge_color' => 'warning',
                        'icon'        => 'bi-tag-fill',
                        'location'    => 'Pricing & Catalog Engine',
                        'description' => "Selling price changed from ₹" . number_format((float)$priceLog['old_selling_price'], 2) . " to ₹" . number_format((float)$priceLog['new_selling_price'], 2)
                            . ($isProtected ? " (Old printed QR tags retain original price)" : " (All QR tags updated to new price)")
                            . (!empty($priceLog['change_reason']) ? ". Reason: " . $priceLog['change_reason'] : "")
                            . ($priceLog['editor_name'] ? " by " . $priceLog['editor_name'] : ""),
                    ];
                    $details['Price Revision Status'] = $isProtected
                        ? 'Protected: Scans at Printed Tag Price (₹' . number_format((float)$price, 2) . ')'
                        : 'Updated: Active Catalog Price (₹' . number_format((float)$priceLog['new_selling_price'], 2) . ')';
                }
            }
        }

        // =========================================================================
        // 0B. CHECK QR GENERATION BATCH CODE DIRECTLY (e.g. QRG-20261120-0001)
        // =========================================================================
        if (!$matched && (str_starts_with($code, 'QRG-') || preg_match('/^QRG-\d{8}-\d{4}$/', $code))) {
            $batch = Database::fetchOne("
                SELECT qrg.*, c.name as concern_name, c.code as concern_code, u.full_name as creator_name
                FROM qr_generations qrg
                LEFT JOIN concerns c ON c.id = qrg.concern_id
                LEFT JOIN users u ON u.id = qrg.created_by
                WHERE UPPER(TRIM(qrg.batch_code)) = ? OR UPPER(TRIM(qrg.qr_prefix)) = ?
                LIMIT 1
            ", [$code, $code]);

            if ($batch) {
                $matched = true;
                $itemType = 'QR Generation Batch';
                $itemTitle = "Batch {$batch['batch_code']} ({$batch['brand_name']})";
                $currentStatus = $batch['stock_added'] ? 'IN_STOCK' : 'GENERATED';
                $statusColor = $batch['stock_added'] ? 'success' : 'primary';
                $currentLocation = ($batch['concern_name'] ?: 'Production') . ' Warehouse Store';

                $sampleCodes = Database::fetchAll("
                    SELECT qr_code FROM qr_generation_items WHERE generation_id = ? ORDER BY sequence_num ASC LIMIT 3
                ", [(int)$batch['id']]);
                $sampleList = implode(', ', array_column($sampleCodes, 'qr_code'));

                $details = [
                    'Batch Code'         => $batch['batch_code'],
                    'Brand Name'         => $batch['brand_name'],
                    'Total Quantity'     => $batch['quantity'] . ' stickers',
                    'Source Area'        => str_replace('_', ' ', $batch['qr_source']),
                    'Category'           => trim(($batch['category_name'] ?? '') . ' ' . ($batch['subcategory_name'] ?? '')),
                    'QR Prefix'          => $batch['qr_prefix'] ?: 'N/A',
                    'Price / MRP'        => '₹ ' . number_format((float)$batch['selling_price'], 2) . ' (MRP: ₹' . number_format((float)$batch['mrp'], 2) . ')',
                    'Concern Facility'   => $batch['concern_name'] ? "{$batch['concern_name']} ({$batch['concern_code']})" : 'Main Concern',
                    'Sample Piece Codes' => $sampleList ?: 'N/A',
                ];

                $timeline[] = [
                    'stage'       => 'QR_GENERATION',
                    'title'       => 'QR Generation Batch Created',
                    'timestamp'   => $batch['created_at'],
                    'badge'       => 'BATCH_' . $batch['quantity'],
                    'badge_color' => 'primary',
                    'icon'        => 'bi-qr-code-scan',
                    'location'    => ($batch['concern_name'] ?: 'Production') . ' QR Printing Section',
                    'description' => "Generated {$batch['quantity']} stickers under prefix {$batch['qr_prefix']} by " . ($batch['creator_name'] ?: 'Staff') . ".",
                ];

                if (!empty($batch['stock_added'])) {
                    $timeline[] = [
                        'stage'       => 'STOCK_ADDED',
                        'title'       => 'Batch Stock Added to Inventory',
                        'timestamp'   => $batch['stock_added_at'] ?: $batch['created_at'],
                        'badge'       => 'IN_STOCK',
                        'badge_color' => 'success',
                        'icon'        => 'bi-boxes',
                        'location'    => ($batch['concern_name'] ?: 'Warehouse') . ' Stock Store',
                        'description' => "All {$batch['quantity']} items converted into active inventory stock.",
                    ];
                }
            }
        }

        // =========================================================================
        // 1. FABRIC ROLL PIECE (e.g. FB112601) OR ROLL NUMBER (e.g. ROLL #1)
        // =========================================================================
        $isRollPiece = false;
        $roll = null;
        $pieceSeq = null;

        if (preg_match('/^([A-Z]{2}\d{4})(\d+)$/', $code, $matches)) {
            $prefix = $matches[1];
            $seq = (int)$matches[2];
            $candidates = Database::fetchAll(
                "SELECT sr.*, s.name as seller_name, s.company as seller_company,
                        pp.purchase_number, pp.purchase_date, pp.invoice_number, pp.id as purchase_id
                 FROM seller_rolls sr
                 JOIN sellers s ON s.id = sr.seller_id
                 LEFT JOIN production_purchase_items ppi ON ppi.seller_roll_id = sr.id
                 LEFT JOIN production_purchases pp ON pp.id = ppi.purchase_id
                 WHERE sr.start_code LIKE ? AND sr.end_code LIKE ?",
                [$prefix . '%', $prefix . '%']
            );

            foreach ($candidates as $cand) {
                if (strtoupper(substr($cand['start_code'], 0, 6)) === $prefix
                    && $seq >= (int)$cand['start_seq'] && $seq <= (int)$cand['end_seq']) {
                    $isRollPiece = true;
                    $roll = $cand;
                    $pieceSeq = $seq;
                    break;
                }
            }
        }

        if (!$isRollPiece) {
            // Check direct roll number, qr_code, start_code
            $directRoll = Database::fetchOne(
                "SELECT sr.*, s.name as seller_name, s.company as seller_company,
                        pp.purchase_number, pp.purchase_date, pp.invoice_number, pp.id as purchase_id
                 FROM seller_rolls sr
                 JOIN sellers s ON s.id = sr.seller_id
                 LEFT JOIN production_purchase_items ppi ON ppi.seller_roll_id = sr.id
                 LEFT JOIN production_purchases pp ON pp.id = ppi.purchase_id
                 WHERE sr.roll_number = ? OR sr.qr_code = ? OR sr.start_code = ? OR sr.end_code = ?
                 LIMIT 1",
                [$code, $code, $code, $code]
            );
            if ($directRoll) {
                $isRollPiece = true;
                $roll = $directRoll;
            }
        }

        if ($isRollPiece && $roll) {
            $matched = true;
            $itemType = 'Fabric Roll Piece';
            $suppName = $roll['seller_company'] ?: $roll['seller_name'];
            $itemTitle = "{$suppName} — {$roll['roll_number']}";

            $totQty = max(1, (int)$roll['quantity']);
            $pieceWeight = round(((float)$roll['weight']) / $totQty, 2);

            $details = [
                'Roll Label' => $roll['roll_number'],
                'Supplier / Mill' => $suppName,
                'Fabric Type' => $roll['material_type'] ?: 'Textile Fabric',
                'Color & Design' => trim(($roll['color'] ?? '') . ' ' . ($roll['design_name'] ?? '')),
                'Total Lot Weight' => number_format((float)$roll['weight'], 2) . ' kg (' . $totQty . ' Pcs)',
                'Piece Weight' => $pieceWeight . ' kg',
                'Piece Tag' => $pieceSeq ? "Piece #{$pieceSeq} of {$totQty} ({$code})" : $code,
            ];

            // Step 1: Procurement
            $timeline[] = [
                'stage' => 'MATERIAL_PURCHASE',
                'title' => 'Fabric Inward & Procurement',
                'timestamp' => $roll['purchase_date'] ?: $roll['created_at'],
                'badge' => 'Procured',
                'badge_color' => 'success',
                'icon' => 'bi-truck',
                'location' => 'Raw Material Inward / Fabric Store',
                'description' => "Procured from {$suppName} (Invoice: " . ($roll['invoice_number'] ?: 'N/A') . ", PO: " . ($roll['purchase_number'] ?: 'N/A') . "). Lot: {$roll['roll_number']} ({$roll['weight']} kg).",
            ];
            $currentLocation = 'Fabric Inventory / Storage Rack';
            $currentStatus = 'IN_STOCK';
            $statusColor = 'success';

            // Check Cutting Batches
            $cuttings = Database::fetchAll(
                "SELECT cb.*, p.name as product_name, cbr.used_weight
                 FROM cutting_batch_rolls cbr
                 JOIN cutting_batches cb ON cb.id = cbr.cutting_batch_id
                 LEFT JOIN production_programs pp ON pp.id = cb.program_id
                 LEFT JOIN products p ON p.id = pp.product_id
                 WHERE cbr.seller_roll_id = ?
                 ORDER BY cb.id ASC",
                [(int)$roll['id']]
            );

            foreach ($cuttings as $cut) {
                $timeline[] = [
                    'stage' => 'CUTTING',
                    'title' => 'Cutting Batch #' . $cut['batch_number'],
                    'timestamp' => $cut['created_at'],
                    'badge' => $cut['status'] ?: 'CUTTING',
                    'badge_color' => 'info',
                    'icon' => 'bi-scissors',
                    'location' => 'Production Cutting Floor',
                    'description' => "Assigned to Cutting Batch {$cut['batch_number']} for product: " . ($cut['product_name'] ?: 'Garment') . ". Cut Qty: " . ($cut['cut_qty'] ?: 0) . " pcs.",
                ];
                $currentLocation = "Cutting Section (Batch {$cut['batch_number']})";
                $currentStatus = 'IN_CUTTING';
                $statusColor = 'info';

                // Check Stitching Batches for this cutting batch
                $stitchings = Database::fetchAll(
                    "SELECT sb.*, pu.name as unit_name
                     FROM stitching_batches sb
                     LEFT JOIN production_units pu ON pu.id = sb.production_unit_id
                     WHERE sb.cutting_batch_id = ?
                     ORDER BY sb.id ASC",
                    [(int)$cut['id']]
                );
                foreach ($stitchings as $st) {
                    $timeline[] = [
                        'stage' => 'STITCHING',
                        'title' => 'Stitching Batch #' . $st['batch_number'],
                        'timestamp' => $st['created_at'],
                        'badge' => $st['status'] ?: 'STITCHING',
                        'badge_color' => 'info',
                        'icon' => 'bi-threads',
                        'location' => ($st['unit_name'] ?: 'Production') . ' Stitching Floor',
                        'description' => "Stitched under Batch {$st['batch_number']}. Output: {$st['stitched_qty']} pcs stitched.",
                    ];
                    $currentLocation = "Stitching Floor (Batch {$st['batch_number']})";
                    $currentStatus = 'IN_STITCHING';
                    $statusColor = 'info';
                }
            }

            // Check Damage
            $dmg = Database::fetchOne(
                "SELECT rd.*, u.full_name as reporter_name
                 FROM roll_damages rd
                 LEFT JOIN users u ON u.id = rd.reported_by
                 WHERE rd.qr_code = ? OR (rd.seller_roll_id = ? AND rd.qr_code IS NULL)
                 LIMIT 1",
                [$code, (int)$roll['id']]
            );
            if ($dmg) {
                $timeline[] = [
                    'stage' => 'DAMAGE',
                    'title' => 'Damage Quarantined #' . $dmg['damage_number'],
                    'timestamp' => $dmg['created_at'],
                    'badge' => $dmg['status'] ?: 'DAMAGED',
                    'badge_color' => 'danger',
                    'icon' => 'bi-exclamation-octagon-fill',
                    'location' => 'Defect Quarantine / Scrap Room',
                    'description' => "Defect reported: {$dmg['damage_reason']}. Loss valuation: ₹" . number_format((float)$dmg['loss_amount'], 2) . ". By: " . ($dmg['reporter_name'] ?: 'Staff'),
                ];
                $currentLocation = "Defect Quarantine ({$dmg['damage_number']})";
                $currentStatus = 'DAMAGED';
                $statusColor = 'danger';
            }

            // Check Billing / Invoiced
            $bill = Database::fetchOne(
                "SELECT pb.*
                 FROM production_bill_item_tags pbit
                 JOIN production_bills pb ON pb.id = pbit.bill_id
                 WHERE pbit.tag_code = ?
                 LIMIT 1",
                [$code]
            );
            if ($bill) {
                $cust = $bill['customer_name'] ?: $bill['company_name'] ?: 'Customer';
                $timeline[] = [
                    'stage' => 'BILLED',
                    'title' => 'Sold / Invoiced #' . $bill['bill_number'],
                    'timestamp' => $bill['created_at'],
                    'badge' => 'Sold',
                    'badge_color' => 'dark',
                    'icon' => 'bi-receipt',
                    'location' => 'Customer Handover (Sold)',
                    'description' => "Invoiced to {$cust} under Bill #{$bill['bill_number']}. Total: ₹" . number_format((float)$bill['total_amount'], 2),
                ];
                $currentLocation = "Sold / Dispatched to {$cust}";
                $currentStatus = 'SOLD';
                $statusColor = 'dark';
            }
        }

        // =========================================================================
        // 2. FINISHED PRODUCT PIECE TAG / GARMENT QR CODE (e.g. MSF112606, TTT112631)
        // =========================================================================
        if (!$matched) {
            $qcItem = Database::fetchOne(
                "SELECT qi.*, qb.batch_number as qc_batch_number, qb.created_at as qc_date,
                        qb.program_id, qb.stitching_batch_id,
                        p.id as product_id, p.name as product_name,
                        pv.variant_code, COALESCE(qi.size_name, sz.name) as size_name, COALESCE(qi.color_name, cl.name) as color_name,
                        u.full_name as inspector_name
                 FROM qc_items qi
                 JOIN qc_batches qb ON qb.id = qi.qc_batch_id
                 LEFT JOIN product_variants pv ON pv.id = qi.variant_id
                 LEFT JOIN products p ON (p.id = pv.product_id OR p.category_id = qi.category_id)
                 LEFT JOIN sizes sz ON sz.id = pv.size_id
                 LEFT JOIN colors cl ON cl.id = pv.color_id
                 LEFT JOIN users u ON u.id = qb.inspector_id
                 WHERE qi.tag_code = ?
                 LIMIT 1",
                [$code]
            );

            $batchItem = Database::fetchOne(
                "SELECT pbi.*, pb.batch_number, pb.created_at as batch_date,
                        pb.status as batch_status, pb.destination_warehouse_id, w.name as dest_warehouse_name,
                        p.id as product_id, p.name as product_name,
                        pv.variant_code, sz.name as size_name, cl.name as color_name
                 FROM production_batch_items pbi
                 JOIN production_batches pb ON pb.id = pbi.production_batch_id
                 LEFT JOIN product_variants pv ON pv.id = pbi.variant_id
                 LEFT JOIN products p ON p.id = pv.product_id
                 LEFT JOIN sizes sz ON sz.id = pv.size_id
                 LEFT JOIN colors cl ON cl.id = pv.color_id
                 LEFT JOIN warehouses w ON w.id = pb.destination_warehouse_id
                 WHERE pbi.piece_tags LIKE ? OR pbi.received_qr_codes LIKE ?
                 LIMIT 1",
                ['%' . $code . '%', '%' . $code . '%']
            );

            $whQr = Database::fetchOne(
                "SELECT wq.*, w.name as warehouse_name,
                        p.id as product_id, p.name as product_name,
                        pv.variant_code, sz.name as size_name, cl.name as color_name
                 FROM warehouse_qr_codes wq
                 LEFT JOIN warehouses w ON w.id = wq.warehouse_id
                 LEFT JOIN product_variants pv ON pv.id = wq.variant_id
                 LEFT JOIN products p ON p.id = pv.product_id
                 LEFT JOIN sizes sz ON sz.id = pv.size_id
                 LEFT JOIN colors cl ON cl.id = pv.color_id
                 WHERE wq.qr_code = ?
                 LIMIT 1",
                [$code]
            );

            // Also check product variant series
            $variantMatch = null;
            if (!$qcItem && !$batchItem && !$whQr) {
                $variantMatch = Database::fetchOne(
                    "SELECT pv.*, p.name as product_name,
                            c.name as category_name,
                            sz.name as size_name, cl.name as color_name, d.name as design_name
                     FROM product_variants pv
                     JOIN products p ON p.id = pv.product_id
                     LEFT JOIN categories c ON c.id = p.category_id
                     LEFT JOIN sizes sz ON sz.id = pv.size_id
                     LEFT JOIN colors cl ON cl.id = pv.color_id
                     LEFT JOIN designs d ON d.id = pv.design_id
                     WHERE pv.qr_code = ? OR pv.variant_code = ?
                     LIMIT 1",
                    [$code, $code]
                );
            }

            if ($qcItem || $batchItem || $whQr || $variantMatch) {
                $matched = true;
                $itemType = 'Finished Garment Piece';
                $prodName = $qcItem['product_name'] ?? $batchItem['product_name'] ?? $whQr['product_name'] ?? $variantMatch['product_name'] ?? 'Garment Piece';
                $variantCode = $qcItem['variant_code'] ?? $batchItem['variant_code'] ?? $whQr['variant_code'] ?? $variantMatch['variant_code'] ?? $code;
                $size = $qcItem['size_name'] ?? $batchItem['size_name'] ?? $whQr['size_name'] ?? $variantMatch['size_name'] ?? '';
                $color = $qcItem['color_name'] ?? $batchItem['color_name'] ?? $whQr['color_name'] ?? $variantMatch['color_name'] ?? '';

                $itemTitle = $prodName . ($size || $color ? " ({$size}" . ($color ? " / {$color}" : '') . ")" : '');
                $details = [
                    'Product Name' => $prodName,
                    'Variant ID' => $variantCode,
                    'Size' => $size ?: 'Standard',
                    'Color' => $color ?: 'Standard',
                    'Piece Tag / QR' => $code,
                ];

                // Backward trace to Program & Stitching if linked to QC
                if (!empty($qcItem['stitching_batch_id'])) {
                    $st = Database::fetchOne(
                        "SELECT sb.*, cb.batch_number as cutting_batch_number
                         FROM stitching_batches sb
                         LEFT JOIN cutting_batches cb ON cb.id = sb.cutting_batch_id
                         WHERE sb.id = ?",
                        [(int)$qcItem['stitching_batch_id']]
                    );
                    if ($st) {
                        $timeline[] = [
                            'stage' => 'STITCHING',
                            'title' => 'Stitched in Batch #' . $st['batch_number'],
                            'timestamp' => $st['created_at'],
                            'badge' => 'COMPLETED',
                            'badge_color' => 'info',
                            'icon' => 'bi-threads',
                            'location' => 'Production Stitching Floor',
                            'description' => "Stitched under Batch {$st['batch_number']}." . ($st['cutting_batch_number'] ? " Cut from Batch {$st['cutting_batch_number']}." : ''),
                        ];
                    }
                }

                // QC Inspection
                if ($qcItem) {
                    $timeline[] = [
                        'stage' => 'QC_INSPECTION',
                        'title' => 'Quality Control Inspection',
                        'timestamp' => $qcItem['qc_date'],
                        'badge' => $qcItem['action_taken'] ?: 'PASSED',
                        'badge_color' => ($qcItem['action_taken'] === 'PASSED') ? 'success' : 'warning',
                        'icon' => 'bi-shield-check',
                        'location' => 'QC & Finishing Inspection Floor',
                        'description' => "Inspected under Batch {$qcItem['qc_batch_number']}. Result: {$qcItem['action_taken']}." . ($qcItem['inspector_name'] ? " Inspector: {$qcItem['inspector_name']}." : ''),
                    ];
                    $currentLocation = 'QC & Finishing Station';
                    $currentStatus = 'QC_' . ($qcItem['action_taken'] ?: 'PASSED');
                    $statusColor = 'info';
                }

                // Packaging Batch
                if ($batchItem) {
                    $timeline[] = [
                        'stage' => 'PACKING_BATCH',
                        'title' => 'Packed in Batch #' . $batchItem['batch_number'],
                        'timestamp' => $batchItem['batch_date'],
                        'badge' => $batchItem['batch_status'] ?: 'PACKED',
                        'badge_color' => 'primary',
                        'icon' => 'bi-box-seam',
                        'location' => 'Packing & Dispatch Section',
                        'description' => "Batched for warehouse transfer to " . ($batchItem['dest_warehouse_name'] ?: 'Central Hub') . ". Batch: {$batchItem['batch_number']}.",
                    ];
                    $currentLocation = 'Production Dispatch / Packing Room';
                    $currentStatus = 'BATCH_PACKED';
                    $statusColor = 'primary';
                }

                // Outward Dispatch
                $prodBatchId = (int)($batchItem['production_batch_id'] ?? 0);
                $outward = Database::fetchOne(
                    "SELECT po.*, w.name as warehouse_name
                     FROM production_outwards po
                     LEFT JOIN warehouses w ON w.id = po.warehouse_id
                     WHERE (po.production_batch_id = ? AND ? > 0) OR po.notes LIKE ?
                     ORDER BY po.id DESC LIMIT 1",
                    [$prodBatchId, $prodBatchId, '%' . $code . '%']
                );
                if ($outward) {
                    $isRcv = ($outward['status'] === 'RECEIVED');
                    $timeline[] = [
                        'stage' => 'DISPATCH_TRANSIT',
                        'title' => 'Outward Dispatch #' . $outward['outward_number'],
                        'timestamp' => $outward['dispatch_date'] ?: $outward['created_at'],
                        'badge' => $outward['status'] ?: 'DISPATCHED',
                        'badge_color' => $isRcv ? 'success' : 'warning',
                        'icon' => 'bi-truck-flatbed',
                        'location' => $isRcv ? ($outward['warehouse_name'] ?: 'Warehouse') : 'In Transit to ' . ($outward['warehouse_name'] ?: 'Warehouse'),
                        'description' => "Outwarded from Production Unit to {$outward['warehouse_name']}. Tracking/Vehicle: " . ($outward['vehicle_number'] ?: 'Direct Transfer') . ". Status: {$outward['status']}.",
                    ];
                    if ($isRcv) {
                        $currentLocation = ($outward['warehouse_name'] ?: 'Warehouse') . ' Inward Floor';
                        $currentStatus = 'AT_WAREHOUSE';
                        $statusColor = 'success';
                    } else {
                        $currentLocation = 'In Transit to ' . ($outward['warehouse_name'] ?: 'Warehouse');
                        $currentStatus = 'IN_TRANSIT';
                        $statusColor = 'warning';
                    }
                }

                // Warehouse Inward Stock
                if ($whQr) {
                    $inwardTime = $whQr['inwarded_at'] ?: $whQr['created_at'];
                    $timeline[] = [
                        'stage' => 'WAREHOUSE_STOCK',
                        'title' => 'Warehouse Inward & Stock Registration',
                        'timestamp' => $inwardTime,
                        'badge' => 'Active Inward Stock',
                        'badge_color' => 'success',
                        'icon' => 'bi-building-check',
                        'location' => ($whQr['warehouse_name'] ?: 'Main Warehouse') . ' Stock Bin',
                        'description' => "Inwarded into warehouse stock inventory at {$whQr['warehouse_name']}.",
                    ];
                    $currentLocation = ($whQr['warehouse_name'] ?: 'Warehouse') . ' Inventory Stock';
                    $currentStatus = 'IN_WAREHOUSE_STOCK';
                    $statusColor = 'success';
                }

                // Allocations to Shops
                $alloc = Database::fetchOne(
                    "SELECT wa.*, s.name as shop_name, ao.dispatch_date, ao.status as dispatch_status
                     FROM warehouse_allocations wa
                     LEFT JOIN shops s ON s.id = wa.shop_id
                     LEFT JOIN allocation_outwards ao ON ao.allocation_id = wa.id
                     WHERE wa.notes LIKE ? OR wa.allocation_number LIKE ?
                     LIMIT 1",
                    ['%' . $code . '%', '%' . $code . '%']
                );
                if ($alloc) {
                    $timeline[] = [
                        'stage' => 'SHOP_ALLOCATION',
                        'title' => 'Allocated to Retail Shop: ' . ($alloc['shop_name'] ?: 'Shop'),
                        'timestamp' => $alloc['created_at'],
                        'badge' => $alloc['status'] ?: 'ALLOCATED',
                        'badge_color' => 'info',
                        'icon' => 'bi-shop',
                        'location' => 'Retail Shop — ' . ($alloc['shop_name'] ?: 'Shop'),
                        'description' => "Allocated under Allocation #{$alloc['allocation_number']} to {$alloc['shop_name']}.",
                    ];
                    $currentLocation = 'Retail Shop (' . ($alloc['shop_name'] ?: 'Shop') . ')';
                    $currentStatus = 'AT_SHOP';
                    $statusColor = 'info';
                }

                // Damage Checks
                $dmg = Database::fetchOne(
                    "SELECT rd.*, u.full_name as reporter_name
                     FROM roll_damages rd
                     LEFT JOIN users u ON u.id = rd.reported_by
                     WHERE rd.qr_code = ? LIMIT 1",
                    [$code]
                );
                if (!$dmg) {
                    $dmg = Database::fetchOne(
                        "SELECT wd.*, u.full_name as reporter_name
                         FROM warehouse_damages wd
                         LEFT JOIN users u ON u.id = wd.reported_by
                         WHERE wd.tag_code = ? LIMIT 1",
                        [$code]
                    );
                }
                if ($dmg) {
                    $dmgNum = $dmg['damage_number'] ?? 'DMG';
                    $lossVal = $dmg['loss_amount'] ?? $dmg['total_loss'] ?? 0;
                    $timeline[] = [
                        'stage' => 'DAMAGE',
                        'title' => 'Damage Reported #' . $dmgNum,
                        'timestamp' => $dmg['created_at'],
                        'badge' => $dmg['status'] ?? 'DAMAGED',
                        'badge_color' => 'danger',
                        'icon' => 'bi-exclamation-octagon-fill',
                        'location' => 'Defect Quarantine',
                        'description' => "Defect: " . ($dmg['damage_reason'] ?? 'Damaged') . ". Loss Valuation: ₹" . number_format((float)$lossVal, 2),
                    ];
                    $currentLocation = "Damage Quarantine ({$dmgNum})";
                    $currentStatus = 'DAMAGED';
                    $statusColor = 'danger';
                }

                // Billing / Sales
                $bill = Database::fetchOne(
                    "SELECT pb.*
                     FROM production_bill_item_tags pbit
                     JOIN production_bills pb ON pb.id = pbit.bill_id
                     WHERE pbit.tag_code = ?
                     LIMIT 1",
                    [$code]
                );
                if ($bill) {
                    $cust = $bill['customer_name'] ?: $bill['company_name'] ?: 'Customer';
                    $timeline[] = [
                        'stage' => 'BILLED',
                        'title' => 'Sold / Billed #' . $bill['bill_number'],
                        'timestamp' => $bill['created_at'],
                        'badge' => 'Sold',
                        'badge_color' => 'dark',
                        'icon' => 'bi-receipt',
                        'location' => 'Customer Handover (Sold)',
                        'description' => "Invoiced to {$cust} under Bill #{$bill['bill_number']}.",
                    ];
                    $currentLocation = "Sold to Customer (Bill #{$bill['bill_number']})";
                    $currentStatus = 'SOLD';
                    $statusColor = 'dark';
                }
            }
        }

        // =========================================================================
        // 3. PURCHASE ORDER / INVOICE NUMBER (e.g. PP-2026-000001)
        // =========================================================================
        if (!$matched) {
            $po = Database::fetchOne(
                "SELECT pp.*, s.name as seller_name, s.company as seller_company
                 FROM production_purchases pp
                 LEFT JOIN sellers s ON s.id = pp.seller_id
                 WHERE pp.purchase_number = ? OR pp.invoice_number = ?
                 LIMIT 1",
                [$code, $code]
            );
            if ($po) {
                $matched = true;
                $itemType = 'Procurement Order';
                $supp = $po['seller_company'] ?: $po['seller_name'];
                $itemTitle = "Procurement Order {$po['purchase_number']} ({$supp})";
                $details = [
                    'PO Number' => $po['purchase_number'],
                    'Supplier' => $supp,
                    'Invoice #' => $po['invoice_number'] ?: 'N/A',
                    'Purchase Date' => date('d M Y', strtotime($po['purchase_date'])),
                    'Total Amount' => '₹ ' . number_format((float)$po['total_amount'], 2),
                ];
                $timeline[] = [
                    'stage' => 'PURCHASE_ORDER',
                    'title' => 'Procurement Order Created',
                    'timestamp' => $po['created_at'],
                    'badge' => 'Recorded',
                    'badge_color' => 'success',
                    'icon' => 'bi-cart-check',
                    'location' => 'Purchasing Department',
                    'description' => "Purchase order generated for {$supp}. Total: ₹" . number_format((float)$po['total_amount'], 2),
                ];
                $currentStatus = 'RECEIVED';
                $statusColor = 'success';
                $currentLocation = 'Inward Materials / Fabric Store';
            }
        }

        // =========================================================================
        // 4. CUTTING BATCH NUMBER (e.g. CUT-20261108-0001)
        // =========================================================================
        if (!$matched) {
            $cutBatch = Database::fetchOne(
                "SELECT cb.*, p.name as product_name
                 FROM cutting_batches cb
                 LEFT JOIN production_programs pp ON pp.id = cb.program_id
                 LEFT JOIN products p ON p.id = pp.product_id
                 WHERE cb.batch_number = ?
                 LIMIT 1",
                [$code]
            );
            if ($cutBatch) {
                $matched = true;
                $itemType = 'Cutting Batch';
                $itemTitle = "Cutting Batch {$cutBatch['batch_number']}";
                $details = [
                    'Batch Number' => $cutBatch['batch_number'],
                    'Target Product' => $cutBatch['product_name'] ?: 'Garment Product',
                    'Status' => $cutBatch['status'] ?: 'IN_PROGRESS',
                    'Planned Qty' => ($cutBatch['planned_qty'] ?: 0) . ' pcs',
                    'Cut Qty' => ($cutBatch['cut_qty'] ?: 0) . ' pcs',
                ];
                $timeline[] = [
                    'stage' => 'CUTTING',
                    'title' => 'Cutting Batch Initialized',
                    'timestamp' => $cutBatch['created_at'],
                    'badge' => $cutBatch['status'] ?: 'CUTTING',
                    'badge_color' => 'info',
                    'icon' => 'bi-scissors',
                    'location' => 'Production Cutting Floor',
                    'description' => "Target Product: " . ($cutBatch['product_name'] ?: 'Garment') . ". Cut: {$cutBatch['cut_qty']} pcs.",
                ];
                $currentStatus = $cutBatch['status'] ?: 'IN_PROGRESS';
                $statusColor = 'info';
                $currentLocation = 'Production Cutting Floor';
            }
        }

        // =========================================================================
        // 5. PACKING / FINISHED PRODUCTION BATCH (e.g. BAT-20261108-0001)
        // =========================================================================
        if (!$matched) {
            $pb = Database::fetchOne(
                "SELECT pb.*, w.name as warehouse_name
                 FROM production_batches pb
                 LEFT JOIN warehouses w ON w.id = pb.destination_warehouse_id
                 WHERE pb.batch_number = ?
                 LIMIT 1",
                [$code]
            );
            if ($pb) {
                $matched = true;
                $itemType = 'Production Batch (Packed)';
                $itemTitle = "Production Batch {$pb['batch_number']}";
                $details = [
                    'Batch Number' => $pb['batch_number'],
                    'Destination Warehouse' => $pb['warehouse_name'] ?: 'Central Hub',
                    'Status' => $pb['status'] ?: 'PACKED',
                    'Total Pieces' => ($pb['total_qty'] ?: 0) . ' pcs',
                    'Total Boxes' => ($pb['box_count'] ?: 1) . ' boxes',
                ];
                $timeline[] = [
                    'stage' => 'PACKING_BATCH',
                    'title' => 'Batch Packed & Registered',
                    'timestamp' => $pb['created_at'],
                    'badge' => $pb['status'] ?: 'PACKED',
                    'badge_color' => 'primary',
                    'icon' => 'bi-box-seam',
                    'location' => 'Packing & Dispatch Section',
                    'description' => "Total {$pb['total_qty']} pcs packed for {$pb['warehouse_name']}.",
                ];
                $currentStatus = $pb['status'] ?: 'PACKED';
                $statusColor = 'primary';
                $currentLocation = 'Production Dispatch / ' . ($pb['warehouse_name'] ?: 'Warehouse');
            }
        }

        // =========================================================================
        // 6. OUTWARD DISPATCH NUMBER (e.g. OUT-20261108-0001)
        // =========================================================================
        if (!$matched) {
            $out = Database::fetchOne(
                "SELECT po.*, w.name as warehouse_name
                 FROM production_outwards po
                 LEFT JOIN warehouses w ON w.id = po.warehouse_id
                 WHERE po.outward_number = ?
                 LIMIT 1",
                [$code]
            );
            if ($out) {
                $matched = true;
                $itemType = 'Outward Dispatch Consignment';
                $itemTitle = "Outward Consignment {$out['outward_number']}";
                $details = [
                    'Outward #' => $out['outward_number'],
                    'Destination' => $out['warehouse_name'] ?: 'Central Warehouse',
                    'Status' => $out['status'] ?: 'DISPATCHED',
                    'Total Quantity' => ($out['quantity'] ?: $out['total_quantity'] ?: 0) . ' pcs',
                    'Vehicle / LR' => ($out['vehicle_number'] ?: 'Direct Courier') . ' ' . ($out['lr_number'] ? "(LR: {$out['lr_number']})" : ''),
                ];
                $timeline[] = [
                    'stage' => 'DISPATCH_TRANSIT',
                    'title' => 'Dispatched from Production Unit',
                    'timestamp' => $out['dispatch_date'] ?: $out['created_at'],
                    'badge' => $out['status'] ?: 'DISPATCHED',
                    'badge_color' => ($out['status'] === 'RECEIVED') ? 'success' : 'warning',
                    'icon' => 'bi-truck-flatbed',
                    'location' => ($out['status'] === 'RECEIVED') ? $out['warehouse_name'] : 'In Transit',
                    'description' => "Dispatched to {$out['warehouse_name']}. Status: {$out['status']}.",
                ];
                $currentStatus = $out['status'] ?: 'DISPATCHED';
                $statusColor = ($out['status'] === 'RECEIVED') ? 'success' : 'warning';
                $currentLocation = ($out['status'] === 'RECEIVED') ? ($out['warehouse_name'] . ' Inward Bay') : ('In Transit to ' . $out['warehouse_name']);
            }
        }

        // =========================================================================
        // 7. DAMAGE LOG NUMBER (e.g. DMG-ROL-2026-...)
        // =========================================================================
        if (!$matched) {
            $dmgLog = Database::fetchOne(
                "SELECT rd.*, s.name as seller_name, u.full_name as reporter_name
                 FROM roll_damages rd
                 LEFT JOIN sellers s ON s.id = rd.seller_id
                 LEFT JOIN users u ON u.id = rd.reported_by
                 WHERE rd.damage_number = ?
                 LIMIT 1",
                [$code]
            );
            if (!$dmgLog) {
                $dmgLog = Database::fetchOne(
                    "SELECT wd.*, u.full_name as reporter_name
                     FROM warehouse_damages wd
                     LEFT JOIN users u ON u.id = wd.reported_by
                     WHERE wd.damage_number = ?
                     LIMIT 1",
                    [$code]
                );
            }
            if ($dmgLog) {
                $matched = true;
                $itemType = 'Defect / Damage Record';
                $dmgNum = $dmgLog['damage_number'];
                $itemTitle = "Damage Record {$dmgNum}";
                $details = [
                    'Damage Record #' => $dmgNum,
                    'Reason' => $dmgLog['damage_reason'],
                    'Loss Valuation' => '₹ ' . number_format((float)($dmgLog['loss_amount'] ?? $dmgLog['total_loss'] ?? 0), 2),
                    'Reported By' => $dmgLog['reporter_name'] ?: 'Staff',
                    'Status' => $dmgLog['status'] ?: 'LOGGED',
                ];
                $timeline[] = [
                    'stage' => 'DAMAGE',
                    'title' => 'Damage Logged',
                    'timestamp' => $dmgLog['created_at'],
                    'badge' => $dmgLog['status'] ?: 'LOGGED',
                    'badge_color' => 'danger',
                    'icon' => 'bi-exclamation-octagon-fill',
                    'location' => 'Defect Quarantine Floor',
                    'description' => "Defect: {$dmgLog['damage_reason']}. Quarantined from active stock.",
                ];
                $currentStatus = $dmgLog['status'] ?: 'LOGGED';
                $statusColor = 'danger';
                $currentLocation = 'Defect Quarantine Area';
            }
        }

        // =========================================================================
        // 8. BILLING / SALES INVOICE (e.g. PB-2026-000001)
        // =========================================================================
        if (!$matched) {
            $billRec = Database::fetchOne(
                "SELECT pb.*
                 FROM production_bills pb
                 WHERE pb.bill_number = ?
                 LIMIT 1",
                [$code]
            );
            if ($billRec) {
                $matched = true;
                $itemType = 'Sales Invoice';
                $cust = $billRec['customer_name'] ?: $billRec['company_name'] ?: 'Customer';
                $itemTitle = "Invoice {$billRec['bill_number']} ({$cust})";
                $details = [
                    'Invoice #' => $billRec['bill_number'],
                    'Customer' => $cust,
                    'Invoice Date' => date('d M Y', strtotime($billRec['bill_date'] ?: $billRec['created_at'])),
                    'Total Amount' => '₹ ' . number_format((float)$billRec['total_amount'], 2),
                    'Payment Status' => $billRec['payment_status'] ?: 'PAID',
                ];
                $timeline[] = [
                    'stage' => 'BILLED',
                    'title' => 'Invoice Issued & Paid',
                    'timestamp' => $billRec['created_at'],
                    'badge' => 'COMPLETED',
                    'badge_color' => 'success',
                    'icon' => 'bi-receipt',
                    'location' => 'Customer Handover',
                    'description' => "Invoiced to {$cust}. Total: ₹" . number_format((float)$billRec['total_amount'], 2),
                ];
                $currentStatus = 'SOLD';
                $statusColor = 'dark';
                $currentLocation = 'Dispatched to Customer (' . $cust . ')';
            }
        }

        

        if (!$matched) {
            return [
                'success' => false,
                'message' => "This QR code ({$code}) is not in any inventory sections.",
            ];
        }

        // Sort timeline chronologically
        usort($timeline, static function(array $a, array $b): int {
            return strtotime($a['timestamp'] ?? '') <=> strtotime($b['timestamp'] ?? '');
        });

        return [
            'success' => true,
            'code' => $code,
            'item_title' => $itemTitle,
            'item_type' => $itemType,
            'current_status' => $currentStatus,
            'status_color' => $statusColor,
            'current_location' => $currentLocation,
            'details' => $details,
            'timeline' => $timeline,
            'timeline_count' => count($timeline),
        ];
    } catch (Throwable $e) {
        error_log("QrFinderService trace error: " . $e->getMessage());
        return [
            'success' => false,
            'message' => "This QR code ({$code}) is not in any inventory sections.",
            'code' => $code,
        ];
    }
}
}
