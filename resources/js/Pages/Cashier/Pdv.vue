<script setup>
import { computed, nextTick, onMounted, onUnmounted, ref } from 'vue';
import { MonitorCog, PackageSearch, Settings, Trash2, Percent, Ban, Printer } from 'lucide-vue-next';

const props = defineProps({
    cashier: { type: Object, required: true },
    manager_mode: { type: Boolean, default: false },
});

const cpf = ref('');
const saleStarted = ref(false);
const barcode = ref('');
const quantityNext = ref(1);
const quantityInput = ref('');
const showQuantity = ref(false);
const showManagerLogin = ref(false);
const managerPassword = ref('');
const discount = ref(0);
const finished = ref(false);
const paymentMode = ref('dinheiro');
const moneyReceived = ref('');
const cheque = ref({ bank_code: '', bank_name: '', agency: '', agency_digit: '', account: '', account_digit: '', number: '', due: '', value: '' });
const debit = ref({ value: '', brand: '' });
const credit = ref({ value: '', installments: 1, brand: '' });
const barcodeEl = ref(null);
const moneyEl = ref(null);

const products = [
    { code: '7898132951382', name: 'COCA COLA LATA 350ML', unit: 'UN', price: 2.3 },
    { code: '7897095042014', name: 'SUCO AURORA UVA TINTO 1,5L', unit: 'UN', price: 19.99 },
    { code: '7897216901763', name: 'LEITE CONDENSADO MOCA TP 395G', unit: 'UN', price: 5.5 },
    { code: '84491', name: 'COCA COLA LATA 350ML', unit: 'UN', price: 2.3 },
];
const items = ref([]);

const currentProduct = computed(() => items.value.at(-1) ?? null);
const subtotal = computed(() => items.value.reduce((sum, item) => sum + item.total, 0));
const total = computed(() => Math.max(0, subtotal.value - Number(discount.value || 0)));
const received = computed(() => {
    if (paymentMode.value === 'dinheiro') return Number(String(moneyReceived.value).replace(',', '.') || 0);
    if (paymentMode.value === 'cheque') return Number(String(cheque.value.value).replace(',', '.') || 0);
    if (paymentMode.value === 'debito') return Number(String(debit.value.value).replace(',', '.') || 0);
    return Number(String(credit.value.value).replace(',', '.') || 0);
});
const change = computed(() => Math.max(0, received.value - total.value));
const balance = computed(() => Math.max(0, total.value - received.value));

function currency(value) {
    return Number(value || 0).toLocaleString('pt-BR', { style: 'currency', currency: 'BRL' });
}

function startSale() {
    saleStarted.value = true;
    finished.value = false;
    nextTick(() => barcodeEl.value?.focus());
}

function addBarcode() {
    const code = barcode.value.trim();
    if (!code) return;
    const product = products.find((p) => p.code === code) ?? {
        code,
        name: `PRODUTO ${code}`,
        unit: 'UN',
        price: 1,
    };
    const qty = Math.max(1, Number(quantityNext.value) || 1);
    items.value.push({
        ...product,
        qty,
        total: product.price * qty,
    });
    barcode.value = '';
    quantityNext.value = 1;
}

function confirmQuantity() {
    quantityNext.value = Math.max(1, Number(quantityInput.value) || 1);
    quantityInput.value = '';
    showQuantity.value = false;
    nextTick(() => barcodeEl.value?.focus());
}

function requestDeleteItem() {
    if (props.manager_mode) {
        items.value.pop();
        return;
    }
    showManagerLogin.value = true;
}

function confirmManagerDelete() {
    if (managerPassword.value.trim()) {
        items.value.pop();
        managerPassword.value = '';
        showManagerLogin.value = false;
    }
}

function finishSale() {
    if (!items.value.length) return;
    finished.value = true;
    nextTick(() => moneyEl.value?.focus());
}

function cancelSale() {
    items.value = [];
    discount.value = 0;
    moneyReceived.value = '';
    finished.value = false;
    saleStarted.value = false;
}

function printReceipt() {
    finished.value = false;
    saleStarted.value = true;
    items.value = [];
    discount.value = 0;
    moneyReceived.value = '';
    nextTick(() => barcodeEl.value?.focus());
}

function onKeydown(event) {
    if (event.key === 'F12') {
        event.preventDefault();
        finishSale();
    }
    if (event.key.toLowerCase() === 'p' && finished.value) {
        event.preventDefault();
        printReceipt();
    }
}

onMounted(() => window.addEventListener('keydown', onKeydown));
onUnmounted(() => window.removeEventListener('keydown', onKeydown));
</script>

<template>
    <div class="min-h-screen bg-[#dff8fb] text-[#003d66]">
        <header class="flex h-24 items-center justify-between bg-[#003f6d] px-7 text-white">
            <div class="flex items-center gap-5">
                <img v-if="cashier.logo_url" :src="cashier.logo_url" :alt="cashier.name" class="h-14 max-w-56 object-contain" />
                <div v-else class="text-4xl font-semibold tracking-wide">HIPERCAIXA - PDV</div>
            </div>
            <div class="flex items-center gap-3 text-sm">
                <span class="rounded-full bg-white/10 px-4 py-2">{{ cashier.name }}</span>
                <span v-if="manager_mode" class="rounded-full bg-emerald-400 px-4 py-2 font-semibold text-[#003f6d]">Modo gerente</span>
            </div>
        </header>

        <main v-if="!saleStarted" class="grid min-h-[calc(100vh-6rem)] place-items-center p-8">
            <section class="w-full max-w-3xl rounded-2xl bg-white p-8 shadow-xl">
                <h1 class="text-3xl font-bold">HiperCaixa</h1>
                <p class="mt-2 text-zinc-600">Login sincronizado para caixa online e offline.</p>
                <div class="mt-8 grid gap-4 sm:grid-cols-3">
                    <button class="rounded-xl bg-[#003f6d] p-5 text-left font-semibold text-white" @click="startSale">
                        <MonitorCog class="mb-3 h-7 w-7" />
                        Abrir caixa
                    </button>
                    <button class="rounded-xl border border-zinc-200 p-5 text-left font-semibold text-zinc-700">
                        <PackageSearch class="mb-3 h-7 w-7" />
                        Estoque
                    </button>
                    <button class="rounded-xl border border-zinc-200 p-5 text-left font-semibold text-zinc-700">
                        <Settings class="mb-3 h-7 w-7" />
                        Configuracao
                    </button>
                </div>
                <div class="mt-6 rounded-xl border border-amber-200 bg-amber-50 p-5 text-amber-900">
                    Backup: ao trocar de computador, use o token em Configuracoes para baixar os dados do web para este caixa.
                </div>
            </section>
        </main>

        <main v-else class="grid gap-2 p-6 lg:grid-cols-[440px_540px_minmax(220px,1fr)]">
            <section class="space-y-3">
                <div class="rounded-lg bg-white px-4 py-4 text-4xl font-bold uppercase shadow-inner">
                    {{ finished ? 'FINALIZAR VENDA' : (currentProduct?.name || 'NOVA VENDA') }}
                </div>
                <div class="grid grid-cols-[220px_1fr] gap-2">
                    <div class="grid h-72 place-items-center rounded-lg border-2 border-[#003f6d] bg-white">
                        <img v-if="cashier.logo_url" :src="cashier.logo_url" class="max-h-40 max-w-40 object-contain" />
                        <MonitorCog v-else class="h-24 w-24 text-[#003f6d]" />
                    </div>
                    <div class="space-y-3">
                        <label class="block rounded-lg bg-[#003f6d] p-3 text-white">
                            <span class="block text-lg font-bold">CPF NA NOTA</span>
                            <input v-model="cpf" class="mt-2 w-full rounded bg-white/25 px-3 py-2 text-white outline-none placeholder-white/70" placeholder="Opcional, Enter para seguir" @keyup.enter="startSale" />
                        </label>
                        <label class="block rounded-lg bg-[#003f6d] p-3 text-white">
                            <span class="block text-lg font-bold">CODIGO DE BARRAS</span>
                            <input ref="barcodeEl" v-model="barcode" class="mt-2 w-full rounded bg-white/25 px-3 py-2 text-white outline-none" @keyup.enter="addBarcode" />
                        </label>
                        <div class="rounded-lg bg-[#003f6d] p-3 text-white">
                            <span class="block text-lg font-bold">VALOR UNITARIO</span>
                            <strong class="block text-right text-2xl">{{ currency(currentProduct?.price || 0) }}</strong>
                        </div>
                        <div class="rounded-lg bg-[#003f6d] p-3 text-white">
                            <span class="block text-lg font-bold">TOTAL DO ITEM</span>
                            <strong class="block text-right text-2xl">{{ currency(currentProduct?.total || 0) }}</strong>
                        </div>
                    </div>
                </div>
                <div class="grid grid-cols-2 gap-2">
                    <button class="rounded-lg bg-[#003f6d] p-4 text-left font-bold text-white" @click="showQuantity = true">F4 - Inserir quantidade</button>
                    <button class="rounded-lg bg-[#003f6d] p-4 text-left font-bold text-white" @click="finishSale">F12 - Finalizar venda</button>
                </div>
                <div class="grid grid-cols-3 gap-2 text-xs font-bold text-[#003f6d]">
                    <button class="rounded bg-white/70 p-3 text-left" @click="requestDeleteItem">F3/F11 - Excluir item</button>
                    <button class="rounded bg-white/70 p-3 text-left" @click="showQuantity = true">F4 - Quantidade</button>
                    <button class="rounded bg-white/70 p-3 text-left" @click="printReceipt">P - Imprimir</button>
                </div>
            </section>

            <section class="space-y-3">
                <div class="overflow-hidden rounded-lg border-2 border-[#003f6d] bg-white">
                    <div class="bg-[#003f6d] py-2 text-center text-xl font-bold text-white">LISTA DE PRODUTOS</div>
                    <table class="w-full text-sm">
                        <thead>
                            <tr class="text-left text-zinc-600">
                                <th class="p-2">N</th>
                                <th class="p-2">Codigo</th>
                                <th class="p-2">Descricao</th>
                                <th class="p-2 text-right">Qtd</th>
                                <th class="p-2 text-right">Total</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="(item, index) in items" :key="`${item.code}-${index}`" class="border-t">
                                <td class="p-2">{{ index + 1 }}</td>
                                <td class="p-2">{{ item.code }}</td>
                                <td class="p-2">{{ item.name }}</td>
                                <td class="p-2 text-right">{{ item.qty.toFixed(3).replace('.', ',') }}</td>
                                <td class="p-2 text-right">{{ currency(item.total) }}</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                <div class="rounded-lg bg-[#003f6d] p-4 text-white">
                    <span class="text-2xl font-bold">SUBTOTAL</span>
                    <strong class="block text-right text-6xl">{{ subtotal.toLocaleString('pt-BR', { minimumFractionDigits: 2 }) }}</strong>
                </div>
                <div class="grid grid-cols-2 gap-2">
                    <div class="rounded-lg bg-[#003f6d] p-4 text-white">
                        <span class="text-xl font-bold">TOTAL RECEBIDO</span>
                        <strong class="block text-right text-3xl">{{ currency(received) }}</strong>
                    </div>
                    <div class="rounded-lg bg-[#003f6d] p-4 text-white">
                        <span class="text-xl font-bold">TROCO</span>
                        <strong class="block text-right text-3xl">{{ currency(change) }}</strong>
                    </div>
                </div>
            </section>

            <section class="space-y-3">
                <div v-if="manager_mode" class="grid gap-2">
                    <button class="rounded-lg bg-white p-3 font-semibold text-[#003f6d]" @click="requestDeleteItem"><Trash2 class="mr-2 inline h-4 w-4" />Excluir item</button>
                    <label class="rounded-lg bg-white p-3 font-semibold text-[#003f6d]">
                        <Percent class="mr-2 inline h-4 w-4" />Desconto
                        <input v-model="discount" type="number" min="0" step="0.01" class="mt-2 w-full rounded border px-3 py-2" />
                    </label>
                    <button class="rounded-lg bg-red-600 p-3 font-semibold text-white" @click="cancelSale"><Ban class="mr-2 inline h-4 w-4" />Cancelar compra</button>
                </div>

                <div v-if="finished" class="rounded-lg bg-white p-4 shadow">
                    <h2 class="mb-3 text-2xl font-bold">Pagamento</h2>
                    <div class="grid grid-cols-2 gap-2">
                        <button class="rounded bg-emerald-500 p-3 font-bold text-white" @click="paymentMode = 'dinheiro'">Dinheiro</button>
                        <button class="rounded bg-emerald-500 p-3 font-bold text-white" @click="paymentMode = 'cheque'">Cheque</button>
                        <button class="rounded bg-emerald-500 p-3 font-bold text-white" @click="paymentMode = 'credito'">Cartao de Credito</button>
                        <button class="rounded bg-emerald-500 p-3 font-bold text-white" @click="paymentMode = 'debito'">Cartao de Debito</button>
                    </div>
                    <div class="mt-4 space-y-3">
                        <input v-if="paymentMode === 'dinheiro'" ref="moneyEl" v-model="moneyReceived" class="w-full rounded border px-3 py-3 text-2xl" placeholder="Valor em dinheiro" />
                        <div v-else-if="paymentMode === 'cheque'" class="grid gap-2">
                            <input v-model="cheque.bank_code" class="rounded border px-3 py-2" placeholder="Codigo do banco" />
                            <input v-model="cheque.bank_name" class="rounded border px-3 py-2" placeholder="Nome do banco" />
                            <input v-model="cheque.agency" class="rounded border px-3 py-2" placeholder="Agencia" />
                            <input v-model="cheque.agency_digit" class="rounded border px-3 py-2" placeholder="Digito/agencia" />
                            <input v-model="cheque.account" class="rounded border px-3 py-2" placeholder="Conta" />
                            <input v-model="cheque.account_digit" class="rounded border px-3 py-2" placeholder="Digito/conta" />
                            <input v-model="cheque.number" class="rounded border px-3 py-2" placeholder="Numero" />
                            <input v-model="cheque.due" class="rounded border px-3 py-2" placeholder="Bom para" />
                            <input v-model="cheque.value" class="rounded border px-3 py-2" placeholder="Valor" />
                        </div>
                        <div v-else-if="paymentMode === 'debito'" class="grid gap-2">
                            <input v-model="debit.value" class="rounded border px-3 py-2" placeholder="Valor" />
                            <input v-model="debit.brand" class="rounded border px-3 py-2" placeholder="Bandeira" />
                        </div>
                        <div v-else class="grid gap-2">
                            <input v-model="credit.value" class="rounded border px-3 py-2" placeholder="Valor exato" />
                            <input v-model="credit.installments" type="number" min="1" class="rounded border px-3 py-2" placeholder="Parcelas" />
                            <input v-model="credit.brand" class="rounded border px-3 py-2" placeholder="Bandeira" />
                        </div>
                    </div>
                    <div class="mt-4 rounded-lg bg-[#003f6d] p-4 text-white">
                        <div class="flex justify-between"><span>Total</span><strong>{{ currency(total) }}</strong></div>
                        <div class="flex justify-between"><span>Saldo</span><strong>{{ currency(balance) }}</strong></div>
                    </div>
                    <button class="mt-4 w-full rounded-lg bg-[#003f6d] p-3 font-bold text-white" @click="printReceipt"><Printer class="mr-2 inline h-4 w-4" />Finalizar venda / Imprimir</button>
                </div>
            </section>
        </main>

        <div v-if="showQuantity" class="fixed inset-0 grid place-items-center bg-black/50 p-4">
            <form class="w-full max-w-sm rounded-xl bg-white p-5" @submit.prevent="confirmQuantity">
                <h2 class="text-lg font-bold">Inserir quantidade</h2>
                <input v-model="quantityInput" autofocus type="number" min="1" step="1" class="mt-4 w-full rounded border px-3 py-3 text-2xl" />
                <button class="mt-4 w-full rounded bg-[#003f6d] p-3 font-bold text-white">Confirmar</button>
            </form>
        </div>

        <div v-if="showManagerLogin" class="fixed inset-0 grid place-items-center bg-black/50 p-4">
            <form class="w-full max-w-sm rounded-xl bg-white p-5" @submit.prevent="confirmManagerDelete">
                <h2 class="text-lg font-bold">Login do infoprodutor</h2>
                <p class="mt-1 text-sm text-zinc-600">Necessario para excluir item no aplicativo.</p>
                <input v-model="managerPassword" autofocus type="password" class="mt-4 w-full rounded border px-3 py-3" placeholder="Senha do infoprodutor" />
                <button class="mt-4 w-full rounded bg-[#003f6d] p-3 font-bold text-white">Autorizar exclusao</button>
            </form>
        </div>
    </div>
</template>
