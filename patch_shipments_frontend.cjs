const fs = require('fs');

const frontendPath = 'src/client/pages/Shipments.tsx';
let content = fs.readFileSync(frontendPath, 'utf8');

// Replace state
content = content.replace(
  "const [formData, setFormData] = useState({ trackingNumber: '', recipientName: '', recipientPhone: '', customerId: 1, totalWeight: 0 });",
  "const [formData, setFormData] = useState({ trackingNumber: '', recipientName: '', recipientPhone: '', customerId: 1, packages: [{ weight: 0 }] });"
);

// Replace form submission clear
content = content.replace(
  "setFormData({ trackingNumber: '', recipientName: '', recipientPhone: '', customerId: 1, totalWeight: 0 });",
  "setFormData({ trackingNumber: '', recipientName: '', recipientPhone: '', customerId: 1, packages: [{ weight: 0 }] });"
);

// Find the Modal form inputs and add package management
const oldModalPart1 = `
          <div>
            <label className="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">هاتف المستلم</label>
            <input
              type="text"
              required
              value={formData.recipientPhone}
              onChange={(e) => setFormData({ ...formData, recipientPhone: e.target.value })}
              className="w-full px-4 py-2 border border-gray-300 dark:border-white/10 dark:bg-white/5 dark:text-white rounded-lg outline-none transition-shadow"
              placeholder="رقم الهاتف..."
            />
          </div>
          <div className="pt-4 border-t border-gray-100 dark:border-white/5 flex gap-3">`;

const newModalPart1 = `
          <div>
            <label className="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">هاتف المستلم</label>
            <input
              type="text"
              required
              value={formData.recipientPhone}
              onChange={(e) => setFormData({ ...formData, recipientPhone: e.target.value })}
              className="w-full px-4 py-2 border border-gray-300 dark:border-white/10 dark:bg-white/5 dark:text-white rounded-lg outline-none transition-shadow"
              placeholder="رقم الهاتف..."
            />
          </div>
          <div className="pt-4 border-t border-gray-100 dark:border-white/5">
            <div className="flex justify-between items-center mb-2">
              <label className="block text-sm font-medium text-gray-700 dark:text-gray-300">الطرود (Packages)</label>
              <button 
                type="button" 
                onClick={() => setFormData({...formData, packages: [...formData.packages, { weight: 0 }]})}
                className="text-xs bg-gray-100 dark:bg-white/5 hover:bg-gray-200 dark:hover:bg-white/10 text-gray-700 dark:text-gray-300 px-2 py-1 rounded"
              >
                + إضافة طرد
              </button>
            </div>
            <div className="space-y-2 max-h-40 overflow-y-auto pr-1">
              {formData.packages.map((pkg, idx) => (
                <div key={idx} className="flex gap-2 items-center">
                  <div className="flex-1">
                    <input
                      type="number"
                      step="0.01"
                      min="0.01"
                      required
                      value={pkg.weight || ''}
                      onChange={(e) => {
                        const newPackages = [...formData.packages];
                        newPackages[idx].weight = Number(e.target.value);
                        setFormData({ ...formData, packages: newPackages });
                      }}
                      className="w-full px-3 py-1.5 border border-gray-300 dark:border-white/10 dark:bg-white/5 dark:text-white rounded-lg outline-none text-sm"
                      placeholder="وزن الطرد (kg)"
                    />
                  </div>
                  {formData.packages.length > 1 && (
                    <button
                      type="button"
                      onClick={() => {
                        const newPackages = formData.packages.filter((_, i) => i !== idx);
                        setFormData({ ...formData, packages: newPackages });
                      }}
                      className="text-red-500 hover:text-red-700 p-1"
                    >
                      ✕
                    </button>
                  )}
                </div>
              ))}
            </div>
            <div className="text-left mt-2 text-sm text-gray-500 dark:text-gray-400">
              إجمالي الوزن: {formData.packages.reduce((acc, p) => acc + (Number(p.weight) || 0), 0).toFixed(2)} kg
            </div>
          </div>
          <div className="pt-4 border-t border-gray-100 dark:border-white/5 flex gap-3">`;

content = content.replace(oldModalPart1, newModalPart1);

// I need to import Plus/X icons maybe? Let's just use text as I did.
fs.writeFileSync(frontendPath, content);
console.log("Patched frontend!");
