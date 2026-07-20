import { Routes, Route } from 'react-router-dom';
import Layout from './components/Layout';
import Dashboard from './pages/Dashboard';
import Customers from './pages/Customers';
import Shipments from './pages/Shipments';
import Batches from './pages/Batches';
import Warehouses from './pages/Warehouses';
import Payments from './pages/Payments';
import Settings from './pages/Settings';

function App() {
  return (
    <Layout>
      <Routes>
        <Route path="/" element={<Dashboard />} />
        <Route path="/customers" element={<Customers />} />
        <Route path="/shipments" element={<Shipments />} />
        <Route path="/batches" element={<Batches />} />
        <Route path="/warehouses" element={<Warehouses />} />
        <Route path="/payments" element={<Payments />} />
        <Route path="/settings" element={<Settings />} />
        <Route path="*" element={<div className="text-gray-500">قريباً...</div>} />
      </Routes>
    </Layout>
  );
}

export default App;

