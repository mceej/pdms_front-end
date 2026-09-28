<template>
    <div class="rdv-dbrm-layout">
        <AppSidebar
            :items="sidebarItems"
            :active-item="activePage"
            @select="activePage = $event"
            @logout="logoutDialogOpen = true"
        />

        <main class="rdv-dbrm-content">
            <DashboardPage v-if="activePage === 'dashboard'" />
            <RdvTargetPage
                v-else-if="activePage === 'target-management'"
                page-title="RDV Focal DBRM"
                variant="drmd"
            />
            <div v-else class="served-list-shell">
                <ServerListPage
                    :current-time="currentTime"
                    :tabs="programOptions"
                    :served-list-form="servedListForm"
                />
            </div>
        </main>

        <LogoutConfirmDialog
            :open="logoutDialogOpen"
            @cancel="logoutDialogOpen = false"
            @confirm="emit('logout')"
        />
    </div>
</template>

<script setup>
import { onMounted, onUnmounted, ref } from 'vue';
import AppSidebar from '../../components/AppSidebar.vue';
import LogoutConfirmDialog from '../../components/LogoutConfirmDialog.vue';
import DashboardPage from '../../dashboard/DashboardPage.vue';
import ServerListPage from '../../components/ServerListPage.vue';
import RdvTargetPage from './DbrmTargetPage.vue';

const emit = defineEmits(['logout']);
const sidebarItems = [
    { key: 'dashboard', label: 'Dashboard', icon: 'pi pi-home' },
    { key: 'target-management', label: 'Target Management', icon: 'pi pi-chart-bar' },
    { key: 'server-list', label: 'Import Served List', icon: 'pi pi-file' },
];
const activePage = ref('dashboard');
const logoutDialogOpen = ref(false);
const programOptions = ['AICS', 'ECT'];
const servedListForm = ref({
    program: '',
    province: '',
    municipality: '',
    barangay: '',
    file: null,
});
const currentTime = ref(new Date().toLocaleString());
let clockTimer;

onMounted(() => {
    clockTimer = window.setInterval(() => {
        currentTime.value = new Date().toLocaleString();
    }, 1000);
});

onUnmounted(() => window.clearInterval(clockTimer));
</script>

<style scoped>
.rdv-dbrm-layout {
    display: flex;
    min-height: 100vh;
    background: #f4f7fb;
}

.rdv-dbrm-content {
    flex: 1 1 auto;
    min-width: 0;
}

.served-list-shell {
    min-height: 100vh;
    padding: 18px 30px 36px;
}

@media (max-width: 768px) {
    .served-list-shell {
        padding: 12px 16px 24px;
    }
}
</style>
