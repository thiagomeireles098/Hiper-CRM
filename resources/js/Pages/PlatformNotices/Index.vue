<script setup>
import { ref } from 'vue';
import { router, useForm } from '@inertiajs/vue3';
import LayoutInfoprodutor from '@/Layouts/LayoutInfoprodutor.vue';
import Button from '@/components/ui/Button.vue';
import { Megaphone, Pencil, Send, Trash2, X } from 'lucide-vue-next';

defineOptions({ layout: LayoutInfoprodutor });

const props = defineProps({
    notices: { type: Array, default: () => [] },
});

const editing = ref(null);
const form = useForm({
    title: '',
    description: '',
    start_date: '',
    end_date: '',
    start_time: '',
    end_time: '',
});

function reset() {
    editing.value = null;
    form.reset();
    form.clearErrors();
}

function editNotice(notice) {
    editing.value = notice;
    form.title = notice.title ?? '';
    form.description = notice.description ?? '';
    form.start_date = notice.start_date ?? '';
    form.end_date = notice.end_date ?? '';
    form.start_time = notice.start_time ?? '';
    form.end_time = notice.end_time ?? '';
    form.clearErrors();
}

function submit() {
    const options = { preserveScroll: true, onSuccess: () => reset() };
    if (editing.value) {
        form.put(`/avisos/${editing.value.id}`, options);
        return;
    }
    form.post('/avisos', options);
}

function destroyNotice(notice) {
    if (!window.confirm(`Excluir aviso "${notice.title}"?`)) return;
    router.delete(`/avisos/${notice.id}`, { preserveScroll: true });
}

function sendNotice(notice) {
    router.post(`/avisos/${notice.id}/enviar`, {}, { preserveScroll: true });
}
</script>

<template>
    <div class="space-y-6">
        <div>
            <h1 class="text-2xl font-bold text-zinc-900 dark:text-white">Avisos</h1>
            <p class="mt-1 text-sm text-zinc-500 dark:text-zinc-400">Envie mensagens temporarias para todos os infoprodutores.</p>
        </div>

        <form class="rounded-xl border border-zinc-200 bg-white p-5 dark:border-zinc-700 dark:bg-zinc-800" @submit.prevent="submit">
            <div class="grid gap-4 md:grid-cols-2">
                <label class="block text-sm text-zinc-700 dark:text-zinc-300">
                    Usuario Admin
                    <input value="Master" readonly class="mt-1 block w-full rounded-lg border border-zinc-300 bg-zinc-100 px-3 py-2 dark:border-zinc-600 dark:bg-zinc-900" />
                </label>
                <label class="block text-sm text-zinc-700 dark:text-zinc-300">
                    Titulo
                    <input v-model="form.title" required class="mt-1 block w-full rounded-lg border border-zinc-300 bg-white px-3 py-2 dark:border-zinc-600 dark:bg-zinc-900" />
                </label>
                <label class="block text-sm md:col-span-2 text-zinc-700 dark:text-zinc-300">
                    Descricao
                    <textarea v-model="form.description" rows="3" class="mt-1 block w-full rounded-lg border border-zinc-300 bg-white px-3 py-2 dark:border-zinc-600 dark:bg-zinc-900" />
                </label>
                <label class="block text-sm text-zinc-700 dark:text-zinc-300">
                    Dia/mes/ano inicio
                    <input v-model="form.start_date" type="date" required class="mt-1 block w-full rounded-lg border border-zinc-300 bg-white px-3 py-2 dark:border-zinc-600 dark:bg-zinc-900" />
                </label>
                <label class="block text-sm text-zinc-700 dark:text-zinc-300">
                    Dia/mes/ano final
                    <input v-model="form.end_date" type="date" required class="mt-1 block w-full rounded-lg border border-zinc-300 bg-white px-3 py-2 dark:border-zinc-600 dark:bg-zinc-900" />
                </label>
                <label class="block text-sm text-zinc-700 dark:text-zinc-300">
                    Hora inicio
                    <input v-model="form.start_time" type="time" required class="mt-1 block w-full rounded-lg border border-zinc-300 bg-white px-3 py-2 dark:border-zinc-600 dark:bg-zinc-900" />
                </label>
                <label class="block text-sm text-zinc-700 dark:text-zinc-300">
                    Hora final
                    <input v-model="form.end_time" type="time" required class="mt-1 block w-full rounded-lg border border-zinc-300 bg-white px-3 py-2 dark:border-zinc-600 dark:bg-zinc-900" />
                </label>
            </div>
            <div class="mt-5 flex gap-3">
                <Button type="submit" :disabled="form.processing">Salvar</Button>
                <Button type="button" variant="outline" @click="reset">
                    <X class="mr-2 h-4 w-4" /> Cancelar
                </Button>
            </div>
        </form>

        <div class="rounded-xl border border-zinc-200 bg-white dark:border-zinc-700 dark:bg-zinc-800">
            <div class="border-b border-zinc-200 px-4 py-3 dark:border-zinc-700">
                <h2 class="text-lg font-semibold text-zinc-900 dark:text-white">Avisos salvos</h2>
            </div>
            <div class="divide-y divide-zinc-200 dark:divide-zinc-700">
                <div v-for="notice in notices" :key="notice.id" class="flex flex-col gap-3 p-4 md:flex-row md:items-center md:justify-between">
                    <div class="min-w-0">
                        <p class="font-semibold text-zinc-900 dark:text-white"><Megaphone class="mr-2 inline h-4 w-4" />{{ notice.title }}</p>
                        <p class="mt-1 text-sm text-zinc-500 dark:text-zinc-400">{{ notice.description }}</p>
                        <p class="mt-1 text-xs text-zinc-400">
                            {{ notice.start_date }} {{ notice.start_time }} ate {{ notice.end_date }} {{ notice.end_time }}
                            <span v-if="notice.is_sent" class="ml-2 text-[var(--color-primary)]">Enviado</span>
                        </p>
                    </div>
                    <div class="flex gap-2">
                        <button class="rounded-lg p-2 text-zinc-500 hover:bg-zinc-100 dark:hover:bg-zinc-700" title="Editar" @click="editNotice(notice)"><Pencil class="h-4 w-4" /></button>
                        <button class="rounded-lg p-2 text-zinc-500 hover:bg-zinc-100 dark:hover:bg-zinc-700" title="Enviar" @click="sendNotice(notice)"><Send class="h-4 w-4" /></button>
                        <button class="rounded-lg p-2 text-red-500 hover:bg-red-50 dark:hover:bg-red-900/20" title="Excluir" @click="destroyNotice(notice)"><Trash2 class="h-4 w-4" /></button>
                    </div>
                </div>
                <p v-if="!notices.length" class="p-8 text-center text-sm text-zinc-500">Nenhum aviso salvo.</p>
            </div>
        </div>
    </div>
</template>
