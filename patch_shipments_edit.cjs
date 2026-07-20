const fs = require('fs');
const frontendPath = 'src/client/pages/Shipments.tsx';
let content = fs.readFileSync(frontendPath, 'utf8');

// Add states for edit
const newStates = `
  const [isEditModalOpen, setIsEditModalOpen] = useState(false);
  const [editingShipment, setEditingShipment] = useState<any>(null);
`;
content = content.replace("const [isSubmitting, setIsSubmitting] = useState(false);", "const [isSubmitting, setIsSubmitting] = useState(false);\n" + newStates);

// Add handleEdit and handleEditSubmit functions
const editFuncs = `
  const handleEditClick = (shipment: any) => {
    setEditingShipment(shipment);
    setIsEditModalOpen(true);
  };

  const handleEditSubmit = async (e: React.FormEvent) => {
    e.preventDefault();
    if (!editingShipment) return;
    setIsSubmitting(true);
    try {
      const res = await fetch(\`/api/shipments/\${editingShipment.id}\`, {
        method: 'PUT',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({
          recipientName: editingShipment.recipientName,
          recipientPhone: editingShipment.recipientPhone,
          status: editingShipment.status,
          totalWeight: editingShipment.totalWeight,
          totalAmount: editingShipment.totalAmount,
          paidAmount: editingShipment.paidAmount,
        }),
      });
      if (res.ok) {
        setIsEditModalOpen(false);
        setEditingShipment(null);
        fetchShipments();
        alert('تم تعديل الشحنة بنجاح');
      } else {
        const err = await res.json();
        alert(err.error || 'حدث خطأ أثناء التعديل');
      }
    } catch (error) {
      alert('خطأ في الاتصال');
    } finally {
      setIsSubmitting(false);
    }
  };
`;
content = content.replace("const handleDeliver = async", editFuncs + "\n  const handleDeliver = async");

// Add Edit Button in the actions column
const newActionsColumn = `                    <td className="px-6 py-4 flex gap-2">
                      <button
                        onClick={() => handleEditClick(shipment)}
                        className="text-xs bg-blue-100 hover:bg-blue-200 dark:bg-blue-900/30 dark:hover:bg-blue-800/30 text-blue-700 dark:text-blue-400 px-3 py-1.5 rounded transition-colors"
                      >
                        تعديل
                      </button>
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

content = content.replace(/<td className="px-6 py-4 flex gap-2">[\s\S]*?تسليم للعميل[\s\S]*?<\/button>[\s\S]*?\}\)/, newActionsColumn + "\n                      )}");

// Add Edit Modal at the end
const editModal = `
      <Modal isOpen={isEditModalOpen} onClose={() => { setIsEditModalOpen(false); setEditingShipment(null); }} title="تعديل الشحنة">
        {editingShipment && (
          <form onSubmit={handleEditSubmit} className="space-y-4 text-right" dir="rtl">
            <div>
              <label className="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">اسم المستلم</label>
              <input
                type="text"
                required
                value={editingShipment.recipientName}
                onChange={(e) => setEditingShipment({ ...editingShipment, recipientName: e.target.value })}
                className="w-full px-4 py-2 border border-gray-300 dark:border-white/10 dark:bg-white/5 dark:text-white rounded-lg outline-none"
              />
            </div>
            <div>
              <label className="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">هاتف المستلم</label>
              <input
                type="text"
                required
                value={editingShipment.recipientPhone}
                onChange={(e) => setEditingShipment({ ...editingShipment, recipientPhone: e.target.value })}
                className="w-full px-4 py-2 border border-gray-300 dark:border-white/10 dark:bg-white/5 dark:text-white rounded-lg outline-none"
              />
            </div>
            <div>
              <label className="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">الوزن الإجمالي (kg)</label>
              <input
                type="number"
                step="0.01"
                required
                value={editingShipment.totalWeight}
                onChange={(e) => setEditingShipment({ ...editingShipment, totalWeight: e.target.value })}
                className="w-full px-4 py-2 border border-gray-300 dark:border-white/10 dark:bg-white/5 dark:text-white rounded-lg outline-none"
              />
            </div>
            <div className="grid grid-cols-2 gap-4">
              <div>
                <label className="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">المبلغ المطلوب (سنت)</label>
                <input
                  type="number"
                  required
                  value={editingShipment.totalAmount}
                  onChange={(e) => setEditingShipment({ ...editingShipment, totalAmount: e.target.value })}
                  className="w-full px-4 py-2 border border-gray-300 dark:border-white/10 dark:bg-white/5 dark:text-white rounded-lg outline-none"
                />
              </div>
              <div>
                <label className="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">المبلغ المدفوع (سنت)</label>
                <input
                  type="number"
                  required
                  value={editingShipment.paidAmount}
                  onChange={(e) => setEditingShipment({ ...editingShipment, paidAmount: e.target.value })}
                  className="w-full px-4 py-2 border border-gray-300 dark:border-white/10 dark:bg-white/5 dark:text-white rounded-lg outline-none"
                />
              </div>
            </div>
            <div>
              <label className="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">حالة الشحنة</label>
              <select
                value={editingShipment.status}
                onChange={(e) => setEditingShipment({ ...editingShipment, status: e.target.value })}
                className="w-full px-4 py-2 border border-gray-300 dark:border-white/10 dark:bg-white/5 dark:text-white rounded-lg outline-none"
                dir="rtl"
              >
                {Object.entries(statusLabels).map(([key, label]) => (
                  <option key={key} value={key}>{label}</option>
                ))}
              </select>
            </div>
            <div className="pt-4 border-t border-gray-100 dark:border-white/5 flex gap-3">
              <button
                type="submit"
                disabled={isSubmitting}
                className="flex-1 bg-slate-900 dark:bg-blue-600 text-white px-4 py-2 rounded-lg text-sm font-medium hover:bg-slate-800 dark:hover:bg-blue-700 transition-colors disabled:opacity-50"
              >
                {isSubmitting ? 'جاري الحفظ...' : 'تحديث'}
              </button>
              <button
                type="button"
                onClick={() => { setIsEditModalOpen(false); setEditingShipment(null); }}
                className="flex-1 bg-gray-100 dark:bg-white/5 text-gray-700 dark:text-gray-300 px-4 py-2 rounded-lg text-sm font-medium hover:bg-gray-200 dark:hover:bg-white/10 transition-colors"
              >
                إلغاء
              </button>
            </div>
          </form>
        )}
      </Modal>
    </>
  );
}
`;

content = content.replace("    </>\n  );\n}", editModal);
fs.writeFileSync(frontendPath, content);
console.log("Patched Edit into Shipments.tsx!");
