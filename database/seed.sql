-- ============================================================
-- GRM INVENTORY SOFTWARE — MASTER SEED DATA (ADMIN ONLY)
-- Generated: 2026-11-17 12:07:41
-- Contains: Admin user, system roles, granular permissions,
--           core organization structure & system settings.
-- All dummy/sample products, purchases, stock & data removed.
-- ============================================================

SET FOREIGN_KEY_CHECKS = 0;

-- ------------------------------------------------------------
-- 1. Roles Seed
-- ------------------------------------------------------------
DELETE FROM `roles`;
INSERT INTO `roles` (`id`, `name`, `slug`, `description`, `is_active`, `is_system`, `created_at`) VALUES
(1, 'Super Admin', 'super-admin', 'Full system access and user administration', 1, 1, NOW()),
(2, 'Admin', 'admin', 'Business administration and managerial approvals', 1, 1, NOW()),
(3, 'Production Manager', 'production-manager', 'Production planning and batch tracking', 1, 1, NOW()),
(4, 'Production Staff', 'production-staff', 'Floor operations and cutting entries', 1, 1, NOW()),
(5, 'Warehouse Manager', 'warehouse-manager', 'Warehouse receiving and allocations', 1, 1, NOW()),
(6, 'Warehouse Staff', 'warehouse-staff', 'Picking, packing, and dispatch', 1, 1, NOW()),
(7, 'Shop Manager', 'shop-manager', 'Retail store operations and point of sale', 1, 1, NOW()),
(8, 'Billing Staff', 'billing-staff', 'Invoicing, POS checkout and receipts', 1, 1, NOW()),
(9, 'Accountant', 'accountant', 'Financial registers, expenses, and ledgers', 1, 1, NOW()),
(10, 'Report Viewer', 'report-viewer', 'Read-only access to analytics and reports', 1, 1, NOW());

-- ------------------------------------------------------------
-- 2. Organization Foundations
-- ------------------------------------------------------------
DELETE FROM `production_units`;
INSERT INTO `production_units` (`id`, `name`, `code`, `address`, `location`, `capacity_per_day`, `is_active`, `created_at`) VALUES
(1, 'Production Factory - Unit 1', 'PU-01', 'Main Industrial Complex, Tirupur', 'Tirupur', 2500, 1, NOW());

DELETE FROM `warehouses`;
INSERT INTO `warehouses` (`id`, `name`, `code`, `address`, `is_active`, `created_at`) VALUES
(1, 'Central Warehouse', 'WH-01', 'Central Logistics Hub, Tirupur', 1, NOW());

DELETE FROM `shops`;
INSERT INTO `shops` (`id`, `name`, `code`, `address`, `is_active`, `created_at`) VALUES
(1, 'Factory Outlet / Showroom', 'SH-01', 'Factory Front Showroom, Tirupur', 1, NOW());

DELETE FROM `sales_channels`;
INSERT INTO `sales_channels` (`id`, `name`, `code`, `is_active`, `created_at`) VALUES
(1, 'Direct Store POS', 'POS', 1, NOW()),
(2, 'WhatsApp Catalog Sales', 'WHATSAPP', 1, NOW()),
(3, 'B2B Wholesale Channel', 'WHOLESALE', 1, NOW());

-- Default business concern (master record for scoping/branding)
INSERT IGNORE INTO `concerns` (`id`, `name`, `code`, `invoice_prefix`, `po_prefix`, `dispatch_prefix`, `is_active`, `created_at`) VALUES
(1, 'GRM Apparels', 'CN-01', 'INV', 'PO', 'DSP', 1, NOW());

-- ------------------------------------------------------------
-- 3. Admin User Seed (Credentials: admin / admin123)
-- ------------------------------------------------------------
DELETE FROM `users`;
INSERT INTO `users` (`id`, `username`, `emp_id`, `email`, `password_hash`, `full_name`, `phone`, `role_id`, `department`, `staff_role`, `production_unit_id`, `warehouse_id`, `shop_id`, `reports_access`, `is_active`, `must_change_password`, `created_at`) VALUES
(1, 'admin', 'EMP-001', 'admin@grm.com', '$2y$10$WXeV9abEWzjWySfggVzhTOp4j7pzF7UuFNUJ1lrLbWe9NDL7Cz7UC', 'System Administrator', '9876543210', 1, 'ADMINISTRATION', 'SUPER_ADMIN', 1, 1, 1, 'ALL', 1, 0, NOW());

-- ------------------------------------------------------------
-- 4. Permissions Seed (306 Granular Permissions)
-- ------------------------------------------------------------
DELETE FROM `permissions`;
INSERT INTO `permissions` (`module`, `action`, `description`, `slug`, `created_at`) VALUES
('allocation', 'approve', 'Approve Stock Allocation Demand', 'allocation.approve', NOW()),
('allocation', 'create', 'Create Store Allocation Request', 'allocation.create', NOW()),
('allocation', 'dispatch', 'Dispatch Allocated Goods to Store', 'allocation.dispatch', NOW()),
('allocation', 'pack', 'Execute Carton Packing & Sealing', 'allocation.pack', NOW()),
('allocation', 'pick', 'Execute Floor Box Picking', 'allocation.pick', NOW()),
('allocation', 'view', 'View Store Allocations & Picking Sheets', 'allocation.view', NOW()),
('allocation_outward', 'create', 'Create Outward Transport Manifest', 'allocation_outward.create', NOW()),
('allocation_outward', 'deliver', 'Mark Outward Shipment as Delivered', 'allocation_outward.deliver', NOW()),
('allocation_outward', 'gatepass', 'Print Transport Gatepass', 'allocation_outward.gatepass', NOW()),
('allocation_outward', 'view', 'View Warehouse Outward Manifests', 'allocation_outward.view', NOW()),
('asset_bulk_labels', 'print', 'Print Bulk Asset Barcode QR Stickers', 'asset_bulk_labels.print', NOW()),
('asset_bulk_labels', 'view', 'View Bulk Asset QR Label Studio', 'asset_bulk_labels.view', NOW()),
('asset_maintenance', 'create', 'Log Maintenance Ticket / Breakdown Service', 'asset_maintenance.create', NOW()),
('asset_maintenance', 'edit', 'Edit Service Details & Invoices', 'asset_maintenance.edit', NOW()),
('asset_maintenance', 'view', 'View Asset Maintenance & Repair Logs', 'asset_maintenance.view', NOW()),
('asset_scanner', 'scan', 'Execute Hardware Scan & Physical Audit', 'asset_scanner.scan', NOW()),
('asset_scanner', 'view', 'Access QR Scanner Station', 'asset_scanner.view', NOW()),
('asset_transfers', 'approve', 'Approve & Receive Asset Relocation', 'asset_transfers.approve', NOW()),
('asset_transfers', 'create', 'Create Asset Relocation Manifest', 'asset_transfers.create', NOW()),
('asset_transfers', 'view', 'View Inter-Unit Asset Transfers', 'asset_transfers.view', NOW()),
('assets', 'create', 'Register New Machinery / Asset Item', 'assets.create', NOW()),
('assets', 'delete', 'Delete Asset Entry', 'assets.delete', NOW()),
('assets', 'edit', 'Edit Asset Details, Location & Value', 'assets.edit', NOW()),
('assets', 'print', 'Print asset labels', 'assets.print', NOW()),
('assets', 'view', 'View Asset Registry & Equipment Valuation', 'assets.view', NOW()),
('audit', 'export', 'Export Audit Logs to CSV', 'audit.export', NOW()),
('audit', 'view', 'View Audit Trail & System Activity Logs', 'audit.view', NOW()),
('backup', 'create', 'Generate Manual Database Backup Snapshot', 'backup.create', NOW()),
('backup', 'delete', 'Delete Database Backup File', 'backup.delete', NOW()),
('backup', 'download', 'Download Backup Archive (ZIP/SQL)', 'backup.download', NOW()),
('backup', 'restore', 'Restore Database from Snapshot File', 'backup.restore', NOW()),
('backup', 'view', 'View Database Snapshots & Recovery Hub', 'backup.view', NOW()),
('batches', 'approve', 'Approve Finished Goods Packaging Batch', 'batches.approve', NOW()),
('batches', 'create', 'Create Packaging Batch & Box Consolidations', 'batches.create', NOW()),
('batches', 'print', 'Print Packaging Batch QR Master Label', 'batches.print', NOW()),
('batches', 'view', 'View Packaging Batches', 'batches.view', NOW()),
('catalog', 'create', 'Create Style Catalog & BOM', 'catalog.create', NOW()),
('catalog', 'delete', 'Delete Catalog Item', 'catalog.delete', NOW()),
('catalog', 'edit', 'Edit Style Catalog & Specifications', 'catalog.edit', NOW()),
('catalog', 'view', 'View Design Catalog & Style Codes', 'catalog.view', NOW()),
('customers', 'create', 'Add Customer Profile & Phone', 'customers.create', NOW()),
('customers', 'edit', 'Edit Customer Profile & Notes', 'customers.edit', NOW()),
('customers', 'view', 'View Customer Directory & 360 CRM', 'customers.view', NOW()),
('cutting', 'create', 'Create Cutting Batch & Roll Allocation', 'cutting.create', NOW()),
('cutting', 'delete', 'Delete Cutting Batch', 'cutting.delete', NOW()),
('cutting', 'edit', 'Edit Cutting Item Quantities & Bundles', 'cutting.edit', NOW()),
('cutting', 'view', 'View Cutting Batches & Lay Sheets', 'cutting.view', NOW()),
('damage', 'create', 'Log Damaged Stock / Water Defect', 'damage.create', NOW()),
('damage', 'resolve', 'Resolve & Classify Damaged Stock Action', 'damage.resolve', NOW()),
('damage', 'view', 'View Warehouse Damages & Write-offs', 'damage.view', NOW());
INSERT INTO `permissions` (`module`, `action`, `description`, `slug`, `created_at`) VALUES
('damages', 'create', 'Report Defective Fabric / Scrap', 'damages.create', NOW()),
('damages', 'delete', 'Delete Damage Record', 'damages.delete', NOW()),
('damages', 'edit', 'Edit Damage Records', 'damages.edit', NOW()),
('damages', 'view', 'View Production Fabric & Floor Damages', 'damages.view', NOW()),
('dashboard', 'view', 'View Executive Dashboard KPIs & Analytics', 'dashboard.view', NOW()),
('inventory', 'delete', 'Delete an available Production inventory QR piece', 'inventory.delete', NOW()),
('inventory', 'view', 'View Passed Finished Goods Inventory', 'inventory.view', NOW()),
('inward', 'create', 'Create Inward GRN / Cross-Check Scan', 'inward.create', NOW()),
('inward', 'print', 'Print Inward Gatepass & Labels', 'inward.print', NOW()),
('inward', 'verify', 'Verify & Confirm Inward Stock Placement', 'inward.verify', NOW()),
('inward', 'view', 'View Inward / GRN Shipments', 'inward.view', NOW()),
('master', 'create', 'Add Master Attribute (Category, Size, Color, Unit)', 'master.create', NOW()),
('master', 'delete', 'Delete Master Attribute Entry', 'master.delete', NOW()),
('master', 'edit', 'Edit Master Attribute Name / Code', 'master.edit', NOW()),
('master', 'view', 'View Master Data (Categories, Sizes, Colors)', 'master.view', NOW()),
('notifications', 'delete', 'Delete / Clear Notifications', 'notifications.delete', NOW()),
('notifications', 'mark_read', 'Mark Notifications as Read', 'notifications.mark_read', NOW()),
('notifications', 'view', 'View System Notification Feed', 'notifications.view', NOW()),
('outward', 'create', 'Create Production Dispatch Challan', 'outward.create', NOW()),
('outward', 'dispatch', 'Dispatch Transport & Generate Gatepass', 'outward.dispatch', NOW()),
('outward', 'view', 'View Production Outward Dispatches', 'outward.view', NOW()),
('pos', 'create', 'Scan & Process Customer Bill / Checkout', 'pos.create', NOW()),
('pos', 'print', 'Print Thermal Sales Invoice Receipt', 'pos.print', NOW()),
('pos', 'view', 'Access POS Billing Counter Terminal', 'pos.view', NOW()),
('production', 'view', 'View Production Overview & Plant Throughput', 'production.view', NOW()),
('production_billing', 'create', 'Create Production Bills', 'production_billing.create', NOW()),
('production_billing', 'delete', 'Delete Production Bills', 'production_billing.delete', NOW()),
('production_billing', 'edit', 'Edit Production Bills', 'production_billing.edit', NOW()),
('production_billing', 'print', 'Print Production Bills', 'production_billing.print', NOW()),
('production_billing', 'view', 'View Production Bills', 'production_billing.view', NOW()),
('production_expenses', 'create', 'Record Machinery Spare / Plant Expense', 'production_expenses.create', NOW()),
('production_expenses', 'delete', 'Delete Expense Entry', 'production_expenses.delete', NOW()),
('production_expenses', 'edit', 'Edit Production Expense Entry', 'production_expenses.edit', NOW()),
('production_expenses', 'print', 'Print Expense Voucher', 'production_expenses.print', NOW()),
('production_expenses', 'view', 'View Plant Machinery & Daily Expenses', 'production_expenses.view', NOW()),
('programs', 'approve', 'Approve & Release Production Program', 'programs.approve', NOW()),
('programs', 'create', 'Create Production Work Orders', 'programs.create', NOW()),
('programs', 'delete', 'Delete Production Program', 'programs.delete', NOW()),
('programs', 'edit', 'Edit Program Specifications & Quantities', 'programs.edit', NOW()),
('programs', 'view', 'View Production Programs', 'programs.view', NOW()),
('purchases', 'create', 'Create Purchase Order / Third-Party Buy', 'purchases.create', NOW()),
('purchases', 'edit', 'Edit Purchase Quantities & Invoices', 'purchases.edit', NOW()),
('purchases', 'receive', 'Receive & Inward Purchase Order Goods', 'purchases.receive', NOW()),
('purchases', 'view', 'View Direct Purchases & Customer Orders', 'purchases.view', NOW()),
('purchases_returns', 'create', 'Create Purchase Return / Exchange request', 'purchases_returns.create', NOW());
INSERT INTO `permissions` (`module`, `action`, `description`, `slug`, `created_at`) VALUES
('purchases_returns', 'delete', 'Delete Purchase Return record', 'purchases_returns.delete', NOW()),
('purchases_returns', 'print', 'Print Purchase Return Invoice / Voucher', 'purchases_returns.print', NOW()),
('purchases_returns', 'process', 'Process and Restock/Settle Return', 'purchases_returns.process', NOW()),
('purchases_returns', 'view', 'View Purchases Returns & Exchanges', 'purchases_returns.view', NOW()),
('qc', 'approve', 'Final QC Clearance & Approval', 'qc.approve', NOW()),
('qc', 'create', 'Create QC Batch & Start Audit', 'qc.create', NOW()),
('qc', 'delete', 'Delete QC Inspection Record', 'qc.delete', NOW()),
('qc', 'edit', 'Inspect & Record Defects / Passed Pieces', 'qc.edit', NOW()),
('qc', 'view', 'View Quality Control (QC) Inspections', 'qc.view', NOW()),
('reports', 'export', 'Export Reports to Excel / CSV', 'reports.export', NOW()),
('reports', 'print', 'Print Formatted PDF Summary Reports', 'reports.print', NOW()),
('reports', 'view', 'View Analytics & Operational Reports', 'reports.view', NOW()),
('reports_inventory', 'export', 'Export Stock Valuation & Inventory report', 'reports_inventory.export', NOW()),
('reports_inventory', 'print', 'Print Inventory summary report', 'reports_inventory.print', NOW()),
('reports_inventory', 'view', 'View Enterprise Stock Valuation Report', 'reports_inventory.view', NOW()),
('reports_online', 'export', 'Export Combined Online report', 'reports_online.export', NOW()),
('reports_online', 'print', 'Print Combined Online report', 'reports_online.print', NOW()),
('reports_online', 'view', 'View Combined Online Channel Reports', 'reports_online.view', NOW()),
('reports_overview', 'export', 'Export Executive Overview data to Excel/CSV', 'reports_overview.export', NOW()),
('reports_overview', 'print', 'Print Executive Overview statement', 'reports_overview.print', NOW()),
('reports_overview', 'view', 'View Executive Overview and P&L Report', 'reports_overview.view', NOW()),
('reports_production', 'export', 'Export Production floor report', 'reports_production.export', NOW()),
('reports_production', 'print', 'Print Production summary report', 'reports_production.print', NOW()),
('reports_production', 'view', 'View Production Floor & Output Report', 'reports_production.view', NOW()),
('reports_profitability', 'export', 'Export Profitability report', 'reports_profitability.export', NOW()),
('reports_profitability', 'print', 'Print Profitability statement', 'reports_profitability.print', NOW()),
('reports_profitability', 'view', 'View Profitability and Margin Report', 'reports_profitability.view', NOW()),
('reports_purchase', 'export', 'Export Purchases and supplier procurement report', 'reports_purchase.export', NOW()),
('reports_purchase', 'print', 'Print Purchase procurement report', 'reports_purchase.print', NOW()),
('reports_purchase', 'view', 'View Direct Purchases & Supplier Report', 'reports_purchase.view', NOW()),
('reports_sales', 'export', 'Export Multi-Channel Sales report', 'reports_sales.export', NOW()),
('reports_sales', 'print', 'Print Multi-Channel Sales report', 'reports_sales.print', NOW()),
('reports_sales', 'view', 'View Multi-Channel Sales Performance Report', 'reports_sales.view', NOW()),
('reports_shop', 'export', 'Export Retail Shop sales report', 'reports_shop.export', NOW()),
('reports_shop', 'print', 'Print Retail Shop performance report', 'reports_shop.print', NOW()),
('reports_shop', 'view', 'View Retail Shop & POS Performance Report', 'reports_shop.view', NOW()),
('reports_warehouse', 'export', 'Export Warehouse storage and dispatch report', 'reports_warehouse.export', NOW()),
('reports_warehouse', 'print', 'Print Warehouse report', 'reports_warehouse.print', NOW()),
('reports_warehouse', 'view', 'View Warehouse Inward & Dispatch Report', 'reports_warehouse.view', NOW()),
('reports_website', 'export', 'Export Website eCommerce analytics', 'reports_website.export', NOW()),
('reports_website', 'print', 'Print Website report', 'reports_website.print', NOW()),
('reports_website', 'view', 'View Website eCommerce Analytics Report', 'reports_website.view', NOW()),
('reports_whatsapp', 'export', 'Export WhatsApp B2B sales data', 'reports_whatsapp.export', NOW()),
('reports_whatsapp', 'print', 'Print WhatsApp B2B report', 'reports_whatsapp.print', NOW()),
('reports_whatsapp', 'view', 'View WhatsApp B2B Sales Report', 'reports_whatsapp.view', NOW()),
('return_to_warehouse', 'create', 'Create Return to Warehouse Carton Manifest', 'return_to_warehouse.create', NOW()),
('return_to_warehouse', 'gatepass', 'Print Store RTV Gatepass & Transport Slip', 'return_to_warehouse.gatepass', NOW()),
('return_to_warehouse', 'print', 'Print Return Box QR Label', 'return_to_warehouse.print', NOW()),
('return_to_warehouse', 'view', 'View Store RTV Returns to Central Warehouse', 'return_to_warehouse.view', NOW()),
('returns', 'classify', 'Classify to Restock / RTV / Scrap', 'returns.classify', NOW());
INSERT INTO `permissions` (`module`, `action`, `description`, `slug`, `created_at`) VALUES
('returns', 'inspect', 'Inspect Return Items & Defect Reasons', 'returns.inspect', NOW()),
('returns', 'receive', 'Receive Return Parcel at Dock', 'returns.receive', NOW()),
('returns', 'view', 'View Warehouse Store Return Parcels', 'returns.view', NOW()),
('roles', 'create', 'Create New Custom Role', 'roles.create', NOW()),
('roles', 'delete', 'Delete Custom Role', 'roles.delete', NOW()),
('roles', 'edit', 'Edit Role & Configure Permissions Matrix', 'roles.edit', NOW()),
('roles', 'view', 'View Roles & Permissions Directory', 'roles.view', NOW()),
('sales', 'delete', 'Void Shop POS invoices and reverse stock and payments', 'sales.delete', NOW()),
('sales', 'edit', 'Edit Shop POS invoices', 'sales.edit', NOW()),
('sales', 'print', 'Reprint Customer Tax Invoice', 'sales.print', NOW()),
('sales', 'view', 'View Sales Register & Customer Invoices', 'sales.view', NOW()),
('sales_channels', 'create', 'Create WhatsApp B2B Order', 'sales_channels.create', NOW()),
('sales_channels', 'process', 'Process & Fulfill WhatsApp Order', 'sales_channels.process', NOW()),
('sales_channels', 'view', 'View WhatsApp B2B Customer Orders', 'sales_channels.view', NOW()),
('sellers', 'create', 'Create Fabric Seller / Job Worker', 'sellers.create', NOW()),
('sellers', 'delete', 'Delete Seller / Fabric Roll', 'sellers.delete', NOW()),
('sellers', 'edit', 'Edit Seller Details & Rates', 'sellers.edit', NOW()),
('sellers', 'view', 'View Fabric Sellers & Job Worker Directory', 'sellers.view', NOW()),
('settings', 'edit', 'Update System Settings, Tax & Branding', 'settings.edit', NOW()),
('settings', 'view', 'View System & Company Configurations', 'settings.view', NOW()),
('shop', 'view', 'View Retail Store Sales Dashboard', 'shop.view', NOW()),
('shop_cash', 'close_shift', 'Close Cash Register & Reconcile Till', 'shop_cash.close_shift', NOW()),
('shop_cash', 'create', 'Open Register Shift / Expense / Cash In / Bank Deposit', 'shop_cash.create', NOW()),
('shop_cash', 'delete', 'Void manual shop cash ledger transactions', 'shop_cash.delete', NOW()),
('shop_cash', 'edit', 'Edit manual shop cash ledger transactions', 'shop_cash.edit', NOW()),
('shop_cash', 'view', 'View Register Shift Flow & Till Status', 'shop_cash.view', NOW()),
('shop_cash', 'voucher', 'Print Petty Cash / Shift Thermal Slip', 'shop_cash.voucher', NOW()),
('shop_damage', 'create', 'Record shop damaged product', 'shop_damage.create', NOW()),
('shop_damage', 'delete', 'Delete shop damage record', 'shop_damage.delete', NOW()),
('shop_damage', 'edit', 'Edit shop damage record', 'shop_damage.edit', NOW()),
('shop_damage', 'process', 'Process salvage or write-off', 'shop_damage.process', NOW()),
('shop_damage', 'view', 'View shop floor defect and damaged items', 'shop_damage.view', NOW()),
('shop_inward', 'print', 'Print Store Inward Gatepass / Label', 'shop_inward.print', NOW()),
('shop_inward', 'receive', 'Scan & Receive Inward Carton Boxes', 'shop_inward.receive', NOW()),
('shop_inward', 'verify', 'Verify & Commit Stock to Store Shelf', 'shop_inward.verify', NOW()),
('shop_inward', 'view', 'View Store Inward Deliveries from Warehouse', 'shop_inward.view', NOW()),
('shop_requests', 'approve', 'Approve & Release Indented Stock', 'shop_requests.approve', NOW()),
('shop_requests', 'create', 'Raise Stock Indent Request', 'shop_requests.create', NOW()),
('shop_requests', 'view', 'View Shop Urgent Indent Requests', 'shop_requests.view', NOW()),
('shop_returns', 'create', 'Create Return Slip / Process Item Exchange', 'shop_returns.create', NOW()),
('shop_returns', 'print', 'Print Customer Credit Note / Refund Slip', 'shop_returns.print', NOW()),
('shop_returns', 'process', 'Approve Cash / Store Credit Refund', 'shop_returns.process', NOW()),
('shop_returns', 'view', 'View Customer Return Slips & Exchanges', 'shop_returns.view', NOW()),
('shop_stock', 'adjust', 'Request and approve shop stock adjustments', 'shop_stock.adjust', NOW()),
('shop_stock', 'edit', 'Edit Shop Product Selling Price & MRP', 'shop_stock.edit', NOW()),
('shop_stock', 'edit_price', 'Quick Edit Retail Selling Price and MRP', 'shop_stock.edit_price', NOW()),
('shop_stock', 'request', 'Request Stock Replenishment from Warehouse', 'shop_stock.request', NOW()),
('shop_stock', 'view', 'View Store Shelf Live Stock Breakdown', 'shop_stock.view', NOW()),
('staff', 'create', 'Create New Staff Member Account', 'staff.create', NOW()),
('staff', 'delete', 'Deactivate / Delete Staff Account', 'staff.delete', NOW());
INSERT INTO `permissions` (`module`, `action`, `description`, `slug`, `created_at`) VALUES
('staff', 'edit', 'Edit Staff Profile & Facility Assignment', 'staff.edit', NOW()),
('staff', 'view', 'View Staff Directory & Department Rosters', 'staff.view', NOW()),
('stitching', 'create', 'Create Stitching Batch / Assign Worker', 'stitching.create', NOW()),
('stitching', 'delete', 'Delete Stitching Batch', 'stitching.delete', NOW()),
('stitching', 'edit', 'Edit Stitching Quantities & Piece-Rates', 'stitching.edit', NOW()),
('stitching', 'view', 'View Stitching Batches & Lines', 'stitching.view', NOW()),
('stitching_wastage', 'create', 'Record Stitching Scrap Sale', 'stitching_wastage.create', NOW()),
('stitching_wastage', 'delete', 'Delete Stitching Scrap Record', 'stitching_wastage.delete', NOW()),
('stitching_wastage', 'edit', 'Edit Stitching Scrap Record', 'stitching_wastage.edit', NOW()),
('stitching_wastage', 'print', 'Print Stitching Scrap Voucher', 'stitching_wastage.print', NOW()),
('stitching_wastage', 'view', 'View Stitching Trimming Wastage', 'stitching_wastage.view', NOW()),
('stock', 'adjust', 'Execute Stock Count Adjustment', 'stock.adjust', NOW()),
('stock', 'edit', 'Edit warehouse stock prices (MRP and selling price)', 'stock.edit', NOW()),
('stock', 'view', 'View Warehouse Live Stock Ledger & Bins', 'stock.view', NOW()),
('warehouse', 'approve', 'Approve warehouse transfer', 'warehouse.approve', NOW()),
('warehouse', 'create', 'Create warehouse transfer', 'warehouse.create', NOW()),
('warehouse', 'delete', 'Delete warehouse', 'warehouse.delete', NOW()),
('warehouse', 'edit', 'Update warehouse details', 'warehouse.edit', NOW()),
('warehouse', 'receive', 'Receive warehouse transfer', 'warehouse.receive', NOW()),
('warehouse', 'view', 'View Warehouse Dashboard & Storage Bays', 'warehouse.view', NOW()),
('warehouse_expenses', 'create', 'Record warehouse freight/utility expense', 'warehouse_expenses.create', NOW()),
('warehouse_expenses', 'delete', 'Delete warehouse expense record', 'warehouse_expenses.delete', NOW()),
('warehouse_expenses', 'export', 'Export warehouse expense records', 'warehouse_expenses.export', NOW()),
('warehouse_expenses', 'print', 'Print warehouse expense statement', 'warehouse_expenses.print', NOW()),
('warehouse_expenses', 'view', 'View warehouse operational expenses', 'warehouse_expenses.view', NOW()),
('warehouse_expenses', 'voucher', 'Print warehouse expense payment voucher', 'warehouse_expenses.voucher', NOW()),
('warehouse_rework', 'create', 'Receive and Log damaged goods from warehouse for rework', 'warehouse_rework.create', NOW()),
('warehouse_rework', 'delete', 'Delete rework entries', 'warehouse_rework.delete', NOW()),
('warehouse_rework', 'dispatch', 'Re-dispatch repaired goods back to warehouse', 'warehouse_rework.dispatch', NOW()),
('warehouse_rework', 'edit', 'Update rework status, tailor assignment, and repair outcomes', 'warehouse_rework.edit', NOW()),
('warehouse_rework', 'view', 'View Warehouse Rework Requests and History', 'warehouse_rework.view', NOW()),
('wastage', 'create', 'Record Fabric Scrap Sale', 'wastage.create', NOW()),
('wastage', 'delete', 'Delete Wastage Sale', 'wastage.delete', NOW()),
('wastage', 'edit', 'Edit Scrap Sale Record', 'wastage.edit', NOW()),
('wastage', 'print', 'Print Wastage Receipt', 'wastage.print', NOW()),
('wastage', 'view', 'View Cutting Wastage Sales', 'wastage.view', NOW()),
('website_customers', 'view', 'View Website channel customers', 'website_customers.view', NOW()),
('website_damage', 'create', 'Log website damage entry', 'website_damage.create', NOW()),
('website_damage', 'outward', 'Dispatch website damaged goods outward', 'website_damage.outward', NOW()),
('website_damage', 'print', 'Print website damage gate pass and labels', 'website_damage.print', NOW()),
('website_damage', 'view', 'View website damaged goods', 'website_damage.view', NOW()),
('website_dashboard', 'view', 'View Website eCommerce Dashboard', 'website_dashboard.view', NOW()),
('website_expenses', 'create', 'Record website hosting/ad/courier expense', 'website_expenses.create', NOW()),
('website_expenses', 'delete', 'Delete website expense record', 'website_expenses.delete', NOW()),
('website_expenses', 'print', 'Print website expense report', 'website_expenses.print', NOW()),
('website_expenses', 'view', 'View website eCommerce expenses', 'website_expenses.view', NOW()),
('website_expenses', 'voucher', 'Print website expense voucher', 'website_expenses.voucher', NOW()),
('website_inventory', 'adjust', 'Adjust website stock levels', 'website_inventory.adjust', NOW()),
('website_inventory', 'view', 'View website dedicated inventory', 'website_inventory.view', NOW()),
('website_inward', 'receive', 'Receive and confirm website inward stock', 'website_inward.receive', NOW());
INSERT INTO `permissions` (`module`, `action`, `description`, `slug`, `created_at`) VALUES
('website_inward', 'view', 'View website stock inward and receipts', 'website_inward.view', NOW()),
('website_orders', 'create', 'Create Manual Website Order Entry', 'website_orders.create', NOW()),
('website_orders', 'process', 'Process & Dispatch Website Order', 'website_orders.process', NOW()),
('website_orders', 'view', 'View Website eCommerce Orders', 'website_orders.view', NOW()),
('website_return_to_wh', 'create', 'Create Website return to warehouse', 'website_return_to_wh.create', NOW()),
('website_return_to_wh', 'gatepass', 'Print Website return-to-warehouse gate pass', 'website_return_to_wh.gatepass', NOW()),
('website_return_to_wh', 'print', 'Print Website return-to-warehouse labels', 'website_return_to_wh.print', NOW()),
('website_return_to_wh', 'view', 'View Website returns to warehouse', 'website_return_to_wh.view', NOW()),
('website_returns', 'create', 'Initiate website return / exchange', 'website_returns.create', NOW()),
('website_returns', 'print', 'Print website return invoices and slips', 'website_returns.print', NOW()),
('website_returns', 'process', 'Process website return and restocking', 'website_returns.process', NOW()),
('website_returns', 'view', 'View website returns and RTO parcels', 'website_returns.view', NOW()),
('wh_batches', 'create', 'Create Warehouse Carton Batch', 'wh_batches.create', NOW()),
('wh_batches', 'print', 'Print Carton Batch QR Label', 'wh_batches.print', NOW()),
('wh_batches', 'view', 'View Warehouse Carton Batches', 'wh_batches.view', NOW()),
('wh_catalog', 'create', 'Add Catalog Style / Product Code', 'wh_catalog.create', NOW()),
('wh_catalog', 'delete', 'Delete Warehouse Catalog Item', 'wh_catalog.delete', NOW()),
('wh_catalog', 'edit', 'Edit Warehouse Catalog Pricing & SKUs', 'wh_catalog.edit', NOW()),
('wh_catalog', 'view', 'View Warehouse Catalog & Third-Party Styles', 'wh_catalog.view', NOW()),
('wh_labels', 'create', 'Generate Bulk QR Barcode Labels', 'wh_labels.create', NOW()),
('wh_labels', 'print', 'Print TSPL Thermal Barcode Stickers', 'wh_labels.print', NOW()),
('wh_labels', 'view', 'View Shelf & Carton QR Label Studio', 'wh_labels.view', NOW()),
('wh_sellers', 'create', 'Create Wholesale Supplier', 'wh_sellers.create', NOW()),
('wh_sellers', 'delete', 'Delete warehouse supplier', 'wh_sellers.delete', NOW()),
('wh_sellers', 'edit', 'Edit Supplier Profile & Invoices', 'wh_sellers.edit', NOW()),
('wh_sellers', 'view', 'View Warehouse Wholesale Suppliers', 'wh_sellers.view', NOW()),
('whatsapp_customers', 'view', 'View WhatsApp channel customers', 'whatsapp_customers.view', NOW()),
('whatsapp_damage', 'create', 'Log new WhatsApp damage entry', 'whatsapp_damage.create', NOW()),
('whatsapp_damage', 'delete', 'Delete quarantined WhatsApp damage records', 'whatsapp_damage.delete', NOW()),
('whatsapp_damage', 'edit', 'Edit quarantined WhatsApp damage details', 'whatsapp_damage.edit', NOW()),
('whatsapp_damage', 'outward', 'Dispatch damaged goods outward', 'whatsapp_damage.outward', NOW()),
('whatsapp_damage', 'print', 'Print damage gate pass and carton labels', 'whatsapp_damage.print', NOW()),
('whatsapp_damage', 'view', 'View WhatsApp defective and damaged items', 'whatsapp_damage.view', NOW()),
('whatsapp_dashboard', 'view', 'View WhatsApp Channel Dashboard', 'whatsapp_dashboard.view', NOW()),
('whatsapp_expenses', 'create', 'Record WhatsApp operational expense', 'whatsapp_expenses.create', NOW()),
('whatsapp_expenses', 'delete', 'Delete WhatsApp expense record', 'whatsapp_expenses.delete', NOW()),
('whatsapp_expenses', 'print', 'Print WhatsApp expense summary', 'whatsapp_expenses.print', NOW()),
('whatsapp_expenses', 'view', 'View WhatsApp channel expenses', 'whatsapp_expenses.view', NOW()),
('whatsapp_expenses', 'voucher', 'Generate and print expense voucher', 'whatsapp_expenses.voucher', NOW()),
('whatsapp_inventory', 'adjust', 'Adjust WhatsApp stock levels', 'whatsapp_inventory.adjust', NOW()),
('whatsapp_inventory', 'edit', 'Edit WhatsApp inventory details & stock prices', 'whatsapp_inventory.edit', NOW()),
('whatsapp_inventory', 'view', 'View WhatsApp channel inventory', 'whatsapp_inventory.view', NOW()),
('whatsapp_inward', 'create', 'Create WhatsApp inward receipt', 'whatsapp_inward.create', NOW()),
('whatsapp_inward', 'receive', 'Receive and confirm inward stock', 'whatsapp_inward.receive', NOW()),
('whatsapp_inward', 'view', 'View WhatsApp stock inward and receipts', 'whatsapp_inward.view', NOW()),
('whatsapp_orders', 'create', 'Create new WhatsApp B2B order', 'whatsapp_orders.create', NOW()),
('whatsapp_orders', 'process', 'Process and dispatch WhatsApp order', 'whatsapp_orders.process', NOW()),
('whatsapp_orders', 'view', 'View WhatsApp B2B Orders and history', 'whatsapp_orders.view', NOW()),
('whatsapp_return_to_wh', 'create', 'Create WhatsApp return to warehouse', 'whatsapp_return_to_wh.create', NOW()),
('whatsapp_return_to_wh', 'gatepass', 'Print WhatsApp return-to-warehouse gate pass', 'whatsapp_return_to_wh.gatepass', NOW());
INSERT INTO `permissions` (`module`, `action`, `description`, `slug`, `created_at`) VALUES
('whatsapp_return_to_wh', 'print', 'Print WhatsApp return-to-warehouse labels', 'whatsapp_return_to_wh.print', NOW()),
('whatsapp_return_to_wh', 'view', 'View WhatsApp returns to warehouse', 'whatsapp_return_to_wh.view', NOW()),
('whatsapp_returns', 'create', 'Create return request for WhatsApp', 'whatsapp_returns.create', NOW()),
('whatsapp_returns', 'print', 'Print WhatsApp return credit notes', 'whatsapp_returns.print', NOW()),
('whatsapp_returns', 'process', 'Process return and restock/refund', 'whatsapp_returns.process', NOW()),
('whatsapp_returns', 'view', 'View WhatsApp returns and exchanges', 'whatsapp_returns.view', NOW());

-- ------------------------------------------------------------
-- 5. Role Permissions Binding (Super Admin & Admin)
-- ------------------------------------------------------------
DELETE FROM `role_permissions`;
INSERT INTO `role_permissions` (`role_id`, `permission_id`, `created_at`)
SELECT 1, id, NOW() FROM `permissions`;
INSERT INTO `role_permissions` (`role_id`, `permission_id`, `created_at`)
SELECT 2, id, NOW() FROM `permissions`;

-- ------------------------------------------------------------
-- 6. Standard Base Units Lookup
-- ------------------------------------------------------------
DELETE FROM `units`;
INSERT INTO `units` (`id`, `name`, `abbreviation`, `is_active`, `created_at`) VALUES
(1, 'Pieces', 'PCS', 1, NOW()),
(2, 'Meters', 'MTR', 1, NOW()),
(3, 'Kilograms', 'KGS', 1, NOW()),
(4, 'Dozens', 'DOZ', 1, NOW());

-- ------------------------------------------------------------
-- 7. Core System Settings
-- ------------------------------------------------------------
DELETE FROM `settings`;
INSERT INTO `settings` (`setting_key`, `setting_value`, `setting_group`, `is_public`, `created_at`) VALUES
('company_name', 'GRM Apparels', 'general', 1, NOW()),
('company_email', 'admin@grm.com', 'general', 1, NOW()),
('company_phone', '9876543210', 'general', 1, NOW()),
('currency_symbol', '₹', 'general', 1, NOW()),
('currency_code', 'INR', 'general', 1, NOW()),
('timezone', 'Asia/Kolkata', 'general', 1, NOW()),
('default_warehouse_id', '1', 'inventory', 0, NOW()),
('default_production_unit_id', '1', 'production', 0, NOW());

SET FOREIGN_KEY_CHECKS = 1;
