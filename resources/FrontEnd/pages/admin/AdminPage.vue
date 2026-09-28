<template>
    <div class="admin-layout">
        <AppSidebar
            :items="adminNavigation"
            :active-item="activeSection"
            @select="activeSection = $event"
            @logout="logoutDialogOpen = true"
        />

        <main class="admin-content">
            <DashboardPage v-if="activeSection === 'dashboard'" />
            <AuditLogPage v-else-if="activeSection === 'audit-log'" />
            <UserManagementPage v-else />
        </main>

        <LogoutConfirmDialog
            :open="logoutDialogOpen"
            @cancel="logoutDialogOpen = false"
            @confirm="emit('logout')"
        />
    </div>
</template>

<script setup>
import { ref } from 'vue';
import AppSidebar from '../../components/AppSidebar.vue';
import AuditLogPage from './AuditLogPage.vue';
import LogoutConfirmDialog from '../../components/LogoutConfirmDialog.vue';
import DashboardPage from '../../dashboard/DashboardPage.vue';
import UserManagementPage from './UserManagementPage.vue';

const emit = defineEmits(['logout']);
const adminNavigation = [
    { key: 'dashboard', label: 'Dashboard', icon: 'pi pi-home' },
    { key: 'audit-log', label: 'Audit Log', icon: 'pi pi-file' },
    { key: 'user-management', label: 'User Management', icon: 'pi pi-users' },
];
const activeSection = ref('dashboard');
const logoutDialogOpen = ref(false);
</script>

<style scoped>
.admin-layout {
    display: flex;
    min-height: 100vh;
    background: #f4f7fb;
}

.admin-content {
    flex: 1 1 auto;
    min-width: 0;
}
</style>