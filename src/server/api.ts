import express, { Router } from 'express';
import { db } from '../db';
import { customers, shipments, batches, routes, warehouses, payments, users } from '../db/schema';
import { eq, desc } from 'drizzle-orm';

const api = Router();

api.get('/me', async (req, res) => {
  const allUsers = await db.select().from(users).limit(1);
  if (allUsers.length > 0) {
    res.json(allUsers[0]);
  } else {
    res.json({
      name: 'غير محدد',
      email: 'غير محدد',
      role: 'admin',
    });
  }
});

// Dashboard Stats
api.get('/dashboard', async (req, res) => {
  const pendingShipments = await db.select().from(shipments).where(eq(shipments.status, 'pending'));
  const activeBatchesCount = await db.select().from(batches).where(eq(batches.status, 'transit'));
  const readyShipments = await db.select().from(shipments).where(eq(shipments.status, 'ready'));
  
  const latestShipments = await db.select().from(shipments).orderBy(desc(shipments.createdAt)).limit(5);
  const activeBatchesList = await db.select().from(batches).where(eq(batches.status, 'transit')).orderBy(desc(batches.createdAt)).limit(5);

  res.json({
    pendingShipments: pendingShipments.length,
    activeBatches: activeBatchesCount.length,
    readyShipments: readyShipments.length,
    alerts: 0,
    latestShipments,
    activeBatchesList,
  });
});

// Customers
api.get('/customers', async (req, res) => {
  const allCustomers = await db.select().from(customers).orderBy(desc(customers.createdAt));
  res.json(allCustomers);
});

api.post('/customers', async (req, res) => {
  const { name, phone, isCreditCustomer } = req.body;
  try {
    const result = await db.insert(customers).values({
      name,
      phone,
      isCreditCustomer: isCreditCustomer ? true : false,
      createdAt: new Date().toISOString(),
    }).returning();
    res.json(result[0]);
  } catch (error: any) {
    res.status(400).json({ error: error.message });
  }
});

// Shipments
api.get('/shipments', async (req, res) => {
  const allShipments = await db.select().from(shipments).orderBy(desc(shipments.createdAt));
  res.json(allShipments);
});

api.post('/shipments', async (req, res) => {
  const { trackingNumber, recipientName, recipientPhone, customerId, totalWeight } = req.body;
  try {
    const result = await db.insert(shipments).values({
      trackingNumber,
      recipientName,
      recipientPhone,
      customerId: customerId || 1, // Defaulting to 1 for now if not provided
      totalWeight: Number(totalWeight) || 0,
      status: 'pending',
      createdAt: new Date().toISOString(),
    }).returning();
    res.json(result[0]);
  } catch (error: any) {
    res.status(400).json({ error: error.message });
  }
});

api.get('/shipments/track/:trackingNumber', async (req, res) => {
  const { trackingNumber } = req.params;
  const shipmentList = await db.select().from(shipments).where(eq(shipments.trackingNumber, trackingNumber));
  
  if (shipmentList.length === 0) {
    return res.status(404).json({ error: 'Shipment not found' });
  }
  
  res.json(shipmentList[0]);
});

// Batches
api.get('/batches', async (req, res) => {
  const allBatches = await db.select().from(batches).orderBy(desc(batches.createdAt));
  res.json(allBatches);
});

// Warehouses
api.get('/warehouses', async (req, res) => {
  const allWarehouses = await db.select().from(warehouses).orderBy(desc(warehouses.createdAt));
  res.json(allWarehouses);
});

api.post('/warehouses', async (req, res) => {
  const { name, location } = req.body;
  try {
    const result = await db.insert(warehouses).values({
      name,
      location,
      createdAt: new Date().toISOString(),
    }).returning();
    res.json(result[0]);
  } catch (error: any) {
    res.status(400).json({ error: error.message });
  }
});

// Payments
api.get('/payments', async (req, res) => {
  const allPayments = await db.select().from(payments).orderBy(desc(payments.createdAt));
  res.json(allPayments);
});

// Error handling middleware for API routes
api.use((err: any, req: express.Request, res: express.Response, next: express.NextFunction) => {
  console.error(err);
  res.status(500).json({ error: 'Internal Server Error', message: err.message });
});

export default api;
