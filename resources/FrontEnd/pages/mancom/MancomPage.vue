<template>
    <div class="mancom-layout">
        <header class="mancom-topbar">
            <div class="mancom-brand">
                <img src="/logo/dswdsidebarlogo.png" alt="DSWD logo" />
                <span>DSWD Assist Track</span>
            </div>
            <button type="button" class="mancom-logout" @click="logoutDialogOpen = true">
                <i class="pi pi-sign-out" aria-hidden="true"></i>
                Log out
            </button>
        </header>

        <DashboardPage />

        <LogoutConfirmDialog
            :open="logoutDialogOpen"
            @cancel="logoutDialogOpen = false"
            @confirm="emit('logout')"
        />
    </div>
</template>

<script setup>
import { ref } from 'vue';
import LogoutConfirmDialog from '../../components/LogoutConfirmDialog.vue';
import DashboardPage from '../../dashboard/DashboardPage.vue';

const emit = defineEmits(['logout']);
const logoutDialogOpen = ref(false);
</script>

<style scoped>
.mancom-layout {
    display: flex;
    flex-direction: column;
    min-height: 100vh;
}

.mancom-topbar {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 7px 24px;
    background: #2E3192;
    color: #fff;
}

.mancom-brand {
    display: flex;
    align-items: center;
    gap: 12px;
    min-width: 0;
    font-size: 15px;
    font-weight: 700;
}

.mancom-brand img {
    display: block;
    width: 42px;
    height: 42px;
    flex: 0 0 42px;
    object-fit: contain;
}

.mancom-logout {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    width: auto;
    padding: 8px 12px;
    border: 1px solid rgb(255 255 255 / 60%);
    border-radius: 4px;
    background: transparent;
    color: #fff;
    cursor: pointer;
    font-size: 13px;
}

.mancom-logout:hover {
    background: rgb(255 255 255 / 14%);
}

@media (max-width: 480px) {
    .mancom-topbar { padding-right: 16px; padding-left: 16px; }
    .mancom-brand { gap: 8px; font-size: 13px; }
    .mancom-brand img { width: 36px; height: 36px; flex-basis: 36px; }
    .mancom-logout { padding: 8px; }
}
</style>
