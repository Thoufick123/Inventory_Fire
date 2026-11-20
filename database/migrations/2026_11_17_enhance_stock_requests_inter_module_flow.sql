-- Migration: Enhance stock requests for module-to-module request & fulfillment flow
-- Date: 2026-11-17

-- 1. Modify status ENUM on production_stock_requests
ALTER TABLE `production_stock_requests`
  MODIFY COLUMN `status` ENUM(
    'PENDING',
    'RECEIVED_BY_SEND_AREA',
    'APPROVED',
    'IN_PRODUCTION',
    'READY_FOR_DISPATCH',
    'DISPATCHED',
    'FULFILLED',
    'REJECTED'
  ) NOT NULL DEFAULT 'PENDING';

-- 2. Add module routing and tracking fields to production_stock_requests
ALTER TABLE `production_stock_requests`
  ADD COLUMN `from_module` varchar(100) NOT NULL DEFAULT 'Central Warehouse' AFTER `request_number`,
  ADD COLUMN `to_module` varchar(100) NOT NULL DEFAULT 'Production Unit' AFTER `from_module`,
  ADD COLUMN `from_unit_id` int(10) unsigned DEFAULT NULL AFTER `to_module`,
  ADD COLUMN `to_unit_id` int(10) unsigned DEFAULT NULL AFTER `from_unit_id`,
  ADD COLUMN `dispatched_pieces` int(10) unsigned NOT NULL DEFAULT 0 AFTER `fulfilled_pieces`,
  ADD COLUMN `received_by_send_area_at` datetime DEFAULT NULL AFTER `notes`,
  ADD COLUMN `received_by_send_area_by` varchar(150) DEFAULT NULL AFTER `received_by_send_area_at`,
  ADD COLUMN `dispatched_at` datetime DEFAULT NULL AFTER `received_by_send_area_by`,
  ADD COLUMN `dispatched_by` varchar(150) DEFAULT NULL AFTER `dispatched_at`,
  ADD COLUMN `received_at` datetime DEFAULT NULL AFTER `dispatched_by`,
  ADD COLUMN `received_by` varchar(150) DEFAULT NULL AFTER `received_at`;

-- 3. Modify status ENUM and add dispatched_qty & received_qty on production_stock_request_items
ALTER TABLE `production_stock_request_items`
  ADD COLUMN `dispatched_qty` int(10) unsigned NOT NULL DEFAULT 0 AFTER `requested_qty`,
  ADD COLUMN `received_qty` int(10) unsigned NOT NULL DEFAULT 0 AFTER `dispatched_qty`,
  MODIFY COLUMN `status` ENUM(
    'PENDING',
    'RECEIVED_BY_SEND_AREA',
    'PARTIAL',
    'ALLOCATED',
    'DISPATCHED',
    'FULFILLED',
    'REJECTED'
  ) NOT NULL DEFAULT 'PENDING';
