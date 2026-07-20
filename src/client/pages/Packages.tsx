import { useEffect, useState } from 'react';
import { Package, Search, CheckCircle } from 'lucide-react';

export default function Packages() {
  const [packages, setPackages] = useState<any[]>([]);
  const [barcode, setBarcode] = useState('');
  const [isScanning, setIsScanning] = useState(false);

  const fetchPackages = () => {
    fetch('/api/packages')
      .then(res => res.json())
      .then(data => { if (Array.isArray(data)) setPackages(data); })
      .catch(console.error);
  };

  useEffect(() => {
    fetchPackages();
  }, []);

  const handleScan = async (e: React.FormEvent) => {
    e.preventDefault();
    if (!barcode.trim()) return;
    
    setIsScanning(true);
    try {
      const res = await fetch(`/api/packages/${barcode.trim()}/scan`, {
        method: 'PUT',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ status: 'arrived' }),
      });
      
      if (res.ok) {
        setBarcode('');
        fetchPackages();
        alert('تم تسجيل وصول الطرد بنجاح');
      } else {
        const err = await res.json();
        alert(err.error || 'حدث خطأ أثناء فحص الباركود');
      }
    } catch (error) {
      console.error(error);
      alert('حدث خطأ في الاتصال');
    } finally {
      setIsScanning(false);
    }
  };

  const statusLabels: Record<string, string> = {
    'received': 'تم الاستلام',
    'transit': 'في الطريق',
    'arrived': 'وصل',
    'missing': 'مفقود',
    'damaged': 'تالف',
    'collected': 'تم التسليم'
  };

  return (
    <div className="space-y-6">
      <div className="bg-white dark:bg-[#0a0a0a] rounded-xl shadow-sm dark:shadow-none border border-gray-100 dark:border-white/5 overflow-hidden p-6">
        <h3 className="text-lg font-semibold text-gray-900 dark:text-gray-100 mb-4">مسح الطرود (وصول إلى المستودع)</h3>
        <form onSubmit={handleScan} className="flex gap-4">
          <div className="relative flex-1 max-w-md">
            <div className="absolute inset-y-0 right-0 flex items-center pr-3 pointer-events-none">
              <Search className="w-5 h-5 text-gray-400 dark:text-gray-500" />
            </div>
            <input
              type="text"
              required
              value={barcode}
              onChange={(e) => setBarcode(e.target.value)}
              className="w-full pr-10 pl-4 py-2 border border-gray-300 dark:border-white/10 dark:bg-white/5 dark:text-white rounded-lg focus:ring-2 focus:ring-slate-900 dark:focus:ring-blue-500 outline-none transition-shadow"
              placeholder="امسح باركود الطرد..."
              dir="ltr"
            />
          </div>
          <button
            type="submit"
            disabled={isScanning}
            className="bg-slate-900 dark:bg-blue-600 text-white px-6 py-2 rounded-lg text-sm font-medium hover:bg-slate-800 dark:hover:bg-blue-700 transition-colors disabled:opacity-50 flex items-center gap-2"
          >
            <CheckCircle className="w-4 h-4" />
            {isScanning ? 'جاري الفحص...' : 'تسجيل الوصول'}
          </button>
        </form>
      </div>

      <div className="bg-white dark:bg-[#0a0a0a] rounded-xl shadow-sm dark:shadow-none border border-gray-100 dark:border-white/5 overflow-hidden">
        <div className="p-6 border-b border-gray-100 dark:border-white/5">
          <h3 className="text-lg font-semibold text-gray-900 dark:text-gray-100">سجل الطرود</h3>
        </div>
        <div className="overflow-x-auto">
          <table className="w-full text-right text-sm">
            <thead className="bg-gray-50 dark:bg-white/5 text-gray-600 dark:text-gray-400">
              <tr>
                <th className="px-6 py-3 font-medium">الباركود</th>
                <th className="px-6 py-3 font-medium">رقم الشحنة الداخلي</th>
                <th className="px-6 py-3 font-medium">الوزن</th>
                <th className="px-6 py-3 font-medium">الحالة</th>
              </tr>
            </thead>
            <tbody className="divide-y divide-gray-100 dark:divide-white/5">
              {packages.map((pkg: any) => (
                <tr key={pkg.id} className="hover:bg-gray-50 dark:hover:bg-white/5 transition-colors">
                  <td className="px-6 py-4 text-gray-900 dark:text-gray-100 font-medium font-mono" dir="ltr">{pkg.barcode}</td>
                  <td className="px-6 py-4 text-gray-600 dark:text-gray-400">#{pkg.shipmentId}</td>
                  <td className="px-6 py-4 text-gray-600 dark:text-gray-400">{pkg.weight} kg</td>
                  <td className="px-6 py-4">
                    <span className={`px-2 py-1 rounded text-xs font-medium inline-block
                      ${pkg.status === 'received' ? 'bg-amber-100 text-amber-700 dark:bg-amber-900/30 dark:text-amber-400' : 
                         pkg.status === 'arrived' ? 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-400' :
                         pkg.status === 'collected' ? 'bg-blue-100 text-blue-700 dark:bg-blue-900/30 dark:text-blue-400' : 
                        'bg-gray-100 text-gray-700 dark:bg-gray-800 dark:text-gray-300'}`}
                    >
                      {statusLabels[pkg.status] || pkg.status}
                    </span>
                  </td>
                </tr>
              ))}
              {packages.length === 0 && (
                <tr>
                  <td colSpan={4} className="px-6 py-12 text-center text-gray-500 dark:text-gray-400">
                    لا يوجد طرود مسجلة
                  </td>
                </tr>
              )}
            </tbody>
          </table>
        </div>
      </div>
    </div>
  );
}
