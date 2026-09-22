<template>
    <template v-if="!isCheckingSession">
        <LoginPage v-if="currentPage === 'login'" @authenticated="currentPage = 'dashboard'" />
        <Dashboard v-else @logout="logout" />
    </template>
</template>

<script setup>
import { onMounted, ref } from 'vue';
import Dashboard from './Dashboard.vue';
import LoginPage from './LoginPage.vue';
import { isSignedIn, signOut } from './auth/adminAuth.js';

const currentPage = ref('login');
const isCheckingSession = ref(true);

onMounted(async () => {
    if (await isSignedIn()) {
        currentPage.value = 'dashboard';
    }

    isCheckingSession.value = false;
});

const logout = async () => {
    await signOut();
    currentPage.value = 'login';
};
</script>
