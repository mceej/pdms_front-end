<template>
    <div class="app-shell">
        <div v-if="currentView === 'login'" class="login-page">
            <div class="login-card">
                <div class="left-panel">
                    <div class="logo-row">
                        <img src="/logo/dswdlogo2.png" alt="DSWD Logo" class="logo-img" />
                        <div class="logo-divider"></div>
                        <img src="/logo/dswdlogo.png" alt="Field Office Logo" class="logo-img" />
                    </div>

                    <div class="welcome-text">
                        <h1>Welcome</h1>
                        <p>Please login to your account to continue</p>
                    </div>

                    <form @submit.prevent="login">
                        <div class="form-group">
                            <label for="username">Username</label>
                            <input
                                id="username"
                                type="text"
                                v-model="email"
                                placeholder="Username"
                                :class="{ 'input-error': errors.email }"
                            />
                            <p v-if="errors.email" class="error-text">The username field is required.</p>
                        </div>

                        <div class="form-group">
                            <label for="password">Password</label>
                            <input
                                id="password"
                                type="password"
                                v-model="password"
                                placeholder="Password"
                                :class="{ 'input-error': errors.password }"
                            />
                            <p v-if="errors.password" class="error-text">The password field is required.</p>
                        </div>

                        <div class="form-options">
                            <label class="checkbox-label">
                                <input
                                    type="checkbox"
                                    v-model="rememberMe"
                                    :class="{ 'checkbox-error': errors.rememberMe }"
                                />
                                I have read and agree to the
                                <a href="#">Privacy Policy</a>
                            </label>
                            <p v-if="errors.rememberMe" class="error-text">You must agree to the Privacy Policy.</p>
                        </div>

                        <button type="submit">Login</button>

                        <p class="help-text">
                            Need help? Send a ticket at
                            <a href="#">https://ticketport.dswdfo11.ph</a>
                        </p>
                    </form>
                </div>

                <div class="right-panel">
                    <div class="image-placeholder"></div>
                    <div class="image-overlay"></div>
                </div>
            </div>
        </div>

        <div v-else class="dashboard-page">
            <div class="dashboard-shell">
                <header class="dashboard-header">
  <div class="header-pattern"></div>
  <div class="header-content">
    <span class="brand">
      <i class="pi pi-shield"></i> DSWD • PAYOUT SYSTEM
    </span>
    <div class="title-row">
      <div>
        <h1>DSWD Assist Track Dashboard</h1>
        <span class="region-tag">{{ regionName }}</span>
      </div>
      <div class="progress-box">
                <div class="header-progress-wrap">
                    <ProgressBar :value="dashboardProgress" :showValue="false" class="header-progress" />
                    <span class="header-progress-value">{{ dashboardProgress }}%</span>
                </div>
                <small>{{ totalPaidCount.toLocaleString() }} out of {{ totalTarget?.toLocaleString() }}</small>
      </div>
    </div>
    <div class="tab-row">
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
                    class="disaster-select"
                    @change="applyFilters"
                />
                </div>
            </div>
    </div>
  </div>
</header>

                <div class="dashboard-as-of">as of {{ currentTime }}</div>

                <section class="stats-grid">
  <div class="metric-card">
    <div class="metric-icon target"><i class="pi pi-bullseye"></i></div>
    <div class="metric-label">Target Number per Payout Site</div>
    <div class="metric-value">{{ totalTarget?.toLocaleString() }}</div>
  </div>
  <div class="metric-card">
    <div class="metric-icon disbursed"><i class="pi pi-wallet"></i></div>
    <div class="metric-label">Total Amount Disbursed</div>
    <div class="metric-value">{{ totalDisbursed }}</div>
  </div>
  <div class="metric-card">
    <div class="metric-icon paid"><i class="pi pi-check-circle"></i></div>
    <div class="metric-label">Paid</div>
    <div class="metric-value">{{ totalPaidCount }}</div>
  </div>
</section>

                <div class="table-panel">
                    <div class="table-header">
                        <div class="table-header-main">
                            <Breadcrumb :model="breadcrumbItems" class="dashboard-breadcrumb" />

                            <div v-if="activeLevel === 'municipality'" class="table-header-actions">
                                <InputText
                                    v-model="municipalitySearch"
                                    placeholder="Search municipality"
                                    class="municipality-search"
                                />
                                <Select
                                    v-model="payoutSiteFilter"
                                    :options="payoutSiteOptions"
                                    placeholder="Payout Site"
                                    showClear
                                    class="payout-select"
                                />
                                <Button label="Apply" size="small" @click="applyFilters" />
                            </div>
                        </div>
                    </div>

                    <DataTable
                        :value="rowsWithProgress"
                        @row-click="(event) => handleRowClick(event.data)"
                        rowHover
                        class="dashboard-table"
                        :class="{ 'clickable-rows': activeLevel !== 'detail' }"
                    >
                        <Column field="name" header="Name" />
                        <Column field="target" header="Total Target">
                            <template #body="{ data }">
                                {{ data.target ? data.target.toLocaleString() : '-----' }}
                            </template>
                        </Column>
                        <Column field="paid" header="Paid">
                            <template #body="{ data }">
                                <div class="paid-cell">
                                    <span>{{ data.paid ? data.paid.toLocaleString() : '-----' }}</span>
                                </div>
                            </template>
                        </Column>
                        <Column header="Progress Bar">
                            <template #body="{ data }">
                                <div class="progress-cell">
                                    <div class="progress-cell-top">
                                        <span class="progress-pct">{{ data.target ? data.progress + '%' : '-----%' }}</span>
                                    </div>
                                    <ProgressBar
                                        :value="data.target ? data.progress : 0"
                                        :showValue="false"
                                        class="compact-progress"
                                    />
                                    <small class="progress-remaining">
                                        Remaining: {{ data.target ? (data.target - data.paid).toLocaleString() : '-----' }}
                                    </small>
                                </div>
                            </template>
                        </Column>

                        <template #footer>
                            <div class="total-row-footer">
                                <span class="total-label">Total</span>
                                <span class="total-target">{{ totalTableTarget ? totalTableTarget.toLocaleString() : '-----' }}</span>
                                <span class="total-paid">{{ totalTablePaid ? totalTablePaid.toLocaleString() : '-----' }}</span>
                                <div class="progress-cell total-progress">
                                    <span class="progress-pct">{{ totalTableTarget ? totalProgress + '%' : '-----%' }}</span>
                                    <ProgressBar
                                        :value="totalTableTarget ? totalProgress : 0"
                                        :showValue="false"
                                        class="compact-progress"
                                    />
                                    <small class="progress-remaining">
                                        Remaining: {{ totalTableTarget ? (totalTableTarget - totalTablePaid).toLocaleString() : '-----' }}
                                    </small>
                                </div>
                            </div>
                        </template>
                    </DataTable>
                </div>

                <section class="overall-progress-panel">
                    <div class="overall-progress-heading">
                        <div>
                            <span class="overall-progress-label">Overall Progress</span>
                            <strong>{{ dashboardProgress }}%</strong>
                        </div>
                        <span>{{ totalPaidCount.toLocaleString() }} paid of {{ totalTarget.toLocaleString() }} target</span>
                    </div>
                    <ProgressBar :value="dashboardProgress" :showValue="false" class="overall-progress-bar" />
                    <small>Remaining: {{ Math.max(Number(totalTarget || 0) - Number(totalPaidCount || 0), 0).toLocaleString() }}</small>
                </section>

                <section class="comparison-section">
                    <div class="comparison-header">
                        <div class="comparison-title-group">
                            <span>{{ selectedBarangay ? `${selectedBarangay.name} Analytics` : 'Data Comparison' }}</span>
                            <div v-if="!selectedBarangay" class="comparison-legend" aria-label="Chart legend">
                                <span><i class="legend-swatch current-swatch"></i>Current</span>
                                <span><i class="legend-swatch previous-swatch"></i>Previous</span>
                            </div>
                        </div>
                        <div class="date-filter">
                            <span>{{ appliedDateLabel }}</span>
                                <div class="date-range-inputs">
                                    <input
                                        v-model="dateFrom"
                                        type="date"
                                        aria-label="Choose comparison start date"
                                        :max="dateTo || undefined"
                                        @change="applyDateRange('from')"
                                    />
                                    <span>to</span>
                                    <input
                                        v-model="dateTo"
                                        type="date"
                                        aria-label="Choose comparison end date"
                                        :min="dateFrom || undefined"
                                        @change="applyDateRange('to')"
                                    />
                                </div>
                        </div>
                    </div>

                    <div v-if="selectedBarangay" class="chart-grid barangay-chart-grid">
                        <div class="chart-card barangay-chart-card">
                            <div class="chart-card-title">Progress</div>
                            <div class="chart-y-axis">
                                <span v-for="marker in chartMarkers" :key="marker">{{ marker }}</span>
                            </div>
                            <div class="bar-group single-bar-group">
                                <div class="bar-column">
                                    <div class="bar" :style="{ height: selectedBarangay.progress + 'px' }"></div>
                                </div>
                            </div>
                            <div class="x-axis">
                                <span>{{ selectedBarangay.name }}</span>
                            </div>
                        </div>

                        <div class="chart-card barangay-chart-card">
                            <div class="chart-card-title">Beneficiaries</div>
                            <div class="chart-y-axis">
                                <span v-for="marker in chartMarkers" :key="marker">{{ marker }}</span>
                            </div>
                            <div class="bar-group single-bar-group">
                                <div class="bar-column">
                                    <div class="bar alt" :style="{ height: Math.min(Number(selectedBarangay.beneficiaries || 0), 180) + 'px' }"></div>
                                </div>
                            </div>
                            <div class="x-axis">
                                <span>{{ selectedBarangay.beneficiaries }}</span>
                            </div>
                        </div>
                    </div>

                    <div v-else class="chart-grid">
                        <div class="chart-card">
                            <div class="chart-card-title">Current</div>
                            <div class="chart-y-axis">
                                <span v-for="marker in chartMarkers" :key="marker">{{ marker }}</span>
                            </div>
                            <div class="chart-state" v-if="apiLoaded && !apiRows.length">No payout records found for the selected filters.</div>
                            <div class="bar-group" v-for="(row, index) in comparisonRows" :key="row.id || index">
                                <div class="bar-column">
                                    <div class="bar" :style="{ height: Math.min(row.progress * 2, 200) + 'px', background: comparisonColor(index) }"></div>
                                    <span class="chart-column-label">{{ row.name }}</span>
                                </div>
                            </div>
                        </div>
                        <div class="chart-card">
                            <div class="chart-card-title">Previous</div>
                            <div class="chart-y-axis">
                                <span v-for="marker in chartMarkers" :key="marker">{{ marker }}</span>
                            </div>
                            <div class="chart-state" v-if="apiLoaded && !apiRows.length">No previous payout records available for the selected filters.</div>
                            <div class="bar-group" v-for="(row, index) in comparisonRows" :key="row.id || index">
                                <div class="bar-column">
                                    <div class="bar alt" :style="{ height: Math.min(row.progress * 2, 200) + 'px', background: comparisonColor(index, true) }"></div>
                                    <span class="chart-column-label">{{ row.name }}</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </section>
            </div>
        </div>
    </div>
</template>

<script setup>
import { computed, onMounted, reactive, ref } from 'vue';

const currentView = ref('login');
const email = ref('');
const password = ref('');
const rememberMe = ref(false);
const errors = reactive({ email: false, password: false, rememberMe: false });
const activeTab = ref('AICS');
const disasterName = ref(''); // ECT-only, per flowchart (auto-suggest + input, captured at import)
const selectedDisasterType = ref('');
const payoutSiteFilter = ref(''); // municipality-level filter, per spec
const dateFrom = ref('');
const dateTo = ref('');
const appliedDateLabel = ref('as of 9/14/2026 | 10:30:23 AM');
const apiRows = ref([]);
const apiLoaded = ref(false);
const apiSummary = ref({ target: 0, paid: 0, remaining: 0, amount_disbursed: 0, progress: 0 });
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

const regionName = ref('REGION XI'); // was hardcoded via CSS ::after — now real, dynamic data

const tabs = ['AICS', 'ECT'];
const selectedProvince = ref(null);
const selectedMunicipality = ref(null);
const selectedBarangay = ref(null);

// --- Mock data (replace with API response) ---
const dashboardData = {
  AICS: {
    target: 10000,
    provinces: [
      {
        name: 'MUNI A',
        target: 5890,
        paid: 2500,
        municipalities: [
          {
            name: 'Municipality A1',
            target: 1500,
            paid: 850,
            payoutSite: 'Site 1',
            barangays: [
              { name: 'Barangay A1-1', target: 520, paid: 300, beneficiaries: 280 },
              { name: 'Barangay A1-2', target: 480, paid: 260, beneficiaries: 240 },
              { name: 'Barangay A1-3', target: 500, paid: 290, beneficiaries: 260 },
            ],
          },
          {
            name: 'Municipality A2',
            target: 1200,
            paid: 620,
            payoutSite: 'Site 2',
            barangays: [
              { name: 'Barangay A2-1', target: 410, paid: 210, beneficiaries: 190 },
              { name: 'Barangay A2-2', target: 390, paid: 200, beneficiaries: 180 },
            ],
          },
        ],
      },
      { name: 'MUNI B', target: 0, paid: 0, municipalities: [] },
      { name: 'MUNI C', target: 0, paid: 0, municipalities: [] },
      { name: 'MUNI D', target: 0, paid: 0, municipalities: [] },
      { name: 'MUNI E', target: 0, paid: 0, municipalities: [] },
    ],
    comparisonLeft: [200, 150, 180, 120, 140],
    comparisonRight: [170, 150, 110, 130, 160],
  },
  ECT: {
    target: 8400,
    provinces: [
      {
        name: 'BRGY A',
        target: 4900,
        paid: 2100,
        municipalities: [
          {
            name: 'Municipality C1',
            target: 1200,
            paid: 560,
            payoutSite: 'Site 1',
            barangays: [
              { name: 'Barangay C1-1', target: 400, paid: 180, beneficiaries: 160 },
              { name: 'Barangay C1-2', target: 350, paid: 170, beneficiaries: 150 },
            ],
          },
        ],
      },
      { name: 'BRGY B', target: 0, paid: 0, municipalities: [] },
      { name: 'BRGY C', target: 0, paid: 0, municipalities: [] },
      { name: 'BRGY D', target: 0, paid: 0, municipalities: [] },
      { name: 'BRGY E', target: 0, paid: 0, municipalities: [] },
    ],
    comparisonLeft: [180, 160, 140, 130, 170],
    comparisonRight: [150, 170, 130, 120, 180],
  },
};

// --- Derived, per-tab data ---
const activeData = computed(() => dashboardData[activeTab.value]);
const totalTarget = computed(() => apiLoaded.value ? apiSummary.value.target : activeData.value.target);

// FIX: sum real paid/target instead of hardcoded '-----'
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

const dashboardProgress = computed(() => {
    if (apiLoaded.value) return apiSummary.value.progress;
  const target = totalTarget.value || 0;
  const paidSum = activeData.value.provinces.reduce((acc, p) => acc + p.paid, 0);
  return target ? Math.round((paidSum / target) * 100) : 0;
});

const comparisonLeft = computed(() => activeData.value.comparisonLeft);
const comparisonRight = computed(() => activeData.value.comparisonRight);
const comparisonRows = computed(() => apiLoaded.value ? apiRows.value : activeData.value.provinces.map((row, index) => ({
    id: index,
    name: row.name,
    progress: row.target ? Math.round((row.paid / row.target) * 100) : 0,
})));

const activeLevel = computed(() => {
  if (selectedBarangay.value) return 'detail';
  if (selectedMunicipality.value) return 'barangay';
  if (selectedProvince.value) return 'municipality';
  return 'province';
});

const currentTableRows = computed(() => {
    if (apiLoaded.value) return apiRows.value;
  if (activeLevel.value === 'province') return activeData.value.provinces;
  if (activeLevel.value === 'municipality') {
    const munis = selectedProvince.value.municipalities || [];
    // Municipality-level payout site filter (spec requirement, was missing)
    if (!payoutSiteFilter.value) return munis;
    return munis.filter((m) => m.payoutSite === payoutSiteFilter.value);
  }
  if (activeLevel.value === 'barangay') return selectedMunicipality.value.barangays || [];
  return [];
});

// FIX: real progress = paid / target, not an average of stored percentages
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

// Payout site options for the filter dropdown, derived from the selected province's municipalities
const payoutSiteOptions = computed(() => {
    if (apiLoaded.value) return apiPayoutSites.value;
  if (!selectedProvince.value) return [];
  const sites = new Set(selectedProvince.value.municipalities.map((m) => m.payoutSite));
  return Array.from(sites);
});

const progressCount = computed(() => apiSummary.value.paid || activeData.value.count || 0);
const progressTotal = computed(() => apiSummary.value.target || activeData.value.total || totalTarget.value);
const paidValue = computed(() => apiSummary.value.paid || totalPaidCount.value);
const chartMarkers = [200, 150, 100, 50, 0];
const comparisonPalette = ['#2588d2', '#f08a24', '#25a269', '#8a63d2', '#d14d72'];
const comparisonColor = (index, previous = false) => {
    const color = comparisonPalette[index % comparisonPalette.length];
    return previous ? `${color}99` : color;
};

const breadcrumbItems = computed(() => {
  const items = [{ label: regionName.value, command: () => goToLevel('province') }];
  if (selectedProvince.value) items.push({ label: selectedProvince.value.name, command: () => goToLevel('municipality') });
  if (selectedMunicipality.value) items.push({ label: selectedMunicipality.value.name, command: () => goToLevel('barangay') });
  if (selectedBarangay.value) items.push({ label: selectedBarangay.value.name });
  return items;
});

const fetchDashboard = async () => {
        const params = new URLSearchParams({ program: activeTab.value });
        if (selectedDisasterType.value) params.set('disaster_type', selectedDisasterType.value);
        if (selectedProvince.value?.id) params.set('province_id', selectedProvince.value.id);
        if (selectedMunicipality.value?.id) params.set('municipality_id', selectedMunicipality.value.id);
        if (selectedBarangay.value?.id) params.set('barangay_id', selectedBarangay.value.id);
        if (payoutSiteFilter.value) params.set('payout_site', payoutSiteFilter.value);
        if (dateFrom.value) params.set('from', dateFrom.value);
        if (dateTo.value) params.set('to', dateTo.value);

        const response = await fetch(`/api/payout-dashboard?${params.toString()}`);
        if (!response.ok) throw new Error('Unable to load payout dashboard data.');
        const payload = await response.json();
        apiRows.value = payload.rows || [];
        apiSummary.value = payload.summary || apiSummary.value;
        apiDisasterTypes.value = payload.disaster_types || [];
        apiPayoutSites.value = payload.payout_sites || [];
        apiLoaded.value = true;
};

const handleRowClick = (row) => {
  if (activeLevel.value === 'province') {
    selectedProvince.value = row;
    selectedMunicipality.value = null;
    selectedBarangay.value = null;
    payoutSiteFilter.value = '';
    fetchDashboard();
    return;
  }
  if (activeLevel.value === 'municipality') {
    selectedMunicipality.value = row;
    selectedBarangay.value = null;
    fetchDashboard();
    return;
  }
  if (activeLevel.value === 'barangay') {
    selectedBarangay.value = row;
        fetchDashboard();
  }
};

const goToLevel = (level) => {
  if (level === 'province') {
    selectedProvince.value = null;
    selectedMunicipality.value = null;
    selectedBarangay.value = null;
    payoutSiteFilter.value = '';
  } else if (level === 'municipality') {
    selectedMunicipality.value = null;
    selectedBarangay.value = null;
  } else if (level === 'barangay') {
    selectedBarangay.value = null;
  }
    fetchDashboard();
};

const setTab = (tab) => {
  activeTab.value = tab;
  disasterName.value = '';
  selectedProvince.value = null;
  selectedMunicipality.value = null;
  selectedBarangay.value = null;
  payoutSiteFilter.value = '';
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

const login = () => {
    errors.email = !email.value;
    errors.password = !password.value;
    errors.rememberMe = !rememberMe.value;
    if (!errors.email && !errors.password && !errors.rememberMe) currentView.value = 'dashboard';
};

onMounted(() => fetchDashboard().catch((error) => console.error(error)));
</script>

<style scoped>
* {
    box-sizing: border-box;
}

button,
input {
    font: inherit;
}

.app-shell {
    min-height: 100vh;
    background: #edf4fb;
    font-family: Arial, sans-serif;
    color: #111827;
}

.login-page {
    min-height: 100vh;
    display: flex;
    font-family: Arial, sans-serif;
    background: white;
}

.login-card {
    width: 100%;
    min-height: 100vh;
    background: white;
    display: flex;
    flex-wrap: wrap;
    overflow: hidden;
    padding: clamp(10px, 2vw, 20px);
    box-sizing: border-box;
    gap: clamp(10px, 2vw, 20px);
}

.left-panel {
    flex: 1 1 400px;
    padding: clamp(24px, 5vw, 48px) clamp(24px, 8vw, 80px);
    display: flex;
    flex-direction: column;
    justify-content: center;
    min-width: 0;
}

.logo-row {
    display: flex;
    justify-content: center;
    align-items: center;
    gap: 20px;
    margin-bottom: 32px;
}

.logo-img {
    height: 90px;
    width: auto;
    object-fit: contain;
    display: block;
}

.logo-divider {
    width: 2px;
    height: 90px;
    background-color: #9ca3af;
    flex-shrink: 0;
}

.welcome-text {
    text-align: center;
    margin-bottom: 32px;
}

.welcome-text h1 {
    font-size: 26px;
    margin-bottom: 6px;
    color: #1a1a2e;
}

.welcome-text p {
    color: #6b7280;
    font-size: 14px;
}

.form-group {
    margin-bottom: 20px;
}

.form-group label {
    display: block;
    margin-bottom: 8px;
    font-weight: 600;
    font-size: 14px;
    color: #1a1a2e;
}

.form-group input[type="text"],
.form-group input[type="password"] {
    width: 100%;
    padding: 12px;
    box-sizing: border-box;
    border: 1px solid #d1d5db;
    border-radius: 6px;
    font-size: 14px;
}

.form-options {
    margin-bottom: 25px;
    font-size: 13px;
}

.checkbox-label {
    display: flex;
    align-items: flex-start;
    gap: 8px;
    color: #4b5563;
    cursor: pointer;
}

.checkbox-label input[type="checkbox"] {
    margin-top: 2px;
}

.checkbox-label a {
    color: #3730a3;
    font-weight: 600;
    text-decoration: none;
}

.checkbox-error {
    accent-color: #ef4444;
}

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

button:hover {
    background: #2e2789;
}

.help-text {
    text-align: center;
    margin-top: 16px;
    font-size: 12px;
    color: #9ca3af;
}

.help-text a {
    color: #6b7280;
}

.form-group input.input-error {
    border-color: #ef4444;
}

.error-text {
    color: #ef4444;
    font-size: 12px;
    margin-top: 6px;
    margin-bottom: 0;
}

.right-panel {
    flex: 1 1 400px;
    min-height: 300px;
    position: relative;
    overflow: hidden;
    border-radius: 16px;
    display: flex;
    flex-direction: column;
    justify-content: center;
    align-items: center;
    gap: 20px;
}

.image-placeholder {
    width: 100%;
    height: 100%;
    min-height: 500px;
    background: url('/logo/dswdlogo3.png') center/cover no-repeat;
}

.image-overlay {
    position: absolute;
    inset: 0;
    background: linear-gradient(135deg, rgba(55, 48, 163, 0.15), rgba(30, 27, 90, 0.25));
    border-radius: 16px;
}

.dashboard-page {
    min-height: 100vh;
    width: 100%;
    padding: 26px 30px 36px;
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

.dashboard-header {
  position: relative;
  overflow: hidden;
  border-radius: 10px;
  padding: 32px 34px 26px;
  margin-bottom: 28px;
  background: linear-gradient(135deg, #052557 0%, #073a91 45%, #0649b9 100%);
  box-shadow: 0 8px 24px rgba(7, 32, 74, 0.35);
}

.dashboard-header {
  border-top: 5px solid #f5bd18; /* gold accent, ties to DSWD identity */
}

/* subtle geometric texture instead of a flat gradient */
.header-pattern {
  position: absolute;
  inset: 0;
  background-image:
    radial-gradient(circle at 85% 15%, rgba(245, 189, 24, 0.18) 0%, transparent 45%),
    repeating-linear-gradient(120deg, rgba(255,255,255,0.03) 0px, rgba(255,255,255,0.03) 2px, transparent 2px, transparent 40px);
  pointer-events: none;
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
    font-size: clamp(2rem, 4vw, 3.2rem);
    font-weight: 800;
    letter-spacing: 0;
    line-height: 1;
}

.dashboard-header::after {
    content: "REGION XI";
    display: block;
    margin-top: 12px;
    color: #fff;
    font-size: 12px;
    font-weight: 700;
}

.progress-box {
    min-width: 340px;
    text-align: right;
}

.progress-value {
    display: inline-block;
    font-size: 2.5rem;
    font-weight: 800;
    margin-bottom: 4px;
}

.progress-track {
    width: 100%;
    height: 14px;
    border-radius: 0;
    background: #071e51;
    overflow: hidden;
    margin-bottom: 6px;
}

.progress-track span {
    display: block;
    height: 100%;
    width: 80%;
    border-radius: 0;
    background: #f6be19;
}

.progress-box small {
    color: #fff;
    font-size: 12px;
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
    width: auto;
    min-width: 82px;
    padding: 5px 20px;
    border-radius: 999px;
    border: 1px solid #fff;
    background: transparent;
    color: #fff;
    font-weight: 700;
    font-size: 12px;
    cursor: pointer;
}

.tab.active {
    background: rgba(255, 255, 255, 0.16);
}

.program-disaster-control {
    display: flex;
    align-items: center;
    gap: 8px;
    padding: 4px 7px 4px 0;
    border-radius: 10px;
}

.program-disaster-control.active {
    padding: 4px 8px 4px 4px;
    background: #fff;
    box-shadow: 0 2px 8px rgba(5, 37, 87, 0.24);
}

.program-disaster-control.active .tab {
    border-color: #fff;
    background: transparent;
    color: #0b247f;
    font-size: 18px;
    padding: 6px 18px;
}

.disaster-control {
    min-width: 260px;
}

.disaster-control :deep(.p-select) {
    width: 100%;
    min-height: 34px;
    border: 1px solid #b9c9e5;
    border-radius: 8px;
    box-shadow: inset 0 1px 2px rgba(5, 37, 87, 0.08);
}

.disaster-control :deep(.p-select-label) {
    color: #0b247f;
    font-size: 12px;
}

.disaster-control :deep(.p-select-dropdown) {
    color: #0b247f;
}

.secondary-btn,
.small-btn {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    border-radius: 6px;
    border: 1px solid rgba(0, 0, 0, 0.18);
    background: rgba(255, 255, 255, 0.35);
    color: #1d1d1d;
    font-weight: 700;
    cursor: pointer;
}

.secondary-btn {
    min-width: 130px;
    padding: 10px 18px;
}

.small-btn {
    min-width: 80px;
    padding: 8px 16px;
}

.stats-grid {
    display: grid;
    grid-template-columns: repeat(3, minmax(180px, 1fr));
    gap: 24px;
    margin: 0 0 28px;
}

.metric-card {
  position: relative;
  background: #fff;
  border-radius: 10px;
  padding: 20px 20px 18px;
  min-height: 150px;
  box-shadow: 0 4px 14px rgba(24, 67, 101, 0.1);
  border: 1px solid #e8eef5;
  transition: transform 0.15s ease, box-shadow 0.15s ease;
}

.metric-card:hover {
  transform: translateY(-2px);
  box-shadow: 0 8px 20px rgba(24, 67, 101, 0.16);
}
.metric-icon {
  position: absolute;
  top: 16px; right: 16px;
  width: 38px; height: 38px;
  border-radius: 10px;
  display: flex; align-items: center; justify-content: center;
  font-size: 16px;
  color: #fff;
}



.metric-card:first-child {
    border-top: 4px solid #a9cdf7;
}

.metric-card.muted {
    opacity: 1;
    background: #e5eef9;
    border-color: #a6cdf8;
    border-top: 4px solid #0d5bc0;
}

.metric-card:nth-child(3) {
    background: #fff4ec;
    border-color: #f5d5bd;
    border-top: 4px solid #ff8b1a;
}

.metric-icon.target { background: #0649b9; }
.metric-icon.disbursed { background: #ff8b1a; }
.metric-icon.paid { background: #16a34a; }

.metric-label {
  font-size: 11.5px; font-weight: 700; letter-spacing: 0.05em;
  text-transform: uppercase; color: #6b7c8f; margin-top: 4px;
}

.metric-value {
  font-size: clamp(1.8rem, 3vw, 2.6rem);
  font-weight: 800; color: #0b1a2e; margin-top: 6px;
}

.metric-value.placeholder {
    color: rgba(17, 17, 17, 0.45);
}

.mini-line {
    width: 100%;
    height: 10px;
    border-radius: 999px;
    background: transparent;
    position: relative;
    overflow: hidden;
}

.mini-line::before {
    content: "";
    position: absolute;
    inset: 0;
    width: 0;
    background: transparent;
    border-radius: inherit;
}

.mini-line.short::before {
    width: 0;
}

.table-panel {
    background: #fff;
    border: 1px solid #d8e5f2;
    border-radius: 8px;
    overflow: hidden;
    margin-bottom: 28px;
    box-shadow: 0 5px 14px rgba(24, 67, 101, 0.14), 0 1px 3px rgba(24, 67, 101, 0.08);
}

.table-header {
    padding: 12px 18px;
    border-bottom: 1px solid #d8e5f2;
    font-weight: 800;
    text-transform: uppercase;
    background: #f8fbff;
}

.dashboard-breadcrumb {
    background: transparent;
    border: 0;
    padding: 0;
}

:deep(.header-progress) {
  height: 12px;
  background: rgba(0,0,0,0.28);
}
:deep(.header-progress .p-progressbar-value) {
  background: linear-gradient(90deg, #f5bd18, #ffd75e);
}

.disaster-input { width: 200px; }

.dashboard-breadcrumb :deep(.p-breadcrumb-list) {
    gap: 4px;
}

.dashboard-breadcrumb :deep(.p-menuitem-link) {
    padding: 7px 9px;
    border-radius: 5px;
    color: #526b88;
    font-size: clamp(0.78rem, 0.85vw, 0.95rem);
    font-weight: 700;
    text-decoration: none;
}

.dashboard-breadcrumb :deep(.p-menuitem-link:hover) {
    background: #eaf2fb;
    color: #164d88;
}

.dashboard-breadcrumb :deep(.p-breadcrumb-item:last-child .p-menuitem-link) {
    color: #173d68;
}

.dashboard-breadcrumb :deep(.p-breadcrumb-separator) {
    color: #90a5ba;
    font-size: 12px;
}

.table-header h2 {
    margin: 0;
    font-size: 1.1rem;
}

.breadcrumb-tabs {
    display: flex;
    align-items: center;
    flex-wrap: wrap;
    gap: 6px;
}

.breadcrumb-tab,
.breadcrumb-current {
    width: auto;
    padding: 4px 6px;
    border: 0;
    border-radius: 4px;
    background: transparent;
    color: #333;
    font-size: 1.1rem;
    font-weight: 800;
    text-transform: uppercase;
}

.breadcrumb-tab {
    cursor: pointer;
    text-decoration: underline;
    text-underline-offset: 3px;
}

.breadcrumb-tab:hover {
    background: rgba(255, 255, 255, 0.55);
}

.breadcrumb-separator {
    color: #777;
    font-size: 1.2rem;
    font-weight: 400;
}

.table-header-main {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 16px;
}

.table-header-actions {
    display: flex;
    align-items: center;
    justify-content: flex-end;
    flex-wrap: wrap;
    gap: 8px;
}

.table-header-actions label {
    color: #444;
    font-size: 11px;
    font-weight: 700;
    text-transform: uppercase;
}

.table-header-actions input {
    width: 140px;
    padding: 7px 8px;
    border: 1px solid rgba(0, 0, 0, 0.2);
    border-radius: 5px;
    background: rgba(255, 255, 255, 0.55);
    color: #222;
    font-size: 12px;
}

.back-btn {
    width: auto;
    min-width: 150px;
    padding: 8px 14px;
    border: 1px solid rgba(0, 0, 0, 0.22);
    border-radius: 6px;
    background: rgba(255, 255, 255, 0.55);
    color: #222;
    font-size: 12px;
    font-weight: 700;
}

.back-btn:hover {
    background: rgba(255, 255, 255, 0.85);
}

.progress-cell {
    display: flex;
    flex-direction: column;
    gap: 4px;
    min-width: 160px;
}

.progress-cell-top {
    display: flex;
    justify-content: space-between;
}

.progress-pct {
    font-size: 13px;
    font-weight: 700;
    color: #111827;
}

:deep(.compact-progress) {
    height: 14px;
    border-radius: 999px;
    background: #dce8f3;
    box-shadow: inset 0 1px 2px rgba(24, 67, 101, 0.12);
}

:deep(.compact-progress .p-progressbar-value) {
    background: linear-gradient(90deg, #2588d2 0%, #0d5bc0 100%);
    border-radius: 999px;
    transition: width 0.3s ease;
    box-shadow: 0 1px 2px rgba(13, 91, 192, 0.3);
}

.progress-remaining {
    color: #6b7280;
    font-size: 11px;
}

.dashboard-table :deep(.p-datatable-tbody > tr) {
    cursor: pointer;
}

.dashboard-table.clickable-rows :deep(.p-datatable-tbody > tr:hover) {
    background: #f3f8fe;
}

.dashboard-table :deep(.p-datatable-thead > tr > th) {
    padding: 14px 14px;
    border-color: #dbe6f1;
    background: #f8fbff;
    color: #173d68;
    font-size: clamp(0.7rem, 0.72vw, 0.82rem);
    font-weight: 800;
    text-transform: uppercase;
}

.dashboard-table :deep(.p-datatable-tbody > tr > td) {
    padding: 14px;
    border-color: #e4edf5;
    color: #24415f;
    font-size: clamp(0.78rem, 0.82vw, 0.95rem);
    vertical-align: middle;
}

.dashboard-table :deep(.p-datatable-tbody > tr:nth-child(even)) {
    background: #fbfdff;
}

.dashboard-table :deep(.p-datatable-tbody > tr) {
    box-shadow: inset 0 -1px 0 rgba(72, 116, 157, 0.08);
}

.paid-cell {
    display: flex;
    flex-direction: column;
    gap: 3px;
    line-height: 1.2;
}

.paid-cell > span {
    color: #174f91;
    font-weight: 700;
}

.paid-cell small {
    color: #7b8ea3;
    font-size: 11px;
}

.total-row-footer {
    display: grid;
    grid-template-columns: repeat(4, minmax(0, 1fr));
    align-items: center;
    gap: 14px;
    width: 100%;
    padding: 14px;
    background: #edf4fb;
    color: #173d68;
    font-weight: 800;
}

.total-label,
.total-target,
.total-paid {
    text-align: center;
}

.total-label {
    text-align: left;
}

.total-progress {
    width: 100%;
}

.dashboard-table :deep(.p-datatable-footer) {
    padding: 0;
    border: 0;
}

.overall-progress-panel {
    margin: 0 0 28px;
    padding: 18px 22px 20px;
    border: 1px solid #c6dced;
    border-radius: 8px;
    background: #f8fbff;
    box-shadow: 0 5px 14px rgba(24, 67, 101, 0.14), 0 1px 3px rgba(24, 67, 101, 0.08);
}

.overall-progress-heading {
    display: flex;
    align-items: end;
    justify-content: space-between;
    gap: 16px;
    margin-bottom: 10px;
    color: #526b88;
    font-size: clamp(0.75rem, 0.8vw, 0.9rem);
}

.overall-progress-heading > div {
    display: flex;
    align-items: baseline;
    gap: 12px;
}

.overall-progress-label {
    color: #173d68;
    font-weight: 800;
    text-transform: uppercase;
}

.overall-progress-heading strong {
    color: #173d68;
    font-size: clamp(1.3rem, 2vw, 2rem);
}

:deep(.overall-progress-bar) {
    height: 22px;
    border-radius: 999px;
    background: #dfeaf3;
    box-shadow: inset 0 1px 2px rgba(24, 67, 101, 0.14);
}

:deep(.overall-progress-bar .p-progressbar-value) {
    border-radius: inherit;
    background: linear-gradient(90deg, #f5bd18, #ffd34f);
    box-shadow: 0 1px 2px rgba(164, 112, 0, 0.28);
}

.overall-progress-panel > small {
    display: block;
    margin-top: 8px;
    color: #6b7f93;
    font-size: 11px;
}

table {
    width: 100%;
    border-collapse: collapse;
}

thead th {
    background: #edf3f9;
    text-align: left;
    padding: 14px 16px;
    font-size: 12px;
    text-transform: uppercase;
    letter-spacing: 0.06em;
}

tbody td {
    padding: 14px 16px;
    border-top: 1px solid #e5edf5;
    font-size: 14px;
}

tbody tr.click-row:hover {
    background: #f3f8fe;
}

.total-row td {
    font-weight: 700;
}

.bar-wrap {
    display: flex;
    align-items: center;
    gap: 10px;
    min-width: 240px;
}

.progress-bar-track {
    display: block;
    width: 180px;
    height: 8px;
    background: #e4ebf2;
    border-radius: 999px;
    overflow: hidden;
}

.progress-bar-track span {
    display: block;
    height: 100%;
    background: #2588d2;
    border-radius: inherit;
}

.bar-wrap small {
    color: #2c2c2c;
    font-size: 11px;
}

.comparison-section {
    background: #f8fbff;
    border: 1px solid #d8e5f2;
    border-radius: 8px;
    padding: 18px 22px 26px;
    box-shadow: 0 5px 14px rgba(24, 67, 101, 0.14), 0 1px 3px rgba(24, 67, 101, 0.08);
}

.comparison-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    gap: 12px;
    margin-bottom: 18px;
    font-size: clamp(0.8rem, 0.85vw, 1rem);
    font-weight: 700;
}

.comparison-title-group {
    display: flex;
    align-items: center;
    gap: 18px;
}

.comparison-legend {
    display: flex;
    align-items: center;
    gap: 14px;
    color: #536273;
    font-size: 11px;
    font-weight: 600;
}

.comparison-legend span {
    display: inline-flex;
    align-items: center;
    gap: 5px;
}

.legend-swatch {
    display: inline-block;
    width: 10px;
    height: 10px;
    border-radius: 3px;
}

.current-swatch {
    background: #2588d2;
}

.previous-swatch {
    background: #2588d299;
}

.date-filter {
    display: flex;
    align-items: center;
    gap: 10px;
    color: #333;
    font-size: 12px;
}

.date-range-inputs {
    display: flex;
    align-items: center;
    gap: 8px;
}

.date-range-inputs span,
.table-header-actions > span {
    color: #555;
    font-size: 11px;
}

.date-filter input {
    border: 1px solid rgba(0, 0, 0, 0.18);
    border-radius: 6px;
    padding: 8px 10px;
    width: 130px;
    background: rgba(255, 255, 255, 0.3);
}

.chart-grid {
    display: grid;
    grid-template-columns: repeat(2, minmax(0, 1fr));
    gap: 24px;
}

.chart-card {
    position: relative;
    overflow: hidden;
    background: #eef5fb;
    border: 1px solid #d5e3f0;
    border-radius: 8px;
    padding: 18px 22px;
    display: flex;
    align-items: end;
    justify-content: space-between;
    min-height: 290px;
    gap: 20px;
    box-shadow: 0 4px 12px rgba(44, 96, 143, 0.14), inset 0 1px 0 rgba(255, 255, 255, 0.7);
}

.chart-card::before {
    content: "";
    position: absolute;
    inset: 42px 22px 48px;
    background: repeating-linear-gradient(
        to bottom,
        transparent 0,
        transparent 43px,
        rgba(111, 137, 161, 0.24) 44px,
        transparent 45px
    );
    pointer-events: none;
}

.chart-card-title {
    position: absolute;
    top: 14px;
    left: 16px;
    font-size: 12px;
    font-weight: 700;
    letter-spacing: 0.05em;
    text-transform: uppercase;
    color: #333;
    z-index: 1;
}

.chart-y-axis {
    height: 225px;
    min-width: 26px;
    display: flex;
    flex-direction: column;
    justify-content: space-between;
    align-items: flex-end;
    padding: 0 0 2px;
    color: #667789;
    font-size: clamp(0.62rem, 0.62vw, 0.72rem);
    line-height: 1;
    z-index: 1;
}

.barangay-chart-card {
    position: relative;
    padding-top: 38px;
}

.single-bar-group {
    width: 100%;
    justify-content: center;
}

.single-bar-group .bar {
    width: 90px;
    max-width: 100%;
}

.barangay-chart-grid {
    grid-template-columns: repeat(2, minmax(220px, 1fr));
}

.bar-group {
    display: flex;
    align-items: end;
    justify-content: center;
    flex: 1;
    height: 225px;
    z-index: 1;
}

.bar-column {
    height: 100%;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: end;
    gap: 6px;
}

.bar-column small {
    color: #536273;
    font-size: 11px;
    font-weight: 700;
}

.chart-column-label {
    width: 100px;
    min-height: 28px;
    color: #435a70;
    font-size: clamp(0.62rem, 0.62vw, 0.72rem);
    line-height: 1.15;
    text-align: center;
    overflow-wrap: anywhere;
}

.bar {
    width: 42px;
    border-radius: 6px 6px 0 0;
    background: #b8c1ca;
    min-height: 20px;
}

.bar.alt {
    background: #c5cdd5;
}

.x-axis {
    display: flex;
    justify-content: space-between;
    margin-top: 12px;
    font-size: 11px;
    color: #333;
    text-transform: uppercase;
    position: relative;
    z-index: 1;
}

@media (max-width: 768px) {
    .dashboard-page {
        padding: 18px 12px;
    }

    .dashboard-header {
        padding: 24px 20px 20px;
    }

    .title-row h1 {
        font-size: clamp(2rem, 8vw, 2.8rem);
        line-height: 1.08;
    }

    .tab-row {
        margin-top: 22px;
        gap: 8px;
    }

    .program-disaster-control.active {
        max-width: 100%;
    }

    .disaster-control {
        min-width: 0;
        width: min(260px, 58vw);
    }

    .filter-select {
        width: 100%;
    }

    .login-card {
        flex-direction: column;
    }

    .right-panel {
        min-height: 200px;
        order: -1;
    }

    .title-row,
    .comparison-header,
    .date-filter {
        flex-direction: column;
        align-items: flex-start;
    }

    .progress-box {
        min-width: 100%;
        text-align: left;
    }

    .stats-grid,
    .chart-grid {
        grid-template-columns: 1fr;
    }

    .table-header-main {
        align-items: flex-start;
        flex-direction: column;
    }

    .table-header-actions {
        width: 100%;
        justify-content: flex-start;
    }

    .overall-progress-heading {
        align-items: flex-start;
        flex-direction: column;
    }

    .total-row-footer {
        grid-template-columns: repeat(2, minmax(0, 1fr));
    }

    .total-row-footer .progress-cell {
        grid-column: 1 / -1;
    }
}
</style>
