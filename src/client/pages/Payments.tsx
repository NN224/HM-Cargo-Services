import { useEffect, useState } from 'react';

export default function Payments() {
  const [payments, setPayments] = useState([]);

  useEffect(() => {
    fetch('/api/payments')
      .then(res => res.json())
      .then(data => { if (Array.isArray(data)) setPayments(data); })
      .catch(console.error);
  }, []);

  return (
    <div className="bg-white dark:bg-[#0a0a0a] rounded-xl shadow-sm dark:shadow-none border border-gray-100 dark:border-white/5 overflow-hidden">
      <div className="p-6 border-b border-gray-100 dark:border-white/5 flex justify-between items-center">
        <h3 className="text-lg font-semibold text-gray-900 dark:text-gray-100">المدفوعات</h3>
        <button className="bg-slate-900 dark:bg-blue-600 text-white px-4 py-2 rounded-lg text-sm font-medium hover:bg-slate-800 dark:hover:bg-blue-700">
          تسجيل دفعة
        </button>
      </div>
      <div className="overflow-x-auto">
        <table className="w-full text-right text-sm">
          <thead className="bg-gray-50 dark:bg-white/5 text-gray-600 dark:text-gray-400">
            <tr>
              <th className="px-6 py-3 font-medium">رقم العملية</th>
              <th className="px-6 py-3 font-medium">العميل</th>
              <th className="px-6 py-3 font-medium">المبلغ</th>
              <th className="px-6 py-3 font-medium">طريقة الدفع</th>
              <th className="px-6 py-3 font-medium">الحالة</th>
              <th className="px-6 py-3 font-medium">التاريخ</th>
            </tr>
          </thead>
          <tbody className="divide-y divide-gray-100 dark:divide-white/5">
            {payments.map((payment: any) => (
              <tr key={payment.id} className="hover:bg-gray-50 dark:hover:bg-white/5">
                <td className="px-6 py-4 text-gray-900 dark:text-gray-100 font-medium font-mono">TXN-{payment.id.toString().padStart(6, '0')}</td>
                <td className="px-6 py-4 text-gray-600 dark:text-gray-400">العميل #{payment.customerId}</td>
                <td className="px-6 py-4 text-gray-900 dark:text-gray-100 font-bold">${(payment.amount / 100).toFixed(2)}</td>
                <td className="px-6 py-4 text-gray-600 dark:text-gray-400">
                  {payment.method === 'cash' ? 'نقدي' : payment.method === 'bank' ? 'حوالة بنكية' : payment.method}
                </td>
                <td className="px-6 py-4">
                  {payment.status === 'completed' ? (
                    <span className="bg-emerald-100 dark:bg-emerald-900/30 text-emerald-700 dark:text-emerald-400 px-2 py-1 rounded text-xs font-medium">مكتمل</span>
                  ) : (
                    <span className="bg-rose-100 dark:bg-rose-900/30 text-rose-700 dark:text-rose-400 px-2 py-1 rounded text-xs font-medium">ملغى</span>
                  )}
                </td>
                <td className="px-6 py-4 text-gray-500 dark:text-gray-400">{new Date(payment.createdAt).toLocaleDateString('ar-EG')}</td>
              </tr>
            ))}
            {payments.length === 0 && (
              <tr>
                <td colSpan={6} className="px-6 py-8 text-center text-gray-500 dark:text-gray-400">لا يوجد مدفوعات</td>
              </tr>
            )}
          </tbody>
        </table>
      </div>
    </div>
  );
}
