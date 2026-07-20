import { useEffect, useState } from 'react';
import { useParams } from 'react-router-dom';
import { Package, Truck, CheckCircle, AlertCircle, Calendar } from 'lucide-react';

export default function PublicTracking() {
  const { token } = useParams();
  const [data, setData] = useState<any>(null);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState<string | null>(null);

  useEffect(() => {
    fetch(`/api/shipments/track/${token}`)
      .then(async res => {
        if (!res.ok) {
          throw new Error('الشحنة غير موجودة أو الرابط غير صالح');
        }
        return res.json();
      })
      .then(data => {
        setData(data);
        setLoading(false);
      })
      .catch(err => {
        setError(err.message);
        setLoading(false);
      });
  }, [token]);

  if (loading) {
    return (
      <div className="min-h-screen bg-gray-50 flex items-center justify-center font-sans" dir="rtl">
        <div className="animate-pulse flex flex-col items-center">
          <div className="w-12 h-12 border-4 border-blue-600 border-t-transparent rounded-full animate-spin mb-4"></div>
          <p className="text-gray-500">جاري البحث عن الشحنة...</p>
        </div>
      </div>
    );
  }

  if (error || !data) {
    return (
      <div className="min-h-screen bg-gray-50 flex items-center justify-center font-sans" dir="rtl">
        <div className="bg-white p-8 rounded-2xl shadow-sm text-center max-w-sm w-full mx-4">
          <div className="w-16 h-16 bg-red-100 text-red-600 rounded-full flex items-center justify-center mx-auto mb-4">
            <AlertCircle className="w-8 h-8" />
          </div>
          <h2 className="text-xl font-bold text-gray-900 mb-2">عذراً</h2>
          <p className="text-gray-500">{error}</p>
        </div>
      </div>
    );
  }

  const statusLabels: Record<string, string> = {
    'pending': 'تم الاستلام', // Using user friendly names
    'received': 'في المستودع',
    'transit': 'في الطريق',
    'arrived': 'وصلت',
    'ready': 'جاهزة للتسليم',
    'collected': 'تم التسليم',
    'cancelled': 'ملغاة'
  };

  const statuses = ['pending', 'transit', 'arrived', 'ready', 'collected'];
  const currentIdx = statuses.indexOf(data.status) !== -1 ? statuses.indexOf(data.status) : (data.status === 'received' ? 0 : -1);
  const progressPercentage = Math.max(0, Math.min(100, (currentIdx / (statuses.length - 1)) * 100));

  const amountRemaining = (data.totalAmount || 0) - (data.paidAmount || 0);

  return (
    <div className="min-h-screen bg-gray-50 font-sans" dir="rtl">
      {/* Header */}
      <header className="bg-white border-b border-gray-200 sticky top-0 z-10">
        <div className="max-w-3xl mx-auto px-4 sm:px-6 h-16 flex items-center justify-between">
          <h1 className="text-xl font-bold tracking-tight text-gray-900">HM Cargo</h1>
          <span className="text-sm font-medium text-gray-500">تتبع الشحنة</span>
        </div>
      </header>

      <main className="max-w-3xl mx-auto px-4 sm:px-6 py-8 space-y-6">
        
        {/* Main Status Card */}
        <div className="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
          <div className="p-6 sm:p-8">
            <div className="flex flex-col sm:flex-row justify-between items-start gap-4 mb-8">
              <div>
                <p className="text-sm text-gray-500 mb-1">حالة الشحنة</p>
                <h2 className="text-3xl font-bold text-gray-900">{statusLabels[data.status] || data.status}</h2>
              </div>
              <div className="text-right sm:text-left">
                <p className="text-sm text-gray-500 mb-1">تاريخ الإنشاء</p>
                <p className="font-medium text-gray-900 flex items-center gap-2">
                  <Calendar className="w-4 h-4 text-gray-400" />
                  {new Date(data.createdAt).toLocaleDateString('ar-EG')}
                </p>
              </div>
            </div>

            {/* Progress Track */}
            <div className="relative mt-12 mb-8">
              <div className="absolute top-1/2 left-0 right-0 h-1.5 bg-gray-100 -translate-y-1/2 rounded-full z-0"></div>
              <div 
                className="absolute top-1/2 right-0 h-1.5 bg-blue-600 -translate-y-1/2 rounded-full z-0 transition-all duration-1000 ease-out"
                style={{ width: `${progressPercentage}%` }}
              ></div>
              
              <div className="relative z-10 flex justify-between">
                {statuses.map((status, index) => {
                  const isCompleted = index <= currentIdx;
                  const isCurrent = index === currentIdx;
                  
                  return (
                    <div key={status} className="flex flex-col items-center">
                      <div className={`w-8 h-8 rounded-full flex items-center justify-center border-4 bg-white transition-colors duration-300 ${
                        isCompleted 
                          ? 'border-blue-600 text-blue-600' 
                          : 'border-gray-100 text-gray-300'
                      }`}>
                        {isCompleted && <div className="w-2.5 h-2.5 rounded-full bg-blue-600 animate-pulse"></div>}
                      </div>
                      <span className={`absolute mt-10 text-xs font-medium whitespace-nowrap transition-colors duration-300 ${
                        isCurrent ? 'text-blue-600 font-bold' : (isCompleted ? 'text-gray-700' : 'text-gray-400')
                      }`}>
                        {statusLabels[status] || status}
                      </span>
                    </div>
                  );
                })}
              </div>
            </div>
          </div>
        </div>

        {/* Financial Info (Safe for public) */}
        <div className="grid grid-cols-1 sm:grid-cols-3 gap-4">
          <div className="bg-white rounded-2xl shadow-sm border border-gray-100 p-6">
            <p className="text-sm text-gray-500 mb-2">إجمالي المبلغ المطلوب</p>
            <p className="text-2xl font-bold text-gray-900">${(data.totalAmount / 100).toFixed(2)}</p>
          </div>
          <div className="bg-white rounded-2xl shadow-sm border border-gray-100 p-6">
            <p className="text-sm text-gray-500 mb-2">المبلغ المدفوع</p>
            <p className="text-2xl font-bold text-emerald-600">${(data.paidAmount / 100).toFixed(2)}</p>
          </div>
          <div className="bg-white rounded-2xl shadow-sm border border-gray-100 p-6">
            <p className="text-sm text-gray-500 mb-2">المتبقي للدفع</p>
            <p className="text-2xl font-bold text-rose-600">${(amountRemaining / 100).toFixed(2)}</p>
          </div>
        </div>

        {/* Packages List */}
        {data.packages && data.packages.length > 0 && (
          <div className="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
            <div className="p-6 border-b border-gray-100">
              <h3 className="text-lg font-semibold text-gray-900 flex items-center gap-2">
                <Package className="w-5 h-5 text-gray-400" />
                مسار الطرود ({data.packages.length})
              </h3>
            </div>
            <div className="divide-y divide-gray-100">
              {data.packages.map((pkg: any, idx: number) => (
                <div key={pkg.id || idx} className="p-6 flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                  <div className="flex items-center gap-4">
                    <div className="w-12 h-12 bg-blue-50 text-blue-600 rounded-xl flex items-center justify-center shrink-0">
                      <span className="font-bold text-lg">{idx + 1}</span>
                    </div>
                    <div>
                      <p className="font-medium text-gray-900 mb-1">طرد رقم {idx + 1}</p>
                      <p className="text-sm text-gray-500">الوزن: {pkg.weight} kg</p>
                    </div>
                  </div>
                  <div className="text-left">
                    <span className={`px-3 py-1.5 rounded-full text-xs font-medium inline-block
                      ${pkg.status === 'received' ? 'bg-amber-100 text-amber-700' : 
                         pkg.status === 'arrived' ? 'bg-emerald-100 text-emerald-700' :
                         pkg.status === 'collected' ? 'bg-blue-100 text-blue-700' : 
                        'bg-gray-100 text-gray-700'}`}
                    >
                      {statusLabels[pkg.status] || pkg.status}
                    </span>
                  </div>
                </div>
              ))}
            </div>
          </div>
        )}
        
      </main>
    </div>
  );
}
