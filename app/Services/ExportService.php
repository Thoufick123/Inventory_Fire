<?php

namespace App\Services;

use Database;

/**
 * ExportService
 * 
 * High-performance data extraction, transformation, and streaming service
 * for enterprise CSV spreadsheets and printable summary reports.
 * Employs SARGable range scans, eliminated correlated subqueries, and
 * pre-indexed joins for fast database throughput.
 */
class ExportService
{
    /**
     * Fetch complete dataset for the given date range and selected modules,
     * including executive financial P&L and operational stock reconciliation metrics.
     */
    public static function getReportData(
        string $startDate,
        string $endDate,
        array $selectedModules,
        ?int $productionUnitId = null,
        array $warehouseIds = [],
        array $concernIds = []
    ): array
    {
        $data = [];
        $warehouseIds = array_values(array_unique(array_filter(array_map('intval', $warehouseIds), static function (int $id): bool {
            return $id > 0;
        })));
        $concernIds = array_values(array_unique(array_filter(array_map('intval', $concernIds), static function (int $id): bool {
            return $id > 0;
        })));
        $hasConcerns = !empty($concernIds);
        $concernPlaceholders = $hasConcerns ? implode(',', array_fill(0, count($concernIds), '?')) : '';
        $cWhere = static function (string $col) use ($hasConcerns, $concernPlaceholders): string {
            return $hasConcerns ? " AND {$col} IN ({$concernPlaceholders})" : '';
        };
        $cParams = static function (array $base) use ($hasConcerns, $concernIds): array {
            return $hasConcerns ? array_merge($base, $concernIds) : $base;
        };

        // Prepare optimized SARGable timestamp boundaries for MariaDB/MySQL index utilization
        $startDateTime = $startDate . ' 00:00:00';
        $endDateTime   = $endDate . ' 23:59:59';
        $startDateOnly = $startDate;
        $endDateOnly   = $endDate;

        // Cache default active production unit name once to eliminate correlated per-row subqueries
        $defaultUnit = Database::fetchOne("SELECT name FROM production_units WHERE is_active = 1 ORDER BY id ASC LIMIT 1");
        $defaultUnitName = $defaultUnit['name'] ?? 'Production Facility';

        // 0. Supplier Purchases (POs & Invoices)
        if (in_array('purchases', $selectedModules, true)) {
            $purParams = array_merge(
                [$startDateOnly, $endDateOnly],
                $hasConcerns ? $concernIds : [],
                [$startDateOnly, $endDateOnly],
                $hasConcerns ? $concernIds : []
            );
            $data['purchases'] = Database::fetchAll(
                "SELECT pp.id, pp.purchase_number, pp.purchase_date, pp.invoice_number,
                        s.name AS supplier_name, s.company AS supplier_company,
                        c.code AS concern_code, c.name AS concern_name,
                        pp.total_amount, pp.notes, pp.created_at,
                        COALESCE(items_stat.total_items, 0) AS total_items,
                        COALESCE(items_stat.total_rolls, 0) AS total_rolls,
                        COALESCE(items_stat.total_weight, 0) AS total_weight
                 FROM production_purchases pp
                 LEFT JOIN sellers s ON s.id = pp.seller_id
                 LEFT JOIN concerns c ON c.id = pp.concern_id
                 LEFT JOIN (
                     SELECT ppi.purchase_id,
                            COUNT(*) AS total_items,
                            SUM(CASE WHEN ppi.item_type = 'FABRIC_ROLL' THEN 1 ELSE 0 END) AS total_rolls,
                            SUM(COALESCE(ppi.weight_kg, 0)) AS total_weight
                     FROM production_purchase_items ppi
                     JOIN production_purchases pp_sub ON pp_sub.id = ppi.purchase_id
                     WHERE pp_sub.purchase_date >= ? AND pp_sub.purchase_date <= ?
                     " . $cWhere('pp_sub.concern_id') . "
                     GROUP BY ppi.purchase_id
                 ) items_stat ON items_stat.purchase_id = pp.id
                 WHERE pp.purchase_date >= ? AND pp.purchase_date <= ?
                 " . $cWhere('pp.concern_id') . "
                 ORDER BY pp.id DESC",
                $purParams
            );
        }

        // 1. Rolls & Inwards (from seller_rolls)
        if (in_array('rolls', $selectedModules, true)) {
            $rollConcernClause = $hasConcerns 
                ? " AND (sr.concern_id IN ($concernPlaceholders) OR sr.id IN (SELECT ppi_c.seller_roll_id FROM production_purchase_items ppi_c JOIN production_purchases pp_c ON pp_c.id = ppi_c.purchase_id WHERE pp_c.concern_id IN ($concernPlaceholders)))" 
                : "";
            $rollsParams = array_merge(
                [$startDateTime, $endDateTime],
                $hasConcerns ? $concernIds : [],
                $hasConcerns ? $concernIds : []
            );
            $data['rolls'] = Database::fetchAll(
                "SELECT sr.roll_number, sr.created_at, s.name AS supplier_name, s.company AS supplier_company,
                        c.code AS concern_code, c.name AS concern_name,
                        sr.material_type AS fabric_type, sr.color, COALESCE(sr.weight, 0) AS weight,
                        COALESCE(sr.price, 0) AS total_cost, 'IN_STOCK' AS status,
                        sr.start_code, sr.end_code, sr.qr_code,
                        '-' AS length,
                        0.00 AS cost_per_meter,
                        ROUND(COALESCE(sr.price, 0) / NULLIF(sr.weight, 0), 2) AS cost_per_kg
                 FROM seller_rolls sr
                 LEFT JOIN sellers s ON s.id = sr.seller_id
                 LEFT JOIN concerns c ON c.id = sr.concern_id
                 WHERE sr.created_at >= ? AND sr.created_at <= ?
                 $rollConcernClause
                 ORDER BY sr.id DESC",
                $rollsParams
            );
        }

        // 2. Production Programs
        if (in_array('programs', $selectedModules, true)) {
            $data['programs'] = Database::fetchAll(
                "SELECT pp.program_number, pp.name AS program_name, p.code AS style_code,
                        c.code AS concern_code, c.name AS concern_name,
                        pp.planned_quantity AS target_pieces, pp.completed_quantity, pp.status,
                        pp.created_at, pp.target_date
                 FROM production_programs pp
                 LEFT JOIN products p ON p.id = pp.product_id
                 LEFT JOIN concerns c ON c.id = pp.concern_id
                 WHERE pp.created_at >= ? AND pp.created_at <= ?
                 " . $cWhere('pp.concern_id') . "
                 ORDER BY pp.id DESC",
                $cParams([$startDateTime, $endDateTime])
            );
        }

        // 3. Cutting Batches
        if (in_array('cuttings', $selectedModules, true)) {
            $data['cuttings'] = Database::fetchAll(
                "SELECT cb.batch_number, pp.program_number,
                        c.code AS concern_code, c.name AS concern_name,
                        cb.roll_weight, cb.planned_qty, cb.cut_qty AS actual_pieces,
                        cb.wastage_qty, cb.rejected_qty, cb.status, cb.created_at
                 FROM cutting_batches cb
                 LEFT JOIN production_programs pp ON pp.id = cb.program_id
                 LEFT JOIN concerns c ON c.id = cb.concern_id
                 WHERE cb.created_at >= ? AND cb.created_at <= ?
                 " . $cWhere('cb.concern_id') . "
                 ORDER BY cb.id DESC",
                $cParams([$startDateTime, $endDateTime])
            );
        }

        // 4. Stitching Batches
        if (in_array('stitching', $selectedModules, true)) {
            $data['stitching'] = Database::fetchAll(
                "SELECT sb.batch_number, pp.program_number, sb.received_qty AS target_quantity,
                        c.code AS concern_code, c.name AS concern_name,
                        sb.stitched_qty AS completed_quantity, sb.rework_qty AS rework_quantity,
                        sb.rejected_qty, sb.status, sb.created_at
                 FROM stitching_batches sb
                 LEFT JOIN production_programs pp ON pp.id = sb.program_id
                 LEFT JOIN concerns c ON c.id = sb.concern_id
                 WHERE sb.created_at >= ? AND sb.created_at <= ?
                 " . $cWhere('sb.concern_id') . "
                 ORDER BY sb.id DESC",
                $cParams([$startDateTime, $endDateTime])
            );
        }

        // 5. Quality Control (QC)
        if (in_array('qc', $selectedModules, true)) {
            $data['qc'] = Database::fetchAll(
                "SELECT qb.batch_number, qb.qc_number, pp.program_number, qb.total_qty AS inspected_quantity,
                        c.code AS concern_code, c.name AS concern_name,
                        qb.passed_qty AS passed_quantity, qb.rework_qty AS rework_quantity, qb.rejected_qty AS rejected_quantity,
                        CASE WHEN qb.total_qty > 0 
                             THEN ROUND((qb.passed_qty / qb.total_qty) * 100, 2) 
                             ELSE 0 END AS yield_percentage,
                        qb.status, qb.created_at
                 FROM qc_batches qb
                 LEFT JOIN production_programs pp ON pp.id = qb.program_id
                 LEFT JOIN concerns c ON c.id = qb.concern_id
                 WHERE qb.created_at >= ? AND qb.created_at <= ?
                 " . $cWhere('qb.concern_id') . "
                 ORDER BY qb.id DESC",
                $cParams([$startDateTime, $endDateTime])
            );
        }

        // 6. Finished Goods Batches (Master Cartons)
        if (in_array('batches', $selectedModules, true)) {
            $data['batches'] = Database::fetchAll(
                "SELECT pb.id, pb.batch_number, pp.program_number, pp.name AS program_name,
                        c.code AS concern_code, c.name AS concern_name,
                        qb.batch_number AS qc_batch_number,
                        COALESCE(pb.box_count, 0) AS box_count,
                        COALESCE(CASE WHEN pb.total_qty > 0 THEN pb.total_qty ELSE pb.quantity END, 0) AS total_pieces,
                        COALESCE(pb.cost_per_piece, 0.00) AS cost_per_piece,
                        COALESCE(pb.total_cost, 0.00) AS total_cost,
                        COALESCE(w.name, 'Central Distribution Warehouse') AS destination_warehouse,
                        pb.status,
                        COALESCE(pb.packed_date, pb.created_at) AS packed_date,
                        pb.created_at
                 FROM production_batches pb
                 LEFT JOIN production_programs pp ON pp.id = pb.program_id
                 LEFT JOIN qc_batches qb ON qb.id = pb.qc_batch_id
                 LEFT JOIN warehouses w ON w.id = pb.destination_warehouse_id
                 LEFT JOIN concerns c ON c.id = pb.concern_id
                 WHERE ((pb.packed_date >= ? AND pb.packed_date <= ?)
                    OR (pb.packed_date IS NULL AND pb.created_at >= ? AND pb.created_at <= ?))
                 " . $cWhere('pb.concern_id') . "
                 ORDER BY pb.id DESC",
                $cParams([$startDateOnly, $endDateOnly, $startDateTime, $endDateTime])
            );
        }

        // 7. Production Outward & Dispatches
        if (in_array('outward', $selectedModules, true)) {
            $outwardWarehouseFilter = '';
            $outwardParams = [$startDateOnly, $endDateOnly, $startDateTime, $endDateTime];
            if ($warehouseIds) {
                $outwardWarehouseFilter = ' AND po.warehouse_id IN (' . implode(',', array_fill(0, count($warehouseIds), '?')) . ')';
                $outwardParams = array_merge($outwardParams, $warehouseIds);
            }
            if ($hasConcerns) {
                $outwardWarehouseFilter .= $cWhere('po.concern_id');
                $outwardParams = array_merge($outwardParams, $concernIds);
            }
            $data['outward'] = Database::fetchAll(
                "SELECT po.id, po.outward_number, pb.batch_number,
                        c.code AS concern_code, c.name AS concern_name,
                        COALESCE(w.name, 'Central Distribution Warehouse') AS warehouse_name,
                        COALESCE(po.total_boxes, 0) AS total_boxes,
                        COALESCE(CASE WHEN po.total_quantity > 0 THEN po.total_quantity ELSE po.quantity END, 0) AS total_pieces,
                        COALESCE(po.transporter_name, '-') AS transporter_name,
                        COALESCE(po.vehicle_number, '-') AS vehicle_number,
                        COALESCE(po.lr_number, '-') AS lr_number,
                        COALESCE(po.driver_name, '-') AS driver_name,
                        COALESCE(po.driver_contact, '-') AS driver_contact,
                        po.status,
                        COALESCE(po.dispatch_date, po.created_at) AS dispatch_date,
                        po.created_at
                 FROM production_outwards po
                 LEFT JOIN production_batches pb ON pb.id = po.production_batch_id
                 LEFT JOIN warehouses w ON w.id = po.warehouse_id
                 LEFT JOIN concerns c ON c.id = po.concern_id
                 WHERE ((po.dispatch_date >= ? AND po.dispatch_date <= ?)
                    OR (po.dispatch_date IS NULL AND po.created_at >= ? AND po.created_at <= ?))
                 $outwardWarehouseFilter
                 ORDER BY po.id DESC",
                $outwardParams
            );
        }

        // Detailed warehouse stock ledger. StockService records increases as
        // positive quantities and decreases as negative quantities.
        if (in_array('stock_ledger', $selectedModules, true)) {
            $warehouseFilter = '';
            $stockParams = [$startDateTime, $endDateTime];
            if ($warehouseIds) {
                $warehouseFilter = ' AND st.location_id IN (' . implode(',', array_fill(0, count($warehouseIds), '?')) . ')';
                $stockParams = array_merge($stockParams, $warehouseIds);
            }
            if ($hasConcerns) {
                $warehouseFilter .= $cWhere('st.concern_id');
                $stockParams = array_merge($stockParams, $concernIds);
            }

            $data['stock_ledger'] = Database::fetchAll(
                "SELECT st.id AS transaction_id, st.created_at AS transaction_date,
                        st.location_id AS warehouse_id, w.name AS warehouse_name, w.code AS warehouse_code,
                        c.code AS concern_code, c.name AS concern_name,
                        st.stock_state, st.transaction_type, st.quantity AS movement_quantity,
                        CASE WHEN st.quantity > 0 THEN st.quantity ELSE 0 END AS stock_in,
                        CASE WHEN st.quantity < 0 THEN -st.quantity ELSE 0 END AS stock_out,
                        st.balance_after, st.reference_type, st.reference_id, st.notes,
                        st.source_type, st.source_id, st.dest_type, st.dest_id,
                        pv.id AS variant_id, pv.variant_code, pv.sku, pv.qr_code, pv.qr_type,
                        pv.series_start_code, pv.series_end_code, pv.is_complementary,
                        pv.hsn_code, pv.gst_rate, pv.mrp, pv.purchase_price, pv.production_cost,
                        pv.selling_price, pv.weight,
                        p.name AS product_name, p.code AS product_code, p.material, p.description,
                        cat.name AS category_name, sub.name AS subcategory_name,
                        subsub.name AS sub_subcategory_name,
                        sz.name AS size_name, col.name AS color_name,
                        d.name AS design_name, d.code AS design_code,
                        u.full_name AS performed_by
                 FROM stock_transactions st
                 INNER JOIN product_variants pv ON pv.id = st.variant_id
                 LEFT JOIN products p ON p.id = pv.product_id
                 LEFT JOIN categories cat ON cat.id = p.category_id
                 LEFT JOIN subcategories sub ON sub.id = p.subcategory_id
                 LEFT JOIN sub_subcategories subsub ON subsub.id = p.sub_subcategory_id
                 LEFT JOIN sizes sz ON sz.id = pv.size_id
                 LEFT JOIN colors col ON col.id = pv.color_id
                 LEFT JOIN designs d ON d.id = pv.design_id
                 LEFT JOIN warehouses w ON w.id = st.location_id
                 LEFT JOIN concerns c ON c.id = st.concern_id
                 LEFT JOIN users u ON u.id = st.user_id
                 WHERE st.location_type = 'WAREHOUSE'
                   AND st.created_at >= ? AND st.created_at <= ?
                   $warehouseFilter
                 ORDER BY st.created_at ASC, st.id ASC",
                $stockParams
            );
        }

        // 8. Production Billing
        if (in_array('billing', $selectedModules, true)) {
            $billParams = array_merge(
                [$startDateOnly, $endDateOnly],
                $hasConcerns ? $concernIds : [],
                [$startDateOnly, $endDateOnly],
                $hasConcerns ? $concernIds : []
            );
            $data['billing'] = Database::fetchAll(
                "SELECT pb.bill_number, pb.bill_date, pb.buyer_type,
                        c.code AS concern_code, c.name AS concern_name,
                        COALESCE(pb.customer_name, pb.company_name, 'Direct Client') AS client_name,
                        pb.customer_phone, pb.subtotal, pb.tax_amount, pb.total_amount,
                        pb.payment_method, pb.payment_status,
                        COALESCE(tag_stats.total_pieces, 0) AS total_pieces
                 FROM production_bills pb
                 LEFT JOIN concerns c ON c.id = pb.concern_id
                 LEFT JOIN (
                     SELECT pbit.bill_id, COUNT(*) AS total_pieces
                     FROM production_bill_item_tags pbit
                     JOIN production_bills pb_sub ON pb_sub.id = pbit.bill_id
                     WHERE pb_sub.bill_date >= ? AND pb_sub.bill_date <= ?
                     " . $cWhere('pb_sub.concern_id') . "
                     GROUP BY pbit.bill_id
                 ) tag_stats ON tag_stats.bill_id = pb.id
                 WHERE pb.bill_date >= ? AND pb.bill_date <= ?
                 " . $cWhere('pb.concern_id') . "
                 ORDER BY pb.id DESC",
                $billParams
            );
        }

        // 9. Customer Returns & Credit Notes
        if (in_array('returns', $selectedModules, true)) {
            $data['returns'] = Database::fetchAll(
                "SELECT pr.return_number, pr.bill_number, pr.customer_name,
                        c.code AS concern_code, c.name AS concern_name,
                        pr.return_type, pr.disposition, pr.total_returned_qty,
                        pr.subtotal, pr.gst_reversal_amount, pr.net_amount,
                        pr.refund_mode, pr.status, pr.created_at
                 FROM production_returns pr
                 LEFT JOIN concerns c ON c.id = pr.concern_id
                 WHERE pr.created_at >= ? AND pr.created_at <= ?
                 " . $cWhere('pr.concern_id') . "
                 ORDER BY pr.id DESC",
                $cParams([$startDateTime, $endDateTime])
            );
        }

        // 10. Warehouse Stock Requests
        if (in_array('stock_requests', $selectedModules, true)) {
            $data['stock_requests'] = Database::fetchAll(
                "SELECT psr.request_number, psr.warehouse_name, psr.requested_by_name,
                        c.code AS concern_code, c.name AS concern_name,
                        psr.priority, psr.required_by_date, psr.total_requested_pieces,
                        psr.fulfilled_pieces, psr.status, psr.created_at
                 FROM production_stock_requests psr
                 LEFT JOIN concerns c ON c.id = psr.concern_id
                 WHERE psr.created_at >= ? AND psr.created_at <= ?
                 " . $cWhere('psr.concern_id') . "
                 ORDER BY psr.id DESC",
                $cParams([$startDateTime, $endDateTime])
            );
        }

        // 11. Wastage Selling (Cutting + Stitching Scrap)
        if (in_array('wastage', $selectedModules, true)) {
            $cuttingWastage = Database::fetchAll(
                "SELECT ws.sale_number AS invoice_number, 'Cutting Scrap' AS wastage_type, ws.buyer_name,
                        c.code AS concern_code, c.name AS concern_name,
                        ws.weight AS weight_kg, ws.rate_per_kg, ws.total_amount, ws.sale_date AS created_at
                 FROM wastage_sales ws
                 LEFT JOIN concerns c ON c.id = ws.concern_id
                 WHERE ws.sale_date >= ? AND ws.sale_date <= ?
                 " . $cWhere('ws.concern_id') . "
                 ORDER BY ws.id DESC",
                $cParams([$startDateOnly, $endDateOnly])
            );

            $stitchingWastage = Database::fetchAll(
                "SELECT sws.sale_number AS invoice_number, 'Stitching Scrap' AS wastage_type, sws.buyer_name,
                        c.code AS concern_code, c.name AS concern_name,
                        sws.quantity AS weight_kg, sws.rate_per_unit AS rate_per_kg, sws.total_amount, sws.sale_date AS created_at
                 FROM stitching_wastage_sales sws
                 LEFT JOIN concerns c ON c.id = sws.concern_id
                 WHERE sws.sale_date >= ? AND sws.sale_date <= ?
                 " . $cWhere('sws.concern_id') . "
                 ORDER BY sws.id DESC",
                $cParams([$startDateOnly, $endDateOnly])
            );

            $data['wastage'] = array_merge($cuttingWastage, $stitchingWastage);
        }

        // 12. Production Expenses
        if (in_array('expenses', $selectedModules, true)) {
            $data['expenses'] = Database::fetchAll(
                "SELECT pe.item_name AS title, pe.category,
                        c.code AS concern_code, c.name AS concern_name,
                        COALESCE(NULLIF(pe.production_unit, ''), ?, 'Production Facility') AS production_unit,
                        pe.total_cost, pe.paid_amount, (pe.total_cost - pe.paid_amount) AS pending_amount,
                        pe.payment_status, pe.expense_date
                 FROM production_expenses pe
                 LEFT JOIN concerns c ON c.id = pe.concern_id
                 WHERE pe.expense_date >= ? AND pe.expense_date <= ?
                 " . $cWhere('pe.concern_id') . "
                 ORDER BY pe.id DESC",
                array_merge([$defaultUnitName, $startDateOnly, $endDateOnly], $hasConcerns ? $concernIds : [])
            );
        }

        // 13. Raw Materials Supply & Procurement
        if (in_array('raw_materials', $selectedModules, true)) {
            $data['raw_materials'] = Database::fetchAll(
                "SELECT ppi.id, pp.purchase_number, pp.purchase_date, pp.invoice_number,
                        s.name AS supplier_name, s.company AS supplier_company,
                        c.code AS concern_code, c.name AS concern_name,
                        ppi.item_name, ppi.color, ppi.size, ppi.unit, ppi.quantity, ppi.unit_price,
                        ppi.total_price, ppi.hsn_code, ppi.gst_type, ppi.tax_treatment,
                        ppi.sgst_amount, ppi.cgst_amount, ppi.igst_amount, ppi.start_code, ppi.created_at
                 FROM production_purchase_items ppi
                 JOIN production_purchases pp ON pp.id = ppi.purchase_id
                 LEFT JOIN sellers s ON s.id = pp.seller_id
                 LEFT JOIN concerns c ON c.id = pp.concern_id
                 WHERE ppi.item_type = 'RAW_MATERIAL'
                   AND pp.purchase_date >= ? AND pp.purchase_date <= ?
                   " . $cWhere('pp.concern_id') . "
                 ORDER BY ppi.id DESC",
                $cParams([$startDateOnly, $endDateOnly])
            );
        }

        // 14. Third-Party Products & Catalog Mapping
        if (in_array('third_party_products', $selectedModules, true)) {
            $data['third_party_products'] = Database::fetchAll(
                "SELECT ppi.id, pp.purchase_number, pp.purchase_date, pp.invoice_number,
                        s.name AS supplier_name, s.company AS supplier_company,
                        c.code AS concern_code, c.name AS concern_name,
                        ppi.item_name, ppi.product_code, ppi.vendor_sku, ppi.color, ppi.size,
                        ppi.unit, ppi.quantity, ppi.unit_price, ppi.mrp, ppi.selling_price, ppi.total_price,
                        ppi.hsn_code, ppi.is_mapped, ppi.mapped_at,
                        tc.name AS category_name, tsc.name AS subcategory_name, tssc.name AS sub_subcategory_name
                 FROM production_purchase_items ppi
                 JOIN production_purchases pp ON pp.id = ppi.purchase_id
                 LEFT JOIN sellers s ON s.id = pp.seller_id
                 LEFT JOIN concerns c ON c.id = pp.concern_id
                 LEFT JOIN third_party_categories tc ON tc.id = ppi.mapped_third_party_category_id
                 LEFT JOIN third_party_subcategories tsc ON tsc.id = ppi.mapped_third_party_subcategory_id
                 LEFT JOIN third_party_sub_subcategories tssc ON tssc.id = ppi.mapped_third_party_sub_subcategory_id
                 WHERE ppi.item_type = 'THIRD_PARTY'
                   AND pp.purchase_date >= ? AND pp.purchase_date <= ?
                   " . $cWhere('pp.concern_id') . "
                 ORDER BY ppi.id DESC",
                $cParams([$startDateOnly, $endDateOnly])
            );
        }

        // 15. Suppliers & Mills Directory
        if (in_array('suppliers', $selectedModules, true)) {
            $suppOrdersFilter = $hasConcerns ? " AND pp.concern_id IN ($concernPlaceholders)" : "";
            $suppParams = array_merge(
                $hasConcerns ? $concernIds : [],
                $hasConcerns ? $concernIds : [],
                $hasConcerns ? $concernIds : []
            );
            $data['suppliers'] = Database::fetchAll(
                "SELECT s.id, s.name, s.company, s.seller_type, s.phone, s.email, s.gstin, s.gst_number,
                        c.code AS concern_code, c.name AS concern_name,
                        s.address, s.city, s.state, s.payment_terms, s.material_type, s.created_at,
                        (SELECT COUNT(*) FROM production_purchases pp WHERE pp.seller_id = s.id $suppOrdersFilter) AS total_orders,
                        (SELECT COALESCE(SUM(total_amount), 0) FROM production_purchases pp WHERE pp.seller_id = s.id $suppOrdersFilter) AS total_order_value
                 FROM sellers s
                 LEFT JOIN concerns c ON c.id = s.concern_id
                 WHERE s.production_deleted_at IS NULL
                 " . $cWhere('s.concern_id') . "
                 ORDER BY s.id DESC",
                $suppParams
            );
        }

        // 16. Goods Inward Notes (GRN)
        if (in_array('goods_inward', $selectedModules, true)) {
            $data['goods_inward'] = Database::fetchAll(
                "SELECT pi.id, pi.grn_number, pi.invoice_number, pi.invoice_date, pi.received_date,
                        c.code AS concern_code, c.name AS concern_name,
                        pi.status, pi.total_received_qty, pi.total_accepted_qty, pi.total_rejected_qty,
                        pi.gate_pass_number, pi.vehicle_number, pi.driver_name, pi.notes,
                        w.name AS warehouse_name, u.full_name AS received_by_name
                 FROM purchase_inwards pi
                 LEFT JOIN warehouses w ON w.id = pi.warehouse_id
                 LEFT JOIN concerns c ON c.id = pi.concern_id
                 LEFT JOIN users u ON u.id = pi.received_by
                 WHERE pi.received_date >= ? AND pi.received_date <= ?
                 " . $cWhere('pi.concern_id') . "
                 ORDER BY pi.id DESC",
                $cParams([$startDateOnly, $endDateOnly])
            );
        }

        // 17. Damage & Rejection Management
        if (in_array('damages', $selectedModules, true)) {
            $data['damages'] = Database::fetchAll(
                "SELECT rd.id, rd.damage_number, rd.item_type, rd.damaged_quantity, rd.damaged_weight,
                        c.code AS concern_code, c.name AS concern_name,
                        rd.loss_amount, rd.damage_reason, rd.defect_notes, rd.status, rd.resolution_type,
                        rd.refund_amount, rd.debit_note_number, rd.created_at,
                        s.company AS supplier_company, s.name AS supplier_name,
                        sr.roll_number, p.name AS product_name
                 FROM roll_damages rd
                 LEFT JOIN sellers s ON s.id = rd.seller_id
                 LEFT JOIN seller_rolls sr ON sr.id = rd.seller_roll_id
                 LEFT JOIN products p ON p.id = rd.product_id
                 LEFT JOIN concerns c ON c.id = rd.concern_id
                 WHERE rd.created_at >= ? AND rd.created_at <= ?
                 " . $cWhere('rd.concern_id') . "
                 ORDER BY rd.id DESC",
                $cParams([$startDateTime, $endDateTime])
            );
        }

        // 18. Product Catalog & Style Master
        if (in_array('catalog', $selectedModules, true)) {
            $data['catalog'] = Database::fetchAll(
                "SELECT p.id, p.name AS product_name, p.code AS product_code, p.source_type,
                        c.name AS category_name, sc.name AS subcategory_name, ssc.name AS sub_subcategory_name,
                        p.hsn_code, p.material, p.is_active, p.created_at,
                        (SELECT COUNT(*) FROM product_variants pv WHERE pv.product_id = p.id) AS variant_count
                 FROM products p
                 LEFT JOIN categories c ON c.id = p.category_id
                 LEFT JOIN subcategories sc ON sc.id = p.subcategory_id
                 LEFT JOIN sub_subcategories ssc ON ssc.id = p.sub_subcategory_id
                 WHERE (p.created_at >= ? AND p.created_at <= ?) OR p.is_active = 1
                 ORDER BY p.id DESC",
                [$startDateTime, $endDateTime]
            );
        }

        // 18b. Product Variants & SKUs
        if (in_array('variants', $selectedModules, true)) {
            $data['variants'] = Database::fetchAll(
                "SELECT pv.id, pv.sku, pv.variant_code, pv.hsn_code, pv.gst_rate, pv.qr_code,
                        pv.mrp, pv.selling_price, pv.purchase_price, pv.production_cost,
                        pv.weight, pv.created_at,
                        COALESCE(stock.available_quantity, 0) AS available_quantity,
                        p.name AS product_name, p.code AS parent_sku,
                        c.name AS category_name, sc.name AS subcategory_name, ssc.name AS sub_subcategory_name,
                        col.name AS color_name, d.name AS design_name, s.name AS size_name
                 FROM product_variants pv
                 JOIN products p ON p.id = pv.product_id
                 LEFT JOIN categories c ON c.id = p.category_id
                 LEFT JOIN subcategories sc ON sc.id = p.subcategory_id
                 LEFT JOIN sub_subcategories ssc ON ssc.id = p.sub_subcategory_id
                 LEFT JOIN colors col ON col.id = pv.color_id
                 LEFT JOIN designs d ON d.id = pv.design_id
                 LEFT JOIN sizes s ON s.id = pv.size_id
                 LEFT JOIN (
                     SELECT variant_id, SUM(quantity) AS available_quantity
                     FROM stock_balances
                     WHERE stock_state = 'AVAILABLE'
                     GROUP BY variant_id
                 ) stock ON stock.variant_id = pv.id
                 WHERE (pv.created_at >= ? AND pv.created_at <= ?) OR pv.is_active = 1
                 ORDER BY pv.id DESC",
                [$startDateTime, $endDateTime]
            );
        }

        // 19. QR Code Generations
        if (in_array('qr_generations', $selectedModules, true)) {
            $data['qr_generations'] = Database::fetchAll(
                "SELECT qg.id, qg.batch_code, qg.generation_type, qg.brand_name,
                        c.code AS concern_code, c.name AS concern_name,
                        qg.category_name, qg.subcategory_name, qg.color_name, qg.size_name,
                        qg.quantity, qg.mrp, qg.selling_price, qg.qr_prefix, qg.stock_added,
                        qg.created_at, u.full_name AS created_by_name
                 FROM qr_generations qg
                 LEFT JOIN concerns c ON c.id = qg.concern_id
                 LEFT JOIN users u ON u.id = qg.created_by
                 WHERE qg.created_at >= ? AND qg.created_at <= ?
                 " . $cWhere('qg.concern_id') . "
                 ORDER BY qg.id DESC",
                $cParams([$startDateTime, $endDateTime])
            );
        }

        // 20. Finished Goods Inventory (On-Hand Balances)
        if (in_array('inventory', $selectedModules, true)) {
            $data['inventory'] = Database::fetchAll(
                "SELECT sb.id, sb.location_type, sb.stock_state, sb.quantity, sb.last_updated,
                        c.code AS concern_code, c.name AS concern_name,
                        pv.sku, pv.variant_code, pv.mrp, pv.selling_price, pv.purchase_price,
                        p.name AS product_name, p.code AS product_code,
                        sz.name AS size_name, col.name AS color_name,
                        CASE WHEN sb.location_type = 'WAREHOUSE' THEN w.name ELSE pu.name END AS location_name
                 FROM stock_balances sb
                 JOIN product_variants pv ON pv.id = sb.variant_id
                 JOIN products p ON p.id = pv.product_id
                 LEFT JOIN sizes sz ON sz.id = pv.size_id
                 LEFT JOIN colors col ON col.id = pv.color_id
                 LEFT JOIN warehouses w ON (w.id = sb.location_id AND sb.location_type = 'WAREHOUSE')
                 LEFT JOIN production_units pu ON (pu.id = sb.location_id AND sb.location_type = 'PRODUCTION_UNIT')
                 LEFT JOIN concerns c ON c.id = sb.concern_id
                 WHERE sb.quantity > 0
                 " . $cWhere('sb.concern_id') . "
                 ORDER BY p.name ASC, pv.sku ASC",
                $hasConcerns ? $concernIds : []
            );
        }

        // 21. Warehouse Rework & Repairs
        if (in_array('warehouse_rework', $selectedModules, true)) {
            $data['warehouse_rework'] = Database::fetchAll(
                "SELECT pwr.id, pwr.rework_number, pwr.quantity, pwr.repaired_qty, pwr.scrapped_qty,
                        c.code AS concern_code, c.name AS concern_name,
                        pwr.defect_type, pwr.damage_reason, pwr.defect_notes, pwr.status,
                        pwr.received_date, pwr.challan_number, pwr.created_at,
                        w.name AS warehouse_name, p.name AS product_name, pv.sku
                 FROM production_warehouse_reworks pwr
                 LEFT JOIN warehouses w ON w.id = pwr.warehouse_id
                 LEFT JOIN products p ON p.id = pwr.product_id
                 LEFT JOIN product_variants pv ON pv.id = pwr.variant_id
                 LEFT JOIN concerns c ON c.id = pwr.concern_id
                 WHERE pwr.created_at >= ? AND pwr.created_at <= ?
                 " . $cWhere('pwr.concern_id') . "
                 ORDER BY pwr.id DESC",
                $cParams([$startDateTime, $endDateTime])
            );
        }

        // 22. Fixed Assets Register
        if (in_array('assets', $selectedModules, true)) {
            $data['assets'] = Database::fetchAll(
                "SELECT a.id, a.asset_code, a.name, a.brand, a.model_number, a.serial_number,
                        c.code AS concern_code, c.name AS concern_name,
                        a.purchase_date, a.purchase_cost, a.current_book_value, a.salvage_value,
                        a.useful_life_years, a.vendor_name, a.invoice_number, a.status,
                        a.condition_state, a.location_type, ac.name AS category_name
                 FROM assets a
                 LEFT JOIN asset_categories ac ON ac.id = a.category_id
                 LEFT JOIN concerns c ON c.id = a.concern_id
                 WHERE ((a.purchase_date >= ? AND a.purchase_date <= ?) OR a.created_at >= ?)
                 " . $cWhere('a.concern_id') . "
                 ORDER BY a.id DESC",
                $cParams([$startDateOnly, $endDateOnly, $startDateTime])
            );
        }

        // 23. Asset Maintenance & Service Logs
        if (in_array('asset_maintenance', $selectedModules, true)) {
            $data['asset_maintenance'] = Database::fetchAll(
                "SELECT aml.id, aml.log_number, a.asset_code, a.name AS asset_name,
                        c.code AS concern_code, c.name AS concern_name,
                        aml.service_type, aml.issue_description, aml.action_taken,
                        aml.serviced_by_vendor, aml.technician_name, aml.service_cost,
                        aml.parts_replaced, aml.service_date, aml.next_due_date,
                        aml.downtime_hours, aml.status
                 FROM asset_maintenance_logs aml
                 JOIN assets a ON a.id = aml.asset_id
                 LEFT JOIN concerns c ON c.id = aml.concern_id
                 WHERE aml.service_date >= ? AND aml.service_date <= ?
                 " . $cWhere('aml.concern_id') . "
                 ORDER BY aml.id DESC",
                $cParams([$startDateOnly, $endDateOnly])
            );
        }

        // =========================================================================
        // EXECUTIVE FINANCIAL & OPERATIONAL STOCK RECONCILIATION SUMMARY
        // All summary stats are concern-scoped when concern filter is active.
        // =========================================================================
        // Billing Revenue & Sold Pieces (Optimized single-pass query)
        $billStat = Database::fetchOne(
            "SELECT COUNT(DISTINCT pb.id) AS bill_count,
                    COALESCE(SUM(pb.subtotal), 0) AS taxable_amt,
                    COALESCE(SUM(pb.tax_amount), 0) AS gst_amt,
                    COALESCE(SUM(pb.total_amount), 0) AS total_revenue
             FROM production_bills pb
             WHERE pb.bill_date >= ? AND pb.bill_date <= ?" . $cWhere('pb.concern_id'),
            $cParams([$startDateOnly, $endDateOnly])
        );
        $billPcs = Database::fetchOne(
            "SELECT COUNT(*) AS total_pcs
             FROM production_bill_item_tags pbit
             JOIN production_bills pb ON pb.id = pbit.bill_id
             WHERE pb.bill_date >= ? AND pb.bill_date <= ?" . $cWhere('pb.concern_id'),
            $cParams([$startDateOnly, $endDateOnly])
        );
        $billingPieces  = (int)($billPcs['total_pcs'] ?? 0);
        $billingTotal   = (float)($billStat['total_revenue'] ?? 0);
        $billingTaxable = (float)($billStat['taxable_amt'] ?? 0);
        $billingGst     = (float)($billStat['gst_amt'] ?? 0);
        $billingCount   = (int)($billStat['bill_count'] ?? 0);

        // Scrap / Wastage Revenue
        $cWastage = Database::fetchOne(
            "SELECT COALESCE(SUM(weight), 0) AS weight, COALESCE(SUM(total_amount), 0) AS rev
             FROM wastage_sales
             WHERE sale_date >= ? AND sale_date <= ?" . $cWhere('concern_id'),
            $cParams([$startDateOnly, $endDateOnly])
        );
        $sWastage = Database::fetchOne(
            "SELECT COALESCE(SUM(quantity), 0) AS weight, COALESCE(SUM(total_amount), 0) AS rev
             FROM stitching_wastage_sales
             WHERE sale_date >= ? AND sale_date <= ?" . $cWhere('concern_id'),
            $cParams([$startDateOnly, $endDateOnly])
        );
        $wastageCuttingRev   = (float)($cWastage['rev'] ?? 0);
        $wastageStitchingRev = (float)($sWastage['rev'] ?? 0);
        $wastageTotalRev     = $wastageCuttingRev + $wastageStitchingRev;

        // Total Gross Revenue / Inflows
        $grossRevenue = $billingTotal + $wastageTotalRev;

        // Expenditures / Outflows: Purchases
        $purStat = Database::fetchOne(
            "SELECT COUNT(*) AS count, COALESCE(SUM(total_amount), 0) AS total_cost
             FROM production_purchases
             WHERE purchase_date >= ? AND purchase_date <= ?" . $cWhere('concern_id'),
            $cParams([$startDateOnly, $endDateOnly])
        );
        $purchasesCost  = (float)($purStat['total_cost'] ?? 0);
        $purchasesCount = (int)($purStat['count'] ?? 0);

        // Expenditures: Factory Operating & Machinery Expenses
        $expStat = Database::fetchOne(
            "SELECT COUNT(*) AS count,
                    COALESCE(SUM(total_cost), 0) AS total_cost,
                    COALESCE(SUM(paid_amount), 0) AS paid_amount
             FROM production_expenses
             WHERE expense_date >= ? AND expense_date <= ?" . $cWhere('concern_id'),
            $cParams([$startDateOnly, $endDateOnly])
        );
        $expensesCost    = (float)($expStat['total_cost'] ?? 0);
        $expensesPaid    = (float)($expStat['paid_amount'] ?? 0);
        $expensesPending = max(0.00, $expensesCost - $expensesPaid);
        $expensesCount   = (int)($expStat['count'] ?? 0);

        // Expenditures: Customer Returns Reversal / Refunds
        $retStat = Database::fetchOne(
            "SELECT COUNT(*) AS count,
                    COALESCE(SUM(total_returned_qty), 0) AS returned_pcs,
                    COALESCE(SUM(subtotal), 0) AS taxable_reversal,
                    COALESCE(SUM(gst_reversal_amount), 0) AS gst_reversal,
                    COALESCE(SUM(net_amount), 0) AS net_refund
             FROM production_returns
             WHERE created_at >= ? AND created_at <= ?" . $cWhere('concern_id'),
            $cParams([$startDateTime, $endDateTime])
        );
        $returnsRefund  = (float)($retStat['net_refund'] ?? 0);
        $returnsCount   = (int)($retStat['count'] ?? 0);
        $returnsPieces  = (int)($retStat['returned_pcs'] ?? 0);

        // Total Outflow / Cost
        $totalExpenditure = $purchasesCost + $expensesCost + $returnsRefund;
        $netProfitLoss    = $grossRevenue - $totalExpenditure;
        $profitMarginPct  = ($grossRevenue > 0) ? round(($netProfitLoss / $grossRevenue) * 100, 2) : 0.0;

        // Operational Stock Pipeline Metrics
        $rollsStat = Database::fetchOne(
            "SELECT COUNT(*) as rolls_count, COALESCE(SUM(weight), 0) as weight_kg, COALESCE(SUM(price), 0) as total_value
             FROM seller_rolls
             WHERE created_at >= ? AND created_at <= ?" . $cWhere('concern_id'),
            $cParams([$startDateTime, $endDateTime])
        );
        $cutStat = Database::fetchOne(
            "SELECT COUNT(*) as batches_count, COALESCE(SUM(cut_qty), 0) as cut_pieces, COALESCE(SUM(wastage_qty), 0) as wastage_kg
             FROM cutting_batches
             WHERE created_at >= ? AND created_at <= ?" . $cWhere('concern_id'),
            $cParams([$startDateTime, $endDateTime])
        );
        $stitchStat = Database::fetchOne(
            "SELECT COUNT(*) as batches_count, COALESCE(SUM(stitched_qty), 0) as stitched_pieces,
                    COALESCE(SUM(rework_qty), 0) as rework_pieces, COALESCE(SUM(rejected_qty), 0) as rejected_pieces
             FROM stitching_batches
             WHERE created_at >= ? AND created_at <= ?" . $cWhere('concern_id'),
            $cParams([$startDateTime, $endDateTime])
        );
        $qcStat = Database::fetchOne(
            "SELECT COUNT(*) as batches_count, COALESCE(SUM(total_qty), 0) as inspected_pieces,
                    COALESCE(SUM(passed_qty), 0) as passed_pieces, COALESCE(SUM(rework_qty), 0) as rework_pieces,
                    COALESCE(SUM(rejected_qty), 0) as rejected_pieces
             FROM qc_batches
             WHERE created_at >= ? AND created_at <= ?" . $cWhere('concern_id'),
            $cParams([$startDateTime, $endDateTime])
        );
        $batchStat = Database::fetchOne(
            "SELECT COUNT(*) as batches_count, COALESCE(SUM(box_count), 0) as box_count,
                    COALESCE(SUM(CASE WHEN total_qty > 0 THEN total_qty ELSE quantity END), 0) as packed_pieces
             FROM production_batches
             WHERE ((packed_date >= ? AND packed_date <= ?)
                OR (packed_date IS NULL AND created_at >= ? AND created_at <= ?))" . $cWhere('concern_id'),
            $cParams([$startDateOnly, $endDateOnly, $startDateTime, $endDateTime])
        );
        $outwardStat = Database::fetchOne(
            "SELECT COUNT(*) as outward_count, COALESCE(SUM(total_boxes), 0) as total_boxes,
                    COALESCE(SUM(CASE WHEN total_quantity > 0 THEN total_quantity ELSE quantity END), 0) as dispatched_pieces
             FROM production_outwards
             WHERE ((dispatch_date >= ? AND dispatch_date <= ?)
                OR (dispatch_date IS NULL AND created_at >= ? AND created_at <= ?))" . $cWhere('concern_id'),
            $cParams([$startDateOnly, $endDateOnly, $startDateTime, $endDateTime])
        );
        $stockReqStat = Database::fetchOne(
            "SELECT COUNT(*) as req_count, COALESCE(SUM(total_requested_pieces), 0) as requested_pieces,
                    COALESCE(SUM(fulfilled_pieces), 0) as fulfilled_pieces
             FROM production_stock_requests
             WHERE created_at >= ? AND created_at <= ?" . $cWhere('concern_id'),
            $cParams([$startDateTime, $endDateTime])
        );

        $qcInspected     = (int)($qcStat['inspected_pieces'] ?? 0);
        $qcPassed        = (int)($qcStat['passed_pieces'] ?? 0);
        $qcYieldPct      = ($qcInspected > 0) ? round(($qcPassed / $qcInspected) * 100, 2) : 0.0;
        $packedPcs       = (int)($batchStat['packed_pieces'] ?? 0);
        $dispatchedPcs   = (int)($outwardStat['dispatched_pieces'] ?? 0);
        $onHandEstimated = max(0, $packedPcs - $dispatchedPcs - $billingPieces);

        $data['summary'] = [
            'financial' => [
                'billing_count'      => $billingCount,
                'billing_pieces'     => $billingPieces,
                'billing_taxable'    => $billingTaxable,
                'billing_gst'        => $billingGst,
                'billing_total'      => $billingTotal,
                'wastage_cutting'    => $wastageCuttingRev,
                'wastage_stitching'  => $wastageStitchingRev,
                'wastage_total'      => $wastageTotalRev,
                'gross_revenue'      => $grossRevenue,
                'purchases_count'    => $purchasesCount,
                'purchases_total'    => $purchasesCost,
                'expenses_count'     => $expensesCount,
                'expenses_total'     => $expensesCost,
                'expenses_paid'      => $expensesPaid,
                'expenses_pending'   => $expensesPending,
                'returns_count'      => $returnsCount,
                'returns_pieces'     => $returnsPieces,
                'returns_total'      => $returnsRefund,
                'total_expenditure'  => $totalExpenditure,
                'net_profit_loss'    => $netProfitLoss,
                'profit_margin_pct'  => $profitMarginPct,
            ],
            'stock' => [
                'inward_rolls_count'     => (int)($rollsStat['rolls_count'] ?? 0),
                'inward_weight_kg'       => (float)($rollsStat['weight_kg'] ?? 0),
                'inward_value'           => (float)($rollsStat['total_value'] ?? 0),
                'cuttings_batches_count' => (int)($cutStat['batches_count'] ?? 0),
                'cuttings_pieces'        => (int)($cutStat['cut_pieces'] ?? 0),
                'cuttings_wastage_kg'    => (float)($cutStat['wastage_kg'] ?? 0),
                'stitching_batches_count'=> (int)($stitchStat['batches_count'] ?? 0),
                'stitching_pieces'       => (int)($stitchStat['stitched_pieces'] ?? 0),
                'stitching_rework'       => (int)($stitchStat['rework_pieces'] ?? 0),
                'stitching_rejected'     => (int)($stitchStat['rejected_pieces'] ?? 0),
                'qc_batches_count'       => (int)($qcStat['batches_count'] ?? 0),
                'qc_inspected_pieces'    => $qcInspected,
                'qc_passed_pieces'       => $qcPassed,
                'qc_rework_pieces'       => (int)($qcStat['rework_pieces'] ?? 0),
                'qc_rejected_pieces'     => (int)($qcStat['rejected_pieces'] ?? 0),
                'qc_yield_pct'           => $qcYieldPct,
                'batches_count'          => (int)($batchStat['batches_count'] ?? 0),
                'batches_boxes'          => (int)($batchStat['box_count'] ?? 0),
                'batches_pieces'         => $packedPcs,
                'outward_count'          => (int)($outwardStat['outward_count'] ?? 0),
                'outward_boxes'          => (int)($outwardStat['total_boxes'] ?? 0),
                'outward_pieces'         => $dispatchedPcs,
                'billing_pieces'         => $billingPieces,
                'stock_on_hand_pieces'   => $onHandEstimated,
                'stock_req_count'        => (int)($stockReqStat['req_count'] ?? 0),
                'stock_req_pieces'       => (int)($stockReqStat['requested_pieces'] ?? 0),
                'stock_req_fulfilled'    => (int)($stockReqStat['fulfilled_pieces'] ?? 0),
            ]
        ];

        return $data;
    }

    /**
     * Generate and stream CSV export directly to browser output.
     * Formats all numbers with pure decimal notation (no comma thousands separators)
     * so Microsoft Excel interprets all values natively as numeric data.
     */
    public static function streamCsv(string $periodLabel, string $startDate, string $endDate, array $data, ?array $facility = null, array $selectedConcerns = []): void
    {
        $facility = $facility ?? (class_exists('ProductionUnit') ? \ProductionUnit::getActiveFacility() : []);
        $facilityName = !empty($facility['name']) ? strtoupper($facility['name']) : 'PRODUCTION MANUFACTURING FACILITY';

        header('Content-Type: text/csv; charset=UTF-8');
        header('Content-Disposition: attachment; filename="export_report_' . date('Ymd_His') . '.csv"');
        header('Pragma: no-cache');
        header('Expires: 0');

        $out = fopen('php://output', 'w');
        // UTF-8 BOM for Microsoft Excel recognition
        fprintf($out, chr(0xEF).chr(0xBB).chr(0xBF));

        // 1. Facility Header
        fputcsv($out, [$facilityName . ' — COMPREHENSIVE DATA EXPORT REPORT']);
        if (!empty($facility['address'])) {
            fputcsv($out, ['Facility Address:', str_replace(["\r\n", "\n", "\r"], ' ', (string)$facility['address'])]);
        }
        fputcsv($out, [
            'Unit Code:', $facility['code'] ?? 'PU-01',
            'Phone / Contact:', $facility['phone'] ?? '-',
            'GSTIN:', $facility['gst_number'] ?? '-'
        ]);
        fputcsv($out, ['Report Period:', $periodLabel, "From: {$startDate}", "To: {$endDate}"]);
        fputcsv($out, ['Generated Timestamp:', date('Y-m-d H:i:s') . ' IST']);
        if (!empty($selectedConcerns)) {
            $concernLabels = array_map(function($c) {
                return '[' . ($c['code'] ?? '') . '] ' . ($c['name'] ?? '');
            }, $selectedConcerns);
            fputcsv($out, ['Business Concern Scope:', implode(' | ', $concernLabels)]);
        } else {
            fputcsv($out, ['Business Concern Scope:', 'All Concerns (Consolidated Enterprise)']);
        }
        fputcsv($out, []); // Blank separator line

        // 2. Executive Financial Summary (Income & Loss)
        if (!empty($data['summary']['financial'])) {
            $fin = $data['summary']['financial'];
            fputcsv($out, ['=== EXECUTIVE FINANCIAL PERFORMANCE (REVENUE, COSTS & PROFIT / LOSS) ===']);
            fputcsv($out, ['Financial Metric', 'Value (INR)', 'Details / Component Breakdown']);
            fputcsv($out, ['Direct Factory Billing Sales', number_format($fin['billing_total'], 2, '.', ''), "Taxable: " . number_format($fin['billing_taxable'], 2, '.', '') . " + GST: " . number_format($fin['billing_gst'], 2, '.', '') . " ({$fin['billing_count']} bills, {$fin['billing_pieces']} pcs)"]);
            fputcsv($out, ['Cutting & Stitching Scrap Sales', number_format($fin['wastage_total'], 2, '.', ''), "Cutting Scrap: " . number_format($fin['wastage_cutting'], 2, '.', '') . " + Stitching Scrap: " . number_format($fin['wastage_stitching'], 2, '.', '')]);
            fputcsv($out, ['TOTAL GROSS REVENUE (INFLOW)', number_format($fin['gross_revenue'], 2, '.', ''), 'All Sales Revenue Inflow for the Period']);
            fputcsv($out, ['Fabric & Material Purchases', number_format($fin['purchases_total'], 2, '.', ''), "{$fin['purchases_count']} Material Purchase Orders"]);
            fputcsv($out, ['Factory Operating & Asset Expenses', number_format($fin['expenses_total'], 2, '.', ''), "Paid: " . number_format($fin['expenses_paid'], 2, '.', '') . " / Pending: " . number_format($fin['expenses_pending'], 2, '.', '')]);
            fputcsv($out, ['Customer Returns Reversal / Refunds', number_format($fin['returns_total'], 2, '.', ''), "{$fin['returns_count']} Returns ({$fin['returns_pieces']} pcs returned)"]);
            fputcsv($out, ['TOTAL OPERATING EXPENDITURES (OUTFLOW)', number_format($fin['total_expenditure'], 2, '.', ''), 'Purchases + Operating Expenses + Return Refunds']);
            fputcsv($out, ['NET PROFIT / LOSS', number_format($fin['net_profit_loss'], 2, '.', ''), ($fin['net_profit_loss'] >= 0 ? 'NET OPERATING PROFIT' : 'NET OPERATING LOSS')]);
            fputcsv($out, ['OPERATING MARGIN (%)', number_format($fin['profit_margin_pct'], 2, '.', '') . '%', 'Net Profit Margin on Gross Inflow']);
            fputcsv($out, []);
        }

        // 3. Executive Stock Pipeline & Production Volume
        if (!empty($data['summary']['stock'])) {
            $stk = $data['summary']['stock'];
            fputcsv($out, ['=== PRODUCTION PIPELINE & STOCK INVENTORY RECONCILIATION ===']);
            fputcsv($out, ['Operational Stage', 'Count / Batches', 'Primary Units', 'Secondary Metric']);
            fputcsv($out, ['Fabric Rolls Inward', $stk['inward_rolls_count'] . ' Rolls', number_format($stk['inward_weight_kg'], 2, '.', '') . ' kg', 'Value: INR ' . number_format($stk['inward_value'], 2, '.', '')]);
            fputcsv($out, ['Cutting Output', $stk['cuttings_batches_count'] . ' Batches', $stk['cuttings_pieces'] . ' Cut Pcs', 'Scrap: ' . number_format($stk['cuttings_wastage_kg'], 2, '.', '') . ' kg']);
            fputcsv($out, ['Stitching Output', $stk['stitching_batches_count'] . ' Batches', $stk['stitching_pieces'] . ' Stitched Pcs', "Rework: {$stk['stitching_rework']} / Reject: {$stk['stitching_rejected']}"]);
            fputcsv($out, ['Quality Control (QC)', $stk['qc_batches_count'] . ' Batches', $stk['qc_passed_pieces'] . ' Passed Pcs', "Yield: {$stk['qc_yield_pct']}% / Inspected: {$stk['qc_inspected_pieces']}"]);
            fputcsv($out, ['Finished Goods Packed (Batches)', $stk['batches_count'] . ' Batches', $stk['batches_pieces'] . ' Pcs Packed', $stk['batches_boxes'] . ' Master Cartons']);
            fputcsv($out, ['Dispatched to Warehouses (Outward)', $stk['outward_count'] . ' Outwards', $stk['outward_pieces'] . ' Dispatched Pcs', $stk['outward_boxes'] . ' Cartons Dispatched']);
            fputcsv($out, ['Direct Factory Sales (Billed)', ($data['summary']['financial']['billing_count'] ?? 0) . ' Invoices', $stk['billing_pieces'] . ' Sold Pcs', 'Direct Billed Pieces']);
            fputcsv($out, ['Warehouse Stock Requisitions', $stk['stock_req_count'] . ' Requisitions', $stk['stock_req_fulfilled'] . ' Fulfilled Pcs', "Total Requested: {$stk['stock_req_pieces']} Pcs"]);
            fputcsv($out, ['Estimated Finished Stock On-Hand', '-', $stk['stock_on_hand_pieces'] . ' Finished Pcs', 'Packed minus Dispatched & Sold']);
            fputcsv($out, []);
        }

        // =========================================================================
        // DETAILED MODULE DATA TABLES
        // =========================================================================

        // 1. Purchases
        if (!empty($data['purchases'])) {
            fputcsv($out, ['=== 1. FABRIC & MATERIAL PURCHASES (PO ORDERS) ===']);
            fputcsv($out, ['Purchase / PO #', 'Purchase Date', 'Supplier', 'Invoice #', 'Total Items', 'Fabric Rolls Count', 'Total Weight (kg)', 'Total Value (INR)', 'Notes', 'Concern']);
            foreach ($data['purchases'] as $pur) {
                fputcsv($out, [
                    $pur['purchase_number'],
                    date('d M Y', strtotime($pur['purchase_date'])),
                    $pur['supplier_company'] ?: ($pur['supplier_name'] ?? 'Direct Supplier'),
                    $pur['invoice_number'] ?: '-',
                    (int)$pur['total_items'],
                    (int)$pur['total_rolls'],
                    number_format((float)$pur['total_weight'], 2, '.', ''),
                    number_format((float)$pur['total_amount'], 2, '.', ''),
                    $pur['notes'] ?? '',
                    $pur['concern_code'] ?? '-',
                ]);
            }
            fputcsv($out, []);
        }

        // 2. Rolls & Inwards
        if (!empty($data['rolls'])) {
            fputcsv($out, ['=== 2. FABRIC ROLLS & INWARDS ===']);
            fputcsv($out, ['Roll Number', 'Inward Date', 'Supplier', 'Fabric Type', 'Color', 'Weight (kg)', 'Cost/kg (INR)', 'Total Cost (INR)', 'Status', 'Concern']);
            foreach ($data['rolls'] as $r) {
                fputcsv($out, [
                    $r['roll_number'],
                    !empty($r['created_at']) ? date('d M Y', strtotime($r['created_at'])) : '-',
                    $r['supplier_company'] ?: ($r['supplier_name'] ?? 'Direct Supplier'),
                    $r['fabric_type'] ?? '-',
                    $r['color'] ?? '-',
                    number_format((float)($r['weight'] ?? 0), 2, '.', ''),
                    number_format((float)($r['cost_per_kg'] ?? 0), 2, '.', ''),
                    number_format((float)($r['total_cost'] ?? 0), 2, '.', ''),
                    $r['status'] ?? 'IN_STOCK',
                    $r['concern_code'] ?? '-',
                ]);
            }
            fputcsv($out, []);
        }

        // 3. Programs
        if (!empty($data['programs'])) {
            fputcsv($out, ['=== 3. PRODUCTION PROGRAMS ===']);
            fputcsv($out, ['Program Number', 'Program Name', 'Style Code', 'Target Pieces', 'Completed Pieces', 'Status', 'Target Date', 'Concern']);
            foreach ($data['programs'] as $p) {
                fputcsv($out, [
                    $p['program_number'],
                    $p['program_name'],
                    $p['style_code'] ?? '-',
                    (int)$p['target_pieces'],
                    (int)($p['completed_quantity'] ?? 0),
                    $p['status'],
                    $p['target_date'] ?? '-',
                    $p['concern_code'] ?? '-',
                ]);
            }
            fputcsv($out, []);
        }

        // 4. Cuttings
        if (!empty($data['cuttings'])) {
            fputcsv($out, ['=== 4. CUTTING BATCHES ===']);
            fputcsv($out, ['Batch Number', 'Program #', 'Roll Weight (kg)', 'Planned Qty', 'Cut Pieces', 'Wastage Pcs / kg', 'Status', 'Date', 'Concern']);
            foreach ($data['cuttings'] as $c) {
                fputcsv($out, [
                    $c['batch_number'],
                    $c['program_number'] ?? '-',
                    number_format((float)($c['roll_weight'] ?? 0), 2, '.', ''),
                    (int)$c['planned_qty'],
                    (int)$c['actual_pieces'],
                    number_format((float)($c['wastage_qty'] ?? 0), 2, '.', ''),
                    $c['status'],
                    !empty($c['created_at']) ? date('d M Y', strtotime($c['created_at'])) : '-',
                    $c['concern_code'] ?? '-',
                ]);
            }
            fputcsv($out, []);
        }

        // 5. Stitching
        if (!empty($data['stitching'])) {
            fputcsv($out, ['=== 5. STITCHING OPERATIONS ===']);
            fputcsv($out, ['Batch Number', 'Program #', 'Target Qty', 'Stitched Pcs', 'Rework Pcs', 'Rejected Pcs', 'Status', 'Date', 'Concern']);
            foreach ($data['stitching'] as $s) {
                fputcsv($out, [
                    $s['batch_number'],
                    $s['program_number'] ?? '-',
                    (int)$s['target_quantity'],
                    (int)$s['completed_quantity'],
                    (int)$s['rework_quantity'],
                    (int)$s['rejected_qty'],
                    $s['status'],
                    !empty($s['created_at']) ? date('d M Y', strtotime($s['created_at'])) : '-',
                    $s['concern_code'] ?? '-',
                ]);
            }
            fputcsv($out, []);
        }

        // 6. QC
        if (!empty($data['qc'])) {
            fputcsv($out, ['=== 6. QUALITY CONTROL (QC) INSPECTIONS ===']);
            fputcsv($out, ['QC Inspection #', 'Batch Number', 'Program #', 'Inspected Pcs', 'Passed Pcs', 'Rework Pcs', 'Rejected Pcs', 'Yield %', 'Status', 'Date', 'Concern']);
            foreach ($data['qc'] as $q) {
                fputcsv($out, [
                    $q['qc_number'],
                    $q['batch_number'] ?? '-',
                    $q['program_number'] ?? '-',
                    (int)$q['inspected_quantity'],
                    (int)$q['passed_quantity'],
                    (int)$q['rework_quantity'],
                    (int)$q['rejected_quantity'],
                    number_format((float)$q['yield_percentage'], 2, '.', '') . '%',
                    $q['status'],
                    !empty($q['created_at']) ? date('d M Y', strtotime($q['created_at'])) : '-',
                    $q['concern_code'] ?? '-',
                ]);
            }
            fputcsv($out, []);
        }

        // 7. Batches (Packed Master Cartons)
        if (!empty($data['batches'])) {
            fputcsv($out, ['=== 7. FINISHED GOODS PACKING BATCHES ===']);
            fputcsv($out, ['Packing Batch #', 'Program #', 'QC Batch #', 'Cartons Count', 'Total Pieces', 'Cost/Pc (INR)', 'Total Value (INR)', 'Destination Warehouse', 'Status', 'Packed Date', 'Concern']);
            foreach ($data['batches'] as $b) {
                fputcsv($out, [
                    $b['batch_number'],
                    $b['program_number'] ?? '-',
                    $b['qc_batch_number'] ?? '-',
                    (int)$b['box_count'],
                    (int)$b['total_pieces'],
                    number_format((float)$b['cost_per_piece'], 2, '.', ''),
                    number_format((float)$b['total_cost'], 2, '.', ''),
                    $b['destination_warehouse'],
                    $b['status'],
                    !empty($b['packed_date']) ? date('d M Y', strtotime($b['packed_date'])) : '-',
                    $b['concern_code'] ?? '-',
                ]);
            }
            fputcsv($out, []);
        }

        // 8. Outward Dispatches
        if (!empty($data['outward'])) {
            fputcsv($out, ['=== 8. WAREHOUSE OUTWARD DISPATCHES ===']);
            fputcsv($out, ['Outward #', 'Packing Batch #', 'Destination Warehouse', 'Boxes Dispatched', 'Pieces Dispatched', 'Transporter', 'Vehicle #', 'LR / Docket #', 'Driver', 'Status', 'Dispatch Date', 'Concern']);
            foreach ($data['outward'] as $o) {
                fputcsv($out, [
                    $o['outward_number'],
                    $o['batch_number'] ?? '-',
                    $o['warehouse_name'],
                    (int)$o['total_boxes'],
                    (int)$o['total_pieces'],
                    $o['transporter_name'],
                    $o['vehicle_number'],
                    $o['lr_number'],
                    $o['driver_name'],
                    $o['status'],
                    !empty($o['dispatch_date']) ? date('d M Y', strtotime($o['dispatch_date'])) : '-',
                    $o['concern_code'] ?? '-',
                ]);
            }
            fputcsv($out, []);
        }

        if (array_key_exists('stock_ledger', $data)) {
            fputcsv($out, ['=== DETAILED WAREHOUSE STOCK MOVEMENT LEDGER ===']);
            fputcsv($out, ['Each month is a separate table. Positive movements are stock added; negative movements are stock removed. Balance is the recorded balance after each transaction.']);

            $monthlyLedger = [];
            foreach ($data['stock_ledger'] as $movement) {
                $monthKey = date('Y-m', strtotime($movement['transaction_date']));
                $monthlyLedger[$monthKey][] = $movement;
            }

            if (!$monthlyLedger) {
                fputcsv($out, ['No warehouse stock movements found for the selected period and warehouses.']);
            }

            foreach ($monthlyLedger as $monthKey => $movements) {
                fputcsv($out, []);
                fputcsv($out, ['=== ' . date('F Y', strtotime($monthKey . '-01')) . ' — WAREHOUSE STOCK MOVEMENTS ===']);
                fputcsv($out, [
                    'Date & Time', 'Transaction ID', 'Warehouse', 'Stock State', 'Transaction Type',
                    'QR Code', 'QR Type', 'QR Series Start', 'QR Series End', 'Variant ID', 'Variant Code', 'SKU',
                    'Product', 'Product Code', 'Material', 'Description', 'Category', 'Subcategory', 'Sub-Subcategory',
                    'Size', 'Color', 'Design / Pattern', 'Design Code', 'HSN', 'GST %', 'Weight',
                    'MRP (INR)', 'Purchase Price (INR)', 'Production Cost (INR)', 'Selling Price (INR)',
                    'Stock Added', 'Stock Removed', 'Net Movement', 'Balance After',
                    'Complementary', 'Source Type', 'Source ID', 'Destination Type', 'Destination ID',
                    'Reference Type', 'Reference ID', 'Notes', 'Updated By', 'Concern',
                ]);
                foreach ($movements as $movement) {
                    fputcsv($out, [
                        $movement['transaction_date'],
                        (int)$movement['transaction_id'],
                        $movement['warehouse_name'] ?? '-',
                        $movement['stock_state'],
                        $movement['transaction_type'],
                        $movement['qr_code'] ?? '-',
                        $movement['qr_type'] ?? '-',
                        $movement['series_start_code'] ?? '-',
                        $movement['series_end_code'] ?? '-',
                        (int)$movement['variant_id'],
                        $movement['variant_code'] ?? '-',
                        $movement['sku'] ?? '-',
                        $movement['product_name'] ?? '-',
                        $movement['product_code'] ?? '-',
                        $movement['material'] ?? '-',
                        str_replace(["\r\n", "\n", "\r"], ' ', (string)($movement['description'] ?? '')),
                        $movement['category_name'] ?? '-',
                        $movement['subcategory_name'] ?? '-',
                        $movement['sub_subcategory_name'] ?? '-',
                        $movement['size_name'] ?? '-',
                        $movement['color_name'] ?? '-',
                        $movement['design_name'] ?? '-',
                        $movement['design_code'] ?? '-',
                        $movement['hsn_code'] ?? '-',
                        number_format((float)($movement['gst_rate'] ?? 0), 2, '.', ''),
                        number_format((float)($movement['weight'] ?? 0), 3, '.', ''),
                        number_format((float)($movement['mrp'] ?? 0), 2, '.', ''),
                        number_format((float)($movement['purchase_price'] ?? 0), 2, '.', ''),
                        number_format((float)($movement['production_cost'] ?? 0), 2, '.', ''),
                        number_format((float)($movement['selling_price'] ?? 0), 2, '.', ''),
                        number_format((float)$movement['stock_in'], 2, '.', ''),
                        number_format((float)$movement['stock_out'], 2, '.', ''),
                        number_format((float)$movement['movement_quantity'], 2, '.', ''),
                        number_format((float)$movement['balance_after'], 2, '.', ''),
                        !empty($movement['is_complementary']) ? 'YES' : 'NO',
                        $movement['source_type'] ?? '-',
                        $movement['source_id'] ?? '-',
                        $movement['dest_type'] ?? '-',
                        $movement['dest_id'] ?? '-',
                        $movement['reference_type'] ?? '-',
                        $movement['reference_id'] ?? '-',
                        str_replace(["\r\n", "\n", "\r"], ' ', (string)($movement['notes'] ?? '')),
                        $movement['performed_by'] ?? '-',
                        $movement['concern_code'] ?? '-',
                    ]);
                }
            }
            fputcsv($out, []);
        }

        // 9. Billing / POS Sales
        if (!empty($data['billing'])) {
            fputcsv($out, ['=== 9. PRODUCTION BILLING & DIRECT SALES ===']);
            fputcsv($out, ['Invoice / Bill #', 'Bill Date', 'Client / Buyer Type', 'Customer Name', 'Phone', 'Total Pieces', 'Taxable (INR)', 'GST Tax (INR)', 'Grand Total (INR)', 'Payment Mode', 'Status', 'Concern']);
            foreach ($data['billing'] as $bl) {
                fputcsv($out, [
                    $bl['bill_number'],
                    date('d M Y', strtotime($bl['bill_date'])),
                    $bl['buyer_type'] ?? 'RETAIL',
                    $bl['client_name'],
                    $bl['customer_phone'] ?: '-',
                    (int)$bl['total_pieces'],
                    number_format((float)$bl['subtotal'], 2, '.', ''),
                    number_format((float)$bl['tax_amount'], 2, '.', ''),
                    number_format((float)$bl['total_amount'], 2, '.', ''),
                    $bl['payment_method'] ?? 'CASH',
                    $bl['payment_status'] ?? 'PAID',
                    $bl['concern_code'] ?? '-',
                ]);
            }
            fputcsv($out, []);
        }

        // 10. Customer Returns
        if (!empty($data['returns'])) {
            fputcsv($out, ['=== 10. CUSTOMER RETURNS & CREDIT NOTES ===']);
            fputcsv($out, ['Return #', 'Original Bill #', 'Customer Name', 'Return Type', 'Returned Pcs', 'Disposition', 'Net Refund (INR)', 'Refund Mode', 'Status', 'Return Date', 'Concern']);
            foreach ($data['returns'] as $rt) {
                fputcsv($out, [
                    $rt['return_number'],
                    $rt['bill_number'] ?? '-',
                    $rt['customer_name'] ?? '-',
                    $rt['return_type'] ?? 'REFUND',
                    (int)$rt['total_returned_qty'],
                    $rt['disposition'] ?? 'RESTOCK',
                    number_format((float)$rt['net_amount'], 2, '.', ''),
                    $rt['refund_mode'] ?? 'CASH',
                    $rt['status'],
                    !empty($rt['created_at']) ? date('d M Y', strtotime($rt['created_at'])) : '-',
                    $rt['concern_code'] ?? '-',
                ]);
            }
            fputcsv($out, []);
        }

        // 11. Stock Requests
        if (!empty($data['stock_requests'])) {
            fputcsv($out, ['=== 11. WAREHOUSE STOCK REQUISITIONS ===']);
            fputcsv($out, ['Request #', 'Requesting Warehouse', 'Requested By', 'Priority', 'Requested Pcs', 'Fulfilled Pcs', 'Status', 'Required Date', 'Requested Date', 'Concern']);
            foreach ($data['stock_requests'] as $sr) {
                fputcsv($out, [
                    $sr['request_number'],
                    $sr['warehouse_name'] ?? 'Main Warehouse',
                    $sr['requested_by_name'] ?? '-',
                    $sr['priority'] ?? 'MEDIUM',
                    (int)$sr['total_requested_pieces'],
                    (int)$sr['fulfilled_pieces'],
                    $sr['status'],
                    $sr['required_by_date'] ?? '-',
                    !empty($sr['created_at']) ? date('d M Y', strtotime($sr['created_at'])) : '-',
                    $sr['concern_code'] ?? '-',
                ]);
            }
            fputcsv($out, []);
        }

        // 12. Wastage Selling
        if (!empty($data['wastage'])) {
            fputcsv($out, ['=== 12. FABRIC & STITCHING SCRAP SALES ===']);
            fputcsv($out, ['Sale / Invoice #', 'Scrap Type', 'Buyer Name', 'Weight / Qty (kg)', 'Rate/kg (INR)', 'Total Amount (INR)', 'Sale Date', 'Concern']);
            foreach ($data['wastage'] as $w) {
                fputcsv($out, [
                    $w['invoice_number'],
                    $w['wastage_type'],
                    $w['buyer_name'] ?? 'Scrap Trader',
                    number_format((float)$w['weight_kg'], 2, '.', ''),
                    number_format((float)$w['rate_per_kg'], 2, '.', ''),
                    number_format((float)$w['total_amount'], 2, '.', ''),
                    !empty($w['created_at']) ? date('d M Y', strtotime($w['created_at'])) : '-',
                    $w['concern_code'] ?? '-',
                ]);
            }
            fputcsv($out, []);
        }

        // 13. Production Expenses
        if (!empty($data['expenses'])) {
            fputcsv($out, ['=== 13. PRODUCTION EXPENSES & OPERATING COSTS ===']);
            fputcsv($out, ['Expense Title', 'Category', 'Production Facility', 'Total Cost (INR)', 'Paid (INR)', 'Pending (INR)', 'Payment Status', 'Expense Date', 'Concern']);
            foreach ($data['expenses'] as $ex) {
                fputcsv($out, [
                    $ex['title'],
                    $ex['category'] ?? 'General',
                    $ex['production_unit'],
                    number_format((float)$ex['total_cost'], 2, '.', ''),
                    number_format((float)$ex['paid_amount'], 2, '.', ''),
                    number_format((float)$ex['pending_amount'], 2, '.', ''),
                    $ex['payment_status'],
                    !empty($ex['expense_date']) ? date('d M Y', strtotime($ex['expense_date'])) : '-',
                    $ex['concern_code'] ?? '-',
                ]);
            }
            fputcsv($out, []);
        }

        // 14. Raw Materials Supply & Procurement
        if (!empty($data['raw_materials'])) {
            fputcsv($out, ['=== 14. RAW MATERIALS SUPPLY & PROCUREMENT ===']);
            fputcsv($out, ['PO Number', 'Purchase Date', 'Supplier', 'Invoice #', 'Material Name', 'Color', 'Count / Size', 'Unit', 'Quantity', 'Purchase Price (INR)', 'Total Amount (INR)', 'HSN Code', 'GST Type', 'Tax Treatment', 'Item Code', 'Concern']);
            foreach ($data['raw_materials'] as $rm) {
                fputcsv($out, [
                    $rm['purchase_number'],
                    !empty($rm['purchase_date']) ? date('d M Y', strtotime($rm['purchase_date'])) : '-',
                    $rm['supplier_company'] ?: ($rm['supplier_name'] ?? 'Direct Supplier'),
                    $rm['invoice_number'] ?: '-',
                    $rm['item_name'],
                    $rm['color'] ?: '-',
                    $rm['size'] ?: '-',
                    $rm['unit'] ?? 'Kilograms',
                    number_format((float)$rm['quantity'], 2, '.', ''),
                    number_format((float)$rm['unit_price'], 2, '.', ''),
                    number_format((float)$rm['total_price'], 2, '.', ''),
                    $rm['hsn_code'] ?: '-',
                    $rm['gst_type'] ?? 'EXEMPTED',
                    $rm['tax_treatment'] ?? 'INCLUDED',
                    $rm['start_code'] ?: '-',
                    $rm['concern_code'] ?? '-',
                ]);
            }
            fputcsv($out, []);
        }

        // 15. Third-Party Finished Products & Mapping
        if (!empty($data['third_party_products'])) {
            fputcsv($out, ['=== 15. THIRD-PARTY PRODUCTS & CATALOG MAPPING ===']);
            fputcsv($out, ['PO Number', 'Purchase Date', 'Supplier', 'Invoice #', 'Product Name', 'Vendor SKU / Code', 'Color', 'Size', 'Unit', 'Quantity', 'Unit Cost (INR)', 'MRP (INR)', 'Selling Price (INR)', 'Total Cost (INR)', 'HSN Code', 'Mapped Category', 'Mapped Subcategory', 'Mapped Sub-Subcategory', 'Mapping Status', 'Concern']);
            foreach ($data['third_party_products'] as $tp) {
                fputcsv($out, [
                    $tp['purchase_number'],
                    !empty($tp['purchase_date']) ? date('d M Y', strtotime($tp['purchase_date'])) : '-',
                    $tp['supplier_company'] ?: ($tp['supplier_name'] ?? 'Direct Supplier'),
                    $tp['invoice_number'] ?: '-',
                    $tp['item_name'],
                    $tp['vendor_sku'] ?: ($tp['product_code'] ?? '-'),
                    $tp['color'] ?: '-',
                    $tp['size'] ?: '-',
                    $tp['unit'] ?? 'Pieces',
                    number_format((float)$tp['quantity'], 2, '.', ''),
                    number_format((float)$tp['unit_price'], 2, '.', ''),
                    number_format((float)$tp['mrp'], 2, '.', ''),
                    number_format((float)$tp['selling_price'], 2, '.', ''),
                    number_format((float)$tp['total_price'], 2, '.', ''),
                    $tp['hsn_code'] ?: '-',
                    $tp['category_name'] ?: 'Uncategorized',
                    $tp['subcategory_name'] ?: '-',
                    $tp['sub_subcategory_name'] ?: '-',
                    !empty($tp['is_mapped']) ? 'MAPPED TO CATALOG' : 'PENDING MAPPING',
                    $tp['concern_code'] ?? '-',
                ]);
            }
            fputcsv($out, []);
        }

        // 16. Suppliers & Mills Directory
        if (!empty($data['suppliers'])) {
            fputcsv($out, ['=== 16. SUPPLIERS & MILLS DIRECTORY ===']);
            fputcsv($out, ['Supplier / Mill Name', 'Company', 'Type', 'Phone', 'Email', 'GSTIN', 'City', 'State', 'Payment Terms', 'Total Orders Placed', 'Total Inward Value (INR)', 'Registered Date', 'Concern']);
            foreach ($data['suppliers'] as $sup) {
                fputcsv($out, [
                    $sup['name'],
                    $sup['company'] ?: '-',
                    $sup['seller_type'] ?? 'MILL',
                    $sup['phone'] ?: '-',
                    $sup['email'] ?: '-',
                    $sup['gstin'] ?: ($sup['gst_number'] ?? '-'),
                    $sup['city'] ?: '-',
                    $sup['state'] ?: '-',
                    $sup['payment_terms'] ?: '-',
                    (int)($sup['total_orders'] ?? 0),
                    number_format((float)($sup['total_order_value'] ?? 0), 2, '.', ''),
                    !empty($sup['created_at']) ? date('d M Y', strtotime($sup['created_at'])) : '-',
                    $sup['concern_code'] ?? '-',
                ]);
            }
            fputcsv($out, []);
        }

        // 17. Goods Inward Notes (GRN)
        if (!empty($data['goods_inward'])) {
            fputcsv($out, ['=== 17. GOODS INWARD NOTES (GRN) ===']);
            fputcsv($out, ['GRN Number', 'Invoice #', 'Invoice Date', 'Received Date', 'Destination Warehouse', 'Gate Pass #', 'Vehicle #', 'Driver Name', 'Total Received Qty', 'Accepted Qty', 'Rejected Qty', 'Status', 'Received By', 'Concern']);
            foreach ($data['goods_inward'] as $grn) {
                fputcsv($out, [
                    $grn['grn_number'],
                    $grn['invoice_number'] ?: '-',
                    !empty($grn['invoice_date']) ? date('d M Y', strtotime($grn['invoice_date'])) : '-',
                    !empty($grn['received_date']) ? date('d M Y', strtotime($grn['received_date'])) : '-',
                    $grn['warehouse_name'] ?? 'Main Warehouse',
                    $grn['gate_pass_number'] ?: '-',
                    $grn['vehicle_number'] ?: '-',
                    $grn['driver_name'] ?: '-',
                    number_format((float)$grn['total_received_qty'], 2, '.', ''),
                    number_format((float)$grn['total_accepted_qty'], 2, '.', ''),
                    number_format((float)$grn['total_rejected_qty'], 2, '.', ''),
                    $grn['status'] ?? 'COMPLETED',
                    $grn['received_by_name'] ?: '-',
                    $grn['concern_code'] ?? '-',
                ]);
            }
            fputcsv($out, []);
        }

        // 18. Damage & Rejection Management
        if (!empty($data['damages'])) {
            fputcsv($out, ['=== 18. DAMAGE & REJECTION MANAGEMENT ===']);
            fputcsv($out, ['Damage Report #', 'Item Type', 'Product / Roll Name', 'Roll Number', 'Supplier', 'Damaged Qty', 'Damaged Weight (kg)', 'Loss Amount (INR)', 'Damage Reason', 'Defect Notes', 'Status', 'Resolution Type', 'Debit Note #', 'Refund Amount (INR)', 'Reported Date', 'Concern']);
            foreach ($data['damages'] as $dm) {
                fputcsv($out, [
                    $dm['damage_number'],
                    $dm['item_type'] ?? 'ROLL',
                    $dm['product_name'] ?: 'Fabric Roll',
                    $dm['roll_number'] ?: '-',
                    $dm['supplier_company'] ?: ($dm['supplier_name'] ?? '-'),
                    number_format((float)($dm['damaged_quantity'] ?? 0), 2, '.', ''),
                    number_format((float)($dm['damaged_weight'] ?? 0), 2, '.', ''),
                    number_format((float)($dm['loss_amount'] ?? 0), 2, '.', ''),
                    $dm['damage_reason'] ?: '-',
                    $dm['defect_notes'] ?: '-',
                    $dm['status'] ?? 'PENDING',
                    $dm['resolution_type'] ?: '-',
                    $dm['debit_note_number'] ?: '-',
                    number_format((float)($dm['refund_amount'] ?? 0), 2, '.', ''),
                    !empty($dm['created_at']) ? date('d M Y', strtotime($dm['created_at'])) : '-',
                    $dm['concern_code'] ?? '-',
                ]);
            }
            fputcsv($out, []);
        }

        // 19. Product Catalog & Style Master
        if (!empty($data['catalog'])) {
            fputcsv($out, ['=== 19. PRODUCT CATALOG & STYLE MASTER ===']);
            fputcsv($out, ['Product / Style Name', 'Product Code', 'Source Type', 'Category', 'Subcategory', 'Sub-Subcategory', 'HSN Code', 'Material', 'Active Variants Count', 'Status', 'Created Date']);
            foreach ($data['catalog'] as $cat) {
                fputcsv($out, [
                    $cat['product_name'],
                    $cat['product_code'] ?: '-',
                    $cat['source_type'] ?? 'MANUFACTURED',
                    $cat['category_name'] ?: '-',
                    $cat['subcategory_name'] ?: '-',
                    $cat['sub_subcategory_name'] ?: '-',
                    $cat['hsn_code'] ?: '-',
                    $cat['material'] ?: '-',
                    (int)($cat['variant_count'] ?? 0),
                    !empty($cat['is_active']) ? 'ACTIVE' : 'INACTIVE',
                    !empty($cat['created_at']) ? date('d M Y', strtotime($cat['created_at'])) : '-',
                ]);
            }
            fputcsv($out, []);
        }

        // 19b. Product Variants & SKUs
        if (!empty($data['variants'])) {
            fputcsv($out, ['=== PRODUCT VARIANTS & SKUS ===']);
            fputcsv($out, [
                'Variant SKU',
                'Product Variant Code',
                'Product / Style Name',
                'Parent Style Code',
                'Size',
                'Color',
                'Design',
                'Category Path',
                'HSN Code',
                'GST Rate (%)',
                'MRP (INR)',
                'Selling Price (INR)',
                'Cost Per Piece (INR)',
                'QR Code / Barcode',
                'Available Qty',
                'Created Date'
            ]);
            foreach ($data['variants'] as $v) {
                $categoryPath = implode(' > ', array_filter([$v['category_name'] ?? '', $v['subcategory_name'] ?? '', $v['sub_subcategory_name'] ?? ''])) ?: '-';
                fputcsv($out, [
                    $v['sku'] ?: ($v['variant_code'] ?: '-'),
                    $v['variant_code'] ?: '-',
                    $v['product_name'] ?: '-',
                    $v['parent_sku'] ?: '-',
                    $v['size_name'] ?: '-',
                    $v['color_name'] ?: '-',
                    $v['design_name'] ?: '-',
                    $categoryPath,
                    $v['hsn_code'] ?: '-',
                    number_format((float)($v['gst_rate'] ?? 0), 2, '.', ''),
                    number_format((float)($v['mrp'] ?? 0), 2, '.', ''),
                    number_format((float)($v['selling_price'] ?? 0), 2, '.', ''),
                    number_format((float)($v['production_cost'] ?: ($v['purchase_price'] ?? 0)), 2, '.', ''),
                    $v['qr_code'] ?: '-',
                    number_format((float)($v['available_quantity'] ?? 0), 0, '.', ''),
                    !empty($v['created_at']) ? date('d M Y', strtotime($v['created_at'])) : '-',
                ]);
            }
            fputcsv($out, []);
        }

        // 20. QR Code Generation Batches
        if (!empty($data['qr_generations'])) {
            fputcsv($out, ['=== 20. QR CODE GENERATION BATCHES ===']);
            fputcsv($out, ['Batch Code', 'Generation Type', 'Brand', 'Category', 'Subcategory', 'Color', 'Size', 'Generated Quantity', 'MRP (INR)', 'Selling Price (INR)', 'QR Prefix', 'Stock Added Status', 'Created By', 'Created Date', 'Concern']);
            foreach ($data['qr_generations'] as $qr) {
                fputcsv($out, [
                    $qr['batch_code'],
                    $qr['generation_type'] ?? 'STANDARD',
                    $qr['brand_name'] ?: '-',
                    $qr['category_name'] ?: '-',
                    $qr['subcategory_name'] ?: '-',
                    $qr['color_name'] ?: '-',
                    $qr['size_name'] ?: '-',
                    (int)($qr['quantity'] ?? 0),
                    number_format((float)($qr['mrp'] ?? 0), 2, '.', ''),
                    number_format((float)($qr['selling_price'] ?? 0), 2, '.', ''),
                    $qr['qr_prefix'] ?: '-',
                    !empty($qr['stock_added']) ? 'ADDED TO STOCK' : 'PENDING INWARD',
                    $qr['created_by_name'] ?: '-',
                    !empty($qr['created_at']) ? date('d M Y', strtotime($qr['created_at'])) : '-',
                    $qr['concern_code'] ?? '-',
                ]);
            }
            fputcsv($out, []);
        }

        // 21. Finished Goods Inventory (On-Hand)
        if (!empty($data['inventory'])) {
            fputcsv($out, ['=== 21. FINISHED GOODS INVENTORY (ON-HAND) ===']);
            fputcsv($out, ['Product Name', 'Product Code', 'SKU', 'Variant Code', 'Size', 'Color', 'Location Name', 'Location Type', 'Stock State', 'On-Hand Quantity', 'Purchase Price (INR)', 'Selling Price (INR)', 'MRP (INR)', 'Last Updated', 'Concern']);
            foreach ($data['inventory'] as $inv) {
                fputcsv($out, [
                    $inv['product_name'],
                    $inv['product_code'] ?: '-',
                    $inv['sku'],
                    $inv['variant_code'] ?: '-',
                    $inv['size_name'] ?: '-',
                    $inv['color_name'] ?: '-',
                    $inv['location_name'] ?: 'Central Facility',
                    $inv['location_type'],
                    $inv['stock_state'],
                    number_format((float)$inv['quantity'], 2, '.', ''),
                    number_format((float)($inv['purchase_price'] ?? 0), 2, '.', ''),
                    number_format((float)($inv['selling_price'] ?? 0), 2, '.', ''),
                    number_format((float)($inv['mrp'] ?? 0), 2, '.', ''),
                    !empty($inv['last_updated']) ? date('d M Y H:i', strtotime($inv['last_updated'])) : '-',
                    $inv['concern_code'] ?? '-',
                ]);
            }
            fputcsv($out, []);
        }

        // 22. Warehouse Rework & Repairs
        if (!empty($data['warehouse_rework'])) {
            fputcsv($out, ['=== 22. WAREHOUSE REWORK & REPAIRS ===']);
            fputcsv($out, ['Rework #', 'Warehouse', 'Product Name', 'SKU', 'Defect Type', 'Damage Reason', 'Defect Notes', 'Quantity Dispatched', 'Repaired Qty', 'Scrapped Qty', 'Challan #', 'Status', 'Received Date', 'Concern']);
            foreach ($data['warehouse_rework'] as $wr) {
                fputcsv($out, [
                    $wr['rework_number'],
                    $wr['warehouse_name'] ?? 'Main Warehouse',
                    $wr['product_name'] ?: '-',
                    $wr['sku'] ?: '-',
                    $wr['defect_type'] ?: '-',
                    $wr['damage_reason'] ?: '-',
                    $wr['defect_notes'] ?: '-',
                    number_format((float)($wr['quantity'] ?? 0), 2, '.', ''),
                    number_format((float)($wr['repaired_qty'] ?? 0), 2, '.', ''),
                    number_format((float)($wr['scrapped_qty'] ?? 0), 2, '.', ''),
                    $wr['challan_number'] ?: '-',
                    $wr['status'] ?? 'PENDING',
                    !empty($wr['received_date']) ? date('d M Y', strtotime($wr['received_date'])) : '-',
                    $wr['concern_code'] ?? '-',
                ]);
            }
            fputcsv($out, []);
        }

        // 23. Fixed Assets Register
        if (!empty($data['assets'])) {
            fputcsv($out, ['=== 23. FIXED ASSETS & MACHINERY REGISTER ===']);
            fputcsv($out, ['Asset Code', 'Asset Name', 'Category', 'Brand', 'Model Number', 'Serial Number', 'Location Type', 'Purchase Date', 'Purchase Cost (INR)', 'Current Book Value (INR)', 'Salvage Value (INR)', 'Useful Life (Years)', 'Vendor Name', 'Invoice #', 'Condition', 'Status', 'Concern']);
            foreach ($data['assets'] as $ast) {
                fputcsv($out, [
                    $ast['asset_code'],
                    $ast['name'],
                    $ast['category_name'] ?: 'General Equipment',
                    $ast['brand'] ?: '-',
                    $ast['model_number'] ?: '-',
                    $ast['serial_number'] ?: '-',
                    $ast['location_type'] ?? 'FACTORY',
                    !empty($ast['purchase_date']) ? date('d M Y', strtotime($ast['purchase_date'])) : '-',
                    number_format((float)($ast['purchase_cost'] ?? 0), 2, '.', ''),
                    number_format((float)($ast['current_book_value'] ?? 0), 2, '.', ''),
                    number_format((float)($ast['salvage_value'] ?? 0), 2, '.', ''),
                    (int)($ast['useful_life_years'] ?? 0),
                    $ast['vendor_name'] ?: '-',
                    $ast['invoice_number'] ?: '-',
                    $ast['condition_state'] ?? 'GOOD',
                    $ast['status'] ?? 'OPERATIONAL',
                    $ast['concern_code'] ?? '-',
                ]);
            }
            fputcsv($out, []);
        }

        // 24. Asset Maintenance & Service Logs
        if (!empty($data['asset_maintenance'])) {
            fputcsv($out, ['=== 24. ASSET MAINTENANCE & SERVICE LOGS ===']);
            fputcsv($out, ['Service Log #', 'Asset Code', 'Asset Name', 'Service Type', 'Issue Description', 'Action Taken', 'Serviced By Vendor', 'Technician Name', 'Parts Replaced', 'Service Cost (INR)', 'Downtime Hours', 'Next Due Date', 'Status', 'Service Date', 'Concern']);
            foreach ($data['asset_maintenance'] as $am) {
                fputcsv($out, [
                    $am['log_number'],
                    $am['asset_code'],
                    $am['asset_name'],
                    $am['service_type'] ?? 'PREVENTIVE',
                    $am['issue_description'] ?: '-',
                    $am['action_taken'] ?: '-',
                    $am['serviced_by_vendor'] ?: '-',
                    $am['technician_name'] ?: '-',
                    $am['parts_replaced'] ?: '-',
                    number_format((float)($am['service_cost'] ?? 0), 2, '.', ''),
                    number_format((float)($am['downtime_hours'] ?? 0), 2, '.', ''),
                    !empty($am['next_due_date']) ? date('d M Y', strtotime($am['next_due_date'])) : '-',
                    $am['status'] ?? 'COMPLETED',
                    !empty($am['service_date']) ? date('d M Y', strtotime($am['service_date'])) : '-',
                    $am['concern_code'] ?? '-',
                ]);
            }
            fputcsv($out, []);
        }

        fclose($out);
        exit;
    }
}
