-- Migration: add indexes to improve dashboard task queries
-- Run this in your database (non-destructive):

ALTER TABLE orders
  ADD INDEX idx_orders_designer_id (designer_id),
  ADD INDEX idx_orders_workshop_id (workshop_id),
  ADD INDEX idx_orders_status (status),
  ADD INDEX idx_orders_payment_status (payment_status);
