<template>
    <div :class="['rdv-cis-layout', { 'rdv-cis-layout--target': activePage === 'target-management' }]">
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
                    payout-type="ECT"
                    :served-list-form="servedListForm"
                    :served-province-options="provinceOptions"
                    :served-municipality-options="municipalityOptions"
                    :served-barangay-options="barangayOptions"
                    :served-list-rows="importedLists"
                    :is-uploading="isUploading"
                    :served-list-message="importMessage"
                    :served-list-error="importFailed"
                    @select-served-list-file="onFileChosen"
                    @delete-served-list="askToDeleteImport"
                    @upload-served-list="uploadServedList"
                />
                <AppFooter variant="served-list" />
            </div>
        </main>

        <div v-if="importToDelete" class="dialog-backdrop" @click.self="importToDelete = null">
            <section class="delete-import-dialog" role="alertdialog" aria-modal="true">
                <h2>Delete this import?</h2>
                <p>
                    <strong>{{ importToDelete.file_name }}</strong> and the
                    {{ importToDelete.rows_imported }} payout records it brought in will be removed.
                    The dashboard figures will change.
                </p>
                <div class="dialog-actions">
                    <button type="button" class="cancel-button" @click="importToDelete = null">Cancel</button>
                    <button type="button" class="delete-button" @click="confirmDeleteImport">Delete import</button>
                </div>
            </section>
        </div>

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
import AppFooter from '../../components/AppFooter.vue';
import LogoutConfirmDialog from '../../components/LogoutConfirmDialog.vue';
import DashboardPage from '../../dashboard/DashboardPage.vue';
import ServerListPage from '../../components/ServerListPage.vue';
import { loadGeographies, placeNames } from '../../data/geographies.js';
import { deleteServedList, importServedList, subscribeServedLists } from '../../data/servedLists.js';
import RdvTargetPage from './CisTargetPage.vue';

const emit = defineEmits(['logout']);
const sidebarItems = [
    { key: 'dashboard', label: 'Dashboard', icon: 'pi pi-home' },
    { key: 'target-management', label: 'Target Management', icon: 'pi pi-chart-bar' },
    { key: 'server-list', label: 'Import Served List', icon: 'pi pi-file' },
];
const activePage = ref('dashboard');
const logoutDialogOpen = ref(false);
const servedListForm = ref({
    disasterName: '',
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

const importToDelete = ref(null);

const askToDeleteImport = (row) => {
    importToDelete.value = row;
    importMessage.value = '';
    importFailed.value = false;
};

const confirmDeleteImport = async () => {
    const row = importToDelete.value;
    importToDelete.value = null;
    isUploading.value = true;

    const result = await deleteServedList(row.id);

    isUploading.value = false;
    importFailed.value = !result.ok;
    importMessage.value = result.ok
        ? `Deleted "${row.file_name}" and the ${result.recordsDeleted} records it brought in.`
        : result.message;
};

const currentTime = ref(new Date().toLocaleString());
let clockTimer;

onMounted(async () => {
    places.value = await loadGeographies();
    unsubscribeServedLists = subscribeServedLists((lists) => {
        importedLists.value = lists.map((list) => ({
            id: list.id,
            file_name: list.fileName,
            imported_at: new Date(list.importedAt).toLocaleString(),
            imported_by: list.importedByName,
            rows_imported: list.rowsImported ?? 0,
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

.rdv-cis-layout--target {
    height: 100vh;
    overflow: hidden;
}

.rdv-cis-layout--target .rdv-cis-content {
    overflow: hidden;
}

.served-list-shell {
    display: flex;
    flex-direction: column;
    min-height: 100vh;
    padding: 18px 30px 36px;
}

@media (max-width: 768px) {
    .served-list-shell {
        padding: 12px 16px 24px;
    }
}

.dialog-backdrop {
    position: fixed;
    inset: 0;
    z-index: 50;
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 20px;
    background: rgba(12, 20, 38, 0.45);
}

.delete-import-dialog {
    width: min(100%, 420px);
    display: flex;
    flex-direction: column;
    gap: 12px;
    padding: 24px;
    border-radius: 12px;
    background: #fff;
    box-shadow: 0 20px 48px rgba(12, 20, 38, 0.25);
}

.delete-import-dialog h2 {
    margin: 0;
    font-size: 18px;
    color: #11203a;
}

.delete-import-dialog p {
    margin: 0;
    font-size: 14px;
    color: #45597a;
}

.dialog-actions {
    display: flex;
    justify-content: flex-end;
    gap: 8px;
}

.dialog-actions button {
    min-height: 36px;
    padding: 0 14px;
    border-radius: 6px;
    border: 1px solid #d4dcea;
    background: #fff;
    color: #33455f;
    cursor: pointer;
    font: inherit;
}

.dialog-actions .delete-button {
    border-color: #b3261e;
    background: #b3261e;
    color: #fff;
}
</style>
