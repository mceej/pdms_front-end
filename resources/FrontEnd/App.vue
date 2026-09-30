<template>
    <template v-if="!isCheckingSession">
        <LoginPage v-if="!currentUser" @authenticated="handleAuthentication" />
        <component :is="activeRolePage" v-else @logout="handleLogout" />
    </template>
</template>

<script setup>
import { computed, onMounted, onUnmounted, ref } from 'vue';
import AdminPage from './pages/admin/AdminPage.vue';
import LoginPage from './LoginPage.vue';
import MancomPage from './pages/mancom/MancomPage.vue';
import RdvCisPage from './pages/rdv-cis/RdvCisPage.vue';
import RdvDbrmPage from './pages/rdv-dbrm/RdvDbrmPage.vue';
import { signOutUser, watchSession } from './auth/session.js';

const currentUser = ref(null);
const isCheckingSession = ref(true);
const rolePages = {
    admin: AdminPage,
    mancom: MancomPage,
    'rdv-cis': RdvCisPage,
    'rdv-dbrm': RdvDbrmPage,
};
const activeRolePage = computed(() => rolePages[currentUser.value?.page] || RdvDbrmPage);

let unwatchSession = () => {};

onMounted(() => {
    // Runs once on start-up, which is what keeps someone signed in across a refresh.
    unwatchSession = watchSession((user) => {
        currentUser.value = user;
        isCheckingSession.value = false;
    });
});

onUnmounted(() => unwatchSession());

const handleAuthentication = (user) => {
    currentUser.value = user;
};

const handleLogout = async () => {
    await signOutUser();
    currentUser.value = null;
};
</script>
