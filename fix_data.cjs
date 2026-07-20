const fs = require('fs');

const dashboard = 'src/client/pages/Dashboard.tsx';
if (fs.existsSync(dashboard)) {
  let content = fs.readFileSync(dashboard, 'utf8');
  content = content.replace('.then(setData)', `.then(data => { if (data && Array.isArray(data.latestShipments)) setData(data); })`);
  fs.writeFileSync(dashboard, content);
}

const pages = ['Shipments.tsx', 'Customers.tsx', 'Batches.tsx', 'Warehouses.tsx', 'Payments.tsx'];
for (const p of pages) {
  const path = `src/client/pages/${p}`;
  if (fs.existsSync(path)) {
    let content = fs.readFileSync(path, 'utf8');
    content = content.replace(`.then(set${p.replace('.tsx', '')})`, `.then(data => { if (Array.isArray(data)) set${p.replace('.tsx', '')}(data); })`);
    content = content.replace(`.then(setBatches)`, `.then(data => { if (Array.isArray(data)) setBatches(data); })`);
    fs.writeFileSync(path, content);
  }
}
