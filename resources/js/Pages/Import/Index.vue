<script setup>
import { ref, reactive, computed } from 'vue';
import { Head, useForm } from '@inertiajs/vue3';
import Papa from 'papaparse';
import AppLayout from '@/Layouts/AppLayout.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import { formatBRL, formatDate } from '@/utils/format';

const props = defineProps({ accounts: Array });

const accountId = ref(props.accounts[0]?.id ?? '');
const fileName = ref('');
const headers = ref([]);
const rawRows = ref([]);
const mapping = reactive({ date: '', description: '', amount: '' });
const parseError = ref('');

const form = useForm({ account_id: '', rows: [] });

// --- helpers de parsing -----------------------------------------------------
function parseAmount(v) {
    if (typeof v === 'number') return v;
    if (v == null) return NaN;
    let s = String(v).trim();
    if (!s) return NaN;
    let neg = false;
    if (/^\(.*\)$/.test(s)) { neg = true; s = s.slice(1, -1); }
    s = s.replace(/[R$\s]/gi, '');
    if (s.startsWith('-')) { neg = true; s = s.slice(1); }
    if (s.includes(',') && s.includes('.')) {
        // o separador decimal é o último que aparece
        if (s.lastIndexOf(',') > s.lastIndexOf('.')) s = s.replace(/\./g, '').replace(',', '.');
        else s = s.replace(/,/g, '');
    } else if (s.includes(',')) {
        s = s.replace(',', '.');
    }
    const n = parseFloat(s);
    if (isNaN(n)) return NaN;
    return neg ? -n : n;
}

function parseDateISO(v) {
    if (!v) return null;
    const s = String(v).trim();
    let m = s.match(/^(\d{4})-(\d{2})-(\d{2})/);
    if (m) return `${m[1]}-${m[2]}-${m[3]}`;
    m = s.match(/^(\d{1,2})[/\-.](\d{1,2})[/\-.](\d{2,4})$/);
    if (m) {
        let [, d, mo, y] = m;
        if (y.length === 2) y = '20' + y;
        return `${y}-${mo.padStart(2, '0')}-${d.padStart(2, '0')}`;
    }
    return null;
}

function guess(fields, patterns) {
    return fields.find((f) => patterns.test(f)) ?? '';
}

// --- upload -----------------------------------------------------------------
function onFile(e) {
    const file = e.target.files[0];
    if (!file) return;
    fileName.value = file.name;
    parseError.value = '';

    Papa.parse(file, {
        header: true,
        skipEmptyLines: true,
        complete: (res) => {
            const fields = (res.meta.fields || []).filter((f) => f && f.trim() !== '');
            if (!fields.length) {
                parseError.value = 'Não consegui ler as colunas do arquivo. Confira se o CSV tem cabeçalho.';
                return;
            }
            headers.value = fields;
            rawRows.value = res.data;
            mapping.date = guess(fields, /data|date|dt/i);
            mapping.description = guess(fields, /desc|hist|lan|memo|detal|estab/i);
            mapping.amount = guess(fields, /valor|amount|montante|value|vlr|credito|debito/i);
        },
        error: () => { parseError.value = 'Falha ao ler o arquivo CSV.'; },
    });
}

// --- preview ----------------------------------------------------------------
const preview = computed(() => {
    if (!mapping.date || !mapping.description || !mapping.amount) return [];
    return rawRows.value.map((r) => {
        const date = parseDateISO(r[mapping.date]);
        const amount = parseAmount(r[mapping.amount]);
        const description = (r[mapping.description] ?? '').toString().trim();
        const valid = !!date && !!description && !isNaN(amount) && Math.abs(amount) >= 0.01;
        return { date, description, amount, valid };
    });
});

const validRows = computed(() => preview.value.filter((r) => r.valid));
const invalidCount = computed(() => preview.value.length - validRows.value.length);

function submit() {
    form.account_id = accountId.value;
    form.rows = validRows.value.map((r) => ({ date: r.date, description: r.description, amount: r.amount }));
    form.post(route('import.store'));
}
</script>

<template>
    <Head title="Importar CSV" />

    <AppLayout>
        <template #title>Importar CSV</template>

        <p class="mb-4 max-w-2xl text-sm text-slate-500">
            Importe um extrato em CSV. Escolha a conta, selecione o arquivo e confira a prévia
            antes de confirmar. <span class="font-medium text-slate-600">Valores negativos viram
            despesas; positivos, receitas.</span> Os lançamentos entram sem categoria — você ajusta depois.
        </p>

        <!-- Passo 1: conta + arquivo -->
        <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
            <div class="grid gap-4 sm:grid-cols-2">
                <div>
                    <InputLabel value="Conta de destino" />
                    <select v-model="accountId" class="mt-1 block w-full rounded-md border-slate-300 text-sm focus:border-violet-500 focus:ring-violet-500">
                        <option v-for="a in accounts" :key="a.id" :value="a.id">{{ a.name }}</option>
                    </select>
                </div>
                <div>
                    <InputLabel value="Arquivo CSV" />
                    <input type="file" accept=".csv,text/csv" @change="onFile"
                           class="mt-1 block w-full text-sm text-slate-600 file:mr-3 file:rounded-md file:border-0 file:bg-violet-50 file:px-3 file:py-2 file:text-sm file:font-semibold file:text-violet-700 hover:file:bg-violet-100" />
                    <p v-if="fileName" class="mt-1 text-xs text-slate-400">{{ fileName }}</p>
                </div>
            </div>
            <p v-if="parseError" class="mt-3 text-sm text-rose-600">{{ parseError }}</p>
        </div>

        <!-- Passo 2: mapeamento -->
        <div v-if="headers.length" class="mt-4 rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
            <h2 class="text-sm font-semibold text-slate-700">Mapeamento das colunas</h2>
            <p class="text-xs text-slate-400">Confirme qual coluna do arquivo corresponde a cada campo.</p>
            <div class="mt-3 grid gap-4 sm:grid-cols-3">
                <div v-for="field in [{ key: 'date', label: 'Data' }, { key: 'description', label: 'Descrição' }, { key: 'amount', label: 'Valor' }]" :key="field.key">
                    <InputLabel :value="field.label" />
                    <select v-model="mapping[field.key]" class="mt-1 block w-full rounded-md border-slate-300 text-sm focus:border-violet-500 focus:ring-violet-500">
                        <option value="">—</option>
                        <option v-for="h in headers" :key="h" :value="h">{{ h }}</option>
                    </select>
                </div>
            </div>
        </div>

        <!-- Passo 3: preview -->
        <div v-if="preview.length" class="mt-4 rounded-2xl border border-slate-200 bg-white shadow-sm">
            <div class="flex flex-wrap items-center justify-between gap-2 border-b border-slate-100 p-4">
                <h2 class="text-sm font-semibold text-slate-700">Prévia</h2>
                <div class="flex items-center gap-3 text-xs">
                    <span class="rounded-full bg-emerald-50 px-2 py-1 font-medium text-emerald-700">{{ validRows.length }} válidos</span>
                    <span v-if="invalidCount" class="rounded-full bg-amber-50 px-2 py-1 font-medium text-amber-700">{{ invalidCount }} ignorados</span>
                </div>
            </div>
            <div class="max-h-96 overflow-auto">
                <table class="min-w-full divide-y divide-slate-100 text-sm">
                    <thead class="sticky top-0 bg-slate-50 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">
                        <tr>
                            <th class="px-4 py-2">Data</th>
                            <th class="px-4 py-2">Descrição</th>
                            <th class="px-4 py-2 text-right">Valor</th>
                            <th class="px-4 py-2">Tipo</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        <tr v-for="(r, i) in preview.slice(0, 100)" :key="i" :class="r.valid ? '' : 'bg-amber-50/50 text-slate-400'">
                            <td class="whitespace-nowrap px-4 py-2">{{ r.date ? formatDate(r.date) : '—' }}</td>
                            <td class="px-4 py-2">{{ r.description || '—' }}</td>
                            <td class="whitespace-nowrap px-4 py-2 text-right font-medium" :class="r.valid ? (r.amount < 0 ? 'text-rose-600' : 'text-emerald-600') : ''">
                                {{ isNaN(r.amount) ? '—' : formatBRL(Math.abs(r.amount)) }}
                            </td>
                            <td class="px-4 py-2">
                                <span v-if="r.valid" class="text-xs" :class="r.amount < 0 ? 'text-rose-600' : 'text-emerald-600'">
                                    {{ r.amount < 0 ? 'Despesa' : 'Receita' }}
                                </span>
                                <span v-else class="text-xs text-amber-600">inválido</span>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
            <div class="flex items-center justify-between border-t border-slate-100 p-4">
                <p class="text-xs text-slate-400">Mostrando até 100 linhas. Linhas inválidas (data/valor ilegíveis) são ignoradas.</p>
                <PrimaryButton :disabled="!validRows.length || form.processing" class="!bg-violet-600 hover:!bg-violet-700" @click="submit">
                    Importar {{ validRows.length }} lançamento(s)
                </PrimaryButton>
            </div>
        </div>
    </AppLayout>
</template>
