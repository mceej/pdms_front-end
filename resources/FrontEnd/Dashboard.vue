﻿<template>
 <div class="dashboard-page">
 <div class="welcome-bar">
 <div class="welcome-user">
 <span class="welcome-user-icon"><i class="pi pi-user"></i></span>
 <strong>Welcome, User (name)</strong>
 </div>
 <div class="welcome-actions">
 <span>Welcome to,</span>
 <button type="button" class="logout-button" @click="emit('logout')">
 <i class="pi pi-sign-out"></i>
 Log out
 </button>
 </div>
 </div>

 <div class="dashboard-shell">
 <nav class="dashboard-nav" aria-label="Main navigation">
 <button
 type="button"
 :class="['view-tab', { active: dashboardView === 'dashboard' }]"
 @click="dashboardView = 'dashboard'"
 >
 Dashboard
 </button>
 <button
 type="button"
 :class="['view-tab', { active: dashboardView === 'server-list' }]"
 @click="dashboardView = 'server-list'"
 >
 Server List
 </button>
 </nav>

 <header class="dashboard-header">
 <div class="header-content">
 <span class="brand">
 <i class="pi"></i> DSWD • PAYOUT SYSTEM
 </span>

 <div class="title-row">
 <div>
 <h1>{{ dashboardView === 'dashboard' ? 'DSWD Assist Track Dashboard' : 'DSWD Assist Track' }}</h1>
 <span class="region-tag">{{ dashboardView === 'dashboard' ? regionName : 'Server List' }}</span>
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
 <strong>{{ formatUpdatedAt(currentTime) }}</strong>
 </div>
 </div>
 </div>

 <div v-if="dashboardView === 'dashboard'" class="tab-row">
 <button type="button" class="tab" :class="{ active: activeTab === 'AICS' }" @click="setTab('AICS')">
 AICS
 </button>

 <div :class="['program-disaster-control', { active: activeTab === 'ECT' }]">
 <button
 type="button"
 :class="['tab', { active: activeTab === 'ECT' }]"
 @click="setTab('ECT')"
 >
 ECT
 </button>

 <div v-if="activeTab === 'ECT'" class="disaster-control">
 <Select
 v-model="selectedDisasterType"
 :options="disasterOptions"
 placeholder="Type of Disaster"
 showClear
 panelClass="disaster-select-panel"
 class="disaster-select"
 @change="applyFilters"
 />
 </div>
 </div>
 </div>
 </div>
 </header>

 <template v-if="dashboardView === 'dashboard'">
 <DashboardOverview
 :total-target="totalTarget"
 :total-disbursed="totalDisbursed"
 :total-paid-count="totalPaidCount"
 :total-balance="totalBalance"
 :unpaid-balance="unpaidBalance"
 :unpaid-disbursed="unpaidDisbursed"
 />

 <DashboardTable
 :breadcrumb-items="breadcrumbItems"
 :active-level="activeLevel"
 :municipality-search="municipalitySearch"
 :payout-site-filter="payoutSiteFilter"
 :payout-site-options="payoutSiteOptions"
 :rows-with-progress="rowsWithProgress"
 :total-table-target="totalTableTarget"
 :total-table-paid="totalTablePaid"
 :total-progress="totalProgress"
 @update:municipalitySearch="municipalitySearch = $event"
 @update:payoutSiteFilter="payoutSiteFilter = $event"
 @apply-filters="applyFilters"
 @row-click="handleRowClick"
 @home-click="goToLevel('province')"
 />

 <ComparisonCharts
 :selected-barangay="selectedBarangay"
 :chart-markers="chartMarkers"
 :comparison-rows="comparisonRows"
 :api-loaded="apiLoaded"
 :api-rows="apiRows"
 :applied-date-label="appliedDateLabel"
 :date-from="dateFrom"
 :date-to="dateTo"
 :dashboard-progress="dashboardProgress"
 :total-paid-count="totalPaidCount"
 :total-target="totalTarget"
 :comparison-color="comparisonColor"
 @update:dateFrom="(value) => { dateFrom = value; applyDateRange('from'); }"
 @update:dateTo="(value) => { dateTo = value; applyDateRange('to'); }"
 />
 </template>

 <ServerListPage
 v-else
 :current-time="currentTime"
 :tabs="tabs"
 :served-list-form="servedListForm"
 :served-province-options="servedProvinceOptions"
 :served-municipality-options="servedMunicipalityOptions"
 :served-barangay-options="servedBarangayOptions"
 :is-uploading="isUploading"
 :served-list-message="servedListMessage"
 :served-list-error="servedListError"
 :served-list-rows="servedListRows"
 :served-list-date-from="servedListDateFrom"
 :served-list-date-to="servedListDateTo"
 :applied-served-list-date-label="appliedServedListDateLabel"
 @upload-served-list="uploadServedList"
 @select-served-list-file="selectServedListFile"
 @update:servedListDateFrom="(value) => { servedListDateFrom = value; applyServedListDateFilter(); }"
 @update:servedListDateTo="(value) => { servedListDateTo = value; applyServedListDateFilter(); }"
 @apply-served-list-date-filter="applyServedListDateFilter"
 />
 </div>
 </div>
</template>

<script setup>
import { computed, nextTick, onMounted, onUnmounted, reactive, ref } from 'vue';
import ComparisonCharts from './dashboard/ComparisonCharts.vue';
import DashboardOverview from './dashboard/DashboardOverview.vue';
import DashboardTable from './dashboard/DashboardTable.vue';
import { fetchPayoutDashboard } from './mock/payoutDashboard.js';
import ServerListPage from './serverList/ServerListPage.vue';

const emit = defineEmits(['logout']);

const dashboardView = ref('dashboard');
const activeTab = ref('AICS');
const disasterName = ref('');
const selectedDisasterType = ref('');
const payoutSiteFilter = ref('');
const municipalitySearch = ref('');
const dateFrom = ref('');
const dateTo = ref('');
const appliedDateLabel = ref('as of 9/14/2026 | 10:30:23 AM');
const currentTime = ref(new Date().toLocaleString());
let clockTimer;

const formatCount = (value) => {
 if (value === null || value === undefined || value === '' || value === '-----') return '0';
 return Number(value).toLocaleString();
};

const formatUpdatedAt = (value) => {
 const date = new Date(value);
 if (Number.isNaN(date.getTime())) return value;
 return `${date.toLocaleDateString('en-US')} | ${date.toLocaleTimeString('en-US', {
 hour: 'numeric',
 minute: '2-digit',
 })}`;
};

const apiRows = ref([]);
const apiLoaded = ref(false);
const apiSummary = ref({ target: 0, paid: 0, remaining: 0, target_amount: 0, amount_disbursed: 0, unpaid_amount: 0, progress: 0 });
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
const apiPayoutSites = ref([]);
const isUploading = ref(false);
const servedListMessage = ref('');
const servedListError = ref(false);
const servedListRows = ref([]);
const servedListForm = reactive({ program: '', province: '', municipality: '', barangay: '', file: null });
const servedListDateFrom = ref('');
const servedListDateTo = ref('');
const appliedServedListDateLabel = ref(`as of ${currentTime.value}`);

const applyServedListDateFilter = () => {
 if (servedListDateFrom.value && servedListDateTo.value && servedListDateTo.value < servedListDateFrom.value) {
 servedListDateTo.value = servedListDateFrom.value;
 }
 appliedServedListDateLabel.value = servedListDateFrom.value && servedListDateTo.value
 ? `from ${servedListDateFrom.value} to ${servedListDateTo.value}`
 : `as of ${currentTime.value}`;
};

const regionName = ref('REGION XI');
const tabs = ['AICS', 'ECT'];
const selectedProvince = ref(null);
const selectedMunicipality = ref(null);
const selectedBarangay = ref(null);

const createDummyProvinces = () => {
 const letters = ['A', 'B', 'C', 'D', 'E'];
 const provinceTargets = [5890, 5000, 5700, 5000, 5890];
 const provincePaid = [2500, 3750, 3980, 3750, 2500];

 return letters.map((provinceLetter, provinceIndex) => ({
 name: `Province ${provinceLetter}`,
 target: provinceTargets[provinceIndex],
 paid: provincePaid[provinceIndex],
 municipalities: letters.map((municipalityLetter, municipalityIndex) => {
 const target = 900 + (municipalityIndex * 120) + (provinceIndex * 80);
 const paid = Math.round(target * ([0.42, 0.55, 0.68, 0.75, 0.84][municipalityIndex]));

 return {
 name: `Municipality ${municipalityLetter}`,
 target,
 paid,
 payoutSite: `Site ${(municipalityIndex % 3) + 1}`,
 barangays: letters.map((barangayLetter, barangayIndex) => {
 const barangayTarget = 180 + (barangayIndex * 55) + (municipalityIndex * 30);
 const barangayPaid = Math.round(barangayTarget * ([0.38, 0.5, 0.62, 0.74, 0.86][barangayIndex]));

 return {
 name: `Barangay ${barangayLetter}`,
 target: barangayTarget,
 paid: barangayPaid,
 beneficiaries: barangayPaid,
 };
 }),
 };
 }),
 }));
};

const dashboardData = {
 AICS: { target: 27480, provinces: createDummyProvinces() },
 ECT: { target: 27480, provinces: createDummyProvinces() },
};

const activeData = computed(() => dashboardData[activeTab.value]);
const servedProvinceOptions = computed(() => dashboardData[servedListForm.program || activeTab.value].provinces.map((province) => province.name));
const servedMunicipalityOptions = computed(() => {
 const province = dashboardData[servedListForm.program || activeTab.value].provinces.find((item) => item.name === servedListForm.province);
 return (province?.municipalities || []).map((municipality) => municipality.name);
});
const servedBarangayOptions = computed(() => {
 const province = dashboardData[servedListForm.program || activeTab.value].provinces.find((item) => item.name === servedListForm.province);
 const municipality = province?.municipalities.find((item) => item.name === servedListForm.municipality);
 return (municipality?.barangays || []).map((barangay) => barangay.name);
});

const totalTarget = computed(() => apiLoaded.value ? apiSummary.value.target : activeData.value.target);
const totalDisbursed = computed(() => {
 if (apiLoaded.value) return `₱${Number(apiSummary.value.amount_disbursed).toLocaleString()}`;
 const sum = activeData.value.provinces.reduce((acc, p) => acc + p.paid, 0);
 return sum > 0 ? `₱${sum.toLocaleString()}` : '-----';
});
const totalPaidCount = computed(() => {
 if (apiLoaded.value) return apiSummary.value.paid;
 const sum = activeData.value.provinces.reduce((acc, p) => acc + p.paid, 0);
 return sum > 0 ? sum.toLocaleString() : '-----';
});
const totalBalance = computed(() => {
 if (apiLoaded.value) return `₱${Number(apiSummary.value.target_amount || 0).toLocaleString()}`;
 return `₱${Number(totalTarget.value || 0).toLocaleString()}`;
});
const unpaidBalance = computed(() => {
 if (apiLoaded.value) return `₱${Math.max(Number(apiSummary.value.target_amount || 0) - Number(apiSummary.value.amount_disbursed || 0), 0).toLocaleString()}`;
 return formatCurrency(Math.max(Number(totalTarget.value || 0) - Number(totalPaidCount.value || 0), 0));
});
const unpaidDisbursed = computed(() => {
 if (apiLoaded.value) return `₱${Number(apiSummary.value.unpaid_amount || 0).toLocaleString()}`;
 return formatCurrency(Math.max(Number(totalTarget.value || 0) - Number(totalPaidCount.value || 0), 0));
});
const dashboardProgress = computed(() => {
 if (apiLoaded.value) return apiSummary.value.progress;
 const target = totalTarget.value || 0;
 const paidSum = activeData.value.provinces.reduce((acc, p) => acc + p.paid, 0);
 return target ? Math.round((paidSum / target) * 100) : 0;
});

const formatCurrency = (value) => `₱${Number(value || 0).toLocaleString()}`;

const comparisonRows = computed(() => apiLoaded.value ? apiRows.value : activeData.value.provinces.map((row, index) => ({
 id: index,
 name: row.name,
 target: row.target,
 paid: row.paid,
 progress: row.target ? Math.round((row.paid / row.target) * 100) : 0,
})));

const activeLevel = computed(() => {
 if (selectedBarangay.value) return 'detail';
 if (selectedMunicipality.value) return 'barangay';
 if (selectedProvince.value) return 'municipality';
 return 'province';
});

const currentTableRows = computed(() => {
 if (apiLoaded.value) {
 const search = municipalitySearch.value.trim().toLowerCase();
 return activeLevel.value === 'municipality' && search
 ? apiRows.value.filter((row) => row.name.toLowerCase().includes(search))
 : apiRows.value;
 }

 if (activeLevel.value === 'province') return activeData.value.provinces;
 if (activeLevel.value === 'municipality') {
 const munis = selectedProvince.value?.municipalities || [];
 const filteredBySite = payoutSiteFilter.value
 ? munis.filter((m) => m.payoutSite === payoutSiteFilter.value)
 : munis;
 const search = municipalitySearch.value.trim().toLowerCase();
 return search
 ? filteredBySite.filter((municipality) => municipality.name.toLowerCase().includes(search))
 : filteredBySite;
 }
 if (activeLevel.value === 'barangay') return selectedMunicipality.value?.barangays || [];
 return [];
});

const rowsWithProgress = computed(() =>
 currentTableRows.value.map((row) => ({
 ...row,
 progress: row.target ? Math.round((row.paid / row.target) * 100) : 0,
 }))
);

const totalTableTarget = computed(() =>
 currentTableRows.value.reduce((sum, r) => sum + (r.target || 0), 0)
);
const totalTablePaid = computed(() =>
 currentTableRows.value.reduce((sum, r) => sum + (r.paid || 0), 0)
);
const totalProgress = computed(() =>
 totalTableTarget.value ? Math.round((totalTablePaid.value / totalTableTarget.value) * 100) : 0
);

const payoutSiteOptions = computed(() => {
 if (apiLoaded.value) return apiPayoutSites.value;
 if (!selectedProvince.value) return [];
 const sites = new Set(selectedProvince.value.municipalities.map((m) => m.payoutSite));
 return Array.from(sites);
});

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
 if (selectedDisasterType.value) filters.disaster_type = selectedDisasterType.value;
 if (selectedProvince.value?.id) filters.province_id = selectedProvince.value.id;
 if (selectedMunicipality.value?.id) filters.municipality_id = selectedMunicipality.value.id;
 if (selectedBarangay.value?.id) filters.barangay_id = selectedBarangay.value.id;
 if (payoutSiteFilter.value) filters.payout_site = payoutSiteFilter.value;
 if (dateFrom.value) filters.from = dateFrom.value;
 if (dateTo.value) filters.to = dateTo.value;

 const payload = await fetchPayoutDashboard(filters);
 apiRows.value = payload.rows || [];
 apiSummary.value = payload.summary || apiSummary.value;
 apiDisasterTypes.value = payload.disaster_types || [];
 apiPayoutSites.value = payload.payout_sites || [];
 apiLoaded.value = Boolean(apiRows.value.length);
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
 fetchDashboard();
};

const applyFilters = () => {
 fetchDashboard();
 if (dateFrom.value && dateTo.value) {
 appliedDateLabel.value = `from ${dateFrom.value} to ${dateTo.value}`;
 } else {
 appliedDateLabel.value = 'as of 9/14/2026 | 10:30:23 AM';
 }
};

const applyDateRange = (changedField) => {
 if (dateFrom.value && dateTo.value && dateTo.value < dateFrom.value) {
 if (changedField === 'from') dateTo.value = dateFrom.value;
 else dateFrom.value = dateTo.value;
 }
 applyFilters();
};

const selectServedListFile = (event) => {
 servedListForm.file = event.target.files?.[0] || null;
 servedListMessage.value = '';
 servedListError.value = false;
};

const uploadServedList = async () => {
 servedListError.value = true;
 servedListMessage.value = 'Uploading a served list needs the PHP backend, which is not built yet.';
};

onMounted(() => fetchDashboard().catch((error) => console.error(error)));
onMounted(() => {
 clockTimer = window.setInterval(() => {
 currentTime.value = new Date().toLocaleString();
 }, 1000);
});

onUnmounted(() => window.clearInterval(clockTimer));
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
 min-height: 100vh;
 width: 100%;
 padding: 0 30px 36px;
}

.welcome-bar {
 display: flex;
 align-items: center;
 justify-content: space-between;
 width: calc(100% + 60px);
 min-height: 46px;
 margin: 0 -30px 16px;
 padding: 0 22px;
 background: #171b82;
 color: #fff;
 box-shadow: 0 2px 5px rgba(12, 23, 92, 0.3);
 font-size: 12px;
}

.welcome-user,
.welcome-actions {
 display: inline-flex;
 align-items: center;
 gap: 9px;
}

.welcome-actions {
 gap: 12px;
}

.logout-button {
 display: inline-flex;
 align-items: center;
 gap: 7px;
 width: auto;
 padding: 7px 12px;
 border: 1px solid rgba(255, 255, 255, 0.35);
 border-radius: 6px;
 background: rgba(255, 255, 255, 0.12);
 color: #fff;
 font-size: 12px;
 font-weight: 700;
}

.logout-button:hover {
 background: rgba(255, 255, 255, 0.22);
}

.welcome-user-icon {
 display: inline-flex;
 align-items: center;
 justify-content: center;
 width: 25px;
 height: 25px;
 border: 2px solid #fff;
 border-radius: 50%;
 font-size: 12px;
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

.dashboard-nav {
 display: flex;
 align-items: center;
 gap: 12px;
 margin-bottom: 18px;
 padding: 6px;
 border-radius: 14px;
 background: rgba(255, 255, 255, 0.72);
 border: 1px solid rgba(12, 35, 77, 0.08);
 box-shadow: 0 10px 26px rgba(16, 40, 78, 0.06);
 width: fit-content;
}

.view-tab {
 width: auto;
 min-width: 132px;
 padding: 10px 18px;
 border: none;
 border-radius: 10px;
 background: transparent;
 color: #35507d;
 font-weight: 700;
 font-size: 0.82rem;
 letter-spacing: 0.02em;
 cursor: pointer;
 transition: all 0.2s ease;
}

.view-tab.active {
 background: linear-gradient(135deg, #0d234a 0%, #1647a5 100%);
 color: #ffffff;
 box-shadow: 0 8px 18px rgba(18, 52, 112, 0.2);
}

.view-tab:hover {
 background: rgba(17, 58, 134, 0.06);
}

.view-tab.active:hover {
 background: linear-gradient(135deg, #0d234a 0%, #1647a5 100%);
}

.dashboard-header {
 position: relative;
 overflow: visible;
 border-radius: 10px;
 padding: 30px 34px 24px;
 margin-bottom: 24px;
 background: linear-gradient(135deg, #052557 0%, #073a91 45%, #0649b9 100%);
 box-shadow: 0 8px 24px rgba(7, 32, 74, 0.35);
 border-top: 5px solid transparent;
 border-image: linear-gradient(90deg, #f28b27 0%, #f28b27 72%, #e9482f 92%, #b71c3c 100%) 1;
}

.header-content { position: relative; z-index: 1; }

.brand {
 display: inline-flex;
 align-items: center;
 gap: 8px;
 font-size: 12px;
 letter-spacing: 0.12em;
 text-transform: uppercase;
 color: #f5bd18;
 font-weight: 800;
}

.region-tag {
 display: inline-block;
 margin-top: 6px;
 padding: 3px 10px;
 border-radius: 999px;
 background: rgba(255,255,255,0.12);
 color: #cfe0fb;
 font-size: 11px;
 font-weight: 700;
 letter-spacing: 0.05em;
}

.title-row {
 display: flex;
 justify-content: space-between;
 align-items: end;
 gap: 18px;
 margin-top: 12px;
}

.title-row h1 {
 margin: 0;
 color: #fff;
 font-size: clamp(2rem, 4vw, 3.2rem);
 font-weight: 800;
 letter-spacing: 0;
 line-height: 1;
}

.progress-box {
 min-width: 340px;
 text-align: left;
}

.header-progress-meta {
 display: flex;
 align-items: baseline;
 gap: 16px;
 margin-bottom: 6px;
 text-align: left;
}

.header-beneficiaries {
 color: #fff;
 font-size: 11px;
 white-space: nowrap;
}

.header-progress-value {
 color: #fff;
 font-size: clamp(1.7rem, 2.8vw, 2.5rem);
 line-height: 1;
 font-weight: 800;
}

.header-updated {
 display: flex;
 flex-direction: column;
 align-items: flex-start;
 gap: 3px;
 margin-top: 10px;
 color: #fff;
 font-size: 11px;
 line-height: 1.2;
}

.header-updated span {
 display: inline-flex;
 align-items: center;
 gap: 4px;
}

.header-updated strong {
 font-size: 11px;
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
 margin: 28px 0 0;
 position: relative;
 z-index: 1;
}

.tab {
 display: inline-flex;
 flex: 0 0 auto;
 align-items: center;
 justify-content: center;
 box-sizing: border-box;
 width: auto;
 height: 36px;
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

.dashboard-as-of {
 margin: -14px 0 18px;
 color: #5b7288;
 font-size: 12px;
 font-weight: 500;
 text-align: right;
}

.disaster-control {
 position: absolute;
 top: 0;
 left: calc(100% + 8px);
 z-index: 10;
 min-width: 260px;
 padding: 4px;
 border: 0;
 border-radius: 8px;
 background: #073a91;
 box-shadow: 0 4px 10px rgba(5, 37, 87, 0.2);
}

.disaster-control :deep(.p-select) {
 width: 100%;
 min-height: 34px;
 border: 1px solid #6d86ed;
 border-radius: 8px;
 background: #073a91;
 box-shadow: inset 0 1px 2px rgba(5, 37, 87, 0.08);
}

.disaster-control :deep(.p-select-label) {
 color: #fff;
 font-size: 12px;
}

.disaster-control :deep(.p-select-dropdown) {
 color: #fff;
}

:deep(.disaster-select-panel) {
 border: 1px solid #6d86ed;
 background: #073a91;
 color: #fff;
}

:deep(.disaster-select-panel .p-select-option) {
 color: #fff;
}

:deep(.disaster-select-panel .p-select-option:hover),
:deep(.disaster-select-panel .p-select-option.p-focus) {
 background: #0649b9;
 color: #fff;
}

@media (max-width: 768px) {
 .welcome-bar {
 width: calc(100% + 32px);
 margin-left: -16px;
 margin-right: -16px;
 padding: 0 16px;
 }

 .title-row {
 flex-direction: column;
 align-items: flex-start;
 gap: 16px;
 }

 .progress-box {
 width: 100%;
 min-width: 0;
 text-align: left;
 }

 .dashboard-page {
 padding: 18px 16px 24px;
 }
}
</style>