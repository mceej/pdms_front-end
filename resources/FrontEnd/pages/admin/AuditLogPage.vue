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

        <div
            class="table-wrap"
            role="region"
            aria-label="Audit log table"
            tabindex="0"
            :style="{ '--visible-row-height': `${visibleRows.length ? visibleRows.length * 76 : 100}px` }"
        >
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
                    <tr v-if="isLoading" class="table-note">
                        <td colspan="7">Loading activity…</td>
                    </tr>
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
        </div>

        <AppFooter />
    </section>
</template>

<script setup>
import { computed, onMounted, onUnmounted, ref, watch } from 'vue';
import AppFooter from '../../components/AppFooter.vue';
import { subscribeAuditLog } from '../../data/auditLog.js';
import { searchableText, useDebounced } from '../../support/useDebounced.js';

const formatMoment = (milliseconds) => {
    if (!milliseconds) {
        return '';
    }

    const moment = new Date(milliseconds);
    const date = moment.toLocaleDateString('en-GB').replace(/\//g, '-');

    return `${date}, ${moment.toLocaleTimeString([], { hour: 'numeric', minute: '2-digit' })}`;
};

const search = ref('');
const searchQuery = useDebounced(search, 150);
const filterOpen = ref(false);
const filterControl = ref(null);
const draftModules = ref([]);
const selectedModules = ref([]);
const page = ref(1);
const pageSize = ref(10);
const rows = ref([]);
const isLoading = ref(true);

let unsubscribeAuditLog = () => {};

onMounted(() => {
    unsubscribeAuditLog = subscribeAuditLog((entries) => {
        rows.value = entries.map((entry) => ({
            ...entry,
            timestamp: formatMoment(entry.at),
            searchText: searchableText(
                entry.name,
                entry.userType,
                entry.module,
                entry.action,
                entry.activity,
                entry.ipAddress
            ),
        }));
        isLoading.value = false;
    });
});

onUnmounted(() => unsubscribeAuditLog());

// Built from the entries themselves, so the filter always offers exactly the
// modules that appear in the log and never drifts from what is recorded.
const moduleOptions = computed(() =>
    [...new Set(rows.value.map((row) => row.module).filter(Boolean))].sort()
);

const filteredRows = computed(() => {
    const query = searchQuery.value.trim().toLowerCase();
    return rows.value.filter((row) => {
        const matchesSearch = !query || row.searchText.includes(query);
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
    flex: 0 0 72px;
    position: relative;
    display: flex;
    align-items: flex-start;
    justify-content: space-between;
    gap: 20px;
    height: 72px;
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

.table-wrap {
    display: block;
    flex: 1 1 auto;
    width: min(100%, 2000px);
    max-width: 100%;
    min-height: 0;
    min-width: 0;
    overflow-x: hidden;
    overflow-y: auto;
    scrollbar-gutter: stable;
    border: 1px solid #dce3ed;
    background-color: #fff;
    background-image:
        linear-gradient(to bottom, #f8faff 0 44px, #e4e8ef 44px 45px, transparent 45px),
        repeating-linear-gradient(to bottom, #e4e8ef 0 1px, transparent 1px 76px);
    background-position: left top, left 45px;
    background-size: 100% 45px, 100% var(--visible-row-height);
    background-repeat: no-repeat;
    scrollbar-color: #9aa6b2 transparent;
    scrollbar-width: thin;
}

.table-wrap::-webkit-scrollbar {
    width: 12px;
}

.table-wrap::-webkit-scrollbar-track,
.table-wrap::-webkit-scrollbar-button {
    background: transparent;
}

.table-wrap::-webkit-scrollbar-button {
    display: none;
}

.table-wrap::-webkit-scrollbar-thumb {
    border: 3px solid transparent;
    border-radius: 8px;
    background-color: #9aa6b2;
    background-clip: content-box;
}

.admin-table {
    width: 100%;
    min-width: 0;
    table-layout: fixed;
    border-collapse: collapse;
    background: #fff;
    color: #111827;
    font-size: 14px;
    text-align: left;
    white-space: normal;
}

.admin-table th:nth-child(1) { width: 17%; }
.admin-table th:nth-child(2) { width: 13%; }
.admin-table th:nth-child(3) { width: 17%; }
.admin-table th:nth-child(4) { width: 13%; }
.admin-table th:nth-child(5) { width: 13%; }
.admin-table th:nth-child(6) { width: 17%; }
.admin-table th:nth-child(7) { width: 18%; }

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
    display: grid;
    grid-template-columns: minmax(0, 1fr) auto minmax(0, 1fr);
    align-items: center;
    min-height: 44px;
    margin-bottom: 16px;
    border: 1px solid #dce3ed;
    border-top: 0;
    background: #fff;
}

.page-controls {
    display: flex;
    align-items: center;
    grid-column: 2;
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
        flex: 0 0 auto;
        align-items: flex-start;
        flex-direction: column;
        height: auto;
        min-height: 0;
    }

    .admin-tools {
        width: 100%;
        align-self: stretch;
        justify-content: flex-end;
    }

    .search-control {
        flex: 1 1 auto;
    }

    .table-pagination {
        grid-template-columns: 1fr;
        gap: 4px;
        padding: 6px;
    }

    .page-controls {
        grid-column: 1;
    }

    .page-controls {
        justify-self: center;
    }
}

.table-note td {
    padding: 22px 16px;
    color: #6b7280;
    font-size: 13px;
    text-align: center;
}

.filter-panel {
    animation: filter-panel-in 120ms ease-out;
}

@keyframes filter-panel-in {
    from { opacity: 0; transform: translateY(-4px); }
    to { opacity: 1; transform: translateY(0); }
}
</style>
