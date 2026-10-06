﻿<template>
    <div class="dashboard-page">
        <div class="dashboard-shell">
            <p v-if="loadError" class="dashboard-error" role="alert">
                <i class="pi pi-exclamation-triangle" aria-hidden="true"></i>
                {{ loadError }}
                <button type="button" @click="fetchDashboard">Try again</button>
            </p>
            <p v-for="warning in loadWarnings" :key="warning" class="dashboard-note">
                <i class="pi pi-info-circle" aria-hidden="true"></i>
                {{ warning }}
            </p>
            <header class="dashboard-header">
                <div class="header-content">
                    <span class="brand">
                        <i class="pi"></i> DSWD • PAYOUT SYSTEM
                    </span>
                    <slot name="header-actions"></slot>

                    <div class="title-row">
                        <div>
                            <h1>DSWD Assist Track Dashboard</h1>
                            <span class="region-tag">{{ regionName }}</span>
                        </div>

                        <div class="progress-box">
                            <div class="header-progress-meta">
                                <span class="header-progress-value">{{ dashboardProgress }}%</span>
                            </div>
                            <div class="header-beneficiaries">
                                {{ formatCount(totalPaidCount) }} of {{ formatCount(totalTarget) }} beneficiaries processed
                            </div>
                            <div class="header-progress-wrap">
                                <ProgressBar :value="dashboardProgress" :showValue="false" class="header-progress" />
                            </div>
                            <div class="header-updated">
                                <span><i class="pi pi-clock"></i> Last Updated</span>
                                <strong>{{ formatUpdatedAt(lastUpdated) }}</strong>
                            </div>
                        </div>
                    </div>

                    <div class="tab-row">
                        <div class="aics-control" ref="aicsControlRef">
                            <button
                                type="button"
                                :class="['tab', 'aics-tab', { active: activeTab === 'AICS' && !selectedAssistanceType }]"
                                @click="toggleAicsTab"
                            >
                                <i class="pi pi-box"></i>
                                AICS
                                <i class="pi pi-chevron-down ect-chevron" :class="{ open: assistanceMenuOpen }"></i>
                            </button>
                            <div v-if="assistanceMenuOpen" class="disaster-menu">
                                <button
                                    type="button"
                                    class="disaster-menu-item disaster-menu-clear"
                                    :disabled="!selectedAssistanceType"
                                    @click="chooseAssistanceType('')"
                                >
                                    Clear selection
                                </button>
                                <button
                                    v-for="option in assistanceTypeOptions"
                                    :key="option"
                                    type="button"
                                    :class="['disaster-menu-item', { selected: selectedAssistanceType === option }]"
                                    @click="chooseAssistanceType(option)"
                                >
                                    {{ option }}
                                </button>
                            </div>
                        </div>

                        <div class="ect-control" ref="ectControlRef">
                            <button
                                type="button"
                                :class="['tab', 'ect-tab', { active: activeTab === 'ECT' && !selectedDisasterType }]"
                                @click="toggleEctTab"
                            >
                                <i class="pi pi-file"></i>
                                ECT
                                <i class="pi pi-chevron-down ect-chevron" :class="{ open: disasterMenuOpen }"></i>
                            </button>

                            <div v-if="disasterMenuOpen" class="disaster-menu">
                                <button
                                    type="button"
                                    class="disaster-menu-item disaster-menu-clear"
                                    :disabled="!selectedDisasterType"
                                    @click="chooseDisasterType('')"
                                >
                                    Clear selection
                                </button>
                                <button
                                    v-for="option in disasterOptions"
                                    :key="option"
                                    type="button"
                                    :class="['disaster-menu-item', { selected: selectedDisasterType === option }]"
                                    @click="chooseDisasterType(option)"
                                >
                                    {{ option }}
                                </button>
                            </div>
                        </div>

                        <span v-if="activeTab === 'ECT' && selectedDisasterType" class="disaster-type-badge">
                            {{ selectedDisasterType }}
                        </span>
                        <span v-if="activeTab === 'AICS' && selectedAssistanceType" class="disaster-type-badge">
                            {{ selectedAssistanceType }}
                        </span>
                    </div>
                </div>
            </header>

            <DashboardOverview
                :total-target="totalTarget"
                :total-paid-count="totalPaidCount"
                :total-remaining="totalRemaining"
                :total-amount-to-disburse="totalBalance"
                :total-disbursed="totalDisbursed"
                :total-unpaid-disbursed="unpaidBalance"
            />

            <!-- Table (with Total row) + Progress Overview merged into one container -->
            <div class="summary-panel">
                <DashboardTable
                    :breadcrumb-items="breadcrumbItems"
                    :active-level="activeLevel"
                    :municipality-search="municipalitySearch"
                    :payout-site-filter="payoutSiteFilter"
                    :payout-site-options="payoutSiteOptions"
                    :rows-with-progress="rowsWithProgress"
                    :total-table-target="summaryTarget"
                    :total-table-paid="summaryPaid"
                    :total-progress="summaryProgress"
                    @update:municipalitySearch="municipalitySearch = $event"
                    @update:payoutSiteFilter="payoutSiteFilter = $event"
                    @apply-filters="applyFilters"
                    @row-click="handleRowClick"
                    @home-click="goToLevel('province')"
                />

                <ComparisonCharts
                    :selected-barangay="selectedBarangay"
                    :chart-markers="chartMarkers"
                    :comparison-rows="chartRowsWithProgress"
                    :api-loaded="apiLoaded"
                    :api-rows="apiRows"
                    :applied-date-label="appliedDateLabel"
                    :date-from="dateFrom"
                    :date-to="dateTo"
                    :dashboard-progress="chartProgress"
                    :total-paid-count="chartTotalPaid"
                    :total-target="chartTotalTarget"
                    :comparison-color="comparisonColor"
                    :active-level="activeLevel"
                    :scope-name="scopeName"
                    :extra-stat="extraStat"
                    :has-pending-range="hasPendingRange"
                    @update:dateFrom="(value) => { dateFrom = value; applyDateRange('from'); }"
                    @update:dateTo="(value) => { dateTo = value; applyDateRange('to'); }"
                    @apply="commitDateRange"
                    @clear="clearDateRange"
                />
            </div>
        </div>

        <footer class="dashboard-footer">
            <p>Ⓒ 2026 Department of Social Welfare and Development - Field Office XI. All Rights Reserved.</p>
        </footer>
    </div>
</template>

<script setup>
import { computed, nextTick, onMounted, onUnmounted, ref } from 'vue';
import ComparisonCharts from './ComparisonCharts.vue';
import DashboardOverview from './DashboardOverview.vue';
import DashboardTable from './DashboardTable.vue';
import { fetchDashboardMetrics, subscribeToChanges } from '../data/metrics.js';

const activeTab = ref('AICS');
const disasterName = ref('');
const selectedDisasterType = ref('');
const selectedAssistanceType = ref('');
const disasterMenuOpen = ref(false);
const assistanceMenuOpen = ref(false);
const ectControlRef = ref(null);
const aicsControlRef = ref(null);
const payoutSiteFilter = ref('');
const municipalitySearch = ref('');
const dateFrom = ref('');
const dateTo = ref('');
const appliedFrom = ref('');
const appliedTo = ref('');
const appliedDateLabel = ref('as of 9/14/2026 | 10:30:23 AM');

const formatCount = (value) => {
    if (value === null || value === undefined || value === '' || value === '-----') return '0';
    return Number(value).toLocaleString();
};

// When the figures last changed, which is the newest import or target edit —
// not the time right now, which says nothing about how fresh the data is.
const formatUpdatedAt = (value) => {
    if (value === null || value === undefined || value === '') return 'never';
    const date = new Date(value);
    if (Number.isNaN(date.getTime())) return String(value);
    return `${date.toLocaleDateString('en-US')} | ${date.toLocaleTimeString('en-US', {
        hour: 'numeric',
        minute: '2-digit',
    })}`;
};

const toggleAicsTab = () => {
    const switchingIn = activeTab.value !== 'AICS';
    if (switchingIn) {
        setTab('AICS');
        assistanceMenuOpen.value = true;
        return;
    }
    assistanceMenuOpen.value = !assistanceMenuOpen.value;
};

const toggleEctTab = () => {
    const switchingIn = activeTab.value !== 'ECT';
    if (switchingIn) {
        setTab('ECT');
        disasterMenuOpen.value = true;
        return;
    }
    disasterMenuOpen.value = !disasterMenuOpen.value;
};

const chooseDisasterType = (value) => {
    selectedDisasterType.value = value;
    disasterMenuOpen.value = false;
    applyFilters();
};

const chooseAssistanceType = (value) => {
    selectedAssistanceType.value = value;
    assistanceMenuOpen.value = false;
    applyFilters();
};

const closeDisasterMenuOnOutsideClick = (event) => {
    if (disasterMenuOpen.value && ectControlRef.value && !ectControlRef.value.contains(event.target)) {
        disasterMenuOpen.value = false;
    }
    if (assistanceMenuOpen.value && aicsControlRef.value && !aicsControlRef.value.contains(event.target)) {
        assistanceMenuOpen.value = false;
    }
};

const emptyKpi = () => ({
    totalBalance: 0,
    targetBeneficiaries: 0,
    totalPaid: 0,
    totalDisbursed: 0,
    unpaidBalance: 0,
    unpaidBeneficiaries: 0,
    progress: 0,
});

const apiRows = ref([]);
// The Total row the server worked out for exactly the rows it sent.
const apiTotal = ref(null);
const apiKpi = ref(emptyKpi());
const apiLoaded = ref(false);
const loadError = ref('');
// Things the figures cannot say for themselves, such as targets left out.
const loadWarnings = ref([]);
const lastUpdated = ref(null);
const apiDisasterTypes = ref([]);
const disasterOptions = computed(() => [
    ...new Set([
        'Typhoon',
        'Flood',
        'Earthquake',
        'Landslide',
        'Storm Surge',
        'Drought',
        'Volcanic Eruption',
        'Severe Thunderstorm',
        ...apiDisasterTypes.value,
    ]),
]);
const assistanceTypeOptions = ['Cash Assistance', 'Medical Support'];
const apiPayoutSites = ref([]);

const regionName = ref('REGION XI');
const selectedProvince = ref(null);
const selectedMunicipality = ref(null);
const selectedBarangay = ref(null);

/* ---------- The six cards at the top ----------
   Every one of them comes from the server, worked out for the place being
   looked at. The three counting people and the three counting pesos are
   deliberately separate figures: how many are still to be paid and how much is
   still to be moved are different questions. */
const totalTarget = computed(() => apiKpi.value.targetBeneficiaries);
const totalPaidCount = computed(() => apiKpi.value.totalPaid);
const totalRemaining = computed(() => apiKpi.value.unpaidBeneficiaries);
const totalBalance = computed(() => formatCurrency(apiKpi.value.totalBalance));
const totalDisbursed = computed(() => formatCurrency(apiKpi.value.totalDisbursed));
const unpaidBalance = computed(() => formatCurrency(apiKpi.value.unpaidBalance));
const dashboardProgress = computed(() => Math.round(apiKpi.value.progress));

const formatCurrency = (value) => `₱${Number(value || 0).toLocaleString()}`;

const activeLevel = computed(() => {
  if (selectedBarangay.value) return 'detail';
  if (selectedMunicipality.value) return 'barangay';
  if (selectedProvince.value) return 'municipality';
  return 'province';
});

// Searching for a municipality narrows the rows here rather than asking the
// server again, so typing stays instant.
const municipalityFilter = computed(
    () => activeLevel.value === 'municipality' ? municipalitySearch.value.trim().toLowerCase() : ''
);

const currentTableRows = computed(() => {
    const search = municipalityFilter.value;

    return search === ''
        ? apiRows.value
        : apiRows.value.filter((row) => row.name.toLowerCase().includes(search));
});

const rowsWithProgress = computed(() =>
  currentTableRows.value.map((row) => ({
    ...row,
    progress: Math.round(row.progress || 0),
  }))
);

/* ---------- Total row ----------
   The server sends the Total for the rows it sent. A municipality search hides
   some of them in the browser, so while one is in use the footer has to add up
   what is actually on screen instead. */
const tableTotal = computed(() => {
    if (apiTotal.value !== null && municipalityFilter.value === '') {
        return { target: apiTotal.value.target, paid: apiTotal.value.paid };
    }

    return {
        target: currentTableRows.value.reduce((sum, row) => sum + (row.target || 0), 0),
        paid: currentTableRows.value.reduce((sum, row) => sum + (row.paid || 0), 0),
    };
});

const totalTableTarget = computed(() => tableTotal.value.target);
const totalTablePaid = computed(() => tableTotal.value.paid);
const totalProgress = computed(() =>
  totalTableTarget.value ? Math.round((totalTablePaid.value / totalTableTarget.value) * 100) : 0
);

/* ---------- Data fed to the Target Distribution / Progress charts ----------
   Mirrors the table's drill-down level, except at 'detail' (a barangay is
   selected) where the chart shows that single barangay's own paid/remaining
   split plus an extra stat, since there's nothing further to drill into. */
const chartRows = computed(() => {
  if (activeLevel.value === 'detail' && selectedBarangay.value) {
    return [selectedBarangay.value];
  }
  return currentTableRows.value;
});
const chartRowsWithProgress = computed(() =>
  chartRows.value.map((row) => ({
    ...row,
    progress: row.target ? Math.round((row.paid / row.target) * 100) : 0,
  }))
);
const chartTotalTarget = computed(() => chartRows.value.reduce((sum, r) => sum + (r.target || 0), 0));
const chartTotalPaid = computed(() => chartRows.value.reduce((sum, r) => sum + (r.paid || 0), 0));
const chartProgress = computed(() =>
  chartTotalTarget.value ? Math.round((chartTotalPaid.value / chartTotalTarget.value) * 100) : 0
);

/* ---------- Total row (table footer) ----------
   Now that the table and the progress overview share one container, the Total
   row must agree with the charts. At 'detail' level the table has no rows, so
   fall back to the selected barangay's numbers used by the charts. */
const summaryTarget = computed(() => activeLevel.value === 'detail' ? chartTotalTarget.value : totalTableTarget.value);
const summaryPaid = computed(() => activeLevel.value === 'detail' ? chartTotalPaid.value : totalTablePaid.value);
const summaryProgress = computed(() => activeLevel.value === 'detail' ? chartProgress.value : totalProgress.value);

// Name of the parent entity the chart is currently scoped to (shown in the subtitle)
const scopeName = computed(() => {
  if (activeLevel.value === 'municipality') return selectedProvince.value?.name || '';
  if (activeLevel.value === 'barangay') return selectedMunicipality.value?.name || '';
  if (activeLevel.value === 'detail') return selectedBarangay.value?.name || '';
  return '';
});

// Extra "minority" data point surfaced once a barangay is selected
const extraStat = computed(() => {
  if (activeLevel.value === 'detail' && selectedBarangay.value) {
    return {
      label: 'Beneficiaries Served',
      value: selectedBarangay.value.beneficiaries ?? selectedBarangay.value.paid,
    };
  }
  return null;
});

const payoutSiteOptions = computed(() => apiPayoutSites.value);

const chartMarkers = [200, 150, 100, 50, 0];
const comparisonPalette = ['#2588d2', '#f08a24', '#25a269', '#8a63d2', '#d14d72'];
const comparisonColor = (index, previous = false) => {
    const color = comparisonPalette[index % comparisonPalette.length];
    return previous ? `${color}99` : color;
};

const preventBreadcrumbJump = (event) => {
        event?.originalEvent?.preventDefault();
};

const breadcrumbItems = computed(() => {
        const items = [{ label: 'Provinces', command: (event) => { preventBreadcrumbJump(event); goToLevel('province'); } }];
    if (selectedProvince.value) items.push({ label: selectedProvince.value.name, command: (event) => { preventBreadcrumbJump(event); goToLevel('municipality'); } });
    if (selectedMunicipality.value) items.push({ label: selectedMunicipality.value.name, command: (event) => { preventBreadcrumbJump(event); goToLevel('barangay'); } });
  if (selectedBarangay.value) items.push({ label: selectedBarangay.value.name });
  return items;
});

const fetchDashboard = async () => {
    const filters = { program: activeTab.value };
    if (selectedDisasterType.value) filters.disasterType = selectedDisasterType.value;
    if (selectedAssistanceType.value) filters.assistanceType = selectedAssistanceType.value;
    if (selectedProvince.value?.id) filters.provinceId = selectedProvince.value.id;
    if (selectedMunicipality.value?.id) filters.municipalityId = selectedMunicipality.value.id;
    if (selectedBarangay.value?.id) filters.barangayId = selectedBarangay.value.id;
    if (payoutSiteFilter.value) filters.payoutSite = payoutSiteFilter.value;
    if (appliedFrom.value) filters.from = appliedFrom.value;
    if (appliedTo.value) filters.to = appliedTo.value;

    const result = await fetchDashboardMetrics(filters);

    if (! result.ok) {
        loadError.value = result.message;
        loadWarnings.value = [];
        apiRows.value = [];
        apiTotal.value = null;
        apiKpi.value = emptyKpi();
        apiLoaded.value = true;

        return;
    }

    apiRows.value = result.rows;
    apiTotal.value = result.total;
    apiKpi.value = result.kpi;
    apiDisasterTypes.value = result.disasterTypes;
    apiPayoutSites.value = result.payoutSites;
    lastUpdated.value = result.lastUpdated;
    loadWarnings.value = result.warnings;
    loadError.value = '';
    apiLoaded.value = true;
};

const handleRowClick = async (row) => {
    const scrollPosition = window.scrollY;

  if (activeLevel.value === 'province') {
    selectedProvince.value = row;
    selectedMunicipality.value = null;
    selectedBarangay.value = null;
    payoutSiteFilter.value = '';
    municipalitySearch.value = '';
        await fetchDashboard();
        await nextTick();
        window.scrollTo({ top: scrollPosition, behavior: 'auto' });
    return;
  }
  if (activeLevel.value === 'municipality') {
    selectedMunicipality.value = row;
    selectedBarangay.value = null;
    municipalitySearch.value = '';
        await fetchDashboard();
        await nextTick();
        window.scrollTo({ top: scrollPosition, behavior: 'auto' });
    return;
  }
  if (activeLevel.value === 'barangay') {
    selectedBarangay.value = row;
        await fetchDashboard();
        await nextTick();
        window.scrollTo({ top: scrollPosition, behavior: 'auto' });
  }
};

const goToLevel = async (level) => {
    const scrollPosition = window.scrollY;

  if (level === 'province') {
    selectedProvince.value = null;
    selectedMunicipality.value = null;
    selectedBarangay.value = null;
    payoutSiteFilter.value = '';
    municipalitySearch.value = '';
  } else if (level === 'municipality') {
    selectedMunicipality.value = null;
    selectedBarangay.value = null;
  } else if (level === 'barangay') {
    selectedBarangay.value = null;
  }
    await fetchDashboard();
    await nextTick();
    window.scrollTo({ top: scrollPosition, behavior: 'auto' });
};

const setTab = (tab) => {
  activeTab.value = tab;
  disasterName.value = '';
  selectedProvince.value = null;
  selectedMunicipality.value = null;
  selectedBarangay.value = null;
  payoutSiteFilter.value = '';
  municipalitySearch.value = '';
  selectedDisasterType.value = '';
    selectedAssistanceType.value = '';
    assistanceMenuOpen.value = false;
  fetchDashboard();
};

const applyFilters = () => {
    fetchDashboard();
    if (appliedFrom.value && appliedTo.value) {
        appliedDateLabel.value = `from ${appliedFrom.value} to ${appliedTo.value}`;
    } else {
        appliedDateLabel.value = `as of ${new Date().toLocaleString()}`;
    }
};

// Typing a date only changes what is pending; Apply is what commits it.
const applyDateRange = (changedField) => {
    if (dateFrom.value && dateTo.value && dateTo.value < dateFrom.value) {
        if (changedField === 'from') dateTo.value = dateFrom.value;
        else dateFrom.value = dateTo.value;
    }
};

const hasPendingRange = computed(
    () => dateFrom.value !== appliedFrom.value || dateTo.value !== appliedTo.value
);

const commitDateRange = () => {
    appliedFrom.value = dateFrom.value;
    appliedTo.value = dateTo.value;
    applyFilters();
};

const clearDateRange = () => {
    dateFrom.value = '';
    dateTo.value = '';
    commitDateRange();
};

onMounted(() => document.addEventListener('click', closeDisasterMenuOnOutsideClick));
onUnmounted(() => document.removeEventListener('click', closeDisasterMenuOnOutsideClick));

onMounted(() => fetchDashboard());

/* ---------- Staying up to date ----------
   Asking the server for finished figures costs the page the live updates the
   old subscription gave it, so this watches for the two things that move them —
   an import arriving or leaving, and a target being edited — and asks again.
   Neither watch downloads the payout records. */
let stopWatching = () => {};

onMounted(() => {
    stopWatching = subscribeToChanges(() => fetchDashboard());
});

onUnmounted(() => stopWatching());
</script>

<style scoped>
* { box-sizing: border-box; }
button, input { font: inherit; }
button {
    width: 100%;
    padding: 13px;
    border: none;
    border-radius: 6px;
    background: #3730a3;
    color: white;
    font-size: 15px;
    font-weight: 600;
    cursor: pointer;
}
button:hover { background: #2e2789; }

.dashboard-page {
    flex: 1 1 auto;
    min-width: 0;
    min-height: 100vh;
    padding: 18px 30px 36px;
}

.dashboard-shell {
    width: 100%;
    max-width: none;
    margin: 0 auto;
    background: transparent;
    border: 0;
    padding: 0;
    border-radius: 0;
    box-shadow: none;
}

/* One container for the table (incl. Total row) and the Progress Overview */
.summary-panel {
    overflow: hidden;
    border: 1px solid #d5e0ea;
    border-radius: 10px;
    background: #fff;
    box-shadow: 0 4px 8px rgba(7, 32, 74, 0.18);
}

.dashboard-header {
    position: relative;
    overflow: visible;
    border-radius: 10px;
    padding: 34px 36px 20px;
    margin-bottom: 24px;
    min-height: 220px;
    background:
        linear-gradient(90deg, #F3BB2E 0%, #F39D2A 33.33%, #F28E27 66.67%, #DD4B3B 100%) top / 100% 7px no-repeat,
        linear-gradient(90deg, #2E3192 );
    box-shadow: 0 4px 8px rgba(7, 32, 74, 0.38);
}

.header-content { position: relative; z-index: 1; }

.header-logout {
    position: absolute;
    top: -12px;
    right: 0;
    display: inline-flex;
    align-items: center;
    gap: 8px;
    width: auto;
    padding: 8px 12px;
    border: 1px solid rgb(255 255 255 / 55%);
    background: transparent;
    color: #fff;
    font-size: 13px;
}

.header-logout:hover { background: rgb(255 255 255 / 14%); }

.brand {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    font-size: 16px;
    letter-spacing: 0;
    text-transform: uppercase;
    color: #9dc1f8;
    font-weight: 700;
}

.region-tag {
    display: inline-block;
    margin-top: 18px;
    padding: 0;
    border-radius: 0;
    background: none;
    color: #ffffff;
    font-size: 16px;
    font-weight: 600;
    letter-spacing: 0;
}

.title-row {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    gap: 24px;
    margin-top: 16px;
}

.title-row h1 {
    margin: 0;
    color: #fff;
    font-size: 50px;
    font-weight: 650;
    letter-spacing: 0;
    line-height: 1;
}

.progress-box {
    width: 360px;
    min-width: 360px;
    margin-top: 20px;
    text-align: left;
}

.header-progress-meta {
    display: flex;
    align-items: baseline;
    gap: 16px;
    margin-bottom: 4px;
    text-align: left;
}

.header-beneficiaries {
    color: #fff;
    font-size: 12px;
    font-weight: 600;
    line-height: 1.2;
    white-space: nowrap;
}

.header-progress-value {
    color: #ffffff;
    font-size: 50px;
    line-height: 1;
    font-weight: 600;
}

.header-progress-wrap {
    margin-top: 8px;
    width: 100%;
}

:deep(.header-progress) {
    height: 6px;
    border-radius: 999px;
    background: #06336f;
    overflow: hidden;
}

:deep(.header-progress .p-progressbar-value) {
    background: #ffae1a;
    border-radius: 999px;
}

.header-updated {
    display: flex;
    flex-direction: column;
    align-items: flex-end;
    gap: 6px;
    margin-top: 4px;
    color: #fff;
    font-size: 13px;
    line-height: 1.2;
    text-align: right;
}

.header-updated span {
    display: inline-flex;
    align-items: center;
    gap: 4px;
    font-weight: 600;
    margin-top: 10px;
}

.header-updated strong {
    font-size: 13px;
    font-weight: 600;
}

.tab-row {
    display: flex;
    align-items: center;
    gap: 10px;
    background: transparent;
    padding: 0;
    border-radius: 0;
    border: 0;
    margin: 5px 0 0;
    position: relative;
    z-index: 1;
}

.tab {
    display: inline-flex;
    flex: 0 0 auto;
    align-items: center;
    justify-content: center;
    gap: 7px;
    box-sizing: border-box;
    width: auto;
    height: 34px;
    min-width: 82px;
    padding: 0 20px;
    border-radius: 8px;
    border: 1px solid #fff;
    background: transparent;
    color: #fff;
    font-weight: 700;
    font-size: 12px;
    cursor: pointer;
}

.tab i { font-size: 12px; }

.tab.active {
    background: #fff;
    border-color: #fff;
    color: #0b247f;
    font-weight: 900;
    letter-spacing: 0.04em;
    box-shadow: 0 2px 8px rgba(5, 37, 87, 0.24);
}

.program-disaster-control {
    position: relative;
    display: flex;
    align-items: center;
    gap: 8px;
    padding: 0;
    border-radius: 8px;
}

.program-disaster-control.active {
    gap: 8px;
    background: transparent;
    box-shadow: none;
}

.program-disaster-control.active .tab {
    border-color: #fff;
    background: #fff;
    color: #0b247f;
    font-weight: 900;
    box-shadow: 0 2px 8px rgba(5, 37, 87, 0.24);
}

.disaster-type-badge {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 98px;
    height: 24px;
    padding: 0 10px;
    border-radius: 6px;
    background:#2e3192;
    color: #ffffff;
    font-size: 11px;
    font-weight: 700;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

.ect-control,
.aics-control {
    position: relative;
}

.ect-tab,
.aics-tab {
    gap: 8px;
}

.aics-tab {
    height: 36px;
    min-width: 72px;
    padding: 0 12px;
    font-size: 11px;
}

.ect-chevron {
    font-size: 10px;
    margin-left: 2px;
    transition: transform 0.15s ease;
}

.ect-chevron.open {
    transform: rotate(180deg);
}

.disaster-menu {
    position: absolute;
    top: calc(100% + 6px);
    left: 0;
    z-index: 10;
    min-width: 200px;
    padding: 6px;
    border-radius: 8px;
    background: #073a91;
    border: 1px solid #6d86ed;
    box-shadow: 0 6px 16px rgba(5, 37, 87, 0.35);
    display: flex;
    flex-direction: column;
    gap: 2px;
}

.disaster-menu-item {
    width: 100%;
    padding: 8px 10px;
    border: none;
    border-radius: 6px;
    background: transparent;
    color: #fff;
    font-size: 12px;
    font-weight: 600;
    text-align: left;
    cursor: pointer;
}

.disaster-menu-item:hover:not(:disabled) {
    background: #0649b9;
}

.disaster-menu-item.selected {
    background: #0649b9;
    font-weight: 800;
}

.disaster-menu-clear {
    color: #b9c8f5;
    font-weight: 500;
    border-bottom: 1px solid rgba(255, 255, 255, 0.15);
    border-radius: 6px 6px 0 0;
    margin-bottom: 4px;
}

.disaster-menu-clear:disabled {
    opacity: 0.4;
    cursor: default;
}

.disaster-type-badge {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: auto;
    min-width: 98px;
    max-width: 220px;
    height: 24px;
    padding: 16px;
    border-radius: 6px;
    background: #ffffff;
    color: #063b95;
    font-size: 13px;
    font-weight: 700;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

.dashboard-footer {
    width: calc(100% + 60px);
    margin: 36px -30px -36px;
    padding: 8px 18px;
    background: #000;
    color: #fff;
    font-size: 10px;
    line-height: 1.35;
    text-align: center;
}

.dashboard-footer p { margin: 0; }

@media (max-width: 768px) {
    .dashboard-header { padding: 28px 22px 20px; }

  .title-row {
      flex-direction: column;
      align-items: flex-start;
      gap: 16px;
  }

    .title-row h1 { font-size: 40px; }

  .progress-box {
      width: 100%;
      min-width: 0;
      text-align: left;
  }

  .header-updated {
      align-items: flex-start;
      text-align: left;
  }

    .dashboard-page { padding: 12px 16px 24px; }

  .dashboard-footer {
      width: calc(100% + 32px);
      margin: 36px -16px -24px;
            padding: 8px 12px;
  }
}

.dashboard-error {
    display: flex;
    align-items: center;
    gap: 10px;
    margin: 0 0 16px;
    padding: 12px 16px;
    border: 1px solid #f2b8b5;
    border-radius: 8px;
    background: #fdecec;
    color: #b3261e;
    font-size: 14px;
}

/* Not a failure: the figures loaded, but something about them is worth saying. */
.dashboard-note {
    display: flex;
    align-items: center;
    gap: 10px;
    margin: 0 0 16px;
    padding: 10px 16px;
    border: 1px solid #f3d9a4;
    border-radius: 8px;
    background: #fdf6e7;
    color: #8a5a00;
    font-size: 14px;
}

.dashboard-error button {
    margin-left: auto;
    padding: 6px 12px;
    border: 1px solid #b3261e;
    border-radius: 6px;
    background: #fff;
    color: #b3261e;
    cursor: pointer;
    font: inherit;
    font-size: 13px;
}

.dashboard-error button:hover {
    background: #b3261e;
    color: #fff;
}
</style>
