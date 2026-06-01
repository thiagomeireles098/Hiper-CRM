<script setup>
import { computed, onMounted, onUnmounted, ref, watch, watchEffect, provide } from 'vue';
import { usePage } from '@inertiajs/vue3';
import { useSidebarProvider } from '@/composables/useSidebar';
import { usePanelPushSubscribe } from '@/composables/usePanelPushSubscribe';
import AppSidebar from '@/components/layout/AppSidebar.vue';
import AppHeader from '@/components/layout/AppHeader.vue';
import MobileBottomNav from '@/components/layout/MobileBottomNav.vue';
import PwaInstallPrompt from '@/components/layout/PwaInstallPrompt.vue';
import NotificationsPanel from '@/components/layout/NotificationsPanel.vue';
import Backdrop from '@/components/layout/Backdrop.vue';
import FlashToast from '@/components/layout/FlashToast.vue';
import CloudBillingBanner from '@/components/layout/CloudBillingBanner.vue';

const { isExpanded } = useSidebarProvider();
usePanelPushSubscribe();
const page = usePage();
const pageTitle = computed(() => page.props.pageTitle ?? null);
const pageTitleBadge = computed(() => page.props.pageTitleBadge ?? null);
const contentMaxWidth = computed(() => (page.props.layoutFullWidth ? 'max-w-[1600px]' : 'max-w-7xl'));
const layoutContentFlushLeft = computed(() => !!page.props.layoutContentFlushLeft);
const activePlatformNotices = computed(() => page.props.activePlatformNotices ?? []);
const visibleNotice = ref(null);
let noticeTimer = null;

const showNotificationsPanel = ref(false);
const notificationsUnreadCount = ref(page.props.notifications_unread_count ?? 0);
watch(
    () => page.props.notifications_unread_count,
    (v) => {
        notificationsUnreadCount.value = v ?? 0;
    }
);
provide('openNotificationsPanel', () => {
    showNotificationsPanel.value = true;
});
provide('notificationsUnreadCount', notificationsUnreadCount);

function onNotificationsUnreadCountUpdate(count) {
    notificationsUnreadCount.value = count;
}

watchEffect(() => {
    const primary = page.props.appSettings?.theme_primary || '#EF3E23';
    document.documentElement.style.setProperty('--color-primary', primary);
});

function showPlatformNotice() {
    const first = activePlatformNotices.value?.[0];
    if (!first) return;
    visibleNotice.value = first;
    window.clearTimeout(noticeTimer);
    noticeTimer = window.setTimeout(() => {
        visibleNotice.value = null;
    }, 20000);
}

onMounted(() => {
    window.addEventListener('platform-notice:show', showPlatformNotice);
    showPlatformNotice();
});

onUnmounted(() => {
    window.removeEventListener('platform-notice:show', showPlatformNotice);
    window.clearTimeout(noticeTimer);
});
</script>

<template>
    <div class="min-h-screen bg-zinc-100 dark:bg-zinc-900">
        <AppSidebar />
        <slot name="sidebar-after-nav" />
        <Backdrop />
        <div
            class="flex min-h-screen flex-col transition-all duration-300 ease-in-out p-3 md:p-4 lg:p-6"
            :class="[
                isExpanded ? 'lg:ml-[260px]' : 'lg:ml-[64px]',
            ]"
        >
            <div class="flex w-full shrink-0 flex-col gap-2">
                <div class="-mx-3 md:-mx-4 lg:-mx-6">
                    <CloudBillingBanner />
                </div>
                <AppHeader :page-title="pageTitle" :page-title-badge="pageTitleBadge" />
                <slot name="header-actions" />
            </div>
            <FlashToast />
            <Teleport to="body">
                <div
                    v-if="visibleNotice"
                    class="fixed bottom-5 right-5 z-[100500] w-[min(92vw,380px)] rounded-xl border border-orange-500/40 bg-white p-4 shadow-2xl dark:bg-zinc-900"
                >
                    <button
                        type="button"
                        class="absolute right-3 top-3 rounded-lg p-1 text-zinc-500 hover:bg-zinc-100 dark:hover:bg-zinc-800"
                        aria-label="Fechar aviso"
                        @click="visibleNotice = null"
                    >
                        ×
                    </button>
                    <p class="pr-8 text-sm font-semibold text-zinc-900 dark:text-white">{{ visibleNotice.title }}</p>
                    <p class="mt-1 text-sm text-zinc-600 dark:text-zinc-300">{{ visibleNotice.description }}</p>
                </div>
            </Teleport>
            <PwaInstallPrompt />
            <NotificationsPanel
                :open="showNotificationsPanel"
                @update:open="showNotificationsPanel = $event"
                @unread-count-update="onNotificationsUnreadCountUpdate"
            />
            <MobileBottomNav />
            <div
                class="flex min-h-0 flex-1 flex-col overflow-hidden rounded-2xl bg-white shadow-sm dark:bg-zinc-800"
            >
                <main class="flex-1 px-4 pb-24 pt-4 md:px-6 md:pt-6 lg:pb-8">
                    <div
                        class="w-full"
                        :class="[
                            layoutContentFlushLeft ? 'max-w-none lg:-ml-6' : 'mx-auto',
                            !layoutContentFlushLeft && contentMaxWidth,
                        ]"
                    >
                        <slot />
                        <slot name="content-footer" />
                    </div>
                </main>
            </div>
        </div>
    </div>
</template>
