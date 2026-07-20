const fs = require('fs');

const frontendPath = 'src/client/pages/Shipments.tsx';
let content = fs.readFileSync(frontendPath, 'utf8');

const oldTrackUI = `<div className="mt-10">
                <h4 className="text-sm font-medium text-gray-900 dark:text-gray-100 mb-6">مسار الشحنة</h4>`;

const newTrackUI = `{trackedShipment.packages && trackedShipment.packages.length > 0 && (
                <div className="mt-8 pt-8 border-t border-gray-100 dark:border-white/5">
                  <h4 className="text-sm font-medium text-gray-900 dark:text-gray-100 mb-4">تفاصيل الطرود ({trackedShipment.packages.length})</h4>
                  <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
                    {trackedShipment.packages.map((pkg, idx) => (
                      <div key={pkg.id || idx} className="bg-white dark:bg-white/5 border border-gray-200 dark:border-white/10 p-3 rounded-lg flex justify-between items-center">
                        <div>
                          <p className="text-xs text-gray-500 dark:text-gray-400 mb-1">الباركود</p>
                          <p className="font-mono text-sm font-medium text-gray-900 dark:text-gray-100">{pkg.barcode}</p>
                        </div>
                        <div className="text-left">
                          <p className="text-xs text-gray-500 dark:text-gray-400 mb-1">الوزن</p>
                          <p className="text-sm font-medium text-gray-900 dark:text-gray-100">{pkg.weight} kg</p>
                        </div>
                      </div>
                    ))}
                  </div>
                </div>
              )}
              
              <div className="mt-10">
                <h4 className="text-sm font-medium text-gray-900 dark:text-gray-100 mb-6">مسار الشحنة</h4>`;

content = content.replace(oldTrackUI, newTrackUI);
fs.writeFileSync(frontendPath, content);
console.log("Patched Track UI!");
