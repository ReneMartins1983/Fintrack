<script setup>
import { Doughnut } from 'vue-chartjs';
import { Chart as ChartJS, Tooltip, Legend, ArcElement } from 'chart.js';

ChartJS.register(Tooltip, Legend, ArcElement);

defineProps({
    data: { type: Object, required: true },
    options: { type: Object, default: () => ({}) },
});
</script>

<template>
    <Doughnut :data="data" :options="options" />
</template>
