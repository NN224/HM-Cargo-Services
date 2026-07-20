const fs = require('fs');

const apiPath = 'src/server/api.ts';
let apiContent = fs.readFileSync(apiPath, 'utf8');

const oldTransaction = `const result = await db.transaction(async (tx) => {
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
    });`;

const newTransaction = `const insertedShipments = await db.insert(shipments).values({
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
          barcode: \`PKG-\${Date.now()}-\${Math.floor(Math.random() * 1000)}\`,
          shipmentId,
          weight: Number(p.weight) || 0,
          status: 'received',
          createdAt: new Date().toISOString()
        });
      }
      const result = insertedShipments[0];`;

apiContent = apiContent.replace(oldTransaction, newTransaction);
fs.writeFileSync(apiPath, apiContent);
console.log("Fixed Transaction!");
