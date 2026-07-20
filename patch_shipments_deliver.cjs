const fs = require('fs');
const frontendPath = 'src/client/pages/Shipments.tsx';
let content = fs.readFileSync(frontendPath, 'utf8');

// Add "Actions" header
content = content.replace('<th className="px-6 py-3 font-medium">الحالة</th>', '<th className="px-6 py-3 font-medium">الحالة</th>\n                  <th className="px-6 py-3 font-medium">الإجراءات</th>');
content = content.replace('colSpan={5}', 'colSpan={6}');

// Add Actions column in the row
const rowAction = `                      </span>
                    </td>
                    <td className="px-6 py-4">
                      {shipment.status === 'ready' && (
                        <button
                          onClick={() => handleDeliver(shipment.id)}
                          className="text-xs bg-emerald-600 hover:bg-emerald-700 text-white px-3 py-1.5 rounded transition-colors"
                        >
                          تسليم للعميل
                        </button>
                      )}
                    </td>
                  </tr>`;
content = content.replace(`                      </span>\n                    </td>\n                  </tr>`, rowAction);

// Add handleDeliver function before fetchShipments
const handleDeliverFunc = `
  const handleDeliver = async (id: number) => {
    if (!confirm('هل أنت متأكد من تسليم هذه الشحنة للعميل؟')) return;
    try {
      const res = await fetch(\`/api/shipments/\${id}/collect\`, { method: 'PUT' });
      if (res.ok) {
        alert('تم تسليم الشحنة بنجاح');
        fetchShipments();
      } else {
        const err = await res.json();
        alert(err.error || 'حدث خطأ');
      }
    } catch(err) {
      alert('خطأ في الاتصال');
    }
  };
`;

content = content.replace('const fetchShipments =', handleDeliverFunc + '\n  const fetchShipments =');

fs.writeFileSync(frontendPath, content);
console.log("Patched deliver action!");
