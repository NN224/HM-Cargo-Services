<?php

use Livewire\Component;

new class extends Component
{
    public function markAllAsRead(): void
    {
        auth()->user()?->unreadNotifications->markAsRead();
    }

    public function markAsRead(string $id): void
    {
        auth()->user()?->notifications()->where('id', $id)->first()?->markAsRead();
    }

    public function deleteNotification(string $id): void
    {
        auth()->user()?->notifications()->where('id', $id)->first()?->delete();
    }
};
?>

@php
    $user = auth()->user();
    $notifications = $user?->notifications()->take(10)->get() ?? collect();
    $unreadCount = $user?->unreadNotifications()->count() ?? 0;
@endphp

<div class="hm-notif-dropdown" x-data="{ notifOpen: false }">
    <button type="button" 
            class="hm-bell-btn {{ $unreadCount > 0 ? 'has-unread' : '' }}" 
            x-on:click="notifOpen = !notifOpen" 
            title="الإشعارات"
            aria-label="الإشعارات" 
            :aria-expanded="notifOpen">
        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="hm-bell-icon">
            <path stroke-linecap="round" stroke-linejoin="round" d="M14.857 17.082a23.848 23.848 0 005.454-1.31A8.967 8.967 0 0118 9.75v-.7V9A6 6 0 006 9v.75a8.967 8.967 0 01-2.312 6.022c1.733.64 3.56 1.085 5.455 1.31m5.714 0a24.255 24.255 0 01-5.714 0m5.714 0a3 3 0 11-5.714 0" />
        </svg>
        @if ($unreadCount > 0)
            <span class="hm-notif-badge">{{ $unreadCount > 99 ? '99+' : $unreadCount }}</span>
        @endif
    </button>

    <div class="hm-notif-menu" x-show="notifOpen" x-transition x-on:click.away="notifOpen = false" x-cloak>
        <div class="hm-notif-header">
            <span class="hm-notif-title-text">الإشعارات @if($unreadCount > 0) <span class="hm-unread-count-chip">{{ $unreadCount }} غير مقروء</span> @endif</span>
            @if ($unreadCount > 0)
                <button type="button" wire:click="markAllAsRead" class="hm-mark-read-btn">تحديد الكل كمقروء</button>
            @endif
        </div>
        <div class="hm-notif-list">
            @forelse ($notifications as $notification)
                @php
                    $data = $notification->data ?? [];
                    $title = $data['title'] ?? $data['message'] ?? 'تنبيه جديد';
                    $body = $data['body'] ?? $data['description'] ?? null;
                    $isUnread = is_null($notification->read_at);
                @endphp
                <div class="hm-notif-item {{ $isUnread ? 'is-unread' : '' }}" wire:key="notif-{{ $notification->id }}">
                    <div class="hm-notif-body-wrap">
                        <div class="hm-notif-item-title">{{ $title }}</div>
                        @if ($body)
                            <div class="hm-notif-item-body">{{ $body }}</div>
                        @endif
                        <small class="hm-notif-item-time">{{ $notification->created_at->diffForHumans() }}</small>
                    </div>
                    <div class="hm-notif-actions">
                        @if ($isUnread)
                            <button type="button" wire:click="markAsRead('{{ $notification->id }}')" class="hm-notif-action-btn" title="تحديد كمقروء">✓</button>
                        @endif
                        <button type="button" wire:click="deleteNotification('{{ $notification->id }}')" class="hm-notif-action-btn delete" title="حذف">✕</button>
                    </div>
                </div>
            @empty
                <div class="hm-notif-empty">
                    <span style="font-size: 28px; opacity: 0.6;">🔔</span>
                    <p style="margin: 6px 0 0; color: #a1a1aa; font-size: 13px;">لا توجد إشعارات حالياً</p>
                </div>
            @endforelse
        </div>
    </div>
</div>