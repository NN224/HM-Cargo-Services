import crypto from 'crypto';
import express, { Router } from 'express';
import { db } from '../db';
import { customers, shipments, batches, routes, warehouses, payments, users, packages } from '../db/schema';
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
  const { recipientName, recipientPhone, customerId, packages: pkgList } = req.body;
  const trackingNumber = req.body.trackingNumber || crypto.randomBytes(16).toString('hex');
  
  if (!pkgList || !Array.isArray(pkgList) || pkgList.length === 0) {
    return res.status(400).json({ error: 'Shipment must contain at least one package.' });
  }

  let calculatedTotalWeight = 0;
  for (const p of pkgList) {
    calculatedTotalWeight += Number(p.weight) || 0;
  }

  try {
    const insertedShipments = await db.insert(shipments).values({
        trackingNumber,
        recipientName,
        recipientPhone,
        customerId: customerId || 1, // Defaulting to 1 for now if not provided
        totalWeight: calculatedTotalWeight,
        status: 'pending',
        createdAt: new Date().toISOString(),
      }).returning();
      
      const shipmentId = insertedShipments[0].id;
      
      for (const p of pkgList) {
        await db.insert(packages).values({
          barcode: `PKG-${Date.now()}-${Math.floor(Math.random() * 1000)}`,
          shipmentId,
          weight: Number(p.weight) || 0,
          status: 'received',
          createdAt: new Date().toISOString()
        });
      }
      const result = insertedShipments[0];
    
    res.json(result);
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
  
  const shipmentInfo = shipmentList[0];
  const pkgList = await db.select().from(packages).where(eq(packages.shipmentId, shipmentInfo.id));
  
  res.json({
    ...shipmentInfo,
    packages: pkgList
  });
});


api.put('/packages/:barcode/scan', async (req, res) => {
  const { barcode } = req.params;
  const { status } = req.body; // e.g., 'arrived'

  try {
    const pkgList = await db.select().from(packages).where(eq(packages.barcode, barcode));
    if (pkgList.length === 0) return res.status(404).json({ error: 'Package not found' });
    
    const pkg = pkgList[0];
    
    await db.update(packages).set({ status }).where(eq(packages.id, pkg.id));
    
    // Check if all packages for this shipment are arrived
    if (status === 'arrived') {
      const allPkgs = await db.select().from(packages).where(eq(packages.shipmentId, pkg.shipmentId));
      const allArrived = allPkgs.every(p => p.status === 'arrived' || p.id === pkg.id); // consider the currently updated one
      
      if (allArrived) {
        await db.update(shipments).set({ status: 'ready' }).where(eq(shipments.id, pkg.shipmentId));
      }
    }
    
    res.json({ success: true, package: { ...pkg, status } });
  } catch (error: any) {
    res.status(400).json({ error: error.message });
  }
});


api.put('/shipments/:id', async (req, res) => {
  const { id } = req.params;
  const { status, recipientName, recipientPhone, totalWeight, totalAmount, paidAmount } = req.body;
  
  try {
    const updateData: any = {};
    if (status !== undefined) updateData.status = status;
    if (recipientName !== undefined) updateData.recipientName = recipientName;
    if (recipientPhone !== undefined) updateData.recipientPhone = recipientPhone;
    if (totalWeight !== undefined) updateData.totalWeight = Number(totalWeight);
    if (totalAmount !== undefined) updateData.totalAmount = Number(totalAmount);
    if (paidAmount !== undefined) updateData.paidAmount = Number(paidAmount);
    
    const result = await db.update(shipments)
      .set(updateData)
      .where(eq(shipments.id, Number(id)))
      .returning();
      
    res.json(result[0]);
  } catch (error: any) {
    res.status(400).json({ error: error.message });
  }
});

api.put('/shipments/:id/collect', async (req, res) => {
  const { id } = req.params;

  try {
    const shipmentList = await db.select().from(shipments).where(eq(shipments.id, Number(id)));
    if (shipmentList.length === 0) return res.status(404).json({ error: 'Shipment not found' });
    
    const shipment = shipmentList[0];
    const allPkgs = await db.select().from(packages).where(eq(packages.shipmentId, shipment.id));
    
    const activePackages = allPkgs.filter(p => p.status !== 'cancelled');
    const allArrived = activePackages.every(p => p.status === 'arrived');
    
    if (!allArrived) {
      return res.status(400).json({ error: 'لا يمكن تسليم الشحنة للعميل إلا إذا وصلت كافة الطرود التابعة لها إلى مستودع الوجهة.' });
    }
    
    // Update shipment to collected
    await db.update(shipments).set({ status: 'collected' }).where(eq(shipments.id, shipment.id));
    
    // Update all packages to collected
    await db.update(packages).set({ status: 'collected' }).where(eq(packages.shipmentId, shipment.id));
    
    res.json({ success: true });
  } catch (error: any) {
    res.status(400).json({ error: error.message });
  }
});

api.get('/packages', async (req, res) => {
  const allPkgs = await db.select().from(packages).orderBy(desc(packages.createdAt));
  res.json(allPkgs);
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
