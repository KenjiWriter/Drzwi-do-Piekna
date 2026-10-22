<script setup>
import { Link } from '@inertiajs/vue3';

// Okruszki: "Strona główna" jest dodawana automatycznie, ostatnia pozycja to bieżąca strona.
defineProps({
    items: { type: Array, required: true }, // [{ name, url }]
});
</script>

<template>
    <nav class="ddp-crumbs" aria-label="Okruszki">
        <ol>
            <li><Link href="/">Strona główna</Link></li>
            <li v-for="(item, i) in items" :key="item.url">
                <span v-if="i === items.length - 1" aria-current="page">{{ item.name }}</span>
                <Link v-else :href="item.url">{{ item.name }}</Link>
            </li>
        </ol>
    </nav>
</template>
