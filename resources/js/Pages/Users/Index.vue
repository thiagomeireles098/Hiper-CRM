<script setup>
import { ref, computed } from 'vue';
import { Link, router, useForm, usePage } from '@inertiajs/vue3';
import LayoutInfoprodutor from '@/Layouts/LayoutInfoprodutor.vue';
import Button from '@/components/ui/Button.vue';
import { UserPlus, Trash2, Shield, User, Pencil, X, CreditCard, CalendarDays, Clock, CheckCircle } from 'lucide-vue-next';

defineOptions({ layout: LayoutInfoprodutor });

const props = defineProps({
    users: { type: Array, default: () => [] },
    defaultInfoprodutorPermissions: { type: Object, default: () => ({}) },
    platformSubscriptionProducts: { type: Array, default: () => [] },
});

const page = usePage();

const userTabs = [
    { key: 'usuarios', label: 'Infoprodutores', href: '/usuarios' },
    { key: 'equipe', label: 'Equipe', href: '/usuarios/equipe' },
];
function isUsersTabActive(href) {
    // Evitar que "/usuarios" fique ativo em "/usuarios/equipe"
    if (href === '/usuarios') {
        return page.url === '/usuarios' || page.url.startsWith('/usuarios?');
    }
    return page.url === href || page.url.startsWith(href + '/') || page.url.startsWith(href + '?');
}

const showCreateModal = ref(false);
const editUser = ref(null);
const billingUser = ref(null);
const billingMode = ref(null);
const deletingId = ref(null);

const createForm = useForm({
    name: '',
    email: '',
    password: '',
    password_confirmation: '',
});

const editForm = useForm({
    name: '',
    email: '',
    password: '',
    password_confirmation: '',
    platform_permissions: {},
});

const billingForm = useForm({
    platform_payment_due_day: '',
    platform_payment_grace_days: 0,
    platform_subscription_product_ids: [],
});

const permissionGroups = [
    {
        title: 'Menu lateral',
        items: [
            { key: 'dashboard.view', label: 'Dashboard' },
            { key: 'vendas.view', label: 'Vendas' },
            { key: 'reembolsos.view', label: 'Reembolsos' },
            { key: 'produtos.view', label: 'Produtos' },
            { key: 'relatorios.view', label: 'Relatorios' },
            { key: 'integracoes.view', label: 'Integracoes' },
            { key: 'email_marketing.view', label: 'E-mail Marketing' },
            { key: 'api_pagamentos.view', label: 'API de Pagamentos' },
            { key: 'equipe.manage', label: 'Equipe' },
            { key: 'configuracoes.view', label: 'Configuracoes' },
            { key: 'platform_subscription.view', label: 'Assinatura' },
        ],
    },
    {
        title: 'Vendas e cobranca',
        items: [
            { key: 'billing.one_time', label: 'Pagamento unico' },
            { key: 'billing.subscription', label: 'Produtos de assinatura' },
            { key: 'vendas.assinaturas.view', label: 'Aba Assinaturas em Vendas' },
        ],
    },
    {
        title: 'Tipos de entrega',
        items: [
            { key: 'products.delivery.produto', label: 'Produto fisico' },
            { key: 'products.delivery.assinantes', label: 'Assinantes' },
            { key: 'products.delivery.area_membros', label: 'Area de membros' },
            { key: 'products.delivery.area_membros_externa', label: 'Area de membros externa' },
            { key: 'products.delivery.link', label: 'Link' },
            { key: 'products.delivery.link_pagamento', label: 'Somente link de pagamento' },
            { key: 'products.delivery.aplicativo', label: 'Aplicativo' },
        ],
    },
    {
        title: 'Produto fisico',
        items: [
            { key: 'products.business.supermercado', label: 'Supermercado' },
            { key: 'products.business.farmacia', label: 'Farmacia' },
            { key: 'products.business.loja_roupas', label: 'Loja de roupas' },
            { key: 'products.business.informatica_assistencia', label: 'Informatica / Assistencia tecnica' },
            { key: 'products.business.padaria', label: 'Padaria' },
        ],
    },
    {
        title: 'Configuracoes internas',
        items: [
            { key: 'settings.email', label: 'E-mail' },
            { key: 'settings.storage', label: 'Storage' },
            { key: 'settings.traducoes', label: 'Traducoes' },
            { key: 'settings.moedas', label: 'Moedas' },
            { key: 'settings.cron', label: 'Cron' },
            { key: 'settings.update', label: 'Update' },
            { key: 'settings.agente_bot', label: 'Agente Bot' },
            { key: 'settings.caixa', label: 'Caixa' },
        ],
    },
];

function withDefaultPermissions(raw = {}) {
    return { ...props.defaultInfoprodutorPermissions, ...(raw || {}) };
}

const isCreateModalOpen = computed(() => showCreateModal.value);
const isEditModalOpen = computed(() => editUser.value !== null);

function openCreateModal() {
    createForm.reset();
    createForm.clearErrors();
    showCreateModal.value = true;
}

function closeCreateModal() {
    showCreateModal.value = false;
}

function openEditModal(u) {
    editUser.value = u;
    editForm.name = u.name;
    editForm.email = u.email;
    editForm.password = '';
    editForm.password_confirmation = '';
    editForm.platform_permissions = withDefaultPermissions(u.platform_permissions);
    editForm.clearErrors();
}

function closeEditModal() {
    editUser.value = null;
}

function openBillingModal(u, mode) {
    billingUser.value = u;
    billingMode.value = mode;
    billingForm.platform_payment_due_day = u.platform_payment_due_day ?? 1;
    billingForm.platform_payment_grace_days = u.platform_payment_grace_days ?? 0;
    billingForm.platform_subscription_product_ids = [...(u.platform_subscription_product_ids ?? [])];
    billingForm.clearErrors();
}

function closeBillingModal() {
    billingUser.value = null;
    billingMode.value = null;
}

function markPaid(u) {
    if (u.is_master) return;
    router.post(`/usuarios/${u.id}/pagamento/pago`, {}, { preserveScroll: true });
}

function submitBilling() {
    if (!billingUser.value) return;
    const payload = {};
    if (billingMode.value === 'grace') payload.platform_payment_grace_days = billingForm.platform_payment_grace_days;
    if (billingMode.value === 'due') payload.platform_payment_due_day = billingForm.platform_payment_due_day;
    if (billingMode.value === 'subscriptions') payload.platform_subscription_product_ids = billingForm.platform_subscription_product_ids;
    router.put(`/usuarios/${billingUser.value.id}/pagamento`, payload, {
        preserveScroll: true,
        onSuccess: () => closeBillingModal(),
    });
}

function copyToClipboard(text) {
    if (!text) return;
    navigator.clipboard?.writeText(text);
}

function submitCreate() {
    createForm.post('/usuarios', {
        preserveScroll: true,
        onSuccess: () => closeCreateModal(),
    });
}

function submitEdit() {
    if (!editUser.value) return;
    editForm.put(`/usuarios/${editUser.value.id}`, {
        preserveScroll: true,
        onSuccess: () => closeEditModal(),
    });
}

function formatDate(value) {
    if (!value) return '—';
    return new Date(value).toLocaleDateString('pt-BR', {
        day: '2-digit',
        month: '2-digit',
        year: 'numeric',
    });
}

function formatBRL(value) {
    return new Intl.NumberFormat('pt-BR', { style: 'currency', currency: 'BRL' }).format(value ?? 0);
}

function confirmDelete(u) {
    if (u.is_master) return;
    if (!window.confirm(`Excluir "${u.name}"? Esta ação não pode ser desfeita.`)) return;
    deletingId.value = u.id;
    router.delete(`/usuarios/${u.id}`, {
        preserveScroll: true,
        onFinish: () => { deletingId.value = null; },
    });
}
</script>

<template>
    <div class="space-y-6">
        <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h1 class="text-2xl font-bold tracking-tight text-zinc-900 dark:text-white">
                    Usuários
                </h1>
                <p class="mt-1 text-sm text-zinc-500 dark:text-zinc-400">
                    Conta Master e infoprodutores da plataforma.
                </p>
            </div>
            <Button class="inline-flex items-center gap-2" @click="openCreateModal">
                <UserPlus class="h-4 w-4" />
                Novo infoprodutor
            </Button>
        </div>

        <!-- Abas Usuários -->
        <nav
            class="inline-flex rounded-xl bg-zinc-100/80 p-1 dark:bg-zinc-800/80"
            aria-label="Abas de usuários"
        >
            <Link
                v-for="t in userTabs"
                :key="t.key"
                :href="t.href"
                :class="[
                    'flex items-center gap-2 rounded-lg px-4 py-2.5 text-sm font-medium transition-all duration-200',
                    isUsersTabActive(t.href)
                        ? 'bg-white text-[var(--color-primary)] shadow-sm dark:bg-zinc-700 dark:text-[var(--color-primary)]'
                        : 'text-zinc-600 hover:text-zinc-900 dark:text-zinc-400 dark:hover:text-white',
                ]"
                :aria-current="isUsersTabActive(t.href) ? 'page' : undefined"
            >
                <Shield v-if="t.key === 'usuarios'" class="h-4 w-4 shrink-0" aria-hidden="true" />
                <User v-else class="h-4 w-4 shrink-0" aria-hidden="true" />
                {{ t.label }}
            </Link>
        </nav>

        <div class="rounded-xl border border-zinc-200 dark:border-zinc-700 bg-zinc-50 dark:bg-zinc-800/80 overflow-hidden">
            <ul class="divide-y divide-zinc-200 dark:divide-zinc-700">
                <li
                    v-for="u in users"
                    :key="u.id"
                    class="flex flex-col gap-2 px-4 py-4 sm:flex-row sm:items-center sm:justify-between sm:gap-4 hover:bg-zinc-100/80 dark:hover:bg-zinc-700/50 transition-colors"
                >
                    <div class="flex min-w-0 flex-1 items-center gap-3">
                        <span
                            v-if="u.avatar_url"
                            class="h-10 w-10 shrink-0 overflow-hidden rounded-full bg-zinc-200 dark:bg-zinc-600"
                        >
                            <img :src="u.avatar_url" :alt="u.name" class="h-full w-full object-cover" />
                        </span>
                        <span
                            v-else
                            class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full text-white"
                            :class="u.is_master ? 'bg-amber-500 dark:bg-amber-600' : 'bg-zinc-400 dark:bg-zinc-600'"
                        >
                            <Shield v-if="u.is_master" class="h-5 w-5" />
                            <User v-else class="h-5 w-5" />
                        </span>
                        <div class="min-w-0 flex-1">
                            <div class="flex flex-wrap items-center gap-2">
                                <span class="font-medium text-zinc-900 dark:text-zinc-100">{{ u.name }}</span>
                                <span
                                    v-if="u.is_master"
                                    class="inline-flex items-center rounded-md bg-amber-100 px-2 py-0.5 text-xs font-medium text-amber-800 dark:bg-amber-900/50 dark:text-amber-200"
                                >
                                    Master
                                </span>
                            </div>
                            <p class="mt-0.5 truncate text-sm text-zinc-500 dark:text-zinc-400">{{ u.email }}</p>
                        </div>
                    </div>
                    <div class="flex items-center gap-2">
                        <span class="text-sm text-zinc-500 dark:text-zinc-400 tabular-nums">
                            {{ formatDate(u.created_at) }}
                        </span>
                        <template v-if="!u.is_master">
                            <button
                                type="button"
                                class="rounded-lg border border-zinc-300 px-2.5 py-2 text-xs font-medium text-zinc-700 hover:border-[var(--color-primary)] hover:text-[var(--color-primary)] dark:border-zinc-600 dark:text-zinc-200"
                                title="Marcar este mes como pago"
                                @click="markPaid(u)"
                            >
                                <CheckCircle class="mr-1 inline h-3.5 w-3.5" /> Pago
                            </button>
                            <button
                                type="button"
                                class="rounded-lg border border-zinc-300 px-2.5 py-2 text-xs font-medium text-zinc-700 hover:border-[var(--color-primary)] hover:text-[var(--color-primary)] dark:border-zinc-600 dark:text-zinc-200"
                                @click="openBillingModal(u, 'grace')"
                            >
                                <Clock class="mr-1 inline h-3.5 w-3.5" /> Dias extras
                            </button>
                            <button
                                type="button"
                                class="rounded-lg border border-zinc-300 px-2.5 py-2 text-xs font-medium text-zinc-700 hover:border-[var(--color-primary)] hover:text-[var(--color-primary)] dark:border-zinc-600 dark:text-zinc-200"
                                @click="openBillingModal(u, 'due')"
                            >
                                <CalendarDays class="mr-1 inline h-3.5 w-3.5" /> Dia do pagamento
                            </button>
                            <button
                                type="button"
                                class="rounded-lg border border-zinc-300 px-2.5 py-2 text-xs font-medium text-zinc-700 hover:border-[var(--color-primary)] hover:text-[var(--color-primary)] dark:border-zinc-600 dark:text-zinc-200"
                                @click="openBillingModal(u, 'subscriptions')"
                            >
                                <CreditCard class="mr-1 inline h-3.5 w-3.5" /> Assinaturas
                            </button>
                        </template>
                        <button
                            type="button"
                            class="rounded-lg p-2 text-zinc-400 hover:bg-zinc-200 hover:text-zinc-700 dark:hover:bg-zinc-600 dark:hover:text-zinc-200 transition-colors"
                            title="Editar usuário"
                            @click="openEditModal(u)"
                        >
                            <Pencil class="h-4 w-4" />
                        </button>
                        <button
                            v-if="!u.is_master"
                            type="button"
                            :disabled="deletingId === u.id"
                            class="rounded-lg p-2 text-zinc-400 hover:bg-red-50 hover:text-red-600 dark:hover:bg-red-900/20 dark:hover:text-red-400 disabled:opacity-50 transition-colors"
                            title="Excluir usuário"
                            @click="confirmDelete(u)"
                        >
                            <Trash2 class="h-4 w-4" />
                        </button>
                    </div>
                </li>
            </ul>
            <p
                v-if="!users.length"
                class="px-4 py-10 text-center text-sm text-zinc-500 dark:text-zinc-400"
            >
                Nenhum usuário cadastrado.
            </p>
        </div>
    </div>

    <!-- Modal: Novo usuário -->
    <Teleport to="body">
        <div
            v-if="isCreateModalOpen"
            class="fixed inset-0 z-[100002] flex items-center justify-center p-4"
            role="dialog"
            aria-modal="true"
            aria-labelledby="modal-create-title"
        >
            <div
                class="fixed inset-0 bg-zinc-900/60 dark:bg-zinc-950/70"
                aria-hidden="true"
                @click="closeCreateModal"
            />
            <div
                class="relative w-full max-w-md rounded-xl border border-zinc-200 bg-white shadow-xl dark:border-zinc-700 dark:bg-zinc-800"
            >
                <div class="flex items-center justify-between border-b border-zinc-200 px-5 py-4 dark:border-zinc-700">
                    <h2 id="modal-create-title" class="text-lg font-semibold text-zinc-900 dark:text-white">
                        Novo infoprodutor
                    </h2>
                    <button
                        type="button"
                        class="rounded-lg p-2 text-zinc-500 hover:bg-zinc-100 hover:text-zinc-700 dark:hover:bg-zinc-700 dark:hover:text-zinc-200"
                        aria-label="Fechar"
                        @click="closeCreateModal"
                    >
                        <X class="h-5 w-5" />
                    </button>
                </div>
                <form class="space-y-4 p-5" @submit.prevent="submitCreate">
                    <div>
                        <label for="create-name" class="block text-sm font-medium text-zinc-700 dark:text-zinc-300">Nome</label>
                        <input
                            id="create-name"
                            v-model="createForm.name"
                            type="text"
                            required
                            class="mt-1 block w-full rounded-lg border border-zinc-300 dark:border-zinc-600 bg-white dark:bg-zinc-900 px-3 py-2 text-zinc-900 dark:text-zinc-100"
                        />
                        <p v-if="createForm.errors.name" class="mt-1 text-sm text-red-600 dark:text-red-400">{{ createForm.errors.name }}</p>
                    </div>
                    <div>
                        <label for="create-email" class="block text-sm font-medium text-zinc-700 dark:text-zinc-300">E-mail</label>
                        <input
                            id="create-email"
                            v-model="createForm.email"
                            type="email"
                            required
                            class="mt-1 block w-full rounded-lg border border-zinc-300 dark:border-zinc-600 bg-white dark:bg-zinc-900 px-3 py-2 text-zinc-900 dark:text-zinc-100"
                        />
                        <p v-if="createForm.errors.email" class="mt-1 text-sm text-red-600 dark:text-red-400">{{ createForm.errors.email }}</p>
                    </div>
                    <div>
                        <label for="create-password" class="block text-sm font-medium text-zinc-700 dark:text-zinc-300">Senha</label>
                        <input
                            id="create-password"
                            v-model="createForm.password"
                            type="password"
                            required
                            minlength="8"
                            class="mt-1 block w-full rounded-lg border border-zinc-300 dark:border-zinc-600 bg-white dark:bg-zinc-900 px-3 py-2 text-zinc-900 dark:text-zinc-100"
                        />
                        <p v-if="createForm.errors.password" class="mt-1 text-sm text-red-600 dark:text-red-400">{{ createForm.errors.password }}</p>
                    </div>
                    <div>
                        <label for="create-password_confirmation" class="block text-sm font-medium text-zinc-700 dark:text-zinc-300">Confirmar senha</label>
                        <input
                            id="create-password_confirmation"
                            v-model="createForm.password_confirmation"
                            type="password"
                            required
                            minlength="8"
                            class="mt-1 block w-full rounded-lg border border-zinc-300 dark:border-zinc-600 bg-white dark:bg-zinc-900 px-3 py-2 text-zinc-900 dark:text-zinc-100"
                        />
                    </div>
                    <div class="flex gap-3 pt-2">
                        <Button type="submit" :disabled="createForm.processing">
                            Cadastrar
                        </Button>
                        <Button type="button" variant="outline" @click="closeCreateModal">
                            Cancelar
                        </Button>
                    </div>
                </form>
            </div>
        </div>
    </Teleport>

    <!-- Modal: Editar usuário -->
    <Teleport to="body">
        <div
            v-if="isEditModalOpen"
            class="fixed inset-0 z-[100002] flex items-center justify-center p-4"
            role="dialog"
            aria-modal="true"
            aria-labelledby="modal-edit-title"
        >
            <div
                class="fixed inset-0 bg-zinc-900/60 dark:bg-zinc-950/70"
                aria-hidden="true"
                @click="closeEditModal"
            />
            <div
                class="relative max-h-[92vh] w-full max-w-5xl overflow-hidden rounded-xl border border-zinc-200 bg-white shadow-xl dark:border-zinc-700 dark:bg-zinc-800"
            >
                <div class="flex items-center justify-between border-b border-zinc-200 px-5 py-4 dark:border-zinc-700">
                    <h2 id="modal-edit-title" class="text-lg font-semibold text-zinc-900 dark:text-white">
                        Editar usuário
                    </h2>
                    <button
                        type="button"
                        class="rounded-lg p-2 text-zinc-500 hover:bg-zinc-100 hover:text-zinc-700 dark:hover:bg-zinc-700 dark:hover:text-zinc-200"
                        aria-label="Fechar"
                        @click="closeEditModal"
                    >
                        <X class="h-5 w-5" />
                    </button>
                </div>
                <form class="max-h-[calc(92vh-73px)] space-y-5 overflow-y-auto p-5" @submit.prevent="submitEdit">
                    <div class="grid gap-5 lg:grid-cols-[1fr_1.4fr]">
                        <section class="space-y-4">
                            <div>
                                <label for="edit-name" class="block text-sm font-medium text-zinc-700 dark:text-zinc-300">Nome</label>
                                <input
                                    id="edit-name"
                                    v-model="editForm.name"
                                    type="text"
                                    required
                                    class="mt-1 block w-full rounded-lg border border-zinc-300 dark:border-zinc-600 bg-white dark:bg-zinc-900 px-3 py-2 text-zinc-900 dark:text-zinc-100"
                                />
                                <p v-if="editForm.errors.name" class="mt-1 text-sm text-red-600 dark:text-red-400">{{ editForm.errors.name }}</p>
                            </div>
                            <div>
                                <label for="edit-email" class="block text-sm font-medium text-zinc-700 dark:text-zinc-300">E-mail</label>
                                <input
                                    id="edit-email"
                                    v-model="editForm.email"
                                    type="email"
                                    required
                                    class="mt-1 block w-full rounded-lg border border-zinc-300 dark:border-zinc-600 bg-white dark:bg-zinc-900 px-3 py-2 text-zinc-900 dark:text-zinc-100"
                                />
                                <p v-if="editForm.errors.email" class="mt-1 text-sm text-red-600 dark:text-red-400">{{ editForm.errors.email }}</p>
                            </div>
                        </section>
                        <section class="rounded-lg border border-zinc-200 p-4 dark:border-zinc-700">
                            <h3 class="text-sm font-semibold text-zinc-900 dark:text-white">Permissoes do infoprodutor</h3>
                            <div class="mt-3 grid gap-4 md:grid-cols-2">
                                <div v-for="group in permissionGroups" :key="group.title" class="rounded-lg border border-zinc-200 p-3 dark:border-zinc-700">
                                    <h4 class="text-xs font-semibold uppercase text-zinc-500 dark:text-zinc-400">{{ group.title }}</h4>
                                    <div class="mt-2 space-y-2">
                                        <label v-for="p in group.items" :key="p.key" class="flex items-center gap-2 text-sm text-zinc-700 dark:text-zinc-200">
                                            <input v-model="editForm.platform_permissions[p.key]" type="checkbox" class="rounded border-zinc-300 dark:border-zinc-600" />
                                            <span>{{ p.label }}</span>
                                        </label>
                                    </div>
                                </div>
                            </div>
                        </section>
                    </div>
                    <div v-if="editUser?.cashier_sync_token" class="rounded-lg border border-zinc-200 bg-zinc-50 p-3 dark:border-zinc-700 dark:bg-zinc-900/40">
                        <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300">Token HiperCaixa</label>
                        <div class="mt-1 flex gap-2">
                            <input
                                :value="editUser.cashier_sync_token"
                                readonly
                                class="block w-full rounded-lg border border-zinc-300 bg-white px-3 py-2 font-mono text-sm text-zinc-900 dark:border-zinc-600 dark:bg-zinc-900 dark:text-zinc-100"
                            />
                            <button
                                type="button"
                                class="rounded-lg border border-zinc-300 px-3 py-2 text-sm font-medium text-zinc-700 hover:border-[var(--color-primary)] hover:text-[var(--color-primary)] dark:border-zinc-600 dark:text-zinc-200"
                                @click="copyToClipboard(editUser.cashier_sync_token)"
                            >
                                Copiar
                            </button>
                        </div>
                    </div>
                    <div>
                        <label for="edit-password" class="block text-sm font-medium text-zinc-700 dark:text-zinc-300">Nova senha (deixe em branco para não alterar)</label>
                        <input
                            id="edit-password"
                            v-model="editForm.password"
                            type="password"
                            minlength="8"
                            class="mt-1 block w-full rounded-lg border border-zinc-300 dark:border-zinc-600 bg-white dark:bg-zinc-900 px-3 py-2 text-zinc-900 dark:text-zinc-100"
                        />
                        <p v-if="editForm.errors.password" class="mt-1 text-sm text-red-600 dark:text-red-400">{{ editForm.errors.password }}</p>
                    </div>
                    <div>
                        <label for="edit-password_confirmation" class="block text-sm font-medium text-zinc-700 dark:text-zinc-300">Confirmar nova senha</label>
                        <input
                            id="edit-password_confirmation"
                            v-model="editForm.password_confirmation"
                            type="password"
                            minlength="8"
                            class="mt-1 block w-full rounded-lg border border-zinc-300 dark:border-zinc-600 bg-white dark:bg-zinc-900 px-3 py-2 text-zinc-900 dark:text-zinc-100"
                        />
                    </div>
                    <div class="flex gap-3 pt-2">
                        <Button type="submit" :disabled="editForm.processing">
                            Salvar
                        </Button>
                        <Button type="button" variant="outline" @click="closeEditModal">
                            Cancelar
                        </Button>
                    </div>
                </form>
            </div>
        </div>
    </Teleport>

    <!-- Modal: Pagamento da plataforma -->
    <Teleport to="body">
        <div
            v-if="billingUser"
            class="fixed inset-0 z-[100002] flex items-center justify-center p-4"
            role="dialog"
            aria-modal="true"
        >
            <div class="fixed inset-0 bg-zinc-900/60 dark:bg-zinc-950/70" aria-hidden="true" @click="closeBillingModal" />
            <div class="relative w-full max-w-lg rounded-xl border border-zinc-200 bg-white shadow-xl dark:border-zinc-700 dark:bg-zinc-800">
                <div class="flex items-center justify-between border-b border-zinc-200 px-5 py-4 dark:border-zinc-700">
                    <h2 class="text-lg font-semibold text-zinc-900 dark:text-white">
                        {{ billingMode === 'grace' ? 'Dias extras' : billingMode === 'due' ? 'Dia do pagamento' : 'Assinaturas' }}
                    </h2>
                    <button type="button" class="rounded-lg p-2 text-zinc-500 hover:bg-zinc-100 dark:hover:bg-zinc-700" @click="closeBillingModal">
                        <X class="h-5 w-5" />
                    </button>
                </div>
                <form class="space-y-4 p-5" @submit.prevent="submitBilling">
                    <p class="text-sm text-zinc-500 dark:text-zinc-400">{{ billingUser.name }} - {{ billingUser.email }}</p>

                    <label v-if="billingMode === 'grace'" class="block text-sm text-zinc-700 dark:text-zinc-300">
                        Quantos dias extras
                        <input v-model="billingForm.platform_payment_grace_days" type="number" min="0" max="365" class="mt-1 block w-full rounded-lg border border-zinc-300 bg-white px-3 py-2 dark:border-zinc-600 dark:bg-zinc-900" />
                        <span class="mt-1 block text-xs text-zinc-500">Juros aplicado: {{ Number(billingForm.platform_payment_grace_days || 0) }}% sem alterar o vencimento original.</span>
                    </label>

                    <label v-else-if="billingMode === 'due'" class="block text-sm text-zinc-700 dark:text-zinc-300">
                        Dia do pagamento
                        <input v-model="billingForm.platform_payment_due_day" type="number" min="1" max="31" class="mt-1 block w-full rounded-lg border border-zinc-300 bg-white px-3 py-2 dark:border-zinc-600 dark:bg-zinc-900" />
                    </label>

                    <div v-else class="space-y-2">
                        <p class="text-sm font-medium text-zinc-700 dark:text-zinc-300">Assinaturas do infoprodutor</p>
                        <label v-for="product in platformSubscriptionProducts" :key="product.id" class="flex items-center justify-between gap-3 rounded-lg border border-zinc-200 p-3 text-sm dark:border-zinc-700">
                            <span>
                                <span class="block font-medium text-zinc-900 dark:text-white">{{ product.name }}</span>
                                <span class="text-xs text-zinc-500">{{ formatBRL(product.price) }} / mes</span>
                            </span>
                            <input v-model="billingForm.platform_subscription_product_ids" :value="product.id" type="checkbox" class="rounded border-zinc-300" />
                        </label>
                        <p v-if="!platformSubscriptionProducts.length" class="rounded-lg border border-dashed border-zinc-300 p-4 text-sm text-zinc-500 dark:border-zinc-700">
                            Crie primeiro um produto do tipo Assinantes na pagina Produtos.
                        </p>
                    </div>

                    <div class="flex gap-3 pt-2">
                        <Button type="submit" :disabled="billingForm.processing">Salvar</Button>
                        <Button type="button" variant="outline" @click="closeBillingModal">Cancelar</Button>
                    </div>
                </form>
            </div>
        </div>
    </Teleport>
</template>
