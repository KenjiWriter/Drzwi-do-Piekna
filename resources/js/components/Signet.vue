<script setup>
import { SIGNET_VIEW_BOX, signetPaths } from '../signet';
</script>

<template>
    <svg :viewBox="SIGNET_VIEW_BOX" aria-hidden="true" focusable="false">
        <path v-for="d in signetPaths" :key="d" :d="d" />
    </svg>
</template>
