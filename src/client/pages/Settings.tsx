import { useEffect, useState } from 'react';

export default function Settings() {
  const [user, setUser] = useState<{name: string, email: string} | null>(null);

  useEffect(() => {
    fetch('/api/me')
      .then(res => res.json())
      .then(setUser)
      .catch(console.error);
  }, []);

  return (
    <div className="bg-white dark:bg-[#0a0a0a] rounded-xl shadow-sm dark:shadow-none border border-gray-100 dark:border-white/5 overflow-hidden">
      <div className="p-6 border-b border-gray-100 dark:border-white/5">
        <h3 className="text-lg font-semibold text-gray-900 dark:text-gray-100">الإعدادات</h3>
      </div>
      <div className="p-6">
        <div className="max-w-2xl space-y-8">
          <section>
            <h4 className="text-md font-semibold text-gray-800 dark:text-gray-200 mb-4">إعدادات النظام</h4>
            <div className="space-y-4">
              <div>
                <label className="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">اسم الشركة</label>
                <input type="text" defaultValue="HM Cargo Services" className="w-full px-4 py-2 border border-gray-300 dark:border-white/10 dark:bg-white/5 dark:text-white rounded-lg outline-none transition-shadow disabled:opacity-50" disabled />
              </div>
              <div>
                <label className="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">العملة الافتراضية</label>
                <input type="text" defaultValue="USD ($)" className="w-full px-4 py-2 border border-gray-300 dark:border-white/10 rounded-lg bg-gray-50 dark:bg-white/5 text-gray-500 dark:text-gray-400 outline-none" disabled />
                <p className="text-xs text-gray-500 dark:text-gray-400 mt-1">العملة مدعومة فقط بالدولار الأمريكي وفقاً لقواعد النظام.</p>
              </div>
            </div>
          </section>
          
          <section>
            <h4 className="text-md font-semibold text-gray-800 dark:text-gray-200 mb-4">حساب المدير</h4>
            <div className="space-y-4">
              <div>
                <label className="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">البريد الإلكتروني</label>
                <input type="email" value={user?.email || ''} className="w-full px-4 py-2 border border-gray-300 dark:border-white/10 dark:bg-white/5 dark:text-white rounded-lg outline-none transition-shadow disabled:opacity-50" disabled />
              </div>
              <div>
                <button className="text-blue-600 dark:text-blue-400 text-sm font-medium hover:underline">تغيير كلمة المرور</button>
              </div>
            </div>
          </section>
          
          <div className="pt-4 border-t border-gray-100 dark:border-white/5">
            <button className="bg-slate-900 dark:bg-blue-600 text-white px-6 py-2 rounded-lg text-sm font-medium hover:bg-slate-800 dark:hover:bg-blue-700 transition-colors">
              حفظ التغييرات
            </button>
          </div>
        </div>
      </div>
    </div>
  );
}
