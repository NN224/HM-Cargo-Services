import { useEffect, useState } from 'react';
import { Package, Truck, Clock, AlertCircle } from 'lucide-react';

export default function Dashboard() {
  const [data, setData] = useState({
    pendingShipments: 0,
    activeBatches: 0,
    readyShipments: 0,
    alerts: 0,
    latestShipments: [] as any[],
    activeBatchesList: [] as any[],
  });

  useEffect(() => {
    fetch('/api/dashboard')
      .then(res => res.json())
      .then(data => { if (data && Array.isArray(data.latestShipments)) setData(data); })
      .catch(console.error);
  }, []);

  const stats = [
    { name: 'شحنات قيد الانتظار', value: data.pendingShipments, icon: Clock, color: 'text-orange-600 dark:text-orange-400', bg: 'bg-orange-100 dark:bg-orange-900/30' },
    { name: 'رحلات نشطة', value: data.activeBatches, icon: Truck, color: 'text-blue-600 dark:text-blue-400', bg: 'bg-blue-100 dark:bg-blue-900/30' },
    { name: 'شحنات جاهزة للتسليم', value: data.readyShipments, icon: Package, color: 'text-emerald-600 dark:text-emerald-400', bg: 'bg-emerald-100 dark:bg-emerald-900/30' },
    { name: 'تنبيهات', value: data.alerts, icon: AlertCircle, color: 'text-rose-600 dark:text-rose-400', bg: 'bg-rose-100 dark:bg-rose-900/30' },
  ];

  return (
    <div className="space-y-6">
      <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
        {stats.map((stat) => (
          <div key={stat.name} className="bg-white dark:bg-[#0a0a0a] rounded-xl shadow-sm dark:shadow-none border border-gray-100 dark:border-white/5 p-6 flex items-center gap-4">
            <div className={`w-12 h-12 rounded-lg flex items-center justify-center ${stat.bg} ${stat.color}`}>
              <stat.icon className="w-6 h-6" />
            </div>
            <div>
              <p className="text-sm font-medium text-gray-500 dark:text-gray-400">{stat.name}</p>
              <p className="text-2xl font-bold text-gray-900 dark:text-gray-100">{stat.value}</p>
            </div>
          </div>
        ))}
      </div>

      <div className="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <div className="bg-white dark:bg-[#0a0a0a] rounded-xl shadow-sm dark:shadow-none border border-gray-100 dark:border-white/5 p-6 overflow-hidden">
          <div className="flex justify-between items-center mb-4">
            <h3 className="text-lg font-semibold text-gray-900 dark:text-gray-100">أحدث الشحنات</h3>
          </div>
          {data.latestShipments.length > 0 ? (
            <div className="overflow-x-auto">
              <table className="w-full text-right text-sm">
                <thead className="bg-gray-50 dark:bg-white/5 text-gray-600 dark:text-gray-400">
                  <tr>
                    <th className="px-4 py-2 font-medium">رقم التتبع</th>
                    <th className="px-4 py-2 font-medium">الحالة</th>
                    <th className="px-4 py-2 font-medium">الوزن</th>
                  </tr>
                </thead>
                <tbody className="divide-y divide-gray-100 dark:divide-white/5">
                  {data.latestShipments.map(shipment => (
                    <tr key={shipment.id} className="hover:bg-gray-50 dark:hover:bg-white/5">
                      <td className="px-4 py-3 font-medium font-mono text-gray-900 dark:text-gray-100">{shipment.trackingNumber}</td>
                      <td className="px-4 py-3">
                        <span className="bg-blue-100 dark:bg-blue-900/30 text-blue-700 dark:text-blue-400 px-2 py-1 rounded text-xs font-medium">{shipment.status}</span>
                      </td>
                      <td className="px-4 py-3 text-gray-600 dark:text-gray-400">{shipment.totalWeight} kg</td>
                    </tr>
                  ))}
                </tbody>
              </table>
            </div>
          ) : (
            <div className="text-center py-8 text-gray-500 dark:text-gray-400">لا توجد بيانات حالياً</div>
          )}
        </div>
        <div className="bg-white dark:bg-[#0a0a0a] rounded-xl shadow-sm dark:shadow-none border border-gray-100 dark:border-white/5 p-6 overflow-hidden">
          <div className="flex justify-between items-center mb-4">
            <h3 className="text-lg font-semibold text-gray-900 dark:text-gray-100">الرحلات (Batches) النشطة</h3>
          </div>
          {data.activeBatchesList.length > 0 ? (
            <div className="overflow-x-auto">
              <table className="w-full text-right text-sm">
                <thead className="bg-gray-50 dark:bg-white/5 text-gray-600 dark:text-gray-400">
                  <tr>
                    <th className="px-4 py-2 font-medium">رقم الرحلة</th>
                    <th className="px-4 py-2 font-medium">الحالة</th>
                    <th className="px-4 py-2 font-medium">تاريخ الإرسال</th>
                  </tr>
                </thead>
                <tbody className="divide-y divide-gray-100 dark:divide-white/5">
                  {data.activeBatchesList.map(batch => (
                    <tr key={batch.id} className="hover:bg-gray-50 dark:hover:bg-white/5">
                      <td className="px-4 py-3 font-medium font-mono text-gray-900 dark:text-gray-100">BCH-{batch.id.toString().padStart(4, '0')}</td>
                      <td className="px-4 py-3">
                        <span className="bg-amber-100 dark:bg-amber-900/30 text-amber-700 dark:text-amber-400 px-2 py-1 rounded text-xs font-medium">{batch.status}</span>
                      </td>
                      <td className="px-4 py-3 text-gray-600 dark:text-gray-400">
                        {batch.dispatchDate ? new Date(batch.dispatchDate).toLocaleDateString('ar-EG') : '—'}
                      </td>
                    </tr>
                  ))}
                </tbody>
              </table>
            </div>
          ) : (
            <div className="text-center py-8 text-gray-500 dark:text-gray-400">لا توجد بيانات حالياً</div>
          )}
        </div>
      </div>
    </div>
  );
}
