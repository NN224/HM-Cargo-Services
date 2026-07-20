const fs = require('fs');
const apiPath = 'src/server/api.ts';
let apiContent = fs.readFileSync(apiPath, 'utf8');

const putEndpoint = `
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
`;

if (!apiContent.includes("api.put('/shipments/:id'")) {
  apiContent = apiContent.replace("api.put('/shipments/:id/collect'", putEndpoint + "\napi.put('/shipments/:id/collect'");
  fs.writeFileSync(apiPath, apiContent);
  console.log("Patched API put endpoint");
}
