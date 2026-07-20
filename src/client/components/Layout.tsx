import { Link, useLocation } from 'react-router-dom';
import { Package, Users, Truck, Warehouse, Banknote, Settings, LayoutDashboard, Moon, Sun } from 'lucide-react';
import { useEffect, useState } from 'react';

export default function Layout({ children }: { children: React.ReactNode }) {
  const location = useLocation();
  const [user, setUser] = useState<{name: string, email: string} | null>(null);
  const [isDark, setIsDark] = useState(() => {
    return localStorage.getItem('theme') !== 'light';
  });

  useEffect(() => {
    if (isDark) {
      document.documentElement.classList.add('dark');
      localStorage.setItem('theme', 'dark');
    } else {
      document.documentElement.classList.remove('dark');
      localStorage.setItem('theme', 'light');
    }
  }, [isDark]);

  useEffect(() => {
    fetch('/api/me')
      .then(res => res.json())
      .then(setUser)
      .catch(console.error);
  }, []);

  const navigation = [
    { name: 'الرئيسية', href: '/', icon: LayoutDashboard },
    { name: 'الشحنات', href: '/shipments', icon: Package },
    { name: 'الطرود', href: '/packages', icon: Package },
    { name: 'الرحلات (Batches)', href: '/batches', icon: Truck },
    { name: 'العملاء', href: '/customers', icon: Users },
    { name: 'المستودعات', href: '/warehouses', icon: Warehouse },
    { name: 'المدفوعات', href: '/payments', icon: Banknote },
    { name: 'الإعدادات', href: '/settings', icon: Settings },
  ];

  return (
    <div className="min-h-screen flex bg-gray-50 dark:bg-black text-right font-sans" dir="rtl">
      {/* Sidebar */}
      <div className="w-64 bg-slate-900 dark:bg-black text-white flex flex-col border-l border-slate-800 dark:border-white/5">
        <div className="p-6 border-b border-slate-800 dark:border-white/5">
          <h1 className="text-2xl font-bold tracking-tight text-white">HM Cargo</h1>
          <p className="text-slate-400 text-sm mt-1">نظام إدارة الشحنات</p>
        </div>
        <nav className="flex-1 px-4 space-y-1 mt-4">
          {navigation.map((item) => {
            const isActive = item.href === '/' ? location.pathname === '/' : location.pathname.startsWith(item.href);
            return (
              <Link
                key={item.name}
                to={item.href}
                className={`flex items-center gap-3 px-4 py-3 rounded-lg transition-all relative ${
                  isActive 
                    ? 'bg-blue-600/10 dark:bg-blue-500/10 text-blue-600 dark:text-blue-400 font-medium' 
                    : 'text-slate-400 hover:bg-slate-800 dark:hover:bg-white/5 hover:text-white'
                }`}
              >
                {isActive && (
                  <div className="absolute right-0 top-1/2 -translate-y-1/2 w-1 h-8 bg-blue-600 dark:bg-blue-400 rounded-l-full"></div>
                )}
                <item.icon className={`w-5 h-5 ${isActive ? 'text-blue-600 dark:text-blue-400' : 'text-slate-400'}`} />
                <span>{item.name}</span>
              </Link>
            );
          })}
        </nav>
        <div className="p-4 border-t border-slate-800 dark:border-white/5">
          {user ? (
            <div className="flex items-center gap-3">
              <div className="w-8 h-8 rounded-full bg-slate-700 dark:bg-white/10 flex items-center justify-center text-white">
                {user.name ? user.name.charAt(0).toUpperCase() : '?'}
              </div>
              <div>
                <p className="text-sm font-medium text-white">{user.name}</p>
                <p className="text-xs text-slate-400 dark:text-gray-500">{user.email}</p>
              </div>
            </div>
          ) : (
            <div className="h-8 animate-pulse bg-slate-800 dark:bg-white/5 rounded"></div>
          )}
        </div>
      </div>

      {/* Main Content */}
      <div className="flex-1 flex flex-col">
        <header className="bg-white dark:bg-black border-b border-gray-200 dark:border-white/5 h-16 flex items-center px-8 justify-between">
          <h2 className="text-xl font-semibold text-gray-800 dark:text-gray-100">
            {navigation.find(n => n.href === location.pathname)?.name || 'HM Cargo'}
          </h2>
          <button 
            onClick={() => setIsDark(!isDark)}
            className="p-2 rounded-lg bg-gray-100 dark:bg-white/5 text-gray-500 dark:text-gray-400 hover:bg-gray-200 dark:hover:bg-white/10 transition-colors"
          >
            {isDark ? <Sun className="w-5 h-5" /> : <Moon className="w-5 h-5" />}
          </button>
        </header>
        <main className="flex-1 p-8 overflow-auto">
          {children}
        </main>
      </div>
    </div>
  );
}
