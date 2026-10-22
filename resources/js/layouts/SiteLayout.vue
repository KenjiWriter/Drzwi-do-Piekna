<script setup>
import ActionBar from '../components/ActionBar.vue';
import Intro from '../components/Intro.vue';
import SiteFooter from '../components/SiteFooter.vue';
import SiteHeader from '../components/SiteHeader.vue';
</script>

<template>
    <div class="site site--with-bar">
        <a class="skip-link" href="#main">Przejdź do treści</a>
        <Intro />
        <SiteHeader />
        <main id="main" class="site__main">
            <slot />
        </main>
        <SiteFooter />
        <ActionBar />
    </div>
</template>
