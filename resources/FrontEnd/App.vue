<template>
    <LoginPage v-if="!userRole" @authenticated="handleAuthentication" />
    <component :is="activeRolePage" v-else @logout="handleLogout" />
</template>

<script setup>
import { computed, ref } from 'vue';
import AdminPage from './pages/admin/AdminPage.vue';
import LoginPage from './LoginPage.vue';
import MancomPage from './pages/mancom/MancomPage.vue';
import RdvCisPage from './pages/rdv-cis/RdvCisPage.vue';
import RdvDbrmPage from './pages/rdv-dbrm/RdvDbrmPage.vue';

const userRole = ref('');
const rolePages = {
    admin: AdminPage,
    mancom: MancomPage,
    'rdv-cis': RdvCisPage,
    'rdv-dbrm': RdvDbrmPage,
};
const activeRolePage = computed(() => rolePages[userRole.value] || RdvDbrmPage);

const handleAuthentication = (role) => {
    userRole.value = role;
};

const handleLogout = () => {
    userRole.value = '';
};
</script>
