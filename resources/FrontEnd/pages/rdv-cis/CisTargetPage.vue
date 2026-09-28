<template>
    <section class="target-workspace">
        <header class="target-header">
            <div class="target-heading">
                <h1>{{ props.pageTitle }}</h1>
                <button type="button" class="add-target-button" @click="openAddTarget">
                    <i class="pi pi-plus" aria-hidden="true"></i>
                    Add New Target
                </button>
            </div>

            <div class="target-tools">
                <label class="target-search">
                    <i class="pi pi-search" aria-hidden="true"></i>
                    <input v-model="search" type="search" placeholder="Search" aria-label="Search targets" />
                </label>
                <div ref="filterRoot" class="target-filter">
                    <button
                        type="button"
                        class="filter-trigger"
                        :aria-expanded="filterOpen"
                        @click="filterOpen = !filterOpen"
                    >
                        <i class="pi pi-filter" aria-hidden="true"></i>
                        Filter
                        <i class="pi pi-chevron-down" aria-hidden="true"></i>
                    </button>
                    <div v-if="filterOpen" class="filter-panel">
                        <strong>{{ filterLabel }}</strong>
                        <label v-for="option in filterOptions" :key="option">
                            <input v-model="draftPrograms" type="checkbox" :value="option" />
                            {{ option }}
                        </label>
                        <div class="filter-actions">
                            <button type="button" class="clear-filter" @click="clearFilters">Clear All</button>
                            <button type="button" class="apply-filter" @click="applyFilters">Apply</button>
                        </div>
                    </div>
                </div>
            </div>
        </header>

        <div
            class="target-table-scroll"
            role="region"
            aria-label="Target management table. Scroll horizontally to see payout site, dates, and actions."
            tabindex="0"
        >
            <table :class="['target-table', { 'drmd-table': isDrmd }]">
                <thead>
                    <tr>
                        <th>Payout Type</th>
                        <th v-if="isDrmd">Disaster Name</th>
                        <template v-else>
                            <th>Program Type</th>
                            <th>Type of Assistance</th>
                        </template>
                        <th>Target Beneficiary</th>
                        <th>Target Disbursement</th>
                        <th>Payout Site</th>
                        <th>Date Start</th>
                        <th>Date End</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-for="target in visibleTargets" :key="target.id">
                        <td>{{ target.payoutType }}</td>
                        <td v-if="isDrmd">{{ target.disasterName }}</td>
                        <template v-else>
                            <td>{{ target.programType }}</td>
                            <td>{{ target.assistanceType }}</td>
                        </template>
                        <td>{{ formatAmount(target.targetBeneficiary) }}</td>
                        <td>{{ formatAmount(target.targetDisbursement) }}</td>
                        <td>{{ target.payoutSite }}</td>
                        <td>{{ formatDate(target.dateStart) }}</td>
                        <td>{{ formatDate(target.dateEnd) }}</td>
                        <td>
                            <button
                                type="button"
                                class="edit-target-button"
                                :aria-label="`Edit target ${isDrmd ? target.disasterName : target.programType}`"
                                title="Edit target"
                                @click="openEditTarget(target)"
                            >
                                <i class="pi pi-pencil" aria-hidden="true"></i>
                            </button>
                        </td>
                    </tr>
                    <tr v-if="visibleTargets.length === 0">
                        <td class="empty-targets" :colspan="isDrmd ? 8 : 9">No targets found.</td>
                    </tr>
                </tbody>
            </table>
        </div>

        <div class="target-pagination">
            <div class="page-controls" aria-label="Target management pagination">
                <button type="button" aria-label="First page" :disabled="page === 1" @click="page = 1">«</button>
                <button type="button" aria-label="Previous page" :disabled="page === 1" @click="page--">‹</button>
                <button
                    v-for="pageNumber in pageCount"
                    :key="pageNumber"
                    type="button"
                    :class="{ current: pageNumber === page }"
                    :aria-current="pageNumber === page ? 'page' : undefined"
                    @click="page = pageNumber"
                >{{ pageNumber }}</button>
                <button type="button" aria-label="Next page" :disabled="page === pageCount" @click="page++">›</button>
                <button type="button" aria-label="Last page" :disabled="page === pageCount" @click="page = pageCount">»</button>
            </div>
            <select v-model.number="pageSize" aria-label="Targets per page">
                <option :value="10">10</option>
                <option :value="25">25</option>
                <option :value="50">50</option>
            </select>
        </div>

        <div v-if="showTargetDialog" class="dialog-backdrop" @click.self="closeTargetDialog">
            <form class="target-dialog" @submit.prevent="saveTarget">
                <h2>{{ editingTargetId === null ? 'Add New Target' : 'Edit Target' }}</h2>
                <label>
                    Payout Type
                    <select v-model="targetDraft.payoutType">
                        <option>AICS</option>
                        <option>ECT</option>
                    </select>
                </label>
                <label v-if="!isDrmd">
                    Program Type
                    <select v-model="targetDraft.programType">
                        <option v-for="program in programTypes" :key="program">{{ program }}</option>
                    </select>
                </label>
                <label v-else>
                    Disaster Name
                    <select v-model="targetDraft.disasterName">
                        <option v-for="disaster in disasterNames" :key="disaster">
                            {{ disaster }}
                        </option>
                    </select>
                </label>
                <label v-if="!isDrmd">
                    Type of Assistance
                    <input v-model="targetDraft.assistanceType" required />
                </label>
                <label>
                    Target Beneficiary
                    <input v-model.number="targetDraft.targetBeneficiary" type="number" min="0" required />
                </label>
                <label>
                    Target Disbursement
                    <input v-model.number="targetDraft.targetDisbursement" type="number" min="0" required />
                </label>
                <label>
                    Payout Site
                    <input v-model="targetDraft.payoutSite" required />
                </label>
                <label>
                    Date Start
                    <input v-model="targetDraft.dateStart" type="date" required />
                </label>
                <label>
                    Date End
                    <input v-model="targetDraft.dateEnd" type="date" required />
                </label>
                <div class="dialog-actions">
                    <button type="button" class="cancel-button" @click="closeTargetDialog">Cancel</button>
                    <button type="submit" class="save-target-button">
                        {{ editingTargetId === null ? 'Add Target' : 'Save Changes' }}
                    </button>
                </div>
            </form>
        </div>
    </section>
</template>

<script setup>
import { computed, onMounted, onUnmounted, ref, watch } from 'vue';

const props = defineProps({
    pageTitle: { type: String, required: true },
    variant: { type: String, default: 'cis' },
});

const programTypes = ['AICS', 'CRA', 'Uplift', 'Akap'];
const disasterNames = [
    'Earthquake',
    'Flood',
    'Typhoon',
    'Landslide',
    'Storm Surge',
    'Volcanic Eruption',
    'Drought',
    'Fire',
    'Heavy Rain',
    'Tropical Storm',
];
const isDrmd = computed(() => props.variant === 'drmd');
const filterLabel = computed(() => (isDrmd.value ? 'Disaster Name' : 'Program Type'));
const filterOptions = computed(() => (isDrmd.value ? disasterNames : programTypes));
const search = ref('');
const page = ref(1);
const pageSize = ref(10);
const filterOpen = ref(false);
const filterRoot = ref(null);
const draftPrograms = ref([]);
const selectedPrograms = ref([]);
const showTargetDialog = ref(false);
const editingTargetId = ref(null);
const targetDraft = ref(createEmptyTarget());
const targets = ref(
    Array.from({ length: 10 }, (_, index) => ({
        id: index + 1,
        payoutType: isDrmd.value ? 'ECT' : 'AICS',
        ...(isDrmd.value
            ? { disasterName: disasterNames[index % disasterNames.length] }
            : {
                programType: programTypes[index % programTypes.length],
                assistanceType: 'Cash Assistance',
            }),
        targetBeneficiary: 1000000 + index * 250000,
        targetDisbursement: 1000000 + index * 250000,
        payoutSite: ['Barangay A', 'Barangay B', 'Barangay C', 'Barangay D', 'Barangay E'][index % 5],
        dateStart: '2026-09-30',
        dateEnd: '2026-10-05',
    })),
);

function createEmptyTarget() {
    const target = {
        payoutType: isDrmd.value ? 'ECT' : 'AICS',
        targetBeneficiary: 0,
        targetDisbursement: 0,
        payoutSite: '',
        dateStart: '',
        dateEnd: '',
    };

    if (isDrmd.value) {
        target.disasterName = disasterNames[0];
    } else {
        target.programType = programTypes[0];
        target.assistanceType = 'Cash Assistance';
    }

    return target;
}

const filteredTargets = computed(() => {
    const query = search.value.trim().toLowerCase();
    return targets.value.filter((target) => {
        const matchesSearch = !query || Object.values(target).join(' ').toLowerCase().includes(query);
        const selectedField = isDrmd.value ? target.disasterName : target.programType;
        const matchesSelection =
            selectedPrograms.value.length === 0 || selectedPrograms.value.includes(selectedField);
        return matchesSearch && matchesSelection;
    });
});

const pageCount = computed(() => Math.max(1, Math.ceil(filteredTargets.value.length / pageSize.value)));
const visibleTargets = computed(() => {
    const start = (page.value - 1) * pageSize.value;
    return filteredTargets.value.slice(start, start + pageSize.value);
});

const formatAmount = (amount) => Number(amount || 0).toLocaleString();
const formatDate = (value) => {
    if (!value) return '';
    const [year, month, day] = value.split('-');
    return `${month}-${day}-${year}`;
};

const applyFilters = () => {
    selectedPrograms.value = [...draftPrograms.value];
    filterOpen.value = false;
    page.value = 1;
};

const clearFilters = () => {
    draftPrograms.value = [];
    selectedPrograms.value = [];
    page.value = 1;
};

const openAddTarget = () => {
    editingTargetId.value = null;
    targetDraft.value = createEmptyTarget();
    showTargetDialog.value = true;
};

const openEditTarget = (target) => {
    editingTargetId.value = target.id;
    targetDraft.value = { ...target };
    showTargetDialog.value = true;
};

const closeTargetDialog = () => {
    showTargetDialog.value = false;
};

const saveTarget = () => {
    const target = { ...targetDraft.value };
    if (editingTargetId.value === null) {
        targets.value.unshift({ id: Date.now(), ...target });
    } else {
        const targetIndex = targets.value.findIndex((item) => item.id === editingTargetId.value);
        if (targetIndex !== -1) targets.value[targetIndex] = { id: editingTargetId.value, ...target };
    }
    showTargetDialog.value = false;
};

const closeFilterOnOutsidePointer = (event) => {
    if (filterOpen.value && !filterRoot.value?.contains(event.target)) {
        filterOpen.value = false;
    }
};

onMounted(() => document.addEventListener('pointerdown', closeFilterOnOutsidePointer));
onUnmounted(() => document.removeEventListener('pointerdown', closeFilterOnOutsidePointer));

watch([search, pageSize], () => { page.value = 1; });
</script>

<style scoped>
* { box-sizing: border-box; }

.target-workspace {
    display: flex;
    flex-direction: column;
    width: 100%;
    min-width: 0;
    min-height: 100vh;
    padding: 22px 28px 0;
    background: #f4f7fb;
}

.target-header {
    display: flex;
    align-items: flex-end;
    justify-content: space-between;
    gap: 20px;
    min-height: 72px;
    width: 100%;
    margin: 0 0 16px;
}

.target-heading {
    display: grid;
    justify-items: start;
    gap: 10px;
}

.target-heading h1 {
    margin: 0;
    color: #20242c;
    font-size: 28px;
    font-weight: 700;
}

.add-target-button {
    display: inline-flex;
    align-items: center;
    gap: 7px;
    min-height: 32px;
    padding: 0 12px;
    border: 0;
    border-radius: 4px;
    background: #302b9c;
    color: #fff;
    cursor: pointer;
    font-size: 12px;
    font-weight: 600;
}

.target-tools {
    display: flex;
    align-items: center;
    gap: 8px;
    padding-bottom: 1px;
}

.target-search,
.filter-trigger {
    display: flex;
    align-items: center;
    gap: 7px;
    height: 32px;
    padding: 0 9px;
    border: 1px solid #dce3ed;
    border-radius: 4px;
    background: #f9fbfe;
    color: #718096;
}

.target-search {
    width: 190px;
}

.target-search input {
    width: 100%;
    min-width: 0;
    border: 0;
    outline: 0;
    background: transparent;
    color: #253143;
    font: inherit;
    font-size: 12px;
}

.target-search i,
.filter-trigger i {
    font-size: 12px;
}

.target-filter {
    position: relative;
}

.filter-trigger {
    cursor: pointer;
    font: inherit;
    font-size: 12px;
}

.filter-trigger .pi-chevron-down {
    font-size: 9px;
}

.filter-panel {
    position: absolute;
    top: calc(100% + 6px);
    right: 0;
    z-index: 20;
    display: grid;
    gap: 9px;
    width: 200px;
    padding: 12px;
    border: 1px solid #dce3ed;
    border-radius: 4px;
    background: #fff;
    box-shadow: 0 6px 18px rgb(13 28 51 / 16%);
}

.filter-panel strong {
    color: #39465a;
    font-size: 12px;
}

.filter-panel label {
    display: flex;
    align-items: center;
    gap: 7px;
    color: #293241;
    font-size: 12px;
}

.filter-panel input {
    margin: 0;
    accent-color: #302b9c;
}

.filter-actions {
    display: flex;
    justify-content: space-between;
    padding-top: 8px;
    border-top: 1px solid #e7ebf1;
}

.filter-actions button {
    min-height: 26px;
    padding: 0 9px;
    border: 0;
    border-radius: 4px;
    cursor: pointer;
    font-size: 11px;
}

.clear-filter {
    background: transparent;
    color: #302b9c;
}

.apply-filter {
    background: #302b9c;
    color: #fff;
}

.target-table-scroll {
    width: 100%;
    max-width: 100%;
    min-width: 0;
    min-height: 700px;
    margin: 0;
    overflow: auto;
    border: 1px solid #dce3ed;
    background: #fff;
}

.target-table-scroll:focus-visible {
    outline: 2px solid #3f8fd2;
    outline-offset: 2px;
}

.target-table {
    width: 1480px;
    min-width: 1480px;
    table-layout: fixed;
    border-collapse: collapse;
    color: #111827;
    font-size: 14px;
    font-weight: 500;
    text-align: left;
    white-space: nowrap;
}

.target-table.drmd-table {
    width: 1480px;
    min-width: 1480px;
}

.target-table th,
.target-table td {
    height: 40px;
    padding: 0 12px;
    border-bottom: 1px solid #e4e8ef;
}

.target-table th {
    height: 47px;
    background: #f8faff;
    color: #354768;
    font-size: 15px;
    font-weight: 2700;
    text-transform: uppercase;
}

.target-table th:nth-child(1),
.target-table td:nth-child(1) { width: 370px; }
.target-table th:nth-child(2),
.target-table td:nth-child(2) { width: 370px; }
.target-table th:nth-child(3),
.target-table td:nth-child(3) { width: 370px; }
.target-table th:nth-child(4),
.target-table td:nth-child(4) { width: 370px; }
.target-table th:nth-child(5),
.target-table td:nth-child(5) { width: 370px; }
.target-table th:nth-child(6),
.target-table td:nth-child(6) { width: 350px; }
.target-table th:nth-child(7),
.target-table td:nth-child(7) { width: 330px; }
.target-table th:nth-child(8),
.target-table td:nth-child(8) { width: 310px; }
.target-table th:nth-child(9),
.target-table td:nth-child(9) { width: 80px; }

.target-table tbody tr:hover {
    background: #f8faff;
}

.edit-target-button {
    display: grid;
    width: 28px;
    height: 28px;
    place-items: center;
    border: 0;
    border-radius: 4px;
    background: transparent;
    color: #273b83;
    cursor: pointer;
}

.edit-target-button:hover {
    background: #eaf0ff;
}

.empty-targets {
    height: 100px !important;
    color: #718096;
    text-align: center;
}

.target-pagination {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 24px;
    width: 100%;
    min-height: 44px;
    margin: 0;
    border: 1px solid #dce3ed;
    border-top: 0;
    background: #fff;
}

.page-controls {
    display: flex;
    align-items: center;
    gap: 5px;
}

.page-controls button {
    display: grid;
    width: 24px;
    height: 24px;
    place-items: center;
    padding: 0;
    border: 0;
    border-radius: 50%;
    background: transparent;
    color: #53647e;
    cursor: pointer;
    font-size: 11px;
}

.page-controls button.current {
    background: #302b9c;
    color: #fff;
}

.page-controls button:disabled {
    cursor: default;
    opacity: 0.4;
}

.target-pagination select {
    padding: 4px 8px;
    border: 1px solid #dce3ed;
    border-radius: 4px;
    background: #fff;
    color: #516074;
    font: inherit;
    font-size: 11px;
}

.dialog-backdrop {
    position: fixed;
    inset: 0;
    z-index: 1000;
    display: grid;
    place-items: center;
    padding: 18px;
    background: rgb(10 23 46 / 55%);
}

.target-dialog {
    display: grid;
    gap: 12px;
    width: min(100%, 440px);
    max-height: calc(100vh - 36px);
    padding: 22px;
    overflow-y: auto;
    border: 1px solid #dce3ed;
    border-radius: 6px;
    background: #fff;
    box-shadow: 0 16px 48px rgb(13 28 51 / 24%);
}

.target-dialog h2 {
    margin: 0 0 4px;
    color: #20242c;
    font-size: 20px;
}

.target-dialog label {
    display: grid;
    gap: 5px;
    color: #39465a;
    font-size: 12px;
    font-weight: 600;
}

.target-dialog input,
.target-dialog select {
    width: 100%;
    min-height: 34px;
    padding: 6px 9px;
    border: 1px solid #cfd8e5;
    border-radius: 4px;
    background: #fff;
    font: inherit;
}

.dialog-actions {
    display: flex;
    justify-content: flex-end;
    gap: 8px;
    margin-top: 4px;
}

.dialog-actions button {
    min-height: 34px;
    padding: 0 12px;
    border: 0;
    border-radius: 4px;
    cursor: pointer;
    font-size: 12px;
}

.cancel-button {
    background: #edf1f6;
    color: #334155;
}

.save-target-button {
    background: #302b9c;
    color: #fff;
}

@media (max-width: 900px) {
    .target-header {
        align-items: flex-start;
        flex-direction: column;
        min-height: 0;
    }

    .target-tools {
        width: 100%;
        justify-content: flex-end;
    }

    .target-search {
        flex: 1 1 auto;
        max-width: 220px;
    }

    .target-table {
        width: 1110px;
        min-width: 1110px;
    }

    .target-table.drmd-table {
        width: 1200px;
        min-width: 1200px;
    }

    .target-table th:nth-child(1),
    .target-table td:nth-child(1) { width: 95px; }
    .target-table th:nth-child(2),
    .target-table td:nth-child(2) { width: 100px; }
    .target-table th:nth-child(3),
    .target-table td:nth-child(3) { width: 155px; }
    .target-table th:nth-child(4),
    .target-table td:nth-child(4) { width: 125px; }
    .target-table th:nth-child(5),
    .target-table td:nth-child(5) { width: 135px; }
}

@media (max-width: 720px) {
    .target-workspace {
        padding: 16px 12px 0;
    }
}

@media (max-width: 520px) {
    .target-heading h1 {
        font-size: 24px;
    }
}
</style>