<script setup>
// Ikony liniowe w rodzinie sygnetu: kreska 1 px, siatka 40, bez wypełnień.
defineProps({
    name: { type: String, required: true }, // lotus | user | gem
});
</script>

<template>
    <svg class="ddp-icon" viewBox="0 0 40 40" aria-hidden="true" focusable="false">
        <template v-if="name === 'lotus'">
            <path d="M20 6C15.5 12 15.5 20.5 20 28C24.5 20.5 24.5 12 20 6Z" />
            <path d="M20 28C13 27 8 22.5 7 15C13.5 15.5 18 19.5 20 28Z" />
            <path d="M20 28C27 27 32 22.5 33 15C26.5 15.5 22 19.5 20 28Z" />
            <path d="M17 29.5C11 31.5 5 30 2 25C7 24 12.5 25.5 16 28" />
            <path d="M23 29.5C29 31.5 35 30 38 25C33 24 27.5 25.5 24 28" />
        </template>
        <template v-else-if="name === 'user'">
            <circle cx="20" cy="20" r="17" />
            <circle cx="20" cy="15.5" r="5" />
            <path d="M9.5 31.5C11.5 26 15.5 23.5 20 23.5C24.5 23.5 28.5 26 30.5 31.5" />
        </template>
        <template v-else-if="name === 'gem'">
            <path d="M11 8H29L36 16L20 34L4 16Z" />
            <path d="M4 16H36M11 8L15 16L20 34L25 16L29 8M15 16L20 8L25 16" />
        </template>
    </svg>
</template>
