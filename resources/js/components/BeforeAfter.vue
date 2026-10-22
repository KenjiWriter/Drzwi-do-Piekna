<script setup>
import Picture from './Picture.vue';

// Para zdjęć przed i po (ten sam kadr 3:4). `name` to klucz po, `${name}-before` to przed.
defineProps({
    name: { type: String, required: true },
    before: { type: String, default: 'Przed' },
    after: { type: String, default: 'Po' },
    alt: { type: String, required: true },
});
</script>

<template>
    <div class="ddp-ba">
        <figure>
            <div class="ddp-ba__img">
                <Picture :name="`${name}-before`" :alt="`${alt}, przed`" sizes="(min-width: 1024px) 14vw, 40vw" />
            </div>
            <figcaption>{{ before }}</figcaption>
        </figure>
        <figure>
            <div class="ddp-ba__img">
                <Picture :name="name" :alt="`${alt}, po`" sizes="(min-width: 1024px) 14vw, 40vw" />
            </div>
            <figcaption>{{ after }}</figcaption>
        </figure>
    </div>
</template>
