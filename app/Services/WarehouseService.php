<?php
/**
 * Warehouse Service
 * Handles warehouse stock receipt, cross-check verification, QR validation, and dispatch reconciling.
 */

require_once BASE_PATH . '/app/Models/ProductionBatch.php';
require_once BASE_PATH . '/app/Models/ProductionOutward.php';
require_once BASE_PATH . '/app/Models/Warehouse.php';
require_once BASE_PATH . '/app/Models/WarehouseDamage.php';
require_once BASE_PATH . '/app/Services/StockService.php';
require_once BASE_PATH . '/app/Services/AuditService.php';

class WarehouseService {

    /**
     * Get outward dispatch details for a production batch.
     */
    public static function getOutwardDispatchDetails(int $batchId): ?array {
        $batch = ProductionBatch::find($batchId);
        if (!$batch) {
            return null;
        }

        $outward = ProductionOutward::findByBatchId($batchId);
        $items = ProductionBatch::getItems($batchId);

        return [
            'batch'   => $batch,
            'outward' => $outward,
            'items'   => $items,
        ];
    }

    /**
     * Process a live cross-check scan on a batch.
     * Recognizes:
     *  1. Master Carton QR (batch_number e.g. BAT-... or outward_number e.g. OUT-...)
     *  2. Individual Piece QR tracking codes (Option A: PRD2611-0001, etc.)
     *  3. Product Variant SKU, Variant Code, or Variant ID
     */
    public static function processCrossCheckScan(int $batchId, string $scannedCode, array $scannedMap = []): array {
        $details = self::getOutwardDispatchDetails($batchId);
        if (!$details) {
            throw new Exception("Production batch #$batchId not found.");
        }

        $batch = $details['batch'];
        $items = $details['items'];
        $scannedCode = trim($scannedCode);

        $extractedCode = $scannedCode;
        $extractedSku = '';
        $extractedTag = '';

        if (str_starts_with($scannedCode, '{') && str_ends_with($scannedCode, '}')) {
            $json = json_decode($scannedCode, true);
            if (!empty($json['sku'])) {
                $extractedSku = trim($json['sku']);
            }
            if (!empty($json['qr_code'])) {
                $extractedCode = trim($json['qr_code']);
            }
            if (!empty($json['tag_code'])) {
                $extractedTag = trim($json['tag_code']);
            }
            if (!empty($json['batch_number'])) {
                $extractedCode = trim($json['batch_number']);
            }
        } elseif (str_contains($scannedCode, '|')) {
            $parts = explode('|', $scannedCode);
            $extractedTag = trim($parts[0]);
            if (!empty($parts[1])) {
                $extractedSku = trim($parts[1]);
            }
        }

        // Check if scanned code is the Batch/Outward Master Carton QR
        $isMasterCarton = (
            strcasecmp($scannedCode, (string)$batch['batch_number']) === 0 ||
            strcasecmp($extractedCode, (string)$batch['batch_number']) === 0 ||
            (!empty($details['outward']) && (
                strcasecmp($scannedCode, (string)$details['outward']['outward_number']) === 0 ||
                strcasecmp($extractedCode, (string)$details['outward']['outward_number']) === 0
            ))
        );

        $matchedVariantId = null;
        $matchedItem = null;

        if ($isMasterCarton) {
            // Master carton QR received: verify all items assigned to this batch
            foreach ($items as $item) {
                $vid = (int)$item['variant_id'];
                $scannedMap[$vid] = (int)$item['quantity'];
            }
        } else {
            $scannedPieceTag = $extractedTag !== '' ? $extractedTag : $scannedCode;

            foreach ($items as $item) {
                $itemSku = trim((string)($item['sku'] ?? ''));
                $itemQr = trim((string)($item['qr_code'] ?? ''));
                $varCode = trim((string)($item['variant_code'] ?? ''));
                $varId = (string)($item['variant_id'] ?? '');

                $outwardPieceTags = array_values(array_filter(array_map(
                    static fn($tag) => strtoupper(trim((string)$tag)),
                    is_array($item['outward_piece_tags'] ?? null) ? $item['outward_piece_tags'] : []
                )));
                $legacyPieceTags = array_values(array_filter(array_map(
                    static fn($tag) => strtoupper(trim((string)$tag)),
                    is_array($item['piece_tags'] ?? null) ? $item['piece_tags'] : []
                )));
                $allVariantPieceTags = array_unique(array_merge($outwardPieceTags, $legacyPieceTags));

                $isMatch = false;

                // 1. Check piece tracking tag (Option A sequential codes PRD2611-0001, etc.)
                if (!empty($allVariantPieceTags)) {
                    $isMatch = in_array(strtoupper($scannedPieceTag), $allVariantPieceTags, true);
                }

                // 2. Check variant QR, SKU, variant code, or variant ID
                if (!$isMatch) {
                    $isMatch = (
                        ($itemQr !== '' && strcasecmp($itemQr, $scannedCode) === 0) ||
                        ($itemQr !== '' && strcasecmp($itemQr, $extractedCode) === 0) ||
                        ($itemSku !== '' && strcasecmp($itemSku, $scannedCode) === 0) ||
                        ($itemSku !== '' && strcasecmp($itemSku, $extractedCode) === 0) ||
                        ($varCode !== '' && strcasecmp($varCode, $scannedCode) === 0) ||
                        ($varCode !== '' && strcasecmp($varCode, $extractedCode) === 0) ||
                        ($extractedSku !== '' && strcasecmp($itemSku, $extractedSku) === 0) ||
                        $varId === $scannedCode ||
                        $varId === $extractedCode
                    );
                }

                // 3. Fallback check for sequence range
                if (!$isMatch && !empty($item['start_seq']) && !empty($item['end_seq'])) {
                    if (preg_match('/(\d+)$/', $scannedPieceTag, $mNum)) {
                        $seq = (int)$mNum[1];
                        $isMatch = ($seq >= (int)$item['start_seq'] && $seq <= (int)$item['end_seq']);
                    }
                }

                if ($isMatch) {
                    $matchedVariantId = (int)$item['variant_id'];
                    $matchedItem = $item;
                    break;
                }
            }

            // Fallback match for single-item batches
            if (!$matchedVariantId && count($items) === 1) {
                $matchedVariantId = (int)$items[0]['variant_id'];
                $matchedItem = $items[0];
            }

            if (!$matchedVariantId) {
                return [
                    'success'      => false,
                    'message'      => "Scanned code '$scannedCode' does not match any product variant or QR code in Batch #{$batch['batch_number']}.",
                    'matched_item' => null,
                    'scanned_map'  => $scannedMap,
                ];
            }

            $expectedCount = max(0, (int)$matchedItem['quantity']);
            if ($expectedCount === 0) {
                return [
                    'success'      => false,
                    'message'      => "No outwarded pieces are available for {$matchedItem['sku']} in this batch.",
                    'matched_item' => $matchedItem,
                    'scanned_map'  => $scannedMap,
                ];
            }

            $isSeriesOrComp = (!empty($matchedItem['is_series']) || !empty($matchedItem['is_complementary']) || ($matchedItem['qr_type'] ?? '') === 'SERIES_BATCH' || (int)($matchedItem['pack_quantity'] ?? 1) > 1);
            if ($isSeriesOrComp) {
                $scannedMap[$matchedVariantId] = (int)$matchedItem['quantity'];
                $scanMessage = "Scanned {$matchedItem['sku']} (All counts inwarded: +{$matchedItem['quantity']} pcs)";
            } else {
                $currentCount = max(0, min($expectedCount, (int)($scannedMap[$matchedVariantId] ?? 0)));
                if ($currentCount >= $expectedCount) {
                    return [
                        'success'      => false,
                        'message'      => "All {$expectedCount} outwarded pieces for {$matchedItem['sku']} have already been scanned.",
                        'matched_item' => $matchedItem,
                        'scanned_map'  => $scannedMap,
                    ];
                }
                $scannedMap[$matchedVariantId] = $currentCount + 1;
                $scanMessage = "Scanned {$matchedItem['sku']} (+1 pc)";
            }
        }

        // Build comparison breakdown and missing QR list
        $totalExpected = 0;
        $totalScanned = 0;
        $comparison = [];
        $missingItems = [];

        foreach ($items as $item) {
            $vid = (int)$item['variant_id'];
            $expected = (int)$item['quantity'];
            $scanned = max(0, min($expected, (int)($scannedMap[$vid] ?? 0)));
            $diff = $scanned - $expected;

            $totalExpected += $expected;
            $totalScanned += $scanned;

            $status = 'MATCHED';
            if ($diff < 0) {
                $status = 'SHORTAGE';
                $missingItems[] = [
                    'variant_id'   => $vid,
                    'sku'          => $item['sku'],
                    'qr_code'      => $item['qr_code'] ?? $item['sku'],
                    'size_name'    => $item['size_name'] ?? 'Free Size',
                    'color_name'   => $item['color_name'] ?? 'Standard',
                    'color_hex'    => $item['color_hex'] ?? '#6c757d',
                    'expected_qty' => $expected,
                    'scanned_qty'  => $scanned,
                    'missing_qty'  => abs($diff),
                ];
            } elseif ($diff > 0) {
                $status = 'EXCESS';
            }

            $comparison[] = [
                'variant_id'    => $vid,
                'sku'           => $item['sku'],
                'qr_code'       => $item['qr_code'] ?? $item['sku'],
                'size_name'     => $item['size_name'] ?? 'Free Size',
                'color_name'    => $item['color_name'] ?? 'Standard',
                'color_hex'     => $item['color_hex'] ?? '#6c757d',
                'selling_price' => (float)($item['selling_price'] ?? 0),
                'expected_qty'  => $expected,
                'scanned_qty'   => $scanned,
                'diff'          => $diff,
                'status'        => $status,
            ];
        }

        $overallDiff = $totalScanned - $totalExpected;
        $isComplete = empty($missingItems) && $totalScanned === $totalExpected;

        return [
            'success'          => true,
            'message'          => $isMasterCarton ? "Master Carton QR scanned! All items verified." : ($scanMessage ?? "Scanned {$matchedItem['sku']}"),
            'is_master_carton' => $isMasterCarton,
            'matched_item'     => $matchedItem,
            'total_expected'   => $totalExpected,
            'total_scanned'    => $totalScanned,
            'overall_diff'     => $overallDiff,
            'is_complete'      => $isComplete,
            'comparison'       => $comparison,
            'missing_items'    => $missingItems,
            'scanned_map'      => $scannedMap,
        ];
    }

    /**
     * Receive and Verify Production Inward Batch.
     * Updates batch status to 'RECEIVED' and increases warehouse stock via StockService.
     */
    public static function receiveProductionBatch(int $batchId, int $userId, ?string $notes = null, ?array $receivedItems = null): bool {
        Database::beginTransaction();
        try {
            $batch = ProductionBatch::find($batchId);
            if (!$batch) {
                throw new Exception("Production batch #$batchId not found.");
            }

            if ($batch['status'] !== 'DISPATCHED' && $batch['status'] !== 'RECEIVED') {
                throw new Exception("Batch status is '{$batch['status']}'. Batches in this status cannot be received.");
            }

            $items = ProductionBatch::getItems($batchId);
            if (empty($items)) {
                throw new Exception("No variant items found in batch #$batchId.");
            }

            $warehouseId = (int)$batch['destination_warehouse_id'];
            $warehouse = Warehouse::find($warehouseId);
            if (!$warehouse) {
                throw new Exception("Destination warehouse #$warehouseId not found.");
            }

            $cid = $batch['concern_id'] ?? (function_exists('currentConcernId') ? currentConcernId() : null);

            $totalAccepted = 0;
            $totalDamaged = 0;
            $totalExpected = 0;
            $totalIncrementalAccepted = 0;
            $totalIncrementalDamaged = 0;
            $discrepancyReport = [];

            // Process stock inward for each variant in batch
            foreach ($items as $item) {
                $vid = (int)$item['variant_id'];
                $itemReceivedData = $receivedItems[$vid] ?? [];
                $expectedQty = (float)$item['quantity'];
                $totalExpected += $expectedQty;

                $prevReceivedQty = (float)($item['received_qty'] ?? 0);
                $prevDamagedQty = (float)($item['damaged_qty'] ?? 0);

                if ($receivedItems !== null && isset($receivedItems[$vid])) {
                    $acceptedQty = (float)($itemReceivedData['received_qty'] ?? $itemReceivedData['accepted_qty'] ?? 0);
                    $damagedQty = (float)($itemReceivedData['damaged_qty'] ?? 0);
                } else {
                    $acceptedQty = $expectedQty;
                    $damagedQty = 0;
                }

                if ($acceptedQty < 0 || $damagedQty < 0 || ($acceptedQty + $damagedQty) > $expectedQty) {
                    throw new Exception("Inward quantity for SKU {$item['sku']} cannot exceed the {$expectedQty} pieces dispatched for this batch.");
                }
                if (floor($acceptedQty) !== $acceptedQty || floor($damagedQty) !== $damagedQty) {
                    throw new Exception("Inward quantities for SKU {$item['sku']} must be whole pieces.");
                }

                $receivedTagList = array_values(array_filter(array_map('trim', explode(',', (string)($itemReceivedData['received_tags'] ?? '')))));
                $damagedTagList = array_values(array_filter(array_map('trim', explode(',', (string)($itemReceivedData['damaged_tags'] ?? '')))));

                $missingQty = max(0, (int)($expectedQty - ($acceptedQty + $damagedQty)));
                $totalAccepted += $acceptedQty;
                $totalDamaged += $damagedQty;
                $diff = ($acceptedQty + $damagedQty) - $expectedQty;

                if ($diff !== 0.0) {
                    $discrepancyReport[] = "SKU {$item['sku']}: Expected $expectedQty, Accepted $acceptedQty, Damaged $damagedQty (Diff: $diff)";
                }

                $itemRecvTags = trim($itemReceivedData['received_tags'] ?? '');
                $itemDmgTags = trim($itemReceivedData['damaged_tags'] ?? '');

                $pieceTags = !empty($item['outward_piece_tags'])
                    ? $item['outward_piece_tags']
                    : (!empty($item['piece_tags']) ? $item['piece_tags'] : []);

                if (empty($itemRecvTags) && !empty($pieceTags) && $acceptedQty > 0) {
                    $recvList = array_slice($pieceTags, 0, (int)$acceptedQty);
                    $itemRecvTags = implode(',', $recvList);
                }
                if (empty($itemDmgTags) && !empty($pieceTags) && $damagedQty > 0) {
                    $dmgList = array_slice($pieceTags, (int)$acceptedQty, (int)$damagedQty);
                    $itemDmgTags = implode(',', $dmgList);
                }

                // Persist variant-level received, damaged, missing quantities, and exact QR codes
                Database::execute(
                    "UPDATE production_batch_items 
                     SET received_qty = ?, damaged_qty = ?, missing_qty = ?, received_qr_codes = ?, damaged_qr_codes = ? 
                     WHERE id = ?",
                    [(int)$acceptedQty, (int)$damagedQty, (int)$missingQty, $itemRecvTags ?: null, $itemDmgTags ?: null, $item['id']]
                );

                // Calculate incremental deltas for AVAILABLE stock
                $deltaAccepted = $acceptedQty - $prevReceivedQty;
                if ($deltaAccepted > 0) {
                    $totalIncrementalAccepted += $deltaAccepted;
                    StockService::increase(
                        variantId: $vid,
                        locationType: 'WAREHOUSE',
                        locationId: $warehouseId,
                        stockState: 'AVAILABLE',
                        quantity: $deltaAccepted,
                        transactionType: 'PRODUCTION_GRN',
                        referenceType: 'production_batch',
                        referenceId: $batchId,
                        userId: $userId,
                        notes: "Production Inward GRN from Batch #" . $batch['batch_number'] . ($notes ? " ($notes)" : "") . ($prevReceivedQty > 0 ? " [Incremental Inward: +{$deltaAccepted} pcs]" : "")
                    );
                } elseif ($deltaAccepted < 0) {
                    $decrementQty = abs($deltaAccepted);
                    StockService::decrease(
                        variantId: $vid,
                        locationType: 'WAREHOUSE',
                        locationId: $warehouseId,
                        stockState: 'AVAILABLE',
                        quantity: $decrementQty,
                        transactionType: 'PRODUCTION_GRN',
                        referenceType: 'production_batch',
                        referenceId: $batchId,
                        userId: $userId,
                        notes: "Production Inward GRN Correction for Batch #" . $batch['batch_number'] . " [Adjustment: -{$decrementQty} pcs]"
                    );
                }

                // Calculate incremental deltas for DAMAGED quarantine stock
                $deltaDamaged = $damagedQty - $prevDamagedQty;
                if ($deltaDamaged > 0) {
                    $totalIncrementalDamaged += $deltaDamaged;
                    $costPerUnit = (float)($item['production_cost'] ?: ($item['selling_price'] ?? 0));
                    $totalLoss = $deltaDamaged * $costPerUnit;

                    $actualDamageTag = !empty($itemDmgTags) ? $itemDmgTags : '';
                    if (empty($actualDamageTag)) {
                        $actualDamageTag = 'DMG-' . date('ym') . '-' . str_pad((string)$batchId, 4, '0', STR_PAD_LEFT);
                    }

                    $damageNumber = 'DMG-' . date('Ymd') . '-' . strtoupper(bin2hex(random_bytes(2)));
                    $damageId = WarehouseDamage::create([
                        'damage_number'      => $damageNumber,
                        'warehouse_id'       => $warehouseId,
                        'variant_id'         => $vid,
                        'tag_code'           => $actualDamageTag,
                        'source_type'        => 'PRODUCTION_INWARD',
                        'reference_id'       => $batchId,
                        'quantity'           => $deltaDamaged,
                        'cost_per_unit'      => $costPerUnit,
                        'total_loss'         => $totalLoss,
                        'damage_reason'      => 'Transit / Inward Verification Damage',
                        'defect_notes'       => "Quarantined during Inward GRN for Batch #{$batch['batch_number']} (Program: {$batch['program_number']})",
                        'status'             => 'LOGGED',
                        'reported_by'        => $userId,
                        'production_unit_id' => !empty($batch['production_unit_id']) ? (int)$batch['production_unit_id'] : null,
                    ]);

                    StockService::increase(
                        variantId: $vid,
                        locationType: 'WAREHOUSE',
                        locationId: $warehouseId,
                        stockState: 'DAMAGED',
                        quantity: $deltaDamaged,
                        transactionType: 'PRODUCTION_GRN',
                        referenceType: 'warehouse_damage',
                        referenceId: $damageId,
                        userId: $userId,
                        notes: "Damaged in transit from Batch #" . $batch['batch_number'] . " (Case #{$damageNumber})"
                    );
                }
            }

            $totalMissing = max(0, (int)($totalExpected - ($totalAccepted + $totalDamaged)));

            // Update batch quantities and status
            Database::execute(
                "UPDATE production_batches 
                 SET received_qty = ?, damaged_qty = ?, missing_qty = ?, status = 'RECEIVED' 
                 WHERE id = ?",
                [(int)$totalAccepted, (int)$totalDamaged, (int)$totalMissing, $batchId]
            );

            // Audit log
            $auditNotes = $notes ?? 'Production Inward GRN Verified';
            if ($totalIncrementalAccepted > 0 || $totalIncrementalDamaged > 0) {
                $auditNotes .= " (Incremental Inward: +{$totalIncrementalAccepted} accepted, +{$totalIncrementalDamaged} damaged)";
            }
            AuditService::log('warehouse', 'inward_received', 'production_batches', $batchId, null, [
                'batch_number'      => $batch['batch_number'],
                'destination_wh_id' => $warehouseId,
                'total_expected'    => $totalExpected,
                'total_accepted'    => $totalAccepted,
                'total_damaged'     => $totalDamaged,
                'total_missing'     => $totalMissing,
                'discrepancies'     => $discrepancyReport,
                'concern_id'        => $cid,
            ], $userId);

            Database::commit();
            return true;
        } catch (Exception $e) {
            Database::rollback();
            throw $e;
        }
    }
}
