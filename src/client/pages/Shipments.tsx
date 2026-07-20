import { useEffect, useState } from 'react';
import { Search, Filter } from 'lucide-react';
import Modal from '../components/Modal';

export default function Shipments() {
  const [shipments, setShipments] = useState<any[]>([]);
  const [trackingNumber, setTrackingNumber] = useState('');
  const [trackedShipment, setTrackedShipment] = useState<any>(null);
  const [trackingError, setTrackingError] = useState('');
  
  const [filterStatus, setFilterStatus] = useState<string>('all');
  const [isModalOpen, setIsModalOpen] = useState(false);
  const [formData, setFormData] = useState({ recipientName: '', recipientPhone: '', customerId: 1, packages: [{ weight: 0 }] });
  const [isSubmitting, setIsSubmitting] = useState(false);

  const [isEditModalOpen, setIsEditModalOpen] = useState(false);
  const [editingShipment, setEditingShipment] = useState<any>(null);


  
  
  const handleEditClick = (shipment: any) => {
    setEditingShipment(shipment);
    setIsEditModalOpen(true);
  };

  const handleEditSubmit = async (e: React.FormEvent) => {
    e.preventDefault();
    if (!editingShipment) return;
    setIsSubmitting(true);
    try {
      const res = await fetch(`/api/shipments/${editingShipment.id}`, {
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

  const handleDeliver = async (id: number) => {
    if (!confirm('هل أنت متأكد من تسليم هذه الشحنة للعميل؟')) return;
    try {
      const res = await fetch(`/api/shipments/${id}/collect`, { method: 'PUT' });
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

  const fetchShipments = () => {
    fetch('/api/shipments')
      .then(res => res.json())
      .then(data => { if (Array.isArray(data)) setShipments(data); })
      .catch(console.error);
  };

  useEffect(() => {
    fetchShipments();
  }, []);

  const handleTrack = async (e: React.FormEvent) => {
    e.preventDefault();
    if (!trackingNumber.trim()) return;
    
    setTrackingError('');
    setTrackedShipment(null);
    
    try {
      const res = await fetch(`/api/shipments/track/${trackingNumber}`);
      if (!res.ok) {
        setTrackingError('لم يتم العثور على شحنة بهذا الرقم');
        return;
      }
      const data = await res.json();
      setTrackedShipment(data);
    } catch (err) {
      setTrackingError('حدث خطأ أثناء البحث');
    }
  };

  const handleSubmit = async (e: React.FormEvent) => {
    e.preventDefault();
    setIsSubmitting(true);
    try {
      const res = await fetch('/api/shipments', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(formData),
      });
      if (res.ok) {
        setIsModalOpen(false);
        setFormData({ recipientName: '', recipientPhone: '', customerId: 1, packages: [{ weight: 0 }] });
        fetchShipments();
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

  const filteredShipments = shipments.filter(s => filterStatus === 'all' || s.status === filterStatus);

  const statusLabels: Record<string, string> = {
    'pending': 'قيد الانتظار',
    'received': 'تم الاستلام',
    'transit': 'في الطريق',
    'arrived': 'وصلت',
    'ready': 'جاهزة',
    'collected': 'تم التسليم',
    'cancelled': 'ملغاة'
  };

  return (
    <>
      <div className="space-y-6">
        {/* Tracking Section */}
        <div className="bg-white dark:bg-black rounded-xl shadow-sm dark:shadow-none border border-gray-100 dark:border-white/5 overflow-hidden p-6">
          <h3 className="text-lg font-semibold text-gray-900 dark:text-gray-100 mb-4">تتبع الشحنة</h3>
          <form onSubmit={handleTrack} className="flex gap-4">
            <div className="relative flex-1 max-w-md">
              <div className="absolute inset-y-0 right-0 flex items-center pr-3 pointer-events-none">
                <Search className="w-5 h-5 text-gray-400 dark:text-gray-500" />
              </div>
              <input
                type="text"
                value={trackingNumber}
                onChange={(e) => setTrackingNumber(e.target.value)}
                placeholder="أدخل رقم التتبع..."
                className="w-full pr-10 pl-4 py-2 border border-gray-300 dark:border-white/10 dark:bg-white/5 dark:text-white rounded-lg focus:ring-2 focus:ring-slate-900 dark:focus:ring-blue-500 focus:border-slate-900 dark:focus:border-blue-500 outline-none transition-shadow"
              />
            </div>
            <button type="submit" className="bg-slate-900 dark:bg-blue-600 text-white px-6 py-2 rounded-lg text-sm font-medium hover:bg-slate-800 dark:hover:bg-blue-700 transition-colors">
              تتبع
            </button>
          </form>
          
          {trackingError && (
            <div className="mt-4 p-3 bg-red-50 dark:bg-red-900/30 text-red-600 dark:text-red-400 rounded-lg text-sm border border-red-100 dark:border-red-900/50">
              {trackingError}
            </div>
          )}

          {trackedShipment && (
            <div className="mt-6 p-6 bg-gray-50 dark:bg-white/5 rounded-lg border border-gray-200 dark:border-white/5">
              <div className="grid grid-cols-2 md:grid-cols-4 gap-6">
                <div>
                  <p className="text-sm text-gray-500 dark:text-gray-400 mb-1">رقم التتبع</p>
                  <p className="font-mono font-medium text-gray-900 dark:text-gray-100">{trackedShipment.trackingNumber}</p>
                </div>
                <div>
                  <p className="text-sm text-gray-500 dark:text-gray-400 mb-1">الحالة الحالية</p>
                  <span className="bg-blue-100 dark:bg-blue-900/30 text-blue-700 dark:text-blue-400 px-2 py-1 rounded text-xs font-medium inline-block mt-1">
                    {statusLabels[trackedShipment.status] || trackedShipment.status}
                  </span>
                </div>
                <div>
                  <p className="text-sm text-gray-500 dark:text-gray-400 mb-1">المستلم</p>
                  <p className="font-medium text-gray-900 dark:text-gray-100">{trackedShipment.recipientName}</p>
                </div>
                <div>
                  <p className="text-sm text-gray-500 dark:text-gray-400 mb-1">تاريخ الإنشاء</p>
                  <p className="font-medium text-gray-900 dark:text-gray-100">
                    {new Date(trackedShipment.createdAt).toLocaleDateString('ar-EG')}
                  </p>
                </div>
              </div>
              
              {/* Progress Bar Timeline */}
              {trackedShipment.packages && trackedShipment.packages.length > 0 && (
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
                <h4 className="text-sm font-medium text-gray-900 dark:text-gray-100 mb-6">مسار الشحنة</h4>
                <div className="relative">
                  {/* Background Track */}
                  <div className="absolute top-1/2 left-0 right-0 h-1.5 bg-gray-200 dark:bg-white/10 -translate-y-1/2 rounded-full z-0"></div>
                  
                  {(() => {
                    const statuses = ['pending', 'transit', 'arrived', 'ready', 'collected'];
                    const currentIdx = statuses.indexOf(trackedShipment.status) !== -1 ? statuses.indexOf(trackedShipment.status) : (trackedShipment.status === 'received' ? 0 : -1);
                    const progressPercentage = Math.max(0, Math.min(100, (currentIdx / (statuses.length - 1)) * 100));
                    
                    return (
                      <>
                        {/* Active Progress */}
                        <div 
                          className="absolute top-1/2 right-0 h-1.5 bg-blue-600 dark:bg-blue-500 -translate-y-1/2 rounded-full z-0 transition-all duration-500 ease-in-out"
                          style={{ width: `${progressPercentage}%` }}
                        ></div>
                        
                        <div className="flex items-center justify-between relative z-10">
                          {statuses.map((status, index) => {
                            const isCompleted = index <= currentIdx;
                            const isCurrent = index === currentIdx;
                            
                            return (
                              <div key={status} className="flex flex-col items-center">
                                <div className={`w-8 h-8 rounded-full flex items-center justify-center border-4 bg-white dark:bg-black transition-colors duration-300 ${
                                  isCompleted 
                                    ? 'border-blue-600 dark:border-blue-500 text-blue-600 dark:text-blue-500' 
                                    : 'border-gray-200 dark:border-white/10 text-gray-300 dark:text-white/20'
                                }`}>
                                  {isCompleted && <div className="w-2.5 h-2.5 rounded-full bg-blue-600 dark:bg-blue-500 animate-pulse"></div>}
                                </div>
                                <span className={`absolute mt-10 text-xs font-medium whitespace-nowrap transition-colors duration-300 ${
                                  isCurrent ? 'text-blue-600 dark:text-blue-400 font-bold' : (isCompleted ? 'text-gray-700 dark:text-gray-300' : 'text-gray-400 dark:text-gray-600')
                                }`}>
                                  {statusLabels[status] || status}
                                </span>
                              </div>
                            );
                          })}
                        </div>
                      </>
                    );
                  })()}
                </div>
                <div className="h-6"></div> {/* Spacer for absolute text */}
              </div>
            </div>
          )}
        </div>

        <div className="bg-white dark:bg-black rounded-xl shadow-sm dark:shadow-none border border-gray-100 dark:border-white/5 overflow-hidden">
          <div className="p-6 border-b border-gray-100 dark:border-white/5 flex flex-col sm:flex-row sm:items-center justify-between gap-4">
            <h3 className="text-lg font-semibold text-gray-900 dark:text-gray-100">سجل الشحنات</h3>
            
            <div className="flex items-center gap-3">
              <div className="relative">
                <div className="absolute inset-y-0 right-0 flex items-center pr-3 pointer-events-none">
                  <Filter className="w-4 h-4 text-gray-400" />
                </div>
                <select
                  value={filterStatus}
                  onChange={(e) => setFilterStatus(e.target.value)}
                  className="pl-4 pr-10 py-2 border border-gray-300 dark:border-white/10 dark:bg-white/5 dark:text-white rounded-lg text-sm focus:ring-2 focus:ring-slate-900 dark:focus:ring-blue-500 outline-none appearance-none"
                  dir="rtl"
                >
                  <option value="all">جميع الحالات</option>
                  {Object.entries(statusLabels).map(([key, label]) => (
                    <option key={key} value={key}>{label}</option>
                  ))}
                </select>
              </div>
              <button 
                onClick={() => setIsModalOpen(true)}
                className="bg-slate-900 dark:bg-blue-600 text-white px-4 py-2 rounded-lg text-sm font-medium hover:bg-slate-800 dark:hover:bg-blue-700 whitespace-nowrap"
              >
                إضافة شحنة
              </button>
            </div>
          </div>
          <div className="overflow-x-auto">
            <table className="w-full text-right text-sm">
              <thead className="bg-gray-50 dark:bg-white/5 text-gray-600 dark:text-gray-400">
                <tr>
                  <th className="px-6 py-3 font-medium">رقم الشحنة</th>
                  <th className="px-6 py-3 font-medium">اسم العميل</th>
                  <th className="px-6 py-3 font-medium">الوجهة</th>
                  <th className="px-6 py-3 font-medium">الوزن</th>
                  <th className="px-6 py-3 font-medium">الحالة</th>
                  <th className="px-6 py-3 font-medium">الإجراءات</th>
                </tr>
              </thead>
              <tbody className="divide-y divide-gray-100 dark:divide-white/5">
                {filteredShipments.map((shipment: any) => (
                  <tr key={shipment.id} className="hover:bg-gray-50 dark:hover:bg-white/5 transition-colors">
                    <td className="px-6 py-4 text-gray-900 dark:text-gray-100 font-medium font-mono">{shipment.trackingNumber}</td>
                    <td className="px-6 py-4 text-gray-600 dark:text-gray-400">{shipment.recipientName}</td>
                    <td className="px-6 py-4 text-gray-500 dark:text-gray-500">-</td>
                    <td className="px-6 py-4 text-gray-600 dark:text-gray-400">{shipment.totalWeight} kg</td>
                    <td className="px-6 py-4">
                      <span className={`px-2 py-1 rounded text-xs font-medium inline-block
                        ${shipment.status === 'pending' ? 'bg-amber-100 text-amber-700 dark:bg-amber-900/30 dark:text-amber-400' : 
                          shipment.status === 'collected' ? 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-400' : 
                          shipment.status === 'cancelled' ? 'bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-400' :
                          'bg-blue-100 text-blue-700 dark:bg-blue-900/30 dark:text-blue-400'}`}
                      >
                        {statusLabels[shipment.status] || shipment.status}
                      </span>
                    </td>
                                        <td className="px-6 py-4 flex gap-2">
                      <button
                        onClick={() => handleEditClick(shipment)}
                        className="text-xs bg-blue-100 hover:bg-blue-200 dark:bg-blue-900/30 dark:hover:bg-blue-800/30 text-blue-700 dark:text-blue-400 px-3 py-1.5 rounded transition-colors"
                      >
                        تعديل
                      </button>
                      <button
                        onClick={() => {
                          const link = `${window.location.origin}/track/${shipment.trackingNumber}`;
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
                      )}
                    </td>
                  </tr>
                ))}
                {filteredShipments.length === 0 && (
                  <tr>
                    <td colSpan={6} className="px-6 py-12 text-center text-gray-500 dark:text-gray-400">
                      لا يوجد شحنات مسجلة
                    </td>
                  </tr>
                )}
              </tbody>
            </table>
          </div>
        </div>
      </div>

      <Modal isOpen={isModalOpen} onClose={() => setIsModalOpen(false)} title="إضافة شحنة جديدة">
        <form onSubmit={handleSubmit} className="space-y-4">
          <div>
            <label className="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">اسم المستلم</label>
            <input
              type="text"
              required
              value={formData.recipientName}
              onChange={(e) => setFormData({ ...formData, recipientName: e.target.value })}
              className="w-full px-4 py-2 border border-gray-300 dark:border-white/10 dark:bg-white/5 dark:text-white rounded-lg outline-none transition-shadow"
              placeholder="الاسم الكامل للمستلم..."
            />
          </div>
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

