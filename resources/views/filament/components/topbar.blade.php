@php
    use App\Filament\Pages\OperationalReports;
    use App\Filament\Pages\ReceiveIntoBatch;
    use App\Filament\Pages\ScanPackages;
    use App\Filament\Resources\Batches\BatchResource;
    use App\Filament\Resources\Customers\CustomerResource;
    use App\Filament\Resources\Payments\PaymentResource;
    use App\Filament\Resources\Routes\RouteResource;
    use App\Filament\Resources\Shipments\ShipmentResource;
    use App\Filament\Resources\Users\UserResource;
    use App\Filament\Resources\Warehouses\WarehouseResource;

    $user = auth()->user();
    $currentUrl = request()->url();
@endphp

<header class="hm-global-topbar" dir="rtl" x-data="{ mobileOpen: false, menuOpen: false }">
    <div class="hm-topbar-inner">
        <!-- Brand & Mobile Toggle & Notifications -->
        <div class="hm-brand-group">
            <button type="button" class="hm-mobile-toggle" @click="mobileOpen = !mobileOpen" aria-label="قائمة التصفح">
                ☰
            </button>
            <a href="{{ filament()->getHomeUrl() }}" class="hm-brand-logo">HM</a>
            @if ($user)
                <livewire:notification-dropdown />
            @endif
        </div>

        <!-- Main Navbar Links -->
        <nav class="hm-nav-links" :class="{ 'hm-mobile-active': mobileOpen }" aria-label="التنقل الرئيسي">
            <a href="{{ filament()->getHomeUrl() }}" class="{{ request()->routeIs('filament.admin.pages.dashboard') ? 'active' : '' }}">لوحة التحكم</a>
            <a href="{{ ReceiveIntoBatch::getUrl() }}" class="{{ str_contains($currentUrl, '/receive-into-batch') ? 'active' : '' }}">استلام بضاعة</a>
            <a href="{{ ScanPackages::getUrl() }}" class="{{ str_contains($currentUrl, '/scan-packages') ? 'active' : '' }}">تسليم بضاعة</a>
            <a href="{{ ShipmentResource::getUrl('index') }}" class="{{ str_contains($currentUrl, '/shipments') ? 'active' : '' }}">الشحنات</a>
            <a href="{{ BatchResource::getUrl('index') }}" class="{{ str_contains($currentUrl, '/batches') ? 'active' : '' }}">الرحلات</a>
            <a href="{{ CustomerResource::getUrl('index') }}" class="{{ str_contains($currentUrl, '/customers') ? 'active' : '' }}">العملاء</a>
            <a href="{{ PaymentResource::getUrl('index') }}" class="{{ str_contains($currentUrl, '/payments') ? 'active' : '' }}">المدفوعات</a>
        </nav>

        <!-- Actions Group (Theme Toggle & Settings) -->
        <div class="hm-actions-group">
            <!-- Theme Toggle Button (Light/Dark Mode) -->
            <button type="button" 
                    class="hm-theme-btn" 
                    x-data="{ isDark: document.documentElement.classList.contains('dark') }" 
                    x-on:click="
                        isDark = !isDark;
                        if (isDark) {
                            document.documentElement.classList.add('dark');
                            localStorage.setItem('theme', 'dark');
                        } else {
                            document.documentElement.classList.remove('dark');
                            localStorage.setItem('theme', 'light');
                        }
                    " 
                    title="تغيير المظهر (داكن / فاتح)" 
                    aria-label="تغيير المظهر">
                <template x-if="isDark">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="hm-theme-icon">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 3v2.25m0 13.5V21m8.966-8.966h-2.25m-13.5 0h-2.25m15.356-6.401l-1.591 1.591M6.563 17.437l-1.591 1.591m12.728 0l-1.591-1.591M6.563 6.563L4.972 4.972M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                    </svg>
                </template>
                <template x-if="!isDark">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="hm-theme-icon">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M21.752 15.002A9.718 9.718 0 0118 15.75c-5.385 0-9.75-4.365-9.75-9.75 0-1.33.266-2.597.748-3.752A9.753 9.753 0 003 11.25C3 16.635 7.365 21 12.75 21a9.753 9.753 0 009.002-5.998z" />
                    </svg>
                </template>
            </button>

            <!-- Admin / Settings Dropdown Button -->
            <div class="hm-admin-dropdown" x-data="{ menuOpen: false }">
                <button type="button" class="hm-settings-btn" x-on:click="menuOpen = !menuOpen" aria-label="الإعدادات والحساب" :aria-expanded="menuOpen">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="hm-gear-icon">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9.594 3.94c.09-.542.56-.94 1.11-.94h2.593c.55 0 1.02.398 1.11.94l.213 1.281c.063.374.313.686.645.87.074.04.147.083.22.127.325.196.72.257 1.075.124l1.217-.456a1.125 1.125 0 011.37.49l1.296 2.247a1.125 1.125 0 01-.26 1.431l-1.003.827c-.293.241-.438.613-.43.992a7.723 7.723 0 010 .255c-.008.378.137.75.43.991l1.004.827c.424.35.534.955.26 1.43l-1.298 2.247a1.125 1.125 0 01-1.369.491l-1.217-.456c-.355-.133-.75-.072-1.076.124a6.47 6.47 0 01-.22.128c-.331.183-.581.495-.644.869l-.213 1.281c-.09.543-.56.94-1.11.94h-2.594c-.55 0-1.019-.398-1.11-.94l-.213-1.281c-.062-.374-.312-.686-.644-.87a6.52 6.52 0 01-.22-.127c-.325-.196-.72-.257-1.076-.124l-1.217.456a1.125 1.125 0 01-1.369-.49l-1.297-2.247a1.125 1.125 0 01.26-1.431l1.004-.827c.292-.24.437-.613.43-.991a6.932 6.932 0 010-.255c.007-.38-.138-.751-.43-.992l-1.004-.827a1.125 1.125 0 01-.26-1.43l1.297-2.247a1.125 1.125 0 011.37-.491l1.216.456c.356.133.751.072 1.076-.124.072-.044.146-.086.22-.128.332-.183.582-.495.644-.869l.214-1.281z" />
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                    </svg>
                </button>
                <div class="hm-dropdown-menu" x-show="menuOpen" x-transition x-on:click.away="menuOpen = false" x-cloak>
                    @if ($user)
                        <div class="hm-menu-user-info">
                            <strong>{{ $user->name }}</strong>
                            <small>{{ $user->isAdministrator() ? 'مدير النظام' : ($user->warehouse?->name ?? 'موظف مستودع') }}</small>
                        </div>
                        <hr class="hm-menu-hr">
                    @endif
                    <a href="{{ WarehouseResource::getUrl('index') }}" class="{{ str_contains($currentUrl, '/warehouses') ? 'active' : '' }}">المستودعات</a>
                    <a href="{{ RouteResource::getUrl('index') }}" class="{{ str_contains($currentUrl, '/routes') ? 'active' : '' }}">المسارات</a>
                    <a href="{{ UserResource::getUrl('index') }}" class="{{ str_contains($currentUrl, '/users') ? 'active' : '' }}">الموظفون</a>
                    <a href="{{ OperationalReports::getUrl() }}" class="{{ str_contains($currentUrl, '/operational-reports') ? 'active' : '' }}">التقارير التشغيلية</a>
                    @if ($user)
                        <hr class="hm-menu-hr">
                        <form method="POST" action="{{ filament()->getLogoutUrl() }}">
                            @csrf
                            <button type="submit" class="hm-menu-logout">تسجيل الخروج 🚪</button>
                        </form>
                    @endif
                </div>
            </div>
        </div>
    </div>
</header>

<style>
    /* Global layout overrides to hide default Filament sidebar & topbar everywhere */
    .fi-sidebar,
    .fi-topbar {
        display: none !important;
    }
    /* Toast notifications position override to float below global topbar */
    .fi-no,
    .fi-notifications {
        top: 80px !important;
        z-index: 9999 !important;
    }
    .fi-layout {
        padding: 0 !important;
        margin: 0 !important;
    }
    .fi-main-ctn {
        padding: 0 !important;
        margin: 0 !important;
        width: 100% !important;
        max-width: 100% !important;
    }
    .fi-main {
        max-width: 1540px !important;
        margin: 0 auto !important;
        padding: 24px clamp(16px, 4vw, 48px) !important;
    }

    /* Custom HM Global Navbar Styling */
    .hm-global-topbar {
        background: #090909;
        border-bottom: 1px solid #1f1f23;
        position: sticky;
        top: 0;
        z-index: 100;
        font-family: inherit;
        color: #f4f4f5;
    }
    .hm-topbar-inner {
        max-width: 1540px;
        margin: 0 auto;
        padding: 0 clamp(16px, 3vw, 40px);
        height: 64px;
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 16px;
    }
    .hm-brand-group {
        display: flex;
        align-items: center;
        gap: 12px;
    }
    .hm-brand-logo {
        font-size: 22px;
        font-weight: 900;
        color: #ffffff;
        text-decoration: none;
        letter-spacing: 1px;
    }
    .hm-mobile-toggle {
        display: none;
        background: transparent;
        border: 1px solid #27272a;
        color: #f4f4f5;
        font-size: 18px;
        padding: 4px 10px;
        border-radius: 8px;
        cursor: pointer;
    }
    .hm-nav-links {
        display: flex;
        align-items: center;
        gap: 8px;
        flex: 1;
        justify-content: center;
    }
    .hm-nav-links a {
        padding: 8px 16px;
        color: #a1a1aa;
        text-decoration: none;
        font-size: 14px;
        font-weight: 600;
        border-radius: 8px;
        transition: all 0.2s;
        white-space: nowrap;
    }
    .hm-nav-links a:hover {
        color: #ffffff;
        background: rgba(255, 255, 255, 0.05);
    }
    .hm-nav-links a.active {
        color: #ffffff;
        background: #18181b;
        border-bottom: 2px solid #3b82f6;
        font-weight: 700;
    }
    .hm-actions-group {
        display: flex;
        align-items: center;
        gap: 10px;
    }
    .hm-notif-dropdown {
        position: relative;
    }
    .hm-bell-btn {
        width: 40px;
        height: 40px;
        border-radius: 10px;
        border: 1px solid #27272a;
        background: #18181b;
        color: #f59e0b;
        display: grid;
        place-items: center;
        cursor: pointer;
        transition: all 0.2s;
        position: relative;
    }
    .hm-bell-btn:hover {
        background: #27272a;
        border-color: #f59e0b;
        color: #fbbf24;
    }
    .hm-bell-icon {
        width: 20px;
        height: 20px;
    }
    .hm-notif-badge {
        position: absolute;
        top: -4px;
        right: -4px;
        background: #ef4444;
        color: #ffffff;
        font-size: 10px;
        font-weight: 800;
        padding: 2px 6px;
        border-radius: 999px;
        border: 2px solid #090909;
        line-height: 1;
    }
    .hm-notif-menu {
        position: absolute;
        right: 0;
        top: 48px;
        width: 360px;
        max-width: 90vw;
        background: #18181b;
        border: 1px solid #27272a;
        border-radius: 14px;
        box-shadow: 0 16px 36px rgba(0, 0, 0, 0.6);
        z-index: 200;
        overflow: hidden;
        font-family: inherit;
    }
    .hm-notif-header {
        display: flex;
        align-items: center;
        justify-content: space-between;
        padding: 12px 16px;
        background: #121215;
        border-bottom: 1px solid #27272a;
    }
    .hm-notif-title-text {
        font-size: 13px;
        font-weight: 700;
        color: #f4f4f5;
        display: flex;
        align-items: center;
        gap: 8px;
    }
    .hm-unread-count-chip {
        font-size: 11px;
        background: rgba(245, 158, 11, 0.15);
        color: #f59e0b;
        padding: 2px 8px;
        border-radius: 999px;
        font-weight: 600;
    }
    .hm-mark-read-btn {
        background: none;
        border: none;
        color: #3b82f6;
        font-size: 12px;
        font-weight: 600;
        cursor: pointer;
        padding: 0;
        font-family: inherit;
    }
    .hm-mark-read-btn:hover {
        text-decoration: underline;
    }
    .hm-notif-list {
        max-height: 360px;
        overflow-y: auto;
    }
    .hm-notif-item {
        display: flex;
        align-items: flex-start;
        justify-content: space-between;
        gap: 12px;
        padding: 12px 16px;
        border-bottom: 1px solid #27272a;
        transition: background 0.15s;
    }
    .hm-notif-item:last-child {
        border-bottom: none;
    }
    .hm-notif-item:hover {
        background: rgba(255, 255, 255, 0.03);
    }
    .hm-notif-item.is-unread {
        background: rgba(245, 158, 11, 0.05);
    }
    .hm-notif-item-title {
        font-size: 13px;
        font-weight: 700;
        color: #f4f4f5;
        line-height: 1.3;
    }
    .hm-notif-item-body {
        font-size: 12px;
        color: #a1a1aa;
        margin-top: 2px;
        line-height: 1.4;
    }
    .hm-notif-item-time {
        display: block;
        font-size: 11px;
        color: #71717a;
        margin-top: 4px;
    }
    .hm-notif-actions {
        display: flex;
        align-items: center;
        gap: 6px;
    }
    .hm-notif-action-btn {
        background: #27272a;
        border: none;
        color: #a1a1aa;
        width: 24px;
        height: 24px;
        border-radius: 6px;
        display: grid;
        place-items: center;
        font-size: 11px;
        cursor: pointer;
        transition: all 0.15s;
    }
    .hm-notif-action-btn:hover {
        background: #3f3f46;
        color: #f4f4f5;
    }
    .hm-notif-action-btn.delete:hover {
        background: rgba(239, 68, 68, 0.2);
        color: #ef4444;
    }
    .hm-notif-empty {
        padding: 32px 16px;
        text-align: center;
    }
    .hm-theme-btn {
        width: 40px;
        height: 40px;
        border-radius: 10px;
        border: 1px solid #27272a;
        background: #18181b;
        color: #f59e0b;
        display: grid;
        place-items: center;
        cursor: pointer;
        transition: all 0.2s;
    }
    .hm-theme-btn:hover {
        background: #27272a;
        border-color: #f59e0b;
        color: #fbbf24;
    }
    .hm-theme-icon {
        width: 20px;
        height: 20px;
    }
    .hm-admin-dropdown {
        position: relative;
    }
    .hm-settings-btn {
        width: 40px;
        height: 40px;
        border-radius: 10px;
        border: 1px solid #27272a;
        background: #18181b;
        color: #3b82f6;
        display: grid;
        place-items: center;
        cursor: pointer;
        transition: all 0.2s;
    }
    .hm-settings-btn:hover {
        background: #27272a;
        border-color: #3b82f6;
    }
    .hm-gear-icon {
        width: 20px;
        height: 20px;
    }
    [x-cloak] {
        display: none !important;
    }
    .hm-dropdown-menu {
        position: absolute;
        top: 48px;
        left: 0;
        width: 220px;
        padding: 8px;
        background: #18181b;
        border: 1px solid #27272a;
        border-radius: 12px;
        box-shadow: 0 12px 30px rgba(0, 0, 0, 0.6);
        z-index: 120;
        display: grid;
        gap: 2px;
    }
    .hm-menu-user-info {
        padding: 8px 12px;
        display: flex;
        flex-direction: column;
        gap: 2px;
    }
    .hm-menu-user-info strong {
        font-size: 14px;
        color: #f4f4f5;
    }
    .hm-menu-user-info small {
        font-size: 11px;
        color: #a1a1aa;
    }
    .hm-menu-hr {
        border: none;
        border-top: 1px solid #27272a;
        margin: 4px 0;
    }
    .hm-dropdown-menu a {
        padding: 10px 12px;
        color: #e4e4e7;
        text-decoration: none;
        font-size: 13px;
        font-weight: 500;
        border-radius: 8px;
        transition: background 0.15s;
        text-align: right;
    }
    .hm-dropdown-menu a:hover {
        background: #27272a;
    }
    .hm-dropdown-menu a.active {
        color: #3b82f6;
        font-weight: 700;
        background: rgba(59, 130, 246, 0.1);
    }
    .hm-menu-logout {
        background: none;
        border: none;
        color: #f87171;
        padding: 10px 12px;
        text-align: right;
        width: 100%;
        font-size: 13px;
        font-weight: 600;
        cursor: pointer;
        border-radius: 8px;
        transition: background 0.15s;
        font-family: inherit;
    }
    .hm-menu-logout:hover {
        background: rgba(239, 68, 68, 0.15);
    }

    @media (max-width: 900px) {
        .hm-mobile-toggle {
            display: block;
        }
        .hm-nav-links {
            display: none;
            position: absolute;
            top: 64px;
            right: 0;
            left: 0;
            background: #090909;
            border-bottom: 1px solid #27272a;
            flex-direction: column;
            padding: 12px 20px;
            gap: 6px;
            align-items: stretch;
        }
        .hm-nav-links.hm-mobile-active {
            display: flex;
        }
        .hm-nav-links a {
            text-align: right;
        }
    }
</style>
