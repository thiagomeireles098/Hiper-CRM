<script setup>
import { computed } from 'vue';
import LayoutInfoprodutor from '@/Layouts/LayoutInfoprodutor.vue';
import Button from '@/components/ui/Button.vue';
import { CreditCard, CalendarDays, BadgeCheck, Percent } from 'lucide-vue-next';

defineOptions({ layout: LayoutInfoprodutor });

const props = defineProps({
    is_master: { type: Boolean, default: false },
    subscription: { type: Object, default: () => ({}) },
});

const title = computed(() => (props.is_master ? 'Editar assinatura' : 'Assinatura'));
const statusLabel = computed(() => (props.subscription?.paid ? 'Pagamento em dia' : 'Pagamento pendente'));
</script>

<template>
    <div class="space-y-6">
        <div>
            <h1 class="text-2xl font-bold tracking-tight text-zinc-900 dark:text-white">
                {{ title }}
            </h1>
            <p class="mt-1 text-sm text-zinc-500 dark:text-zinc-400">
                {{ is_master ? 'Configure a assinatura usada pelos infoprodutores para acessar a plataforma.' : 'Acompanhe o acesso da sua conta na plataforma.' }}
            </p>
        </div>

        <div class="grid gap-4 md:grid-cols-4">
            <div class="rounded-xl border border-zinc-200 bg-white p-4 dark:border-zinc-700 dark:bg-zinc-800">
                <CreditCard class="h-5 w-5 text-[var(--color-primary)]" />
                <p class="mt-3 text-sm text-zinc-500 dark:text-zinc-400">Plano</p>
                <p class="mt-1 font-semibold text-zinc-900 dark:text-white">{{ subscription.name }}</p>
            </div>
            <div class="rounded-xl border border-zinc-200 bg-white p-4 dark:border-zinc-700 dark:bg-zinc-800">
                <CalendarDays class="h-5 w-5 text-[var(--color-primary)]" />
                <p class="mt-3 text-sm text-zinc-500 dark:text-zinc-400">Dia de vencimento</p>
                <p class="mt-1 font-semibold text-zinc-900 dark:text-white">{{ subscription.due_day || 'Nao definido' }}</p>
            </div>
            <div class="rounded-xl border border-zinc-200 bg-white p-4 dark:border-zinc-700 dark:bg-zinc-800">
                <BadgeCheck class="h-5 w-5 text-[var(--color-primary)]" />
                <p class="mt-3 text-sm text-zinc-500 dark:text-zinc-400">Status</p>
                <p class="mt-1 font-semibold text-zinc-900 dark:text-white">{{ statusLabel }}</p>
            </div>
            <div class="rounded-xl border border-zinc-200 bg-white p-4 dark:border-zinc-700 dark:bg-zinc-800">
                <Percent class="h-5 w-5 text-[var(--color-primary)]" />
                <p class="mt-3 text-sm text-zinc-500 dark:text-zinc-400">Juros suporte</p>
                <p class="mt-1 font-semibold text-zinc-900 dark:text-white">{{ subscription.grace_interest_percent || 0 }}%</p>
            </div>
        </div>

        <section class="rounded-xl border border-zinc-200 bg-white p-5 dark:border-zinc-700 dark:bg-zinc-800">
            <h2 class="text-lg font-semibold text-zinc-900 dark:text-white">
                Produto de assinatura da plataforma
            </h2>
            <p class="mt-2 text-sm text-zinc-500 dark:text-zinc-400">
                Esta tela fica pronta para receber a mesma configuracao de produto/checkout usada nos produtos, mas aplicada ao pagamento dos infoprodutores.
            </p>
            <Button class="mt-4" disabled>
                {{ is_master ? 'Gerador de assinatura em preparacao' : 'Aguardar configuracao do master' }}
            </Button>
        </section>
    </div>
</template>
