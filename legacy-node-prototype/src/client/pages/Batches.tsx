import { useEffect, useState } from 'react';

export default function Batches() {
  const [batches, setBatches] = useState([]);

  useEffect(() => {
    fetch('/api/batches')
      .then(res => res.json())
      .then(data => { if (Array.isArray(data)) setBatches(data); })
      .catch(console.error);
  }, []);

  return (
    <div className="bg-white dark:bg-black rounded-xl shadow-sm dark:shadow-none border border-gray-100 dark:border-white/5 overflow-hidden">
      <div className="p-6 border-b border-gray-100 dark:border-white/5 flex justify-between items-center">
        <h3 className="text-lg font-semibold text-gray-900 dark:text-gray-100">الرحلات (Batches)</h3>
        <button className="bg-slate-900 dark:bg-blue-600 text-white px-4 py-2 rounded-lg text-sm font-medium hover:bg-slate-800 dark:hover:bg-blue-700">
          إضافة رحلة
        </button>
      </div>
      <div className="overflow-x-auto">
        <table className="w-full text-right text-sm">
          <thead className="bg-gray-50 dark:bg-white/5 text-gray-600 dark:text-gray-400">
            <tr>
              <th className="px-6 py-3 font-medium">رقم الرحلة</th>
              <th className="px-6 py-3 font-medium">الحالة</th>
              <th className="px-6 py-3 font-medium">تاريخ الإرسال</th>
              <th className="px-6 py-3 font-medium">تاريخ الوصول</th>
              <th className="px-6 py-3 font-medium">التكلفة (للكيلو)</th>
            </tr>
          </thead>
          <tbody className="divide-y divide-gray-100 dark:divide-white/5">
            {batches.map((batch: any) => (
              <tr key={batch.id} className="hover:bg-gray-50 dark:hover:bg-white/5">
                <td className="px-6 py-4 text-gray-900 dark:text-gray-100 font-medium font-mono">BCH-{batch.id.toString().padStart(4, '0')}</td>
                <td className="px-6 py-4">
                  <span className="bg-amber-100 dark:bg-amber-900/30 text-amber-700 dark:text-amber-400 px-2 py-1 rounded text-xs font-medium">{batch.status}</span>
                </td>
                <td className="px-6 py-4 text-gray-600 dark:text-gray-400">{batch.dispatchDate ? new Date(batch.dispatchDate).toLocaleDateString('ar-EG') : '—'}</td>
                <td className="px-6 py-4 text-gray-600 dark:text-gray-400">{batch.arrivalDate ? new Date(batch.arrivalDate).toLocaleDateString('ar-EG') : '—'}</td>
                <td className="px-6 py-4 text-gray-900 dark:text-gray-100 font-medium">{batch.costPerKg ? `$${(batch.costPerKg / 100).toFixed(2)}` : '—'}</td>
              </tr>
            ))}
            {batches.length === 0 && (
              <tr>
                <td colSpan={5} className="px-6 py-8 text-center text-gray-500 dark:text-gray-400">لا يوجد رحلات</td>
              </tr>
            )}
          </tbody>
        </table>
      </div>
    </div>
  );
}
