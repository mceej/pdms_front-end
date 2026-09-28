<template>
    <div class="mancom-layout">
        <DashboardPage>
            <template #header-actions>
                <button type="button" class="mancom-logout" @click="logoutDialogOpen = true">
                    <i class="pi pi-sign-out" aria-hidden="true"></i>
                    Log out
                </button>
            </template>
        </DashboardPage>

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
    min-height: 100vh;
}

.mancom-layout :deep(.header-logout),
.mancom-logout {
    position: absolute;
    top: -12px;
    right: 0;
    display: inline-flex;
    align-items: center;
    gap: 8px;
    width: auto;
    padding: 8px 12px;
    border: 1px solid rgb(255 255 255 / 55%);
    border-radius: 4px;
    background: transparent;
    color: #fff;
    cursor: pointer;
    font-size: 13px;
}

.mancom-logout:hover {
    background: rgb(255 255 255 / 14%);
}
</style>