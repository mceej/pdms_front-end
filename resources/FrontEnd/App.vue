<template>
    <LoginPage v-if="currentPage === 'login'" @authenticated="currentPage = 'dashboard'" />
    <Dashboard v-else @logout="currentPage = 'login'" />
</template>

<script setup>
import { ref } from 'vue';
import Dashboard from './Dashboard.vue';
import LoginPage from './LoginPage.vue';

const currentPage = ref('login');
</script>
