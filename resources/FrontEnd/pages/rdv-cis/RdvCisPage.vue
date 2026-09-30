<template>
    <div class="rdv-cis-layout">
        <AppSidebar
            :items="sidebarItems"
            :active-item="activePage"
            @select="activePage = $event"
            @logout="logoutDialogOpen = true"
        />

        <main class="rdv-cis-content">
            <DashboardPage
                v-if="activePage === 'dashboard'"
            />
            <RdvTargetPage
                v-else-if="activePage === 'target-management'"
                page-title="RDV Focal CIS"
                variant="cis"
            />
            <div v-else class="served-list-shell">
                <ServerListPage
                    :current-time="currentTime"
                    :tabs="programOptions"
                    :served-list-form="servedListForm"
                    :served-province-options="provinceOptions"
                    :served-municipality-options="municipalityOptions"
                    :served-barangay-options="barangayOptions"
                    :served-list-rows="importedLists"
                    :is-uploading="isUploading"
                    :served-list-message="importMessage"
                    :served-list-error="importFailed"
                    @select-served-list-file="onFileChosen"
                    @upload-served-list="uploadServedList"
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
import { computed, onMounted, onUnmounted, ref } from 'vue';
import AppSidebar from '../../components/AppSidebar.vue';
import LogoutConfirmDialog from '../../components/LogoutConfirmDialog.vue';
import DashboardPage from '../../dashboard/DashboardPage.vue';
import ServerListPage from '../../components/ServerListPage.vue';
import { loadGeographies, placeNames } from '../../data/geographies.js';
import { importServedList, subscribeServedLists } from '../../data/servedLists.js';
import RdvTargetPage from './CisTargetPage.vue';

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
const places = ref([]);
const importedLists = ref([]);
const isUploading = ref(false);
const importMessage = ref('');
const importFailed = ref(false);
let unsubscribeServedLists = () => {};

const provinceOptions = computed(() => placeNames(places.value, 'province'));
const municipalityOptions = computed(
    () => placeNames(places.value, 'municipality', servedListForm.value.province || null)
);
const barangayOptions = computed(
    () => placeNames(places.value, 'barangay', servedListForm.value.municipality || null)
);

const onFileChosen = (event) => {
    servedListForm.value.file = event?.target?.files?.[0] || null;
    importMessage.value = '';
    importFailed.value = false;
};

const uploadServedList = async () => {
    if (!servedListForm.value.file) {
        importFailed.value = true;
        importMessage.value = 'Choose a CSV file first.';
        return;
    }

    isUploading.value = true;
    importMessage.value = '';
    importFailed.value = false;

    const result = await importServedList({
        file: servedListForm.value.file,
        province: servedListForm.value.province,
        municipality: servedListForm.value.municipality,
        barangay: servedListForm.value.barangay,
        disasterType: servedListForm.value.disasterName || '',
        program: servedListForm.value.program || 'AICS',
    });

    isUploading.value = false;

    if (!result.ok) {
        importFailed.value = true;
        importMessage.value = result.message;
        return;
    }

    const skipped = result.skipped?.length ? ` (${result.skipped.length} skipped)` : '';
    importMessage.value = `Imported ${result.rowsImported} of ${result.rowsRead} rows into ${result.where}${skipped}.`;
    servedListForm.value.file = null;
};

const currentTime = ref(new Date().toLocaleString());
let clockTimer;

onMounted(async () => {
    places.value = await loadGeographies();
    unsubscribeServedLists = subscribeServedLists((lists) => {
        importedLists.value = lists.map((list) => ({
            file_name: list.fileName,
            imported_at: new Date(list.importedAt).toLocaleString(),
            imported_by: list.importedByName,
        }));
    });
});

onUnmounted(() => unsubscribeServedLists());

onMounted(() => {
    clockTimer = window.setInterval(() => {
        currentTime.value = new Date().toLocaleString();
    }, 1000);
});

onUnmounted(() => window.clearInterval(clockTimer));
</script>

<style scoped>
.rdv-cis-layout {
    display: flex;
    min-height: 100vh;
    background: #f4f7fb;
}

.rdv-cis-content {
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
