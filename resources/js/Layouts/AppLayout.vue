<script setup>
import { ref, computed, watch } from 'vue';
import { Link, usePage, router } from '@inertiajs/vue3';
import FinLogo from '@/Components/FinLogo.vue';

const page = usePage();
const user = computed(() => page.props.auth.user);
const flash = computed(() => page.props.flash?.success);

const sidebarOpen = ref(false);
const showToast = ref(false);

watch(flash, (value) => {
    if (value) {
        showToast.value = true;
        setTimeout(() => (showToast.value = false), 3000);
    }
}, { immediate: true });

const nav = [
    { name: 'Visão geral', route: 'dashboard', icon: '<path fill-rule="evenodd" d="M9.293 2.293a1 1 0 011.414 0l7 7A1 1 0 0117 11h-1v6a1 1 0 01-1 1h-2a1 1 0 01-1-1v-3H9v3a1 1 0 01-1 1H6a1 1 0 01-1-1v-6H4a1 1 0 01-.707-1.707l7-7z" clip-rule="evenodd" />' },
    { name: 'Lançamentos', route: 'transactions.index', icon: '<path d="M8 5a1 1 0 100 2h5.586l-1.293 1.293a1 1 0 001.414 1.414l3-3a1 1 0 000-1.414l-3-3a1 1 0 10-1.414 1.414L13.586 5H8zM12 15a1 1 0 100-2H6.414l1.293-1.293a1 1 0 10-1.414-1.414l-3 3a1 1 0 000 1.414l3 3a1 1 0 001.414-1.414L6.414 15H12z" />' },
    { name: 'Categorias', route: 'categories.index', icon: '<path fill-rule="evenodd" d="M17.707 9.293l-5-5A1 1 0 0012 4H7a3 3 0 00-3 3v5a1 1 0 00.293.707l5 5a1 1 0 001.414 0l7-7a1 1 0 000-1.414zM8 9a1 1 0 100-2 1 1 0 000 2z" clip-rule="evenodd" />' },
    { name: 'Contas', route: 'accounts.index', icon: '<path d="M4 4a2 2 0 00-2 2v1h16V6a2 2 0 00-2-2H4zM18 9H2v5a2 2 0 002 2h12a2 2 0 002-2V9z" />' },
    { name: 'Orçamentos', route: 'budgets.index', icon: '<path d="M2 11a1 1 0 011-1h2a1 1 0 011 1v5a1 1 0 01-1 1H3a1 1 0 01-1-1v-5zM8 7a1 1 0 011-1h2a1 1 0 011 1v9a1 1 0 01-1 1H9a1 1 0 01-1-1V7zM14 4a1 1 0 011-1h2a1 1 0 011 1v12a1 1 0 01-1 1h-2a1 1 0 01-1-1V4z" />' },
    { name: 'Importar CSV', route: 'import.index', icon: '<path d="M9.25 13.25a.75.75 0 001.5 0V4.636l2.955 3.129a.75.75 0 001.09-1.03l-4.25-4.5a.75.75 0 00-1.09 0l-4.25 4.5a.75.75 0 101.09 1.03L9.25 4.636v8.614z" /><path d="M3.5 12.75a.75.75 0 00-1.5 0v2.5A2.75 2.75 0 004.75 18h10.5A2.75 2.75 0 0018 15.25v-2.5a.75.75 0 00-1.5 0v2.5c0 .69-.56 1.25-1.25 1.25H4.75c-.69 0-1.25-.56-1.25-1.25v-2.5z" />' },
];

const isCurrent = (name) => route().current(name);

const logout = () => router.post(route('logout'));
</script>

<template>
    <div class="min-h-screen bg-slate-50">
        <!-- Overlay mobile -->
        <div v-show="sidebarOpen" class="fixed inset-0 z-30 bg-slate-900/50 lg:hidden" @click="sidebarOpen = false" />

        <!-- Sidebar -->
        <aside
            class="fixed inset-y-0 left-0 z-40 flex w-64 flex-col bg-slate-900 text-slate-300 transition-transform duration-200 lg:translate-x-0"
            :class="sidebarOpen ? 'translate-x-0' : '-translate-x-full'"
        >
            <div class="flex items-center gap-2.5 px-6 py-5">
                <FinLogo class="h-9 w-9" />
                <span class="text-lg font-bold text-white">FinTrack</span>
            </div>

            <nav class="mt-2 flex-1 space-y-1 px-3">
                <Link
                    v-for="item in nav"
                    :key="item.route"
                    :href="route(item.route)"
                    class="flex items-center gap-3 rounded-lg px-3 py-2.5 text-sm font-medium transition"
                    :class="isCurrent(item.route)
                        ? 'bg-violet-600 text-white shadow-lg shadow-violet-900/30'
                        : 'text-slate-400 hover:bg-slate-800 hover:text-white'"
                >
                    <svg class="h-5 w-5 shrink-0" viewBox="0 0 20 20" fill="currentColor" v-html="item.icon" />
                    {{ item.name }}
                </Link>
            </nav>

            <div class="border-t border-slate-800 p-3">
                <div class="flex items-center gap-3 rounded-lg px-3 py-2">
                    <div class="flex h-9 w-9 items-center justify-center rounded-full bg-violet-600 text-sm font-semibold text-white">
                        {{ user.name.charAt(0).toUpperCase() }}
                    </div>
                    <div class="min-w-0 flex-1">
                        <p class="truncate text-sm font-medium text-white">{{ user.name }}</p>
                        <p class="truncate text-xs text-slate-500">{{ user.email }}</p>
                    </div>
                </div>
                <div class="mt-1 flex gap-1">
                    <Link :href="route('profile.edit')" class="flex-1 rounded-md px-3 py-2 text-center text-xs font-medium text-slate-400 hover:bg-slate-800 hover:text-white">
                        Perfil
                    </Link>
                    <button type="button" @click="logout" class="flex-1 rounded-md px-3 py-2 text-center text-xs font-medium text-slate-400 hover:bg-slate-800 hover:text-white">
                        Sair
                    </button>
                </div>
            </div>
        </aside>

        <!-- Conteúdo -->
        <div class="lg:pl-64">
            <header class="sticky top-0 z-20 flex items-center gap-4 border-b border-slate-200 bg-white/80 px-4 py-4 backdrop-blur sm:px-6 lg:px-8">
                <button type="button" class="text-slate-500 lg:hidden" @click="sidebarOpen = true">
                    <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M4 6h16M4 12h16M4 18h16" />
                    </svg>
                </button>
                <h1 class="text-lg font-semibold text-slate-800">
                    <slot name="title" />
                </h1>
                <div class="ml-auto">
                    <slot name="actions" />
                </div>
            </header>

            <main class="px-4 py-6 sm:px-6 lg:px-8">
                <slot />
            </main>
        </div>

        <!-- Toast de sucesso -->
        <Transition
            enter-active-class="transition duration-300" enter-from-class="translate-y-4 opacity-0"
            leave-active-class="transition duration-200" leave-to-class="translate-y-4 opacity-0"
        >
            <div v-if="showToast" class="fixed bottom-6 right-6 z-50 flex items-center gap-2 rounded-xl bg-slate-900 px-4 py-3 text-sm font-medium text-white shadow-2xl">
                <svg class="h-5 w-5 text-emerald-400" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd" /></svg>
                {{ flash }}
            </div>
        </Transition>
    </div>
</template>
