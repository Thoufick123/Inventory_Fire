-- Migration: Add Performance Indexes for Data Exports and Date Range Reports
-- Created: 2026-11-18

ALTER TABLE `seller_rolls`
  ADD KEY `idx_seller_rolls_created_at` (`created_at`);

ALTER TABLE `production_programs`
  ADD KEY `idx_production_programs_created_at` (`created_at`);

ALTER TABLE `cutting_batches`
  ADD KEY `idx_cutting_batches_created_at` (`created_at`);

ALTER TABLE `stitching_batches`
  ADD KEY `idx_stitching_batches_created_at` (`created_at`);

ALTER TABLE `qc_batches`
  ADD KEY `idx_qc_batches_created_at` (`created_at`);

ALTER TABLE `production_batches`
  ADD KEY `idx_production_batches_packed_created` (`packed_date`, `created_at`);

ALTER TABLE `production_outwards`
  ADD KEY `idx_production_outwards_dispatch_created` (`dispatch_date`, `created_at`);

ALTER TABLE `production_expenses`
  ADD KEY `idx_production_expenses_date` (`expense_date`);

ALTER TABLE `wastage_sales`
  ADD KEY `idx_wastage_sales_date` (`sale_date`);

ALTER TABLE `stitching_wastage_sales`
  ADD KEY `idx_stitching_wastage_sales_date` (`sale_date`);

ALTER TABLE `production_stock_requests`
  ADD KEY `idx_production_stock_requests_created_at` (`created_at`);
