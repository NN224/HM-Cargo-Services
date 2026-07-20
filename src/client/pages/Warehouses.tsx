import { useEffect, useState } from 'react';
import Modal from '../components/Modal';

export default function Warehouses() {
  const [warehouses, setWarehouses] = useState<any[]>([]);
  const [isModalOpen, setIsModalOpen] = useState(false);
  const [formData, setFormData] = useState({ name: '', location: '' });
  const [isSubmitting, setIsSubmitting] = useState(false);

  const fetchWarehouses = () => {
    fetch('/api/warehouses')
      .then(res => res.json())
      .then(data => { if (Array.isArray(data)) setWarehouses(data); })
      .catch(console.error);
  };

  useEffect(() => {
    fetchWarehouses();
  }, []);

  const handleSubmit = async (e: React.FormEvent) => {
    e.preventDefault();
    setIsSubmitting(true);
    try {
      const res = await fetch('/api/warehouses', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(formData),
      });
      if (res.ok) {
        setIsModalOpen(false);
        setFormData({ name: '', location: '' });
        fetchWarehouses();
      } else {
        const err = await res.json();
        alert(err.error || 'حدث خطأ أثناء الإضافة');
      }
    } catch (error) {
      console.error(error);
      alert('حدث خطأ أثناء الاتصال بالخادم');
    } finally {
      setIsSubmitting(false);
    }
  };

  return (
    <>
      <div className="bg-white dark:bg-[#0a0a0a] rounded-xl shadow-sm dark:shadow-none border border-gray-100 dark:border-white/5 overflow-hidden">
        <div className="p-6 border-b border-gray-100 dark:border-white/5 flex justify-between items-center">
          <h3 className="text-lg font-semibold text-gray-900 dark:text-gray-100">المستودعات</h3>
          <button 
            onClick={() => setIsModalOpen(true)}
            className="bg-slate-900 dark:bg-blue-600 text-white px-4 py-2 rounded-lg text-sm font-medium hover:bg-slate-800 dark:hover:bg-blue-700 transition-colors"
          >
            إضافة مستودع
          </button>
        </div>
        <div className="overflow-x-auto">
          <table className="w-full text-right text-sm">
            <thead className="bg-gray-50 dark:bg-white/5 text-gray-600 dark:text-gray-400">
              <tr>
                <th className="px-6 py-3 font-medium">اسم المستودع</th>
                <th className="px-6 py-3 font-medium">الموقع</th>
                <th className="px-6 py-3 font-medium">تاريخ الإضافة</th>
              </tr>
            </thead>
            <tbody className="divide-y divide-gray-100 dark:divide-white/5">
              {warehouses.map((warehouse: any) => (
                <tr key={warehouse.id} className="hover:bg-gray-50 dark:hover:bg-white/5">
                  <td className="px-6 py-4 text-gray-900 dark:text-gray-100 font-medium">{warehouse.name}</td>
                  <td className="px-6 py-4 text-gray-600 dark:text-gray-400">{warehouse.location}</td>
                  <td className="px-6 py-4 text-gray-500 dark:text-gray-400">{new Date(warehouse.createdAt).toLocaleDateString('ar-EG')}</td>
                </tr>
              ))}
              {warehouses.length === 0 && (
                <tr>
                  <td colSpan={3} className="px-6 py-8 text-center text-gray-500 dark:text-gray-400">لا يوجد مستودعات</td>
                </tr>
              )}
            </tbody>
          </table>
        </div>
      </div>

      <Modal isOpen={isModalOpen} onClose={() => setIsModalOpen(false)} title="إضافة مستودع جديد">
        <form onSubmit={handleSubmit} className="space-y-4">
          <div>
            <label className="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">اسم المستودع</label>
            <input
              type="text"
              required
              value={formData.name}
              onChange={(e) => setFormData({ ...formData, name: e.target.value })}
              className="w-full px-4 py-2 border border-gray-300 dark:border-white/10 dark:bg-white/5 dark:text-white rounded-lg focus:ring-2 focus:ring-slate-900 dark:focus:ring-blue-500 focus:border-slate-900 dark:focus:border-blue-500 outline-none transition-shadow"
              placeholder="مثال: مستودع دبي الرئيسي"
            />
          </div>
          <div>
            <label className="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">الموقع / العنوان</label>
            <input
              type="text"
              required
              value={formData.location}
              onChange={(e) => setFormData({ ...formData, location: e.target.value })}
              className="w-full px-4 py-2 border border-gray-300 dark:border-white/10 dark:bg-white/5 dark:text-white rounded-lg focus:ring-2 focus:ring-slate-900 dark:focus:ring-blue-500 focus:border-slate-900 dark:focus:border-blue-500 outline-none transition-shadow"
              placeholder="المدينة، المنطقة"
            />
          </div>
          <div className="pt-4 border-t border-gray-100 dark:border-white/5 flex gap-3">
            <button
              type="submit"
              disabled={isSubmitting}
              className="flex-1 bg-slate-900 dark:bg-blue-600 text-white px-4 py-2 rounded-lg text-sm font-medium hover:bg-slate-800 dark:hover:bg-blue-700 transition-colors disabled:opacity-50"
            >
              {isSubmitting ? 'جاري الحفظ...' : 'حفظ'}
            </button>
            <button
              type="button"
              onClick={() => setIsModalOpen(false)}
              className="flex-1 bg-gray-100 dark:bg-white/5 text-gray-700 dark:text-gray-300 px-4 py-2 rounded-lg text-sm font-medium hover:bg-gray-200 dark:hover:bg-white/10 transition-colors"
            >
              إلغاء
            </button>
          </div>
        </form>
      </Modal>
    </>
  );
}
