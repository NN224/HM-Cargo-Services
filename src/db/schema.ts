import { sqliteTable, text, integer, real } from 'drizzle-orm/sqlite-core';

export const users = sqliteTable('users', {
  id: integer('id').primaryKey({ autoIncrement: true }),
  name: text('name').notNull(),
  email: text('email').notNull().unique(),
  role: text('role').notNull(), // 'admin', 'warehouse_employee'
  warehouseId: integer('warehouse_id'), // foreign key to warehouses
  createdAt: text('created_at').notNull(),
});

export const warehouses = sqliteTable('warehouses', {
  id: integer('id').primaryKey({ autoIncrement: true }),
  name: text('name').notNull(),
  location: text('location').notNull(),
  createdAt: text('created_at').notNull(),
});

export const customers = sqliteTable('customers', {
  id: integer('id').primaryKey({ autoIncrement: true }),
  name: text('name').notNull(),
  phone: text('phone').notNull(),
  isCreditCustomer: integer('is_credit_customer', { mode: 'boolean' }).notNull().default(false),
  createdAt: text('created_at').notNull(),
});

export const routes = sqliteTable('routes', {
  id: integer('id').primaryKey({ autoIncrement: true }),
  name: text('name').notNull(), // e.g. "Dubai-to-Syria"
  originWarehouseId: integer('origin_warehouse_id').notNull(),
  destinationWarehouseId: integer('destination_warehouse_id').notNull(),
  transitWarehouseId: integer('transit_warehouse_id'),
  baseCostPerKg: integer('base_cost_per_kg').notNull(), // in cents
  createdAt: text('created_at').notNull(),
});

export const customerRates = sqliteTable('customer_rates', {
  id: integer('id').primaryKey({ autoIncrement: true }),
  customerId: integer('customer_id').notNull(),
  routeId: integer('route_id').notNull(),
  ratePerKg: integer('rate_per_kg').notNull(), // in cents
  createdAt: text('created_at').notNull(),
});

export const batches = sqliteTable('batches', {
  id: integer('id').primaryKey({ autoIncrement: true }),
  routeId: integer('route_id').notNull(),
  status: text('status').notNull(), // 'open', 'dispatched', 'transit', 'arrived', 'completed'
  dispatchDate: text('dispatch_date'),
  arrivalDate: text('arrival_date'),
  costPerKg: integer('cost_per_kg'), // snapshotted at dispatch, in cents
  createdAt: text('created_at').notNull(),
});

export const shipments = sqliteTable('shipments', {
  id: integer('id').primaryKey({ autoIncrement: true }),
  trackingNumber: text('tracking_number').notNull().unique(), // Unique, high entropy
  customerId: integer('customer_id').notNull(), // Billing customer
  recipientName: text('recipient_name').notNull(),
  recipientPhone: text('recipient_phone').notNull(),
  batchId: integer('batch_id'),
  status: text('status').notNull(), // 'pending', 'received', 'transit', 'arrived', 'ready', 'collected', 'cancelled'
  ratePerKg: integer('rate_per_kg'), // snapshotted when assigned to batch, in cents
  totalWeight: real('total_weight').notNull().default(0), // exact decimal
  totalAmount: integer('total_amount').notNull().default(0), // in cents
  paidAmount: integer('total_paid').notNull().default(0), // in cents
  createdAt: text('created_at').notNull(),
});

export const packages = sqliteTable('packages', {
  id: integer('id').primaryKey({ autoIncrement: true }),
  barcode: text('barcode').notNull().unique(), // system generated
  shipmentId: integer('shipment_id').notNull(),
  weight: real('weight').notNull(), // exact decimal
  status: text('status').notNull(), // 'received', 'transit', 'arrived', 'missing', 'damaged'
  createdAt: text('created_at').notNull(),
});

export const payments = sqliteTable('payments', {
  id: integer('id').primaryKey({ autoIncrement: true }),
  customerId: integer('customer_id').notNull(),
  shipmentId: integer('shipment_id'), // Optional: if paid directly for a shipment
  amount: integer('amount').notNull(), // in cents
  method: text('method').notNull(), // 'cash', 'whish', 'bank', 'other'
  status: text('status').notNull(), // 'completed', 'reversed'
  createdAt: text('created_at').notNull(),
});
