<script setup>
import { computed, ref, watch } from 'vue';
import { useForm } from '@inertiajs/vue3';
import {
    BookOpen,
    ChevronRight,
    CreditCard,
    Link,
    Package,
    Smartphone,
    Users,
    X,
} from 'lucide-vue-next';
import Button from '@/components/ui/Button.vue';
import Toggle from '@/components/ui/Toggle.vue';

const props = defineProps({
    open: { type: Boolean, default: false },
    productTypes: { type: Array, default: () => [] },
    billingTypes: { type: Array, default: () => [] },
    exchangeRates: { type: Object, default: () => ({ brl_eur: 0.16, brl_usd: 0.18 }) },
    pluginFormSections: { type: Array, default: () => [] },
});

const emit = defineEmits(['close', 'success']);

const step = ref(1);
const selectedType = ref(null);

const typeIcons = {
    aplicativo: Smartphone,
    area_membros: Users,
    produto: Package,
    link: Link,
    link_pagamento: CreditCard,
};

const businessTypes = [
    { value: 'supermercado', label: 'Supermercado', description: 'Estoque, validade, fiscal, balanca e etiquetas.' },
    { value: 'farmacia', label: 'Farmacia', description: 'ANVISA, lote, validade, receitas e convenios.' },
    { value: 'loja_roupas', label: 'Loja de roupas', description: 'Variacoes por tamanho, cor, grade, SKU e catalogo.' },
    { value: 'informatica_assistencia', label: 'Loja de informatica / Assistencia tecnica', description: 'Produto, serial, garantia, equipamento e ordem de servico.' },
    { value: 'padaria', label: 'Padaria', description: 'Producao, ficha tecnica, pereciveis, venda por peso e perdas.' },
];

const businessFieldGroups = {
    supermercado: [
        { title: 'Informacoes basicas', fields: [
            field('nome_produto', 'Nome do produto', 'text', { bind: 'name', required: true }),
            field('codigo_barras', 'Codigo de barras', 'text', { helper: 'Use o leitor com este campo selecionado para preencher automaticamente.' }),
            field('codigo_interno', 'Codigo interno'),
            field('categoria', 'Categoria'),
            field('marca', 'Marca'),
            field('unidade', 'Unidade', 'select', { options: ['UN', 'KG', 'LT'] }),
        ] },
        { title: 'Controle de estoque', fields: [
            field('quantidade', 'Quantidade', 'number'),
            field('estoque_minimo', 'Estoque minimo', 'number'),
            field('localizacao', 'Localizacao'),
            checkbox('controle_grade', 'Controle por grade'),
            checkbox('controle_peso', 'Controle por peso'),
        ] },
        { title: 'Alimenticios', fields: [
            field('data_validade', 'Data de validade', 'date'),
            field('lote', 'Lote'),
            field('peso', 'Peso', 'number'),
            checkbox('controle_fifo_fefo', 'Controle FIFO/FEFO'),
        ] },
        { title: 'Precos', fields: [
            field('preco_custo', 'Preco custo', 'number'),
            field('preco_venda', 'Preco venda', 'number', { bind: 'price', required: true }),
            checkbox('promocao', 'Promocao'),
            field('atacado_varejo', 'Atacado/varejo'),
        ] },
        { title: 'Fiscal', fields: [
            field('ncm', 'NCM'),
            field('cfop', 'CFOP'),
            field('cst_csosn', 'CST/CSOSN'),
            field('icms', 'ICMS'),
            field('pis_cofins', 'PIS/COFINS'),
        ] },
        resources(['Integracao com balanca', 'Etiquetas', 'Leitor codigo de barras', 'Promocoes automaticas', 'Combos']),
    ],
    farmacia: [
        { title: 'Informacoes basicas', fields: [
            field('nome_medicamento', 'Nome medicamento', 'text', { bind: 'name', required: true }),
            field('nome_generico', 'Nome generico'),
            field('laboratorio', 'Laboratorio'),
            field('codigo_barras', 'Codigo barras', 'text', { helper: 'Use o leitor com este campo selecionado para preencher automaticamente.' }),
        ] },
        { title: 'Controle farmaceutico', fields: [
            field('registro_anvisa', 'Registro ANVISA'),
            field('principio_ativo', 'Principio ativo'),
            field('dosagem', 'Dosagem'),
            field('tipo_receita', 'Tipo receita'),
            checkbox('medicamento_controlado', 'Medicamento controlado'),
            field('lista_anvisa', 'Lista ANVISA'),
        ] },
        { title: 'Controle de lote', fields: [
            field('numero_lote', 'Numero lote'),
            field('validade', 'Validade', 'date'),
            field('fabricacao', 'Fabricacao', 'date'),
            field('quantidade_lote', 'Quantidade por lote', 'number'),
        ] },
        { title: 'Fiscal', fields: [
            field('ncm', 'NCM'),
            field('pmc', 'PMC', 'number', { bind: 'price' }),
            field('tributacao_especifica', 'Tributacao especifica'),
        ] },
        resources(['Controle SNGPC', 'Bloqueio vencidos', 'Controle receitas', 'Historico paciente', 'Convenios']),
    ],
    loja_roupas: [
        { title: 'Informacoes basicas', fields: [
            field('nome_produto', 'Nome produto', 'text', { bind: 'name', required: true }),
            field('marca', 'Marca'),
            field('colecao', 'Colecao'),
            field('genero', 'Genero'),
        ] },
        { title: 'Variacoes', fields: [
            field('tamanho', 'Tamanho'),
            field('cor', 'Cor'),
            field('modelo', 'Modelo'),
            field('grade', 'Grade'),
        ] },
        { title: 'Estoque', fields: [
            field('quantidade_variacao', 'Quantidade por variacao', 'number'),
            checkbox('controle_grade', 'Controle grade'),
            field('sku_individual', 'Codigo SKU individual'),
        ] },
        { title: 'Precos', fields: [
            field('custo', 'Custo', 'number'),
            field('venda', 'Venda', 'number', { bind: 'price', required: true }),
            checkbox('promocao', 'Promocao'),
            field('cashback', 'Cashback'),
        ] },
        resources(['Foto produto', 'Etiquetas', 'Codigo interno por tamanho/cor', 'Catalogo online']),
    ],
    informatica_assistencia: [
        { title: 'Produtos informatica', fields: [
            field('nome_produto', 'Nome produto', 'text', { bind: 'name', required: true }),
            field('marca', 'Marca'),
            field('modelo', 'Modelo'),
            field('numero_serie', 'Numero serie'),
        ] },
        { title: 'Especificacoes', fields: [
            field('voltagem', 'Voltagem'),
            field('capacidade', 'Capacidade'),
            field('compatibilidade', 'Compatibilidade'),
            field('garantia', 'Garantia'),
        ] },
        { title: 'Controle', fields: [
            field('imei_serial', 'IMEI/SERIAL'),
            field('estoque', 'Estoque', 'number'),
            field('fornecedor', 'Fornecedor'),
        ] },
        { title: 'Fiscal', fields: [
            field('ncm', 'NCM'),
            field('tributacao_eletronicos', 'Tributacao eletronicos'),
        ] },
        { title: 'Cadastro equipamento', fields: [
            field('cliente', 'Cliente'),
            field('equipamento', 'Equipamento'),
            field('defeito_relatado', 'Defeito relatado', 'textarea'),
            field('senha', 'Senha'),
            field('estado_aparelho', 'Estado aparelho'),
        ] },
        { title: 'Ordem de servico', fields: [
            field('tecnico_responsavel', 'Tecnico responsavel'),
            field('pecas_utilizadas', 'Pecas utilizadas', 'textarea'),
            field('fotos', 'Fotos', 'textarea', { helper: 'Anote links, nomes dos arquivos ou observacoes das fotos.' }),
            field('status_servico', 'Status do servico'),
            field('laudo_tecnico', 'Laudo tecnico', 'textarea'),
        ] },
        resources(['Checklist entrada', 'Assinatura digital', 'Garantia servico', 'Historico reparos']),
    ],
    padaria: [
        { title: 'Informacoes basicas', fields: [
            field('nome_produto', 'Nome produto', 'text', { bind: 'name', required: true }),
            field('categoria', 'Categoria'),
            field('unidade', 'Unidade'),
            field('peso', 'Peso', 'number'),
        ] },
        { title: 'Producao', fields: [
            field('receita_ficha_tecnica', 'Receita/Ficha tecnica', 'textarea'),
            field('ingredientes', 'Ingredientes', 'textarea'),
            field('custo_producao', 'Custo producao', 'number'),
            field('rendimento', 'Rendimento'),
        ] },
        { title: 'Controle', fields: [
            field('validade_curta', 'Validade curta', 'date'),
            field('producao_diaria', 'Producao diaria', 'number'),
            checkbox('produtos_pereciveis', 'Produtos pereciveis'),
        ] },
        { title: 'Venda', fields: [
            checkbox('venda_peso', 'Venda por peso'),
            checkbox('etiquetas', 'Etiquetas'),
            checkbox('balanca_integrada', 'Balanca integrada'),
            field('preco_venda', 'Preco venda', 'number', { bind: 'price', required: true }),
        ] },
        resources(['Controle producao', 'Consumo materia-prima', 'Perda/desperdicio', 'Produtos fabricados internamente']),
    ],
};

function field(key, label, type = 'text', extra = {}) {
    return { key, label, type, ...extra };
}

function checkbox(key, label) {
    return field(key, label, 'checkbox');
}

function resources(labels) {
    return {
        title: 'Recursos importantes',
        fields: labels.map((label) => checkbox(slugKey(label), label)),
    };
}

function slugKey(label) {
    return label
        .normalize('NFD')
        .replace(/[\u0300-\u036f]/g, '')
        .toLowerCase()
        .replace(/[^a-z0-9]+/g, '_')
        .replace(/^_|_$/g, '');
}

const form = useForm({
    name: '',
    description: '',
    type: '',
    billing_type: 'one_time',
    price: '',
    currency: 'BRL',
    is_active: true,
    image: null,
    deliverable_link: '',
    business_type: '',
    business_product_data: {},
});

const priceNum = computed(() => parseFloat(form.price) || 0);
const priceEur = computed(() => (priceNum.value * (props.exchangeRates.brl_eur ?? 0.16)).toFixed(2));
const priceUsd = computed(() => (priceNum.value * (props.exchangeRates.brl_usd ?? 0.18)).toFixed(2));
const currentBusinessConfig = computed(() => businessFieldGroups[form.business_type] ?? []);
const selectedBusinessTypeLabel = computed(() => businessTypes.find((type) => type.value === form.business_type)?.label ?? '');
const isBusinessProduct = computed(() => form.type === 'produto');

function selectType(type) {
    if (!type.available) return;
    selectedType.value = type.value;
    form.type = type.value;
    step.value = type.value === 'produto' ? 2 : 3;
}

function selectBusinessType(type) {
    form.business_type = type.value;
    form.business_product_data = {};
    step.value = 3;
}

function fieldModel(fieldConfig) {
    if (fieldConfig.bind === 'name') return form.name;
    if (fieldConfig.bind === 'price') return form.price;
    return form.business_product_data[fieldConfig.key] ?? (fieldConfig.type === 'checkbox' ? false : '');
}

function updateField(fieldConfig, value) {
    if (fieldConfig.bind === 'name') {
        form.name = value;
        return;
    }
    if (fieldConfig.bind === 'price') {
        form.price = value;
        return;
    }
    form.business_product_data[fieldConfig.key] = value;
}

function back() {
    if (step.value === 3 && isBusinessProduct.value) {
        step.value = 2;
        return;
    }
    step.value = 1;
    selectedType.value = null;
    form.type = '';
    form.business_type = '';
    form.business_product_data = {};
}

function close() {
    resetState();
    emit('close');
}

function resetState() {
    step.value = 1;
    selectedType.value = null;
    form.reset();
    form.business_product_data = {};
}

function submit() {
    form.post('/produtos', {
        forceFormData: true,
        onSuccess: () => {
            close();
            emit('success');
        },
    });
}

function onFileChange(e) {
    const file = e.target.files?.[0];
    form.image = file || null;
}

watch(
    () => props.open,
    (isOpen) => {
        if (!isOpen) resetState();
    }
);
</script>

<template>
    <Teleport to="body">
        <div
            v-if="open"
            class="fixed inset-0 z-[100000] flex justify-end"
            aria-modal="true"
            role="dialog"
            aria-labelledby="sidebar-title"
        >
            <div
                class="fixed inset-0 bg-zinc-900/50 dark:bg-zinc-950/70"
                aria-hidden="true"
                @click="close"
            />
            <aside
                class="relative z-[100001] flex h-full w-full max-w-2xl flex-col rounded-l-2xl bg-white shadow-xl dark:bg-zinc-900 sm:w-[640px]"
                @click.stop
            >
                <div class="flex shrink-0 items-center justify-between rounded-tl-2xl border-b border-zinc-200 px-4 py-3 dark:border-zinc-800">
                    <h2 id="sidebar-title" class="text-lg font-semibold text-zinc-900 dark:text-white">
                        {{ step === 1 ? 'Novo produto' : step === 2 ? 'Tipo de negocio' : 'Criar produto' }}
                    </h2>
                    <button
                        type="button"
                        class="rounded-lg p-2 text-zinc-500 transition-colors hover:bg-zinc-100 hover:text-zinc-700 dark:hover:bg-zinc-800 dark:hover:text-zinc-300"
                        aria-label="Fechar"
                        @click="close"
                    >
                        <X class="h-5 w-5" />
                    </button>
                </div>

                <div class="flex-1 overflow-y-auto px-4 py-4">
                    <div v-if="step === 1" class="space-y-3">
                        <p class="text-sm text-zinc-600 dark:text-zinc-400">
                            Escolha o tipo de entrega do produto.
                        </p>
                        <div class="grid gap-3">
                            <button
                                v-for="t in productTypes"
                                :key="t.value"
                                type="button"
                                :disabled="!t.available"
                                :class="[
                                    'flex items-start gap-3 rounded-xl border p-4 text-left transition',
                                    t.available
                                        ? 'border-zinc-200 bg-zinc-50 hover:border-[var(--color-primary)] hover:bg-[var(--color-primary)]/5 dark:border-zinc-700 dark:bg-zinc-800/50 dark:hover:border-[var(--color-primary)]'
                                        : 'cursor-not-allowed border-zinc-200 bg-zinc-100/50 opacity-70 dark:border-zinc-800 dark:bg-zinc-800/30',
                                ]"
                                @click="selectType(t)"
                            >
                                <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-lg bg-white dark:bg-zinc-700">
                                    <component :is="typeIcons[t.value] || BookOpen" class="h-5 w-5 text-zinc-600 dark:text-zinc-300" />
                                </span>
                                <div class="min-w-0 flex-1">
                                    <div class="flex items-center gap-2">
                                        <span class="font-medium text-zinc-900 dark:text-white">
                                            {{ t.label }}
                                        </span>
                                        <span
                                            v-if="!t.available"
                                            class="rounded bg-amber-100 px-1.5 py-0.5 text-xs font-medium text-amber-800 dark:bg-amber-900/50 dark:text-amber-200"
                                        >
                                            Em breve
                                        </span>
                                    </div>
                                    <p class="mt-0.5 text-sm text-zinc-500 dark:text-zinc-400">
                                        {{ t.description }}
                                    </p>
                                </div>
                                <ChevronRight v-if="t.available" class="h-5 w-5 shrink-0 text-zinc-400" />
                            </button>
                        </div>
                    </div>

                    <div v-else-if="step === 2" class="space-y-3">
                        <button type="button" class="text-sm text-[var(--color-primary)] hover:underline" @click="back">
                            Voltar ao tipo
                        </button>
                        <p class="text-sm text-zinc-600 dark:text-zinc-400">
                            Escolha para qual tipo de negocio esse produto sera cadastrado.
                        </p>
                        <div class="grid gap-3">
                            <button
                                v-for="type in businessTypes"
                                :key="type.value"
                                type="button"
                                class="flex items-start gap-3 rounded-xl border border-zinc-200 bg-zinc-50 p-4 text-left transition hover:border-[var(--color-primary)] hover:bg-[var(--color-primary)]/5 dark:border-zinc-700 dark:bg-zinc-800/50"
                                @click="selectBusinessType(type)"
                            >
                                <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-lg bg-white dark:bg-zinc-700">
                                    <Package class="h-5 w-5 text-zinc-600 dark:text-zinc-300" />
                                </span>
                                <span class="min-w-0 flex-1">
                                    <span class="block font-medium text-zinc-900 dark:text-white">{{ type.label }}</span>
                                    <span class="mt-0.5 block text-sm text-zinc-500 dark:text-zinc-400">{{ type.description }}</span>
                                </span>
                                <ChevronRight class="h-5 w-5 shrink-0 text-zinc-400" />
                            </button>
                        </div>
                    </div>

                    <form v-else class="space-y-4" @submit.prevent="submit">
                        <div>
                            <button type="button" class="mb-2 text-sm text-[var(--color-primary)] hover:underline" @click="back">
                                {{ isBusinessProduct ? 'Voltar ao tipo de negocio' : 'Voltar ao tipo' }}
                            </button>
                            <p v-if="isBusinessProduct" class="text-sm font-medium text-zinc-700 dark:text-zinc-300">
                                Produto para {{ selectedBusinessTypeLabel }}
                            </p>
                        </div>

                        <template v-if="isBusinessProduct">
                            <div
                                v-for="group in currentBusinessConfig"
                                :key="group.title"
                                class="space-y-3 rounded-xl border border-zinc-200 p-3 dark:border-zinc-700"
                            >
                                <h3 class="text-sm font-semibold text-zinc-900 dark:text-white">{{ group.title }}</h3>
                                <div class="grid gap-3 sm:grid-cols-2">
                                    <div
                                        v-for="fieldConfig in group.fields"
                                        :key="fieldConfig.key"
                                        :class="fieldConfig.type === 'textarea' ? 'sm:col-span-2' : ''"
                                    >
                                        <label
                                            v-if="fieldConfig.type !== 'checkbox'"
                                            class="block text-sm font-medium text-zinc-700 dark:text-zinc-300"
                                        >
                                            {{ fieldConfig.label }} <span v-if="fieldConfig.required">*</span>
                                        </label>
                                        <select
                                            v-if="fieldConfig.type === 'select'"
                                            :value="fieldModel(fieldConfig)"
                                            :required="fieldConfig.required"
                                            class="mt-1 block w-full rounded-lg border border-zinc-300 bg-white px-3 py-2 text-zinc-900 focus:border-[var(--color-primary)] focus:outline-none focus:ring-1 focus:ring-[var(--color-primary)] dark:border-zinc-600 dark:bg-zinc-800 dark:text-white"
                                            @change="updateField(fieldConfig, $event.target.value)"
                                        >
                                            <option value="">Selecione</option>
                                            <option v-for="option in fieldConfig.options" :key="option" :value="option">{{ option }}</option>
                                        </select>
                                        <textarea
                                            v-else-if="fieldConfig.type === 'textarea'"
                                            :value="fieldModel(fieldConfig)"
                                            rows="3"
                                            :required="fieldConfig.required"
                                            class="mt-1 block w-full rounded-lg border border-zinc-300 bg-white px-3 py-2 text-zinc-900 placeholder-zinc-400 focus:border-[var(--color-primary)] focus:outline-none focus:ring-1 focus:ring-[var(--color-primary)] dark:border-zinc-600 dark:bg-zinc-800 dark:text-white dark:placeholder-zinc-500"
                                            @input="updateField(fieldConfig, $event.target.value)"
                                        />
                                        <label v-else-if="fieldConfig.type === 'checkbox'" class="flex items-center gap-2 rounded-lg border border-zinc-200 p-2 text-sm text-zinc-700 dark:border-zinc-700 dark:text-zinc-300">
                                            <input
                                                type="checkbox"
                                                :checked="Boolean(fieldModel(fieldConfig))"
                                                class="h-4 w-4 rounded border-zinc-300 text-[var(--color-primary)] focus:ring-[var(--color-primary)]"
                                                @change="updateField(fieldConfig, $event.target.checked)"
                                            />
                                            {{ fieldConfig.label }}
                                        </label>
                                        <input
                                            v-else
                                            :value="fieldModel(fieldConfig)"
                                            :type="fieldConfig.type"
                                            :step="fieldConfig.type === 'number' ? '0.01' : undefined"
                                            :min="fieldConfig.type === 'number' ? '0' : undefined"
                                            :required="fieldConfig.required"
                                            class="mt-1 block w-full rounded-lg border border-zinc-300 bg-white px-3 py-2 text-zinc-900 placeholder-zinc-400 focus:border-[var(--color-primary)] focus:outline-none focus:ring-1 focus:ring-[var(--color-primary)] dark:border-zinc-600 dark:bg-zinc-800 dark:text-white dark:placeholder-zinc-500"
                                            @input="updateField(fieldConfig, $event.target.value)"
                                        />
                                        <p v-if="fieldConfig.helper" class="mt-0.5 text-xs text-zinc-500 dark:text-zinc-400">
                                            {{ fieldConfig.helper }}
                                        </p>
                                    </div>
                                </div>
                            </div>
                        </template>

                        <template v-else>
                            <div>
                                <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300">
                                    Nome *
                                </label>
                                <input
                                    v-model="form.name"
                                    type="text"
                                    required
                                    class="mt-1 block w-full rounded-lg border border-zinc-300 bg-white px-3 py-2 text-zinc-900 placeholder-zinc-400 focus:border-[var(--color-primary)] focus:outline-none focus:ring-1 focus:ring-[var(--color-primary)] dark:border-zinc-600 dark:bg-zinc-800 dark:text-white dark:placeholder-zinc-500"
                                    placeholder="Ex: Curso de Desenvolvimento Web"
                                />
                                <p v-if="form.errors.name" class="mt-1 text-sm text-red-600 dark:text-red-400">
                                    {{ form.errors.name }}
                                </p>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300">
                                    Descricao
                                </label>
                                <textarea
                                    v-model="form.description"
                                    rows="3"
                                    class="mt-1 block w-full rounded-lg border border-zinc-300 bg-white px-3 py-2 text-zinc-900 placeholder-zinc-400 focus:border-[var(--color-primary)] focus:outline-none focus:ring-1 focus:ring-[var(--color-primary)] dark:border-zinc-600 dark:bg-zinc-800 dark:text-white dark:placeholder-zinc-500"
                                    placeholder="Breve descricao do produto"
                                />
                            </div>
                        </template>

                        <div>
                            <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300">
                                Tipo de cobranca
                            </label>
                            <div class="mt-1.5 flex gap-2">
                                <button
                                    v-for="bt in billingTypes"
                                    :key="bt.value"
                                    type="button"
                                    :class="[
                                        'flex-1 rounded-lg border px-3 py-2.5 text-sm font-medium transition',
                                        form.billing_type === bt.value
                                            ? 'border-[var(--color-primary)] bg-[var(--color-primary)]/10 text-[var(--color-primary)] dark:bg-[var(--color-primary)]/20'
                                            : 'border-zinc-300 bg-white text-zinc-600 hover:border-zinc-400 hover:bg-zinc-50 dark:border-zinc-600 dark:bg-zinc-800 dark:text-zinc-400 dark:hover:bg-zinc-700',
                                    ]"
                                    @click="form.billing_type = bt.value"
                                >
                                    {{ bt.label }}
                                </button>
                            </div>
                        </div>

                        <div v-if="form.type === 'link'">
                            <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300">
                                Link do entregavel
                            </label>
                            <input
                                v-model="form.deliverable_link"
                                type="url"
                                class="mt-1 block w-full rounded-lg border border-zinc-300 bg-white px-3 py-2 text-zinc-900 placeholder-zinc-400 focus:border-[var(--color-primary)] focus:outline-none focus:ring-1 focus:ring-[var(--color-primary)] dark:border-zinc-600 dark:bg-zinc-800 dark:text-white dark:placeholder-zinc-500"
                                placeholder="https://..."
                            />
                            <p class="mt-0.5 text-xs text-zinc-500 dark:text-zinc-400">
                                Enviado por e-mail apos a compra.
                            </p>
                            <p v-if="form.errors.deliverable_link" class="mt-1 text-sm text-red-600 dark:text-red-400">
                                {{ form.errors.deliverable_link }}
                            </p>
                        </div>

                        <div v-if="!isBusinessProduct">
                            <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300">
                                Preco (BRL) *
                            </label>
                            <input
                                v-model="form.price"
                                type="number"
                                step="0.01"
                                min="0"
                                required
                                class="mt-1 block w-full rounded-lg border border-zinc-300 bg-white px-3 py-2 text-zinc-900 placeholder-zinc-400 focus:border-[var(--color-primary)] focus:outline-none focus:ring-1 focus:ring-[var(--color-primary)] dark:border-zinc-600 dark:bg-zinc-800 dark:text-white dark:placeholder-zinc-500"
                                placeholder="0,00"
                            />
                        </div>
                        <p class="text-xs text-zinc-500 dark:text-zinc-400">
                            Valor aproximado: EUR {{ priceEur }} · USD {{ priceUsd }}
                        </p>
                        <p v-if="form.errors.price" class="text-sm text-red-600 dark:text-red-400">
                            {{ form.errors.price }}
                        </p>
                        <p v-if="form.errors.business_type" class="text-sm text-red-600 dark:text-red-400">
                            {{ form.errors.business_type }}
                        </p>

                        <div>
                            <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300">
                                Imagem
                            </label>
                            <p class="mt-0.5 text-xs text-zinc-500 dark:text-zinc-400">
                                Exibida em formato quadrado (1:1). Recomendado enviar imagem quadrada.
                            </p>
                            <input
                                type="file"
                                accept="image/*"
                                class="mt-1 block w-full text-sm text-zinc-600 file:mr-3 file:rounded-lg file:border-0 file:bg-[var(--color-primary)] file:px-4 file:py-2 file:text-white dark:text-zinc-400"
                                @change="onFileChange"
                            />
                            <p v-if="form.image" class="mt-1 text-sm text-zinc-500">
                                {{ form.image.name }}
                            </p>
                        </div>

                        <div class="flex items-center gap-2">
                            <Toggle v-model="form.is_active" label="Produto ativo" />
                        </div>

                        <div v-if="pluginFormSections?.length" class="space-y-2 border-t border-zinc-200 pt-4 dark:border-zinc-700">
                            <template v-for="(section, idx) in pluginFormSections" :key="idx">
                                <div v-if="section.html" v-html="section.html" />
                                <div v-else-if="section.slot" class="text-sm text-zinc-500">
                                    {{ section.slot }}
                                </div>
                            </template>
                        </div>
                        <div class="flex gap-2 pt-2">
                            <Button type="submit" :disabled="form.processing">
                                Criar produto
                            </Button>
                            <Button type="button" variant="outline" @click="close">
                                Cancelar
                            </Button>
                        </div>
                    </form>
                </div>
            </aside>
        </div>
    </Teleport>
</template>
