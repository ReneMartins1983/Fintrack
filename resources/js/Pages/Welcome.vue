<script setup>
import { Head, Link } from '@inertiajs/vue3';
import FinLogo from '@/Components/FinLogo.vue';

defineProps({
    canLogin: { type: Boolean },
    canRegister: { type: Boolean },
});

const features = [
    { icon: '📊', title: 'Dashboard claro', text: 'Saldo, receitas, despesas e a evolução dos últimos 6 meses num relance.' },
    { icon: '🏷️', title: 'Categorias', text: 'Organize cada gasto por categoria, com cor e ícone próprios.' },
    { icon: '💼', title: 'Contas', text: 'Carteira, banco, poupança e cartão — cada um com seu saldo.' },
    { icon: '🎯', title: 'Orçamentos', text: 'Defina limites mensais por categoria e acompanhe o consumo.' },
];
</script>

<template>
    <Head title="FinTrack — Controle de finanças pessoais" />

    <div class="min-h-screen bg-slate-50 text-slate-800">
        <!-- Topo -->
        <header class="mx-auto flex max-w-6xl items-center justify-between px-6 py-5">
            <div class="flex items-center gap-2.5">
                <FinLogo class="h-9 w-9" />
                <span class="text-lg font-bold text-slate-900">FinTrack</span>
            </div>
            <nav v-if="canLogin" class="flex items-center gap-2">
                <Link :href="route('login')" class="rounded-lg px-4 py-2 text-sm font-medium text-slate-600 hover:text-slate-900">Entrar</Link>
                <Link v-if="canRegister" :href="route('register')" class="rounded-lg bg-violet-600 px-4 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-violet-700">Criar conta</Link>
            </nav>
        </header>

        <!-- Hero -->
        <section class="relative overflow-hidden">
            <div class="pointer-events-none absolute inset-0 -z-10">
                <div class="absolute -top-32 left-1/2 h-80 w-[44rem] -translate-x-1/2 rounded-full bg-violet-300/40 blur-3xl"></div>
            </div>

            <div class="mx-auto max-w-3xl px-6 py-20 text-center sm:py-28">
                <span class="inline-flex items-center gap-2 rounded-full border border-violet-200 bg-white px-3 py-1 text-xs font-medium text-violet-700 shadow-sm">
                    💜 Simples · rápido · sem planilha
                </span>
                <h1 class="mx-auto mt-6 text-4xl font-extrabold tracking-tight text-slate-900 sm:text-5xl">
                    Controle suas finanças <span class="text-violet-600">sem complicação</span>
                </h1>
                <p class="mx-auto mt-5 max-w-xl text-lg text-slate-600">
                    Registre receitas e despesas, organize por categorias e contas, e veja para
                    onde seu dinheiro está indo — com gráficos claros e orçamentos opcionais.
                </p>
                <div class="mt-9 flex flex-col items-center justify-center gap-3 sm:flex-row">
                    <Link :href="canRegister ? route('register') : route('login')"
                          class="rounded-xl bg-violet-600 px-6 py-3 text-base font-semibold text-white shadow-lg shadow-violet-600/20 transition hover:bg-violet-700">
                        Começar agora
                    </Link>
                    <Link :href="route('login')" class="rounded-xl border border-slate-200 bg-white px-6 py-3 text-base font-semibold text-slate-700 transition hover:bg-slate-50">
                        Entrar
                    </Link>
                </div>
                <p class="mt-6 text-sm text-slate-500">
                    Conta de demonstração: <span class="font-semibold text-slate-700">demo@fintrack.app</span> / <span class="font-semibold text-slate-700">password</span>
                </p>
            </div>
        </section>

        <!-- Features -->
        <section class="mx-auto max-w-5xl px-6 pb-24">
            <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                <div v-for="f in features" :key="f.title" class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                    <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-violet-100 text-xl">{{ f.icon }}</div>
                    <h3 class="mt-3 font-semibold text-slate-800">{{ f.title }}</h3>
                    <p class="mt-1 text-sm text-slate-500">{{ f.text }}</p>
                </div>
            </div>
        </section>

        <footer class="border-t border-slate-200 py-6 text-center text-sm text-slate-400">
            FinTrack — projeto pessoal · Laravel + Inertia + Vue
        </footer>
    </div>
</template>
