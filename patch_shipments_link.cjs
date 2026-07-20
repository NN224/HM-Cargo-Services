const fs = require('fs');
const frontendPath = 'src/client/pages/Shipments.tsx';
let content = fs.readFileSync(frontendPath, 'utf8');

// Remove trackingNumber from initial state and clears
content = content.replace("const [formData, setFormData] = useState({ trackingNumber: '', recipientName: '', recipientPhone: '', customerId: 1, packages: [{ weight: 0 }] });", "const [formData, setFormData] = useState({ recipientName: '', recipientPhone: '', customerId: 1, packages: [{ weight: 0 }] });");
content = content.replace("setFormData({ trackingNumber: '', recipientName: '', recipientPhone: '', customerId: 1, packages: [{ weight: 0 }] });", "setFormData({ recipientName: '', recipientPhone: '', customerId: 1, packages: [{ weight: 0 }] });");

// Remove trackingNumber input from modal
const trackingInput = `          <div>
            <label className="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">رقم التتبع</label>
            <input
              type="text"
              required
              value={formData.trackingNumber}
              onChange={(e) => setFormData({ ...formData, trackingNumber: e.target.value })}
              className="w-full px-4 py-2 border border-gray-300 dark:border-white/10 dark:bg-white/5 dark:text-white rounded-lg outline-none transition-shadow"
              placeholder="رقم تتبع الشحنة..."
            />
          </div>`;
content = content.replace(trackingInput, '');

// Add "Copy Link" to the actions column
const actionsColumn = `                    <td className="px-6 py-4">
                      {shipment.status === 'ready' && (
                        <button
                          onClick={() => handleDeliver(shipment.id)}
                          className="text-xs bg-emerald-600 hover:bg-emerald-700 text-white px-3 py-1.5 rounded transition-colors"
                        >
                          تسليم للعميل
                        </button>
                      )}`;
const newActionsColumn = `                    <td className="px-6 py-4 flex gap-2">
                      <button
                        onClick={() => {
                          const link = \`\${window.location.origin}/track/\${shipment.trackingNumber}\`;
                          navigator.clipboard.writeText(link);
                          alert('تم نسخ رابط التتبع: ' + link);
                        }}
                        className="text-xs bg-slate-100 hover:bg-slate-200 dark:bg-white/5 dark:hover:bg-white/10 text-slate-700 dark:text-slate-300 px-3 py-1.5 rounded transition-colors"
                      >
                        نسخ الرابط
                      </button>
                      {shipment.status === 'ready' && (
                        <button
                          onClick={() => handleDeliver(shipment.id)}
                          className="text-xs bg-emerald-600 hover:bg-emerald-700 text-white px-3 py-1.5 rounded transition-colors"
                        >
                          تسليم للعميل
                        </button>
                      )}`;
content = content.replace(actionsColumn, newActionsColumn);

fs.writeFileSync(frontendPath, content);
console.log("Patched tracking link into Shipments.tsx!");
