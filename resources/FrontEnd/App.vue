<template>
    <LoginPage v-if="currentPage === 'login'" @authenticated="currentPage = 'dashboard'" />
    <Dashboard v-else />
</template>

<script setup>
import { ref } from 'vue';
import Dashboard from './Dashboard.vue';
import LoginPage from './LoginPage.vue';

const currentPage = ref('login');
</script>
