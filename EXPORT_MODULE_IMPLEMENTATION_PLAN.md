# Existing Export Module Enhancement Plan

## Status

Planning only. No application code or database has been changed for this plan.

## Objective

Extend the existing Data Export Center without replacing it. Preserve its current routes, filters, CSV streaming, printable report, and quick exports while adding reliable record-level and variant-wise quantity exports.

## Existing implementation

- `ExportController::index()` defines 27 export modules and supplies concerns, facilities, warehouses, and date defaults to `views/export/index.php`.
- `views/export/index.php` currently offers month or custom dates, concern selection, warehouse selection, module checkboxes, and CSV or printable-summary output.
- `ExportController::download()` validates module names against an allow-list, retrieves data through `ExportService::getReportData()`, then calls the CSV writer or printable report view.
- `ExportService::getReportData()` fetches module datasets and always calculates executive financial and operational summary data.
- `ExportService::streamCsv()` emits a single sectioned CSV with UTF-8 BOM, summary tables, and module tables.
- Printable output uses `views/production/reports/print.php`; there is no dedicated `views/export/print.php`.
- Quick routes are already present for current-month CSV, Direct Bills CSV, and Normal/Standard Bills CSV.

## Current gaps to address

1. Many module exports are header/batch summaries rather than item or variant rows.
2. The Variants export reports one row per variant and an available quantity aggregated across all `AVAILABLE` stock balances; it does not apply the selected concern or warehouse filter and does not show location/state quantities.
3. Inventory exports stock-balance rows by location and state, but the selected warehouse filter is not applied. Design and some other variant attributes are missing.
4. Stock Ledger is already transaction-level and variant-aware, with positive/negative movement quantities and warehouse filtering. Preserve this detail and make its quantity definitions explicit.
5. Normal and Direct Billing exports are invoice summaries with a tag-count quantity. They do not expose invoice item rows, variant/SKU, or per-variant sold quantity.
6. Purchases exports PO summaries. Raw Materials and Third-Party exports contain purchase-line quantities, but the mappings/variant identity and received/inwarded quantity detail need to be made consistent.
7. GRN, QC, stitching, stock requests, returns, and other operational modules mostly export batch totals without item-level variant quantities.
8. QR Generation exports the batch quantity/status only; it does not expose per-variant or per-QR counts.
9. The UI's concern-to-module mapping is client-side. Download requests must enforce concern/module access server-side.
10. Clearing all module checkboxes currently falls back to exporting every module. Invalid custom date ranges and unknown formats also need explicit validation.
11. The period is applied inconsistently: inventory and variant stock are snapshots, not historical date-range data. This distinction is not clear in the UI or file.
12. The warehouse filter currently applies to Outward and Stock Ledger only, despite the broader export configuration.

## Recommended export options

Keep the current form and extend it with a compact **Data Detail** selector:

- **Summary** — existing batch/invoice/aggregate rows.
- **Detailed records** — one row per source record or line item where the database stores that detail.
- **Variant-wise quantities** — one row per product variant and relevant location/status, with explicit quantity and unit columns.

The selected detail level applies to modules that support it. For modules with no variant concept (for example Expenses or Assets), retain their detailed record rows and display a clear “variant breakdown not applicable” note rather than fabricating a variant.

Keep the current output formats:

- **CSV** — the primary data interchange output, retaining the existing sectioned report and numeric values.
- **Printable Summary** — retain KPI cards and summary tables; include detailed/variant tables only when requested and when result volume is suitable for a printable report.

Retain the existing date, concern, warehouse, module, current-month, Direct Bills, and Normal Bills options. Add a visible export summary before submission showing selected period, concern scope, warehouse scope, modules, and detail level.

### Quantity definitions

Every quantity column must identify its meaning and unit. Do not use a generic “Qty” column where the source has distinct measures:

- Ordered, received, accepted, rejected, inwarded, and remaining purchase quantities.
- Planned, cut, stitched, passed, rework, rejected, packed, dispatched, sold, returned, requested, fulfilled, repaired, and scrapped pieces.
- On-hand balance by variant, warehouse/location, and stock state.
- Stock movement in, out, signed net movement, and transaction balance.
- Roll count, meters, weight in kg, and monetary values as separate measures.

For snapshot reports, label the data as **current as of export time**; do not imply that current inventory is a historical balance for the selected date period.

## Module-by-module detail plan

| Existing module | Preserve current export | Add detailed / variant-wise rows |
|---|---|---|
| `purchases` | PO/date/supplier/invoice totals | PO line rows with item type/name, linked product/variant/SKU where available, unit, ordered/received/accepted/rejected/inwarded/remaining quantity, unit price, tax, line total, and concern. Keep PO header as its own summary section. |
| `rolls` | Roll serial, supplier, material, color, weight, cost | Keep one row per roll; include purchase/GRN, warehouse, QR/serial, length/meters when stored, received/accepted/rejected quantities, status, and separate kg/meters/value fields. |
| `raw_materials` | Purchase-line name, unit, quantity, cost, tax | Add purchase item ID, mapped product/variant/SKU when one exists, QR/start code, ordered and inwarded quantities, remaining quantity, and stock location where available. |
| `third_party_products` | Vendor product and mapping summary | Add mapped product ID, variant ID/code/SKU, size/color/design, QR identity, ordered/inwarded/remaining quantity, unit cost, MRP, selling price, tax, and mapping state. |
| `suppliers` | Contact and purchase count/value | Preserve supplier-level summary; detailed level adds one row per purchase or purchase line, with quantities and values. Do not repeat supplier totals on every item row without labeling them as repeated aggregates. |
| `goods_inward` | GRN header, received/accepted/rejected totals | Add GRN line rows with purchase item, product/variant/SKU, received/accepted/rejected/inwarded/remaining quantities, unit, warehouse, and supplier. |
| `programs` | Program target/completed status | Add product/style and size/color/variant plan rows where program items store them; show planned, completed, and remaining quantities separately. |
| `cuttings` | Cutting batch totals, roll weight, wastage | Add cutting-item/variant rows where recorded: size/color, planned quantity, cut quantity, rejects, wastage quantity/unit, and roll reference. |
| `stitching` | Batch target, stitched, rework, rejected totals | Add stitching-item/variant/SKU rows with target, stitched, rework, rejected, and remaining quantities. |
| `qc` | Inspection batch totals and yield | Add QC item rows by variant/SKU with inspected, passed, rework, rejected, yield, defect/result details, and QC batch/program references. Keep percentages derived from the exported counts. |
| `damages` | Damage report totals and disposition | Add one row per damaged roll/product/variant item with damaged quantity, weight/unit, loss value, resolution, supplier, and source reference. |
| `catalog` | Parent product/style and active variant count | Keep parent product rows. Detailed option adds child variant rows or directs users to the Variants module; avoid multiplying parent totals in the catalog table. |
| `variants` | One row per SKU with prices and current available quantity | Make variant ID the stable key. Add design, concern, active state, quantity by warehouse/location and stock state, plus an explicitly labeled all-location total. Apply concern and warehouse filters. |
| `qr_generations` | Batch code, type, source, quantity, stock status | Add QR item rows with QR code/sequence, source purchase or production link, product/variant/SKU, size/color/design, MRP/SP, item status, and batch code. Batch quantity must reconcile to item count. |
| `batches` | Packing batch, boxes, pieces, cost, destination | Add packed item/variant rows where the batch detail stores them, including packed quantity per SKU and carton allocation where available. |
| `outward` | Dispatch header, cartons, total pieces, carrier data | Add dispatch line rows with variant/SKU, dispatched quantity, carton quantity, source batch, and destination warehouse. |
| `inventory` | Current positive stock balances | Export by variant + location + stock state. Add variant ID, product/style, SKU, size/color/design, quantity, unit, warehouse/location, concern, prices, and snapshot timestamp. Apply the warehouse filter here. |
| `stock_ledger` | Existing transaction-level stock history | Preserve one row per stock transaction; include variant identity, location, stock state, movement type, in/out/signed quantity, balance after, unit, reference, source/destination, actor, and concern. Add opening/closing summaries only if they can be computed without double-counting. |
| `stock_requests` | Request-level requested/fulfilled totals | Add request item rows with product/variant/SKU, requested, dispatched, received, fulfilled, and remaining quantities, plus unit, warehouse, priority, and status. |
| `warehouse_rework` | Rework record quantity/repaired/scrapped totals | Add per product/variant/SKU rows with received, repaired, scrapped, and remaining quantities, warehouse, and defect/resolution. |
| `billing` | Standard invoice header totals | Add invoice item/tag rows with bill number/date, product/variant/SKU, QR/tag code when present, sold quantity, unit price, discount, tax, line total, payment status, and concern. Exclude Direct Bills exactly as today. |
| `direct_billing` | Separate Direct Bill totals | Add the same line-level variant and quantity fields as Standard Billing, while retaining the Direct Bill flag and separate export route/file. Do not mix totals with Standard Billing. |
| `returns` | Return header and total refunded quantity/value | Add return item rows with original invoice, product/variant/SKU, returned quantity, condition/disposition, refund/tax reversal, restocked/damaged result, and unit. |
| `wastage` | Sale number, type, buyer, weight/quantity, rate, total | Keep separate kg and piece/unit quantities where applicable; add material/source reference and item rows if the source tables contain them. Do not label every stitching quantity as kg unless its unit is kg. |
| `expenses` | Expense item, category, paid/pending totals | Keep one row per expense with total, paid, pending, status, date, facility, and concern. Quantity/variant breakdown is not applicable unless an expense record actually stores item quantities. |
| `assets` | Asset register and value/status | Keep one row per asset, including asset code, serial/model, quantity only if the schema stores it, cost/book value, location, and concern. |
| `asset_maintenance` | Maintenance/service event and costs | Keep one row per service log. Add parts and their quantities only from stored maintenance detail; do not infer inventory consumption from free-text parts descriptions. |

## Variant-wise quantity contract

For every report that supports variants, use stable identifiers and a consistent set of columns:

- Concern ID/code/name.
- Source document type, ID/number, and date.
- Product/style ID/name/code.
- Variant ID, variant code, SKU, size, color, and design.
- QR/barcode or serial when stored and relevant.
- Warehouse/location ID/name and stock state when relevant.
- Quantity, unit, and quantity meaning (for example `sold_qty`, `passed_qty`, `on_hand_qty`).
- Relevant unit price, MRP, selling price, cost, and line total.

The output row grain must be documented per module. For example, billing detail is one row per bill item (or per bill item/tag when each QR is a sold unit); inventory detail is one row per variant/location/state. If a join is one-to-many, aggregate the child table to the intended grain before joining so quantities and totals are not multiplied.

## Implementation approach: adapt existing files

### 1. Centralize module definitions

Keep `ExportController::index()` as the module source initially, but extract the module allow-list/metadata into one definition used by both the page and download validation. This prevents a checkbox from being visible but rejected—or a hidden module from being accepted.

### 2. Add server-validated options

In `ExportController::download()`:

- Allow-list `format`, detail level, and selected module values.
- Validate real `YYYY-MM-DD` dates, enforce `date_from <= date_to`, and return a visible error for invalid values.
- Treat no selected modules as a validation error, or require an explicit “All modules” control; never silently change “none” into “all”.
- Validate concern IDs against the user's allowed concern scope. Only authorized global users may request consolidated/all-concern data.
- Validate warehouse IDs against active warehouses in the allowed concern scope.
- Validate that each requested module is enabled for the selected concern scope on the server; JavaScript mapping is only a convenience.
- Keep current-month, Normal Bills, and Direct Bills routes but route them through the same validated request path.

### 3. Extend the existing service

Keep `ExportService::getReportData()` and its SQL-based, parameterized approach. Add a stable module dataset contract and module-aware detail level. Split datasets into header/detail collections only where needed; do not replace the export service with a new subsystem.

For each detailed query:

- Select the correct event date for the selected period.
- Apply concern filters to the owning record, not an unrelated joined table.
- Apply warehouse filters to inventory, ledger, outward, and other location-bound records.
- Parameterize values; generate placeholders for variable-length ID filters.
- Pre-aggregate one-to-many relationships before joining header totals.
- Keep snapshot modules (Variants/Inventory) separate from date-range transactions and label them as current snapshots.
- Avoid loading full datasets when a selected module is not requested. Make executive summary inclusion explicit and avoid showing unrelated summary data for a detail-only export.

### 4. Extend CSV serialization

Keep `ExportService::streamCsv()` as the primary writer. Add stable module-specific headers and rows for detail and variant modes, preserving:

- UTF-8 BOM and `fputcsv()` quoting.
- Decimal numeric values (not thousands-formatted strings) for spreadsheet math.
- Separate sections and clear row-grain labels.
- Stable columns even when a selected module returns zero records.
- Formula-injection-safe text handling for untrusted supplier/customer/free-text fields.
- Download filenames derived from a safe allow-listed prefix, period, and detail level.

### 5. Extend the existing print report

Keep `views/production/reports/print.php` as the printable destination. Pass the selected detail level, modules, scope labels, and snapshot timestamp explicitly.

- Summary mode retains KPI cards and summary tables.
- Detailed mode adds only the selected module tables.
- Variant mode adds compact variant/quantity tables for supported modules.
- For large results, display a clear message directing users to CSV rather than rendering an unbounded print page.
- Show the exact concern, warehouse, date basis, and detail level in the report header.

### 6. Update the existing export page

Update `views/export/index.php` without changing the existing module groups:

- Add Summary / Detailed / Variant-wise quantity selection with help text.
- Explain that selected dates filter transactions, while inventory/variant stock is a current snapshot.
- Make warehouse filter help match all modules that actually apply it.
- Add select-all and clear behavior that cannot silently turn an empty selection into a full export.
- Keep the current quick buttons and module descriptions; add variant/quantity details to relevant module descriptions.
- Show accessible validation messages and a selection summary before download.

## Target files

- `app/Controllers/ExportController.php` — module definitions, request validation, scope enforcement, selected detail level.
- `app/Services/ExportService.php` — module queries, variant and quantity datasets, CSV headers/serialization.
- `views/export/index.php` — existing export options and detail-level UI.
- `views/production/reports/print.php` — printable selected detail tables and scope metadata.
- `routes/web.php` — preserve existing routes; add a route only if a real async preview is later approved.
- Tests covering export filters, row grain, numeric quantities, and concern/warehouse boundaries.
- Database migration only if an existing source lacks a needed relationship or quantity field. Prefer joining existing data; do not add duplicate storage solely for reporting.

## Delivery phases

1. **Inventory the current field contract:** document each query's source tables, date field, concern field, warehouse field, and row grain.
2. **Fix filter semantics:** validate date/module/format inputs and enforce server-side concern and warehouse scope.
3. **Add reliable inventory snapshots:** correct Variants and Inventory scoping; use variant/location/state quantities and snapshot labels.
4. **Add procurement and manufacturing detail:** purchases, GRN, raw/third-party product variants, programs, cutting, stitching, QC, damage, and QR rows.
5. **Add sales and logistics detail:** bill lines, direct bill lines, return items, stock requests, packing, outward, and rework variant quantities.
6. **Serialize both outputs:** keep CSV stable and add bounded detail tables to the existing print view.
7. **Finish UI and quick exports:** add detail selection, preview/scope summary, and preserve the quick CSV routes.
8. **Validate in staging:** compare exported quantities to their source screens and SQL totals before deployment.

## Validation checklist

- Each selected module has the same documented row grain in CSV and print output.
- Variant quantities reconcile to the source records for one concern, multiple concerns, and all authorized concerns.
- Inventory quantity reconciles by variant + warehouse + stock state; warehouse filters exclude unrelated locations.
- Ledger in/out/net quantities reconcile to stock transactions without join multiplication.
- Bill detail quantities reconcile to bill item/tag records; Standard and Direct Bills remain separate.
- Purchase, GRN, QC, stitching, and outward quantities reconcile to their item-level source totals.
- Snapshot modules are labeled current and are not incorrectly filtered as historical transaction data.
- Empty module selection, invalid dates, reversed date ranges, invalid formats, unauthorized concerns, and unauthorized warehouses return explicit errors.
- CSV numerics remain numeric in Excel; commas, quotes, Unicode, formula-like text, and empty datasets are handled safely.
- Quick current-month, Normal Bills, and Direct Bills exports remain functional and apply the same authorization rules.
- Printable output remains readable and directs oversized detailed exports to CSV.

## Scope note

This plan does not propose a new database export system, XLSX dependency, or replacement reporting framework. It reuses the current Export Center, `ExportService`, CSV writer, print view, module filters, and quick-export routes.
