const fs = require('fs');
const apiPath = 'src/server/api.ts';
let apiContent = fs.readFileSync(apiPath, 'utf8');

// We need to add the endpoints. Let's add them before `api.get('/batches'`

const endpoints = `
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

// Batches`;

apiContent = apiContent.replace('// Batches', endpoints);

fs.writeFileSync(apiPath, apiContent);
console.log("Added new endpoints!");
