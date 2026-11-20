# Selected QR Posting and QC Implementation Plan

## Status

Planning document only. No application code, database migration, or live database has been changed for this plan.

## Goal

Allow a user to select individual generated QR labels and move only those selected, eligible items into Purchased Stock. Show the posted state on each QR item so the remaining labels can be posted later without duplicating inventory.

## Confirmed decisions

- Selection applies across the QR Generation module's source areas; eligibility is determined by the QR source and its stock/QC workflow, not by whether the batch is labeled Manual or Catalog.
- Production Product QR items stay on the existing QC → Finished Goods workflow. They must not bypass QC to enter Purchased Stock.
- Only selected, eligible QR items are posted. Already-posted, rejected, or otherwise ineligible QR items cannot be selected again.

## Current behavior and gaps

- The QR batch detail page offers one batch-level `Add to Purchased Stock` action.
- `QrGenerationService::addToStock()` increments stock using the batch quantity and records `stock_added` on the whole generation.
- `qr_generation_items` has no per-QR stock-posting state, so the application cannot safely distinguish posted labels from remaining labels.
- The batch-level action marks the purchase line inwarded and the GRN stocked, even though a partial QR selection requires partial receipt state.
- QC results are represented mainly as quantities on `qc_batches` and `qc_items`; that is insufficient to identify which individual QR labels passed.
- The QR detail page says Fabric Rolls must enter stock through QC, while the current posting service permits the Fabric source. The implementation must resolve this discrepancy and enforce the approved QC rule consistently.

## Proposed user experience

### QR batch detail page

1. Show an item-level status on every QR card:
   - `Pending QC` or `QC Passed` where QC applies.
   - `Ready to post` for items eligible for direct Purchased Stock posting.
   - `Moved to Purchased Stock` for posted items.
   - `Rework` or `Rejected` for QC outcomes that cannot be posted.
2. Provide checkboxes only for items eligible to post. Posted and rejected items remain visible but disabled.
3. Add `Select all eligible` and `Clear selection` controls, plus a live selected count.
4. Replace the batch-wide button with `Move selected to Purchased Stock`.
5. Before posting, show a confirmation with batch/source, selected QR codes, count, and destination warehouse.
6. After posting, show `X of Y moved`; keep any remaining eligible labels selectable. Provide a link to Purchased Stock.
7. Leave print-all behavior unchanged; printing must not post inventory.

### QC inspection page

1. For sources that require QC, let the inspector scan or select QR codes associated with the current QC batch.
2. Record an item-level outcome (`Pending`, `Passed`, `Rework`, or `Rejected`) and inspector/time; retain a history when a reworked item is inspected again.
3. Display counts by result and compare them with the existing inspected/passed/rework/rejected quantities by variant. Do not allow the individual QR outcomes to exceed or contradict those QC totals.
4. Provide a separate, explicit action to post only selected `Passed` QR items to their permitted inventory destination. Do not post pending, rework, or rejected items.
5. Production Product QRs continue to enter Finished Goods only through the existing QC completion path. They do not get a Purchased Stock action.

## Eligibility policy

| QR source | Purchased Stock action | QC requirement / destination |
|---|---|---|
| Third-Party Product | Allow selected, not-yet-posted QRs when the batch is linked to a valid purchase item and mapped/resolvable to a stock variant. | No new QC bypass; follow the existing purchase receiving rules. |
| Raw Material | Allow selected, not-yet-posted QRs when the batch is linked to a valid purchase item and the stock unit is resolvable. | Follow the existing purchase receiving rules. |
| Fabric Roll | Do not permit direct posting from the QR detail page. | Recommended rule: only QC-approved selected rolls may post through the QC workflow. Confirm this rule before implementation because the existing page warning and service currently disagree. |
| Production Product | Do not permit Purchased Stock posting. | Preserve QC → Finished Goods. Only QC-passed pieces enter Finished Goods. |
| Unlinked/manual/legacy batch | Do not post unless the service can validate a source purchase item and destination stock identity. | Show an explicit reason, not a success-shaped fallback. |

The policy must use the persisted `qr_source` and purchase/QC links, not `generation_type` or a display label. This avoids treating a batch marked “Manual Custom” as unlinked when it actually belongs to a supported purchase source.

## Data and migration

### Per-QR Purchased Stock state

Add to `qr_generation_items`:

- `stock_added` (`TINYINT(1) NOT NULL DEFAULT 0`)
- `stock_added_at` (`DATETIME NULL`)
- `stock_added_by` (`INT UNSIGNED NULL`)

Backfill `stock_added = 1` for items whose existing parent `qr_generations.stock_added = 1`. This prevents already-posted legacy batches from being posted twice.

Continue using the generation-level `stock_added` only as a derived compatibility summary: set it to `1` when every postable QR in that batch is posted; otherwise keep it `0`. Item state is authoritative for selection and duplicate prevention.

### Per-QR QC outcome

Add a QC-to-QR association that can retain reinspection history, rather than relying only on a batch quantity. Prefer a dedicated table such as `qr_generation_item_qc_history` with:

- QR generation item ID
- QC batch ID
- result (`PASSED`, `REWORK`, or `REJECTED`)
- inspector/user ID and timestamp
- optional notes

Add foreign keys/indexes consistent with the existing schema conventions. The current QC summary tables remain the aggregate source for reporting; the new association identifies which labels those counts represent.

Update both the fresh-install schema and an ordered migration. Do not run the migration on a live database without explicit deployment approval.

## Posting logic

Implement a selected-item posting service operation that accepts a generation ID, a list of QR item IDs, and the acting user.

1. Validate a non-empty selection and reject malformed IDs.
2. Load and lock the generation, purchase line, and selected QR rows inside a transaction.
3. Confirm each selected QR belongs to the requested generation, is active, is not already posted, and is eligible for that source and QC state.
4. Confirm the parent batch is in the user's authorized concern and is linked to the expected purchase/QC source.
5. Resolve a valid stock variant per QR item. Where existing Third-Party mapping requires variant creation, perform it once and persist the resolved variant; do not silently skip stock updates if resolution fails.
6. Group the selected labels by variant and stock unit. Increase inventory by the selected count in the existing warehouse stock service, with a posting reference that makes the operation auditable and traceable to selected QR IDs.
7. Mark exactly those QR item rows as posted, with actor and timestamp. Recalculate the generation-level summary.
8. Increase the purchase line's `inwarded_qty` by the selected quantity. Mark the line/purchase/GRN fully stocked only when their accepted/ordered quantities are fully posted; preserve a partial state otherwise. Never mark an entire line or GRN complete after a subset was posted.
9. Write an audit event containing source, selected QR IDs/codes, quantity by variant, destination warehouse, and acting user.
10. Commit all stock and QR state changes atomically. Roll back and surface the error if any validation or stock operation fails.

Duplicate form submissions and concurrent users must not double-increase stock. Row locks plus conditional updates (`stock_added = 0`) must make the operation idempotent.

## QC logic

1. Match each QR item to its QC batch and variant; reject QRs from another batch or concern.
2. Require an explicit QC outcome before any QC-gated stock posting.
3. Permit only `PASSED` QRs for posting. Keep `REWORK` unavailable until reinspected and `REJECTED` permanently unavailable unless an authorized correction workflow exists.
4. Ensure the sum of item-level outcomes agrees with the existing QC passed/rework/rejected quantities before completing QC or posting inventory.
5. For Production Product batches, preserve the current QC completion and Finished Goods posting path. The new Purchased Stock service must reject this source server-side even if a forged request reaches it.
6. For Fabric Rolls, use the same explicit QC gate on the QR detail and QC pages. Do not expose a direct posting button until the agreed Fabric policy is confirmed.

## Files expected to change during implementation

- `views/production/qr_generation/view.php` — per-label status, selection controls, selected-count confirmation, and partial-post status.
- `app/Controllers/QrGenerationController.php` — new POST handler, input validation, redirects/messages, and the existing batch view data.
- `app/Services/QrGenerationService.php` — per-item eligibility, transaction-safe posting, summary recalculation, and item-level QC data access.
- `routes/web.php` — selected-item POST route protected by the existing QR-generation permission.
- Existing QC inspection controller and its actual view template — QR scan/selection outcomes and QC-gated post action. Locate the rendered template before editing; QC logic currently resides in `ProductionController`.
- `database/migrations/2026_11_20_add_qr_item_stock_posting_and_qc.sql` (or the next unused migration sequence) — item-level stock/QC tracking and safe legacy backfill.
- `database/schema.sql` — fresh-install schema additions.

Do not modify Purchased Stock unrelated features or the existing print layout.

## Validation plan

### Service tests

- Posting one selected QR adds exactly one unit and marks only that QR.
- Posting a subset leaves the other QR items pending and the batch summary incomplete.
- Posting the remaining eligible QR items completes the batch summary.
- Reposting an already-posted QR, selecting a QR from another batch, or submitting an empty/invalid list fails without changing stock.
- A simulated stock-service failure rolls back both inventory and QR posting state.
- Partial posting updates inwarded quantity without marking the purchase line or GRN fully stocked.
- Legacy batch-level `stock_added = 1` is backfilled and cannot be duplicated.
- Rework/rejected/pending QC items cannot be posted.
- Production Product QRs are rejected by the Purchased Stock endpoint and remain on the QC → Finished Goods path.
- Concern mismatch or unauthorized batch access is rejected.

### UI checks

- All eligible QR-generation tabs show item-level selection and posted status.
- Select-all affects only eligible pending labels; posted/rejected labels remain disabled.
- Confirmation displays the exact selected count/codes.
- After partial posting, remaining eligible labels are still selectable and the posted count is accurate.
- Production batches retain their QC controls and never show Purchased Stock selection.
- Fabric QR detail never permits direct posting; the final QC-only behavior follows the confirmed policy.

### Rollout

1. Review and approve the Fabric QC rule.
2. Apply the migration in a staging database and verify the legacy backfill counts.
3. Run focused service/controller tests and inspect stock ledger/inward/GRN results for both partial and complete posting.
4. Deploy code and migration together.
5. Verify one partial post and one completion against a staging purchase; do not test by altering production inventory.
