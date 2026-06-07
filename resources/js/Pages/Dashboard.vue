<script setup>
import { computed } from 'vue';
import { Head, Link } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import LineChart from '@/Components/Charts/LineChart.vue';
import DoughnutChart from '@/Components/Charts/DoughnutChart.vue';
import { formatBRL, formatDate } from '@/utils/format';

const props = defineProps({
    summary: Object,
    monthlyChart: Object,
    categoryChart: Array,
    budgets: Array,
    recent: Array,
    monthLabel: String,
});

const cards = computed(() => [
    { label: 'Saldo total', value: props.summary.balance, color: 'text-slate-900', ring: 'bg-violet-100 text-violet-700', icon: '💜' },
    { label: 'Receitas do mês', value: props.summary.income, color: 'text-emerald-600', ring: 'bg-emerald-100 text-emerald-700', icon: '↑' },
    { label: 'Despesas do mês', value: props.summary.expense, color: 'text-rose-600', ring: 'bg-rose-100 text-rose-700', icon: '↓' },
    { label: 'Resultado do mês', value: props.summary.result, color: props.summary.result >= 0 ? 'text-emerald-600' : 'text-rose-600', ring: 'bg-amber-100 text-amber-700', icon: '=' },
]);

const lineData = computed(() => ({
    labels: props.monthlyChart.labels,
    datasets: [
        { label: 'Receitas', data: props.monthlyChart.income, borderColor: '#16a34a', backgroundColor: 'rgba(22,163,74,0.12)', fill: true, tension: 0.35, pointRadius: 3 },
        { label: 'Despesas', data: props.monthlyChart.expense, borderColor: '#ef4444', backgroundColor: 'rgba(239,68,68,0.12)', fill: true, tension: 0.35, pointRadius: 3 },
    ],
}));

const lineOptions = {
    responsive: true,
    maintainAspectRatio: false,
    plugins: { legend: { position: 'bottom', labels: { usePointStyle: true, boxWidth: 8 } } },
    scales: {
        y: { ticks: { callback: (v) => 'R$ ' + v.toLocaleString('pt-BR') }, grid: { color: '#f1f5f9' } },
        x: { grid: { display: false } },
    },
};

const doughnutData = computed(() => ({
    labels: props.categoryChart.map((c) => c.name),
    datasets: [{
        data: props.categoryChart.map((c) => c.total),
        backgroundColor: props.categoryChart.map((c) => c.color),
        borderWidth: 2,
        borderColor: '#fff',
    }],
}));

const doughnutOptions = {
    responsive: true,
    maintainAspectRatio: false,
    cutout: '62%',
    plugins: { legend: { position: 'bottom', labels: { usePointStyle: true, boxWidth: 8, padding: 12 } } },
};
</script>

<template>
    <Head title="Visão geral" />

    <AppLayout>
        <template #title>Visão geral</template>
        <template #actions>
            <Link :href="route('transactions.index')" class="rounded-lg bg-violet-600 px-4 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-violet-700">
                + Novo lançamento
            </Link>
        </template>

        <!-- Cards -->
        <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
            <div v-for="card in cards" :key="card.label" class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                <div class="flex items-center justify-between">
                    <span class="text-sm font-medium text-slate-500">{{ card.label }}</span>
                    <span class="flex h-8 w-8 items-center justify-center rounded-full text-sm font-bold" :class="card.ring">{{ card.icon }}</span>
                </div>
                <p class="mt-3 text-2xl font-bold tracking-tight" :class="card.color">{{ formatBRL(card.value) }}</p>
            </div>
        </div>

        <!-- Gráficos -->
        <div class="mt-6 grid gap-4 lg:grid-cols-3">
            <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm lg:col-span-2">
                <h2 class="text-sm font-semibold text-slate-700">Receitas x Despesas (6 meses)</h2>
                <div class="mt-4 h-72">
                    <LineChart :data="lineData" :options="lineOptions" />
                </div>
            </div>
            <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                <h2 class="text-sm font-semibold text-slate-700">Despesas por categoria</h2>
                <p class="text-xs text-slate-400">{{ monthLabel }}</p>
                <div class="mt-4 h-72">
                    <DoughnutChart v-if="categoryChart.length" :data="doughnutData" :options="doughnutOptions" />
                    <p v-else class="flex h-full items-center justify-center text-sm text-slate-400">Sem despesas neste mês.</p>
                </div>
            </div>
        </div>

        <!-- Orçamentos + Recentes -->
        <div class="mt-6 grid gap-4 lg:grid-cols-2">
            <!-- Orçamentos -->
            <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                <div class="flex items-center justify-between">
                    <h2 class="text-sm font-semibold text-slate-700">Orçamentos do mês</h2>
                    <Link :href="route('budgets.index')" class="text-xs font-medium text-violet-600 hover:underline">Gerenciar</Link>
                </div>
                <div v-if="budgets.length" class="mt-4 space-y-4">
                    <div v-for="b in budgets" :key="b.id">
                        <div class="flex items-center justify-between text-sm">
                            <span class="font-medium text-slate-600">{{ b.category.icon }} {{ b.category.name }}</span>
                            <span class="text-slate-500">{{ formatBRL(b.spent) }} / {{ formatBRL(b.limit) }}</span>
                        </div>
                        <div class="mt-1.5 h-2 overflow-hidden rounded-full bg-slate-100">
                            <div class="h-full rounded-full transition-all"
                                 :class="b.percent >= 100 ? 'bg-rose-500' : b.percent >= 80 ? 'bg-amber-500' : 'bg-violet-500'"
                                 :style="{ width: b.percent + '%' }" />
                        </div>
                    </div>
                </div>
                <p v-else class="mt-4 text-sm text-slate-400">Nenhum orçamento definido (opcional).</p>
            </div>

            <!-- Recentes -->
            <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                <h2 class="text-sm font-semibold text-slate-700">Lançamentos recentes</h2>
                <ul class="mt-4 divide-y divide-slate-100">
                    <li v-for="t in recent" :key="t.id" class="flex items-center justify-between py-2.5">
                        <div class="flex items-center gap-3">
                            <span class="flex h-9 w-9 items-center justify-center rounded-full text-base"
                                  :style="{ backgroundColor: (t.category?.color || '#7c3aed') + '22' }">
                                {{ t.category?.icon || '💸' }}
                            </span>
                            <div>
                                <p class="text-sm font-medium text-slate-700">{{ t.description }}</p>
                                <p class="text-xs text-slate-400">{{ formatDate(t.date) }} · {{ t.account?.name }}</p>
                            </div>
                        </div>
                        <span class="text-sm font-semibold" :class="t.type === 'income' ? 'text-emerald-600' : 'text-rose-600'">
                            {{ t.type === 'income' ? '+' : '-' }} {{ formatBRL(t.amount) }}
                        </span>
                    </li>
                </ul>
                <p v-if="!recent.length" class="mt-4 text-sm text-slate-400">Nenhum lançamento ainda.</p>
            </div>
        </div>
    </AppLayout>
</template>
