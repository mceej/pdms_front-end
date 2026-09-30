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
                <label class="filter-control">
                    <i class="pi pi-filter" aria-hidden="true"></i>
                    <select v-model="moduleFilter" aria-label="Filter audit log by module">
                        <option value="">All modules</option>
                        <option v-for="module in moduleOptions" :key="module" :value="module">
                            {{ module }}
                        </option>
                    </select>
                </label>
            </div>
        </header>

        <div
            class="table-wrap"
            role="region"
            aria-label="Audit log table. Scroll horizontally to see Activity and IP Address."
            tabindex="0"
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
            <select v-model.number="pageSize" aria-label="Audit log rows per page">
                <option :value="10">10</option>
                <option :value="25">25</option>
                <option :value="50">50</option>
            </select>
        </div>

    </section>
</template>

<script setup>
import { computed, onMounted, onUnmounted, ref, watch } from 'vue';
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
const moduleFilter = ref('');
const page = ref(1);
const pageSize = ref(25);
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
        const matchesModule = !moduleFilter.value || row.module === moduleFilter.value;
        return matchesSearch && matchesModule;
    });
});

const pageCount = computed(() => Math.max(1, Math.ceil(filteredRows.value.length / pageSize.value)));
const visibleRows = computed(() => {
    const start = (page.value - 1) * pageSize.value;
    return filteredRows.value.slice(start, start + pageSize.value);
});

watch(
    [search, moduleFilter, pageSize],
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
    min-height: 100vh;
    padding: 22px 28px 0;
    background: #f4f7fb;
}

.admin-page-header {
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

.filter-control select,
.table-pagination select {
    border: 0;
    outline: 0;
    background: transparent;
    color: #516074;
    font: inherit;
    font-size: 14px;
}

.filter-control select {
    width: 58px;
    cursor: pointer;
}

.table-wrap {
    display: block;
    width: min(100%, 2000px);
    max-width: 100%;
    min-height: 700px;
    min-width: 0;
    overflow-x: auto;
    overflow-y: auto;
    border: 1px solid #dce3ed;
    background: #fff;
}

.admin-table {
    width: 2120px;
    min-width: 2120px;
    table-layout: fixed;
    border-collapse: collapse;
    color: #111827;
    font-size: 14px;
    text-align: left;
    white-space: nowrap;
}

.admin-table th:nth-child(1),
.admin-table td:nth-child(1) {
    width: 400px;
}

.admin-table th:nth-child(2),
.admin-table td:nth-child(2) {
    width: 310px;
}

.admin-table th:nth-child(3),
.admin-table td:nth-child(3) {
    width: 400px;
}

.admin-table th:nth-child(4),
.admin-table td:nth-child(4) {
    width: 450px;
}

.admin-table th:nth-child(5),
.admin-table td:nth-child(5) {
    width: 320px;
}

.admin-table th:nth-child(6),
.admin-table td:nth-child(6) {
    width: 370px;
}

.admin-table th:nth-child(7),
.admin-table td:nth-child(7) {
    width: 180px;
}

.admin-table th {
    height: 32px;
    padding: 0 14px;
    background: #f8faff;
    color: #354768;
    font-size: 14px;
    font-weight: 700;
    text-transform: uppercase;
}

.admin-table td {
    height: 50px;
    padding: 0 14px;
    border-top: 1px solid #e4e8ef;
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
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 24px;
    min-height: 44px;
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
        padding: 16px 12px 0;
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
