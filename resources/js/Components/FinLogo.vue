<script setup>
defineProps({
    // permite controlar o tamanho/estilo via class externa
});
</script>

<template>
    <svg viewBox="0 0 40 40" fill="none" xmlns="http://www.w3.org/2000/svg">
        <defs>
            <linearGradient id="finlogo-bg" x1="0" y1="0" x2="40" y2="40" gradientUnits="userSpaceOnUse">
                <stop stop-color="#8b5cf6" />
                <stop offset="1" stop-color="#6d28d9" />
            </linearGradient>
        </defs>

        <!-- base arredondada -->
        <rect width="40" height="40" rx="11" fill="url(#finlogo-bg)" />

        <!-- área sob a linha -->
        <path d="M9 27 L17 19 L23 23 L31 12 L31 30 L9 30 Z"
              fill="#ffffff" fill-opacity="0.16" />

        <!-- linha ascendente -->
        <path d="M9 27 L17 19 L23 23 L31 12"
              stroke="#ffffff" stroke-width="2.6"
              stroke-linecap="round" stroke-linejoin="round" />

        <!-- moeda no topo da linha -->
        <circle cx="31" cy="12" r="3.4" fill="#fbbf24" stroke="#ffffff" stroke-width="1.4" />
    </svg>
</template>
