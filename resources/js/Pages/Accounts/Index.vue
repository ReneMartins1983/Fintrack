<script setup>
import { ref } from 'vue';
import { Head, useForm, router } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import Modal from '@/Components/Modal.vue';
import InputLabel from '@/Components/InputLabel.vue';
import TextInput from '@/Components/TextInput.vue';
import InputError from '@/Components/InputError.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import { formatBRL } from '@/utils/format';

const props = defineProps({ accounts: Array, types: Object });

const showModal = ref(false);
const editing = ref(false);
const form = useForm({ id: null, name: '', type: 'checking', color: '#7c3aed', initial_balance: '0' });

function openCreate() {
    editing.value = false;
    form.reset();
    form.clearErrors();
    showModal.value = true;
}
function openEdit(a) {
    editing.value = true;
    form.clearErrors();
    Object.assign(form, { id: a.id, name: a.name, type: a.type, color: a.color, initial_balance: a.initial_balance });
    showModal.value = true;
}
function submit() {
    const options = { preserveScroll: true, onSuccess: () => (showModal.value = false) };
    editing.value
        ? form.put(route('accounts.update', form.id), options)
        : form.post(route('accounts.store'), options);
}
function remove(a) {
    if (confirm('Remover esta conta? Todos os lançamentos dela também serão removidos.')) {
        router.delete(route('accounts.destroy', a.id), { preserveScroll: true });
    }
}
</script>

<template>
    <Head title="Contas" />

    <AppLayout>
        <template #title>Contas</template>
        <template #actions>
            <button @click="openCreate" class="rounded-lg bg-violet-600 px-4 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-violet-700">
                + Nova conta
            </button>
        </template>

        <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-3">
            <div v-for="a in accounts" :key="a.id" class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
                <div class="h-2" :style="{ backgroundColor: a.color }" />
                <div class="p-5">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm font-semibold text-slate-700">{{ a.name }}</p>
                            <p class="text-xs text-slate-400">{{ a.type_label }}</p>
                        </div>
                        <span class="flex h-9 w-9 items-center justify-center rounded-full text-white" :style="{ backgroundColor: a.color }">
                            {{ a.name.charAt(0).toUpperCase() }}
                        </span>
                    </div>
                    <p class="mt-4 text-xs text-slate-400">Saldo atual</p>
                    <p class="text-2xl font-bold tracking-tight" :class="a.balance >= 0 ? 'text-slate-900' : 'text-rose-600'">{{ formatBRL(a.balance) }}</p>
                    <div class="mt-3 flex gap-3 border-t border-slate-100 pt-3">
                        <button @click="openEdit(a)" class="text-xs font-medium text-violet-600 hover:underline">Editar</button>
                        <button @click="remove(a)" class="text-xs font-medium text-slate-400 hover:text-rose-600">Excluir</button>
                    </div>
                </div>
            </div>

            <button @click="openCreate" class="flex min-h-[160px] items-center justify-center rounded-2xl border-2 border-dashed border-slate-200 text-sm font-medium text-slate-400 transition hover:border-violet-300 hover:text-violet-600">
                + Adicionar conta
            </button>
        </div>

        <Modal :show="showModal" @close="showModal = false">
            <form @submit.prevent="submit" class="p-6">
                <h2 class="text-lg font-semibold text-slate-800">{{ editing ? 'Editar conta' : 'Nova conta' }}</h2>

                <div class="mt-4">
                    <InputLabel value="Nome" />
                    <TextInput v-model="form.name" type="text" class="mt-1 block w-full" placeholder="Ex.: Conta corrente" />
                    <InputError :message="form.errors.name" class="mt-1" />
                </div>

                <div class="mt-4 grid gap-4 sm:grid-cols-2">
                    <div>
                        <InputLabel value="Tipo" />
                        <select v-model="form.type" class="mt-1 block w-full rounded-md border-slate-300 text-sm focus:border-violet-500 focus:ring-violet-500">
                            <option v-for="(label, key) in types" :key="key" :value="key">{{ label }}</option>
                        </select>
                    </div>
                    <div>
                        <InputLabel value="Saldo inicial (R$)" />
                        <TextInput v-model="form.initial_balance" type="number" step="0.01" class="mt-1 block w-full" />
                        <InputError :message="form.errors.initial_balance" class="mt-1" />
                    </div>
                </div>

                <div class="mt-4">
                    <InputLabel value="Cor" />
                    <input type="color" v-model="form.color" class="mt-1 h-10 w-16 cursor-pointer rounded-md border border-slate-300" />
                </div>

                <div class="mt-6 flex justify-end gap-2">
                    <SecondaryButton type="button" @click="showModal = false">Cancelar</SecondaryButton>
                    <PrimaryButton :disabled="form.processing" class="!bg-violet-600 hover:!bg-violet-700">{{ editing ? 'Salvar' : 'Adicionar' }}</PrimaryButton>
                </div>
            </form>
        </Modal>
    </AppLayout>
</template>
