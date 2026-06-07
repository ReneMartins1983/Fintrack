<script setup>
import { ref, computed } from 'vue';
import { Head, useForm, router } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import Modal from '@/Components/Modal.vue';
import InputLabel from '@/Components/InputLabel.vue';
import TextInput from '@/Components/TextInput.vue';
import InputError from '@/Components/InputError.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';

const props = defineProps({ categories: Array });

const income = computed(() => props.categories.filter((c) => c.type === 'income'));
const expense = computed(() => props.categories.filter((c) => c.type === 'expense'));

const icons = ['💸', '🛒', '🏠', '🚗', '🍔', '🎮', '💊', '📺', '📚', '💰', '💻', '📈', '✈️', '🎁', '⚡', '📱'];

const showModal = ref(false);
const editing = ref(false);
const form = useForm({ id: null, name: '', type: 'expense', color: '#7c3aed', icon: '💸' });

function openCreate(type) {
    editing.value = false;
    form.reset();
    form.clearErrors();
    form.type = type;
    showModal.value = true;
}
function openEdit(c) {
    editing.value = true;
    form.clearErrors();
    Object.assign(form, { id: c.id, name: c.name, type: c.type, color: c.color, icon: c.icon });
    showModal.value = true;
}
function submit() {
    const options = { preserveScroll: true, onSuccess: () => (showModal.value = false) };
    editing.value
        ? form.put(route('categories.update', form.id), options)
        : form.post(route('categories.store'), options);
}
function remove(c) {
    if (confirm('Remover esta categoria? Os lançamentos ligados a ela ficarão sem categoria.')) {
        router.delete(route('categories.destroy', c.id), { preserveScroll: true });
    }
}
</script>

<template>
    <Head title="Categorias" />

    <AppLayout>
        <template #title>Categorias</template>

        <div class="grid gap-4 lg:grid-cols-2">
            <div v-for="group in [{ key: 'expense', label: 'Despesas', items: expense }, { key: 'income', label: 'Receitas', items: income }]" :key="group.key"
                 class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                <div class="flex items-center justify-between">
                    <h2 class="text-sm font-semibold text-slate-700">{{ group.label }}</h2>
                    <button @click="openCreate(group.key)" class="rounded-lg bg-violet-50 px-3 py-1.5 text-xs font-semibold text-violet-700 hover:bg-violet-100">
                        + Adicionar
                    </button>
                </div>
                <ul class="mt-4 space-y-2">
                    <li v-for="c in group.items" :key="c.id" class="flex items-center justify-between rounded-lg border border-slate-100 px-3 py-2">
                        <span class="flex items-center gap-2">
                            <span class="flex h-8 w-8 items-center justify-center rounded-full text-base" :style="{ backgroundColor: c.color + '22' }">{{ c.icon }}</span>
                            <span class="text-sm font-medium text-slate-700">{{ c.name }}</span>
                            <span class="text-xs text-slate-400">({{ c.transactions_count }})</span>
                        </span>
                        <span>
                            <button @click="openEdit(c)" class="text-xs font-medium text-violet-600 hover:underline">Editar</button>
                            <button @click="remove(c)" class="ml-3 text-xs font-medium text-slate-400 hover:text-rose-600">Excluir</button>
                        </span>
                    </li>
                    <li v-if="!group.items.length" class="rounded-lg border border-dashed border-slate-200 py-6 text-center text-sm text-slate-400">
                        Nenhuma categoria ainda.
                    </li>
                </ul>
            </div>
        </div>

        <Modal :show="showModal" @close="showModal = false">
            <form @submit.prevent="submit" class="p-6">
                <h2 class="text-lg font-semibold text-slate-800">{{ editing ? 'Editar categoria' : 'Nova categoria' }}</h2>

                <div class="mt-4 flex gap-2">
                    <button type="button" @click="form.type = 'expense'"
                            class="flex-1 rounded-lg border px-3 py-2 text-sm font-medium" :class="form.type === 'expense' ? 'border-rose-500 bg-rose-50 text-rose-700' : 'border-slate-200 text-slate-500'">Despesa</button>
                    <button type="button" @click="form.type = 'income'"
                            class="flex-1 rounded-lg border px-3 py-2 text-sm font-medium" :class="form.type === 'income' ? 'border-emerald-500 bg-emerald-50 text-emerald-700' : 'border-slate-200 text-slate-500'">Receita</button>
                </div>

                <div class="mt-4">
                    <InputLabel value="Nome" />
                    <TextInput v-model="form.name" type="text" class="mt-1 block w-full" placeholder="Ex.: Mercado" />
                    <InputError :message="form.errors.name" class="mt-1" />
                </div>

                <div class="mt-4 flex items-center gap-4">
                    <div>
                        <InputLabel value="Cor" />
                        <input type="color" v-model="form.color" class="mt-1 h-10 w-16 cursor-pointer rounded-md border border-slate-300" />
                    </div>
                    <div class="flex-1">
                        <InputLabel value="Ícone" />
                        <div class="mt-1 flex flex-wrap gap-1">
                            <button v-for="ic in icons" :key="ic" type="button" @click="form.icon = ic"
                                    class="flex h-8 w-8 items-center justify-center rounded-md text-base"
                                    :class="form.icon === ic ? 'bg-violet-100 ring-2 ring-violet-500' : 'bg-slate-100 hover:bg-slate-200'">
                                {{ ic }}
                            </button>
                        </div>
                    </div>
                </div>

                <div class="mt-6 flex justify-end gap-2">
                    <SecondaryButton type="button" @click="showModal = false">Cancelar</SecondaryButton>
                    <PrimaryButton :disabled="form.processing" class="!bg-violet-600 hover:!bg-violet-700">{{ editing ? 'Salvar' : 'Adicionar' }}</PrimaryButton>
                </div>
            </form>
        </Modal>
    </AppLayout>
</template>
