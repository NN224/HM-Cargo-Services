const fs = require('fs');
const appPath = 'src/client/App.tsx';
let appContent = fs.readFileSync(appPath, 'utf8');

// Add import PublicTracking
if (!appContent.includes('import PublicTracking from')) {
  appContent = appContent.replace("import Settings from './pages/Settings';", "import Settings from './pages/Settings';\nimport PublicTracking from './pages/PublicTracking';");
}

// Update routing
const newRouting = `function App() {
  return (
    <Routes>
      <Route path="/track/:token" element={<PublicTracking />} />
      <Route path="*" element={
        <Layout>
          <Routes>
            <Route path="/" element={<Dashboard />} />
            <Route path="/customers" element={<Customers />} />
            <Route path="/shipments" element={<Shipments />} />
            <Route path="/packages" element={<Packages />} />
            <Route path="/batches" element={<Batches />} />
            <Route path="/warehouses" element={<Warehouses />} />
            <Route path="/payments" element={<Payments />} />
            <Route path="/settings" element={<Settings />} />
            <Route path="*" element={<div className="text-gray-500">قريباً...</div>} />
          </Routes>
        </Layout>
      } />
    </Routes>
  );
}`;

appContent = appContent.replace(/function App\(\) \{[\s\S]*\}/, newRouting);
fs.writeFileSync(appPath, appContent);
console.log("Patched App route!");
