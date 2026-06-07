<script setup>
import { Head, useForm, router } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import InputLabel from '@/Components/InputLabel.vue';
import TextInput from '@/Components/TextInput.vue';
import InputError from '@/Components/InputError.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import { formatBRL } from '@/utils/format';

const props = defineProps({ budgets: Array, monthLabel: String, availableCategories: Array });

const form = useForm({ category_id: '', amount: '' });

function submit() {
    form.post(route('budgets.store'), {
        preserveScroll: true,
        onSuccess: () => form.reset(),
    });
}
function remove(b) {
    if (confirm('Remover este orçamento?')) {
        router.delete(route('budgets.destroy', b.id), { preserveScroll: true });
    }
}
</script>

<template>
    <Head title="Orçamentos" />

    <AppLayout>
        <template #title>Orçamentos</template>

        <p class="mb-4 text-sm text-slate-500">
            Defina um limite mensal por categoria de despesa e acompanhe o quanto já gastou.
            <span class="font-medium text-slate-600">É opcional</span> — use só nas categorias que quiser controlar.
        </p>

        <div class="grid gap-4 lg:grid-cols-3">
            <!-- Lista de orçamentos -->
            <div class="lg:col-span-2">
                <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                    <div class="flex items-center justify-between">
                        <h2 class="text-sm font-semibold text-slate-700">Orçamentos definidos</h2>
                        <span class="text-xs text-slate-400">{{ monthLabel }}</span>
                    </div>

                    <div v-if="budgets.length" class="mt-4 space-y-5">
                        <div v-for="b in budgets" :key="b.id">
                            <div class="flex items-center justify-between text-sm">
                                <span class="flex items-center gap-2 font-medium text-slate-700">
                                    <span class="flex h-7 w-7 items-center justify-center rounded-full text-sm" :style="{ backgroundColor: b.category.color + '22' }">{{ b.category.icon }}</span>
                                    {{ b.category.name }}
                                </span>
                                <span class="flex items-center gap-3">
                                    <span class="text-slate-500">{{ formatBRL(b.spent) }} / {{ formatBRL(b.limit) }}</span>
                                    <button @click="remove(b)" class="text-xs text-slate-300 hover:text-rose-600">✕</button>
                                </span>
                            </div>
                            <div class="mt-1.5 h-2.5 overflow-hidden rounded-full bg-slate-100">
                                <div class="h-full rounded-full transition-all"
                                     :class="b.percent >= 100 ? 'bg-rose-500' : b.percent >= 80 ? 'bg-amber-500' : 'bg-violet-500'"
                                     :style="{ width: b.percent + '%' }" />
                            </div>
                            <p class="mt-1 text-xs" :class="b.percent >= 100 ? 'text-rose-600' : 'text-slate-400'">
                                {{ b.percent }}% utilizado
                                <span v-if="b.percent >= 100"> — limite ultrapassado</span>
                            </p>
                        </div>
                    </div>
                    <p v-else class="mt-6 text-center text-sm text-slate-400">Nenhum orçamento definido ainda.</p>
                </div>
            </div>

            <!-- Form novo orçamento -->
            <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                <h2 class="text-sm font-semibold text-slate-700">Novo orçamento</h2>
                <form @submit.prevent="submit" class="mt-4 space-y-4">
                    <div>
                        <InputLabel value="Categoria" />
                        <select v-model="form.category_id" class="mt-1 block w-full rounded-md border-slate-300 text-sm focus:border-violet-500 focus:ring-violet-500">
                            <option value="" disabled>Selecione…</option>
                            <option v-for="c in availableCategories" :key="c.id" :value="c.id">{{ c.icon }} {{ c.name }}</option>
                        </select>
                        <InputError :message="form.errors.category_id" class="mt-1" />
                        <p v-if="!availableCategories.length" class="mt-1 text-xs text-slate-400">Todas as categorias de despesa já têm orçamento.</p>
                    </div>
                    <div>
                        <InputLabel value="Limite mensal (R$)" />
                        <TextInput v-model="form.amount" type="number" step="0.01" min="0.01" class="mt-1 block w-full" />
                        <InputError :message="form.errors.amount" class="mt-1" />
                    </div>
                    <PrimaryButton :disabled="form.processing || !availableCategories.length" class="w-full justify-center !bg-violet-600 hover:!bg-violet-700">
                        Salvar orçamento
                    </PrimaryButton>
                </form>
            </div>
        </div>
    </AppLayout>
</template>
