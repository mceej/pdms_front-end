<template>
    <section class="admin-workspace">
        <header class="admin-page-header">
            <h1>Audit Log</h1>
            <div class="admin-tools">
                <label class="search-control">
                    <i class="pi pi-search" aria-hidden="true"></i>
                    <input
                        v-model="search"
                        type="search"
                        placeholder="Search"
                        aria-label="Search audit log"
                    />
                </label>
                <div class="filter-control" ref="filterControl">
                    <button
                        type="button"
                        class="filter-trigger"
                        :class="{ 'filter-active': hasSelectedFilters }"
                        :aria-expanded="filterOpen"
                        @click="filterOpen = !filterOpen"
                    >
                        <i class="pi pi-filter" aria-hidden="true"></i>
                        <span>Filter</span>
                        <i class="pi pi-chevron-down" aria-hidden="true"></i>
                    </button>
                    <div v-if="filterOpen" class="filter-panel">
                        <section class="filter-group">
                            <h2>Module</h2>
                            <label v-for="module in moduleOptions" :key="module">
                                <input
                                    v-model="draftModules"
                                    type="checkbox"
                                    :value="module"
                                />
                                {{ module }}
                            </label>
                        </section>
                        <div class="filter-actions">
                            <button type="button" class="clear-filters" @click="clearFilters">
                                Clear All
                            </button>
                            <button type="button" class="apply-filters" @click="applyFilters">
                                Apply
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </header>

        <div class="table-wrap" role="region" aria-label="Audit log table" tabindex="0">
            <table class="admin-table">
                <thead>
                    <tr>
                        <th>Timestamp</th>
                        <th>Name</th>
                        <th>User Type</th>
                        <th>Module</th>
                        <th>Actions</th>
                        <th>Activity</th>
                        <th>IP Address</th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-for="row in visibleRows" :key="row.id">
                        <td>{{ row.timestamp }}</td>
                        <td>{{ row.name }}</td>
                        <td>{{ row.userType }}</td>
                        <td>
                            <span class="module-tag">{{ row.module }}</span>
                        </td>
                        <td>{{ row.action }}</td>
                        <td>{{ row.activity }}</td>
                        <td>{{ row.ipAddress }}</td>
                    </tr>
                    <tr v-if="visibleRows.length === 0">
                        <td class="empty-row" colspan="7">No audit records found.</td>
                    </tr>
                </tbody>
            </table>
        </div>

        <div class="table-pagination">
            <div class="page-controls" aria-label="Audit log pagination">
                <button
                    type="button"
                    aria-label="First page"
                    :disabled="page === 1"
                    @click="page = 1"
                >
                    «
                </button>
                <button
                    type="button"
                    aria-label="Previous page"
                    :disabled="page === 1"
                    @click="page--"
                >
                    ‹
                </button>
                <button
                    v-for="pageNumber in pageCount"
                    :key="pageNumber"
                    type="button"
                    :class="{ current: pageNumber === page }"
                    :aria-current="pageNumber === page ? 'page' : undefined"
                    @click="page = pageNumber"
                >
                    {{ pageNumber }}
                </button>
                <button
                    type="button"
                    aria-label="Next page"
                    :disabled="page === pageCount"
                    @click="page++"
                >
                    ›
                </button>
                <button
                    type="button"
                    aria-label="Last page"
                    :disabled="page === pageCount"
                    @click="page = pageCount"
                >
                    »
                </button>
            </div>
            <select v-model.number="pageSize" aria-label="Audit log rows per page">
                <option :value="10">10</option>
                <option :value="25">25</option>
                <option :value="50">50</option>
            </select>
        </div>

        <AppFooter />
    </section>
</template>

<script setup>
import { computed, onMounted, onUnmounted, ref, watch } from 'vue';
import AppFooter from '../../components/AppFooter.vue';

const search = ref('');
const filterOpen = ref(false);
const filterControl = ref(null);
const moduleOptions = ['Target Management', 'Dashboard', 'Import Served List'];
const draftModules = ref([]);
const selectedModules = ref([]);
const page = ref(1);
const pageSize = ref(25);
const rows = ref(
    [
        ...Array.from({ length: 10 }, (_, rowIndex) => ({
            id: rowIndex + 1,
            timestamp: '25-02-2026, 9:30 AM',
            name: 'Juan Dela Cruz',
            userType: 'Program RDV Focal',
            module: 'Target Management',
            action: 'Add Target',
            activity: 'Added Target record ID #5522',
            ipAddress: '192.168.1.45',
        })),
        {
            id: 11,
            timestamp: '25-02-2026, 10:05 AM',
            name: 'Maria Santos',
            userType: 'Administrator',
            module: 'Dashboard',
            action: 'View Dashboard',
            activity: 'Viewed payout dashboard summary',
            ipAddress: '192.168.1.52',
        },
        {
            id: 12,
            timestamp: '25-02-2026, 10:18 AM',
            name: 'Carlos Reyes',
            userType: 'Program RDV Focal',
            module: 'Import Served List',
            action: 'Import File',
            activity: 'Imported served-list-february.csv',
            ipAddress: '192.168.1.63',
        },
        {
            id: 13,
            timestamp: '25-02-2026, 10:42 AM',
            name: 'Ana Garcia',
            userType: 'Regional Focal',
            module: 'Target Management',
            action: 'Update Target',
            activity: 'Updated target record ID #5522',
            ipAddress: '192.168.1.71',
        },
        {
            id: 14,
            timestamp: '25-02-2026, 11:10 AM',
            name: 'Ramon Cruz',
            userType: 'Administrator',
            module: 'Dashboard',
            action: 'Export Report',
            activity: 'Exported monthly payout report',
            ipAddress: '192.168.1.88',
        },
        {
            id: 15,
            timestamp: '25-02-2026, 11:35 AM',
            name: 'Liza Mendoza',
            userType: 'Program RDV Focal',
            module: 'Import Served List',
            action: 'Validate File',
            activity: 'Validated served-list-march.csv',
            ipAddress: '192.168.1.96',
        },
    ],
);

const filteredRows = computed(() => {
    const query = search.value.trim().toLowerCase();
    return rows.value.filter((row) => {
        const matchesSearch =
            !query || Object.values(row).join(' ').toLowerCase().includes(query);
        const matchesModule =
            selectedModules.value.length === 0 || selectedModules.value.includes(row.module);
        return matchesSearch && matchesModule;
    });
});

const pageCount = computed(() => Math.max(1, Math.ceil(filteredRows.value.length / pageSize.value)));
const visibleRows = computed(() => {
    const start = (page.value - 1) * pageSize.value;
    return filteredRows.value.slice(start, start + pageSize.value);
});

const hasSelectedFilters = computed(() => draftModules.value.length > 0);

const applyFilters = () => {
    selectedModules.value = [...draftModules.value];
    filterOpen.value = false;
    page.value = 1;
};

const clearFilters = () => {
    draftModules.value = [];
    selectedModules.value = [];
    page.value = 1;
};

const closeFilterOnOutsidePointer = (event) => {
    if (filterOpen.value && !filterControl.value?.contains(event.target)) {
        filterOpen.value = false;
    }
};

onMounted(() => document.addEventListener('pointerdown', closeFilterOnOutsidePointer));
onUnmounted(() => document.removeEventListener('pointerdown', closeFilterOnOutsidePointer));

watch(
    [search, pageSize],
    () => {
        page.value = 1;
    },
);
</script>

<style scoped>
* {
    box-sizing: border-box;
}

.admin-workspace {
    display: flex;
    flex-direction: column;
    width: 100%;
    min-width: 0;
    height: 100vh;
    padding: 22px 28px 24px;
    overflow: hidden;
    background: #f4f7fb;
}

.admin-page-header {
    flex: 0 0 auto;
    position: relative;
    display: flex;
    align-items: flex-start;
    justify-content: space-between;
    gap: 20px;
    min-height: 72px;
    margin-bottom: 14px;
}

.admin-page-header h1 {
    margin: 0;
    color: #20242c;
    font-size: 35px;
    font-weight: 700;
}

.admin-tools {
    display: flex;
    align-self: flex-end;
    align-items: center;
    gap: 8px;
    margin-left: auto;
}

.search-control,
.filter-control {
    display: flex;
    align-items: center;
    gap: 6px;
    height: 30px;
    padding: 0 8px;
    border: 1px solid #dce3ed;
    border-radius: 4px;
    background: #f9fbfe;
    color: #718096;
}

.search-control {
    width: 190px;
}

.search-control input {
    width: 100%;
    min-width: 0;
    border: 0;
    outline: 0;
    background: transparent;
    color: #253143;
    font: inherit;
    font-size: 14px;
}

.search-control input::placeholder {
    color: #9aa7b8;
}

.search-control i,
.filter-control i {
    font-size: 14px;
}

.table-pagination select {
    border: 0;
    outline: 0;
    background: transparent;
    color: #516074;
    font: inherit;
    font-size: 14px;
}

.filter-control {
    position: relative;
    height: auto;
    padding: 0;
    overflow: visible;
    border: 0;
    background: transparent;
}

.filter-trigger {
    display: flex;
    align-items: center;
    gap: 7px;
    height: 30px;
    padding: 0 9px;
    border: 1px solid #dce3ed;
    border-radius: 4px;
    background: #f9fbfe;
    color: #718096;
    cursor: pointer;
    font: inherit;
    font-size: 14px;
}

.filter-trigger .pi-chevron-down {
    margin-left: 3px;
    font-size: 10px;
}

.filter-trigger.filter-active {
    border-color: #9fc9ed;
    background: #e5f3ff;
    color: #256da8;
}

.filter-panel {
    position: absolute;
    top: calc(100% + 7px);
    right: 0;
    z-index: 30;
    width: 250px;
    padding: 14px;
    border: 1px solid #dce3ed;
    border-radius: 5px;
    background: #fff;
    box-shadow: 0 8px 24px rgb(20 35 60 / 16%);
}

.filter-group {
    display: grid;
    gap: 8px;
    padding: 0 0 12px;
}

.filter-group h2 {
    margin: 0;
    color: #39465a;
    font-size: 12px;
    font-weight: 700;
}

.filter-group label {
    display: flex;
    align-items: center;
    gap: 8px;
    color: #273244;
    cursor: pointer;
    font-size: 12px;
    font-weight: 400;
}

.filter-group input {
    width: 14px;
    height: 14px;
    margin: 0;
    accent-color: #302b9c;
}

.filter-actions {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding-top: 10px;
    border-top: 1px solid #e7ebf1;
}

.filter-actions button {
    min-height: 28px;
    padding: 0 10px;
    border: 0;
    border-radius: 4px;
    cursor: pointer;
    font: inherit;
    font-size: 12px;
}

.clear-filters {
    background: transparent;
    color: #302b9c;
}

.apply-filters {
    background: #302b9c;
    color: #fff;
}

/* Keep every audit-log column within the page width; only vertical scrolling is needed. */
.table-wrap {
    display: block;
    flex: 1 1 auto;
    width: min(100%, 2000px);
    max-width: 100%;
    min-height: 0;
    min-width: 0;
    overflow-x: hidden;
    overflow-y: auto;
    border: 1px solid #dce3ed;
    background: #fff;
}

.admin-table {
    width: 100%;
    min-width: 0;
    table-layout: auto;
    border-collapse: collapse;
    color: #111827;
    font-size: 14px;
    text-align: left;
    white-space: normal;
}

.admin-table th {
    position: sticky;
    top: 0;
    z-index: 1;
    height: 45px;
    padding: 0 14px;
    background: #f8faff;
    color: #354768;
    font-size: 14px;
    font-weight: 700;
    text-transform: uppercase;
}

.admin-table td {
    height: 76px;
    padding: 0 14px;
    border-top: 1px solid #e4e8ef;
    overflow-wrap: anywhere;
}

.admin-table tbody tr:hover {
    background: #f9fbff;
}

.module-tag {
    display: inline-block;
    padding: 3px 8px;
    border: 1px solid #d7dce5;
    border-radius: 999px;
    background: #f8f9fb;
    color: #323b49;
    font-size: 14px;
}

.empty-row {
    height: 100px !important;
    color: #718096;
    text-align: center;
}

.table-pagination {
    flex: 0 0 auto;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 24px;
    min-height: 44px;
    margin-bottom: 16px;
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
    font-size: 14px;
}

.page-controls button.current {
    background: #302b9c;
    color: #fff;
}

.page-controls button:disabled {
    cursor: default;
    opacity: 0.4;
}

.table-pagination > select {
    padding: 4px 8px;
    border: 1px solid #dce3ed;
    border-radius: 4px;
}

@media (max-width: 720px) {
    .admin-workspace {
        padding: 16px 12px 16px;
    }

    .admin-page-header {
        align-items: flex-start;
        flex-direction: column;
    }

    .admin-tools {
        width: 100%;
        align-self: stretch;
        justify-content: flex-end;
    }

    .search-control {
        flex: 1 1 auto;
    }
}
</style>