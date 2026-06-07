<script setup>
import { ref, reactive, computed, watch } from 'vue';
import { Head, Link, useForm, router } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import Modal from '@/Components/Modal.vue';
import InputLabel from '@/Components/InputLabel.vue';
import TextInput from '@/Components/TextInput.vue';
import InputError from '@/Components/InputError.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import { formatBRL, formatDate, todayISO } from '@/utils/format';

const props = defineProps({
    transactions: Object,
    accounts: Array,
    categories: Array,
    filters: Object,
});

// ---- Filtros ----
const filters = reactive({ ...props.filters });
let timer = null;
watch(filters, () => {
    clearTimeout(timer);
    timer = setTimeout(() => {
        router.get(route('transactions.index'), filters, { preserveState: true, replace: true, preserveScroll: true });
    }, 200);
});

// ---- Modal / formulário ----
const showModal = ref(false);
const editing = ref(false);
const form = useForm({
    id: null,
    account_id: '',
    category_id: '',
    type: 'expense',
    amount: '',
    description: '',
    date: todayISO(),
});

const filteredCategories = computed(() => props.categories.filter((c) => c.type === form.type));

function openCreate() {
    editing.value = false;
    form.reset();
    form.clearErrors();
    form.account_id = props.accounts[0]?.id ?? '';
    form.date = todayISO();
    showModal.value = true;
}

function openEdit(t) {
    editing.value = true;
    form.clearErrors();
    form.id = t.id;
    form.account_id = t.account_id;
    form.category_id = t.category_id ?? '';
    form.type = t.type;
    form.amount = t.amount;
    form.description = t.description;
    form.date = t.date;
    showModal.value = true;
}

function submit() {
    const options = { preserveScroll: true, onSuccess: () => (showModal.value = false) };
    if (editing.value) {
        form.put(route('transactions.update', form.id), options);
    } else {
        form.post(route('transactions.store'), options);
    }
}

function remove(t) {
    if (confirm('Remover este lançamento?')) {
        router.delete(route('transactions.destroy', t.id), { preserveScroll: true });
    }
}
</script>

<template>
    <Head title="Lançamentos" />

    <AppLayout>
        <template #title>Lançamentos</template>
        <template #actions>
            <button @click="openCreate" class="rounded-lg bg-violet-600 px-4 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-violet-700">
                + Novo lançamento
            </button>
        </template>

        <!-- Filtros -->
        <div class="grid gap-3 rounded-2xl border border-slate-200 bg-white p-4 shadow-sm sm:grid-cols-2 lg:grid-cols-4">
            <div>
                <label class="text-xs font-medium text-slate-500">Mês</label>
                <input type="month" v-model="filters.month" class="mt-1 block w-full rounded-lg border-slate-300 text-sm focus:border-violet-500 focus:ring-violet-500" />
            </div>
            <div>
                <label class="text-xs font-medium text-slate-500">Tipo</label>
                <select v-model="filters.type" class="mt-1 block w-full rounded-lg border-slate-300 text-sm focus:border-violet-500 focus:ring-violet-500">
                    <option value="">Todos</option>
                    <option value="income">Receitas</option>
                    <option value="expense">Despesas</option>
                </select>
            </div>
            <div>
                <label class="text-xs font-medium text-slate-500">Categoria</label>
                <select v-model="filters.category_id" class="mt-1 block w-full rounded-lg border-slate-300 text-sm focus:border-violet-500 focus:ring-violet-500">
                    <option value="">Todas</option>
                    <option v-for="c in categories" :key="c.id" :value="c.id">{{ c.icon }} {{ c.name }}</option>
                </select>
            </div>
            <div>
                <label class="text-xs font-medium text-slate-500">Conta</label>
                <select v-model="filters.account_id" class="mt-1 block w-full rounded-lg border-slate-300 text-sm focus:border-violet-500 focus:ring-violet-500">
                    <option value="">Todas</option>
                    <option v-for="a in accounts" :key="a.id" :value="a.id">{{ a.name }}</option>
                </select>
            </div>
        </div>

        <!-- Tabela -->
        <div class="mt-4 overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
            <table class="min-w-full divide-y divide-slate-100">
                <thead class="bg-slate-50 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">
                    <tr>
                        <th class="px-4 py-3">Data</th>
                        <th class="px-4 py-3">Descrição</th>
                        <th class="px-4 py-3">Categoria</th>
                        <th class="px-4 py-3">Conta</th>
                        <th class="px-4 py-3 text-right">Valor</th>
                        <th class="px-4 py-3"></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 text-sm">
                    <tr v-for="t in transactions.data" :key="t.id" class="hover:bg-slate-50">
                        <td class="whitespace-nowrap px-4 py-3 text-slate-500">{{ formatDate(t.date) }}</td>
                        <td class="px-4 py-3 font-medium text-slate-700">{{ t.description }}</td>
                        <td class="px-4 py-3">
                            <span v-if="t.category" class="inline-flex items-center gap-1 rounded-full px-2 py-0.5 text-xs font-medium"
                                  :style="{ backgroundColor: t.category.color + '22', color: t.category.color }">
                                {{ t.category.icon }} {{ t.category.name }}
                            </span>
                            <span v-else class="text-xs text-slate-400">—</span>
                        </td>
                        <td class="px-4 py-3 text-slate-500">{{ t.account?.name }}</td>
                        <td class="whitespace-nowrap px-4 py-3 text-right font-semibold" :class="t.type === 'income' ? 'text-emerald-600' : 'text-rose-600'">
                            {{ t.type === 'income' ? '+' : '-' }} {{ formatBRL(t.amount) }}
                        </td>
                        <td class="whitespace-nowrap px-4 py-3 text-right">
                            <button @click="openEdit(t)" class="text-xs font-medium text-violet-600 hover:underline">Editar</button>
                            <button @click="remove(t)" class="ml-3 text-xs font-medium text-slate-400 hover:text-rose-600">Excluir</button>
                        </td>
                    </tr>
                    <tr v-if="!transactions.data.length">
                        <td colspan="6" class="px-4 py-10 text-center text-sm text-slate-400">Nenhum lançamento neste período.</td>
                    </tr>
                </tbody>
            </table>
        </div>

        <!-- Paginação -->
        <div v-if="transactions.links.length > 3" class="mt-4 flex flex-wrap gap-1">
            <component
                :is="link.url ? Link : 'span'"
                v-for="(link, i) in transactions.links" :key="i"
                :href="link.url || undefined"
                preserve-scroll
                class="rounded-md px-3 py-1.5 text-sm"
                :class="link.active ? 'bg-violet-600 text-white' : link.url ? 'bg-white text-slate-600 hover:bg-slate-100 border border-slate-200' : 'text-slate-300'"
                v-html="link.label"
            />
        </div>

        <!-- Modal -->
        <Modal :show="showModal" @close="showModal = false">
            <form @submit.prevent="submit" class="p-6">
                <h2 class="text-lg font-semibold text-slate-800">{{ editing ? 'Editar lançamento' : 'Novo lançamento' }}</h2>

                <div class="mt-4 flex gap-2">
                    <button type="button" @click="form.type = 'expense'; form.category_id = ''"
                            class="flex-1 rounded-lg border px-3 py-2 text-sm font-medium transition"
                            :class="form.type === 'expense' ? 'border-rose-500 bg-rose-50 text-rose-700' : 'border-slate-200 text-slate-500'">
                        Despesa
                    </button>
                    <button type="button" @click="form.type = 'income'; form.category_id = ''"
                            class="flex-1 rounded-lg border px-3 py-2 text-sm font-medium transition"
                            :class="form.type === 'income' ? 'border-emerald-500 bg-emerald-50 text-emerald-700' : 'border-slate-200 text-slate-500'">
                        Receita
                    </button>
                </div>

                <div class="mt-4 grid gap-4 sm:grid-cols-2">
                    <div>
                        <InputLabel value="Valor (R$)" />
                        <TextInput v-model="form.amount" type="number" step="0.01" min="0.01" class="mt-1 block w-full" />
                        <InputError :message="form.errors.amount" class="mt-1" />
                    </div>
                    <div>
                        <InputLabel value="Data" />
                        <TextInput v-model="form.date" type="date" class="mt-1 block w-full" />
                        <InputError :message="form.errors.date" class="mt-1" />
                    </div>
                </div>

                <div class="mt-4">
                    <InputLabel value="Descrição" />
                    <TextInput v-model="form.description" type="text" class="mt-1 block w-full" placeholder="Ex.: Compra no supermercado" />
                    <InputError :message="form.errors.description" class="mt-1" />
                </div>

                <div class="mt-4 grid gap-4 sm:grid-cols-2">
                    <div>
                        <InputLabel value="Conta" />
                        <select v-model="form.account_id" class="mt-1 block w-full rounded-md border-slate-300 text-sm focus:border-violet-500 focus:ring-violet-500">
                            <option v-for="a in accounts" :key="a.id" :value="a.id">{{ a.name }}</option>
                        </select>
                        <InputError :message="form.errors.account_id" class="mt-1" />
                    </div>
                    <div>
                        <InputLabel value="Categoria" />
                        <select v-model="form.category_id" class="mt-1 block w-full rounded-md border-slate-300 text-sm focus:border-violet-500 focus:ring-violet-500">
                            <option value="">Sem categoria</option>
                            <option v-for="c in filteredCategories" :key="c.id" :value="c.id">{{ c.icon }} {{ c.name }}</option>
                        </select>
                        <InputError :message="form.errors.category_id" class="mt-1" />
                    </div>
                </div>

                <div class="mt-6 flex justify-end gap-2">
                    <SecondaryButton type="button" @click="showModal = false">Cancelar</SecondaryButton>
                    <PrimaryButton :disabled="form.processing" class="!bg-violet-600 hover:!bg-violet-700">
                        {{ editing ? 'Salvar' : 'Adicionar' }}
                    </PrimaryButton>
                </div>
            </form>
        </Modal>
    </AppLayout>
</template>
