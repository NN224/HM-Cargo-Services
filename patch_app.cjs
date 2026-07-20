const fs = require('fs');

const appPath = 'src/client/App.tsx';
let appContent = fs.readFileSync(appPath, 'utf8');
appContent = appContent.replace("import Shipments from './pages/Shipments';", "import Shipments from './pages/Shipments';\nimport Packages from './pages/Packages';");
appContent = appContent.replace('<Route path="/shipments" element={<Shipments />} />', '<Route path="/shipments" element={<Shipments />} />\n        <Route path="/packages" element={<Packages />} />');
fs.writeFileSync(appPath, appContent);

const layoutPath = 'src/client/components/Layout.tsx';
let layoutContent = fs.readFileSync(layoutPath, 'utf8');
// Assuming navigation includes Shipments, let's add Packages
layoutContent = layoutContent.replace("{ name: 'الشحنات', href: '/shipments', icon: Package },", "{ name: 'الشحنات', href: '/shipments', icon: Package },\n    { name: 'الطرود', href: '/packages', icon: Package },");
fs.writeFileSync(layoutPath, layoutContent);
console.log("Patched App and Layout!");
