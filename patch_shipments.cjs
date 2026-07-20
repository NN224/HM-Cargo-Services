const fs = require('fs');

const apiPath = 'src/server/api.ts';
let apiContent = fs.readFileSync(apiPath, 'utf8');

const oldShipmentPost = `api.post('/shipments', async (req, res) => {
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
});`;

const newShipmentPost = `api.post('/shipments', async (req, res) => {
  const { trackingNumber, recipientName, recipientPhone, customerId, packages: pkgList } = req.body;
  
  if (!pkgList || !Array.isArray(pkgList) || pkgList.length === 0) {
    return res.status(400).json({ error: 'Shipment must contain at least one package.' });
  }

  let calculatedTotalWeight = 0;
  for (const p of pkgList) {
    calculatedTotalWeight += Number(p.weight) || 0;
  }

  try {
    const result = await db.transaction(async (tx) => {
      const insertedShipments = await tx.insert(shipments).values({
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
        await tx.insert(packages).values({
          barcode: \`PKG-\${Date.now()}-\${Math.floor(Math.random() * 1000)}\`,
          shipmentId,
          weight: Number(p.weight) || 0,
          status: 'received',
          createdAt: new Date().toISOString()
        });
      }
      return insertedShipments[0];
    });
    
    res.json(result);
  } catch (error: any) {
    res.status(400).json({ error: error.message });
  }
});`;

apiContent = apiContent.replace(oldShipmentPost, newShipmentPost);

// Need to import `packages` from schema
if (!apiContent.includes('import { customers, shipments, batches, routes, warehouses, payments, users, packages }')) {
  apiContent = apiContent.replace('import { customers, shipments, batches, routes, warehouses, payments, users } from', 'import { customers, shipments, batches, routes, warehouses, payments, users, packages } from');
}

fs.writeFileSync(apiPath, apiContent);
console.log("Patched API!");
