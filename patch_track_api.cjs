const fs = require('fs');

const apiPath = 'src/server/api.ts';
let apiContent = fs.readFileSync(apiPath, 'utf8');

const oldTrack = `api.get('/shipments/track/:trackingNumber', async (req, res) => {
  const { trackingNumber } = req.params;
  const shipmentList = await db.select().from(shipments).where(eq(shipments.trackingNumber, trackingNumber));
  
  if (shipmentList.length === 0) {
    return res.status(404).json({ error: 'Shipment not found' });
  }
  
  res.json(shipmentList[0]);
});`;

const newTrack = `api.get('/shipments/track/:trackingNumber', async (req, res) => {
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
});`;

apiContent = apiContent.replace(oldTrack, newTrack);
fs.writeFileSync(apiPath, apiContent);
console.log("Patched Track API!");
