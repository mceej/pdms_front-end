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
                    <span class="brand">DSWD • PAYOUT SYSTEM</span>
                    <div class="title-row">
                        <h1>DSWD Assist Track Dashboard</h1>
                        <div class="progress-box">
                            <div class="progress-value">{{ dashboardProgress }}%</div>
                            <div class="progress-track">
                                <span :style="{ width: dashboardProgress + '%' }"></span>
                            </div>
                            <small>{{ progressCount }} out of {{ progressTotal }}</small>
                        </div>
                    </div>

                <div class="tab-row">
                    <button
                        v-for="tab in tabs"
                        :key="tab"
                        type="button"
                        :class="['tab', { active: activeTab === tab }]"
                        @click="setTab(tab)"
                    >
                        {{ tab }}
                    </button>
                    <div class="top-actions">
                        <select v-model="selectedDisasterType" class="filter-select">
                            <option value="Type of Disaster">Type of Disaster</option>
                            <option value="Flood">Flood</option>
                            <option value="Earthquake">Earthquake</option>
                            <option value="Typhoon">Typhoon</option>
                        </select>
                    </div>
                </div>
                </header>

                <section class="stats-grid">
                    <div class="metric-card">
                        <div class="metric-label">Target Number per Payout Site</div>
                        <div class="metric-value">{{ totalTarget }}</div>
                        <div class="mini-line"></div>
                    </div>
                    <div class="metric-card muted">
                        <div class="metric-label">Total Amount Disbursed</div>
                        <div class="metric-value placeholder">{{ totalDisbursed }}</div>
                        <div class="mini-line short"></div>
                    </div>
                    <div class="metric-card muted">
                        <div class="metric-label">Paid</div>
                        <div class="metric-value placeholder">{{ paidValue }}</div>
                        <div class="mini-line short"></div>
                    </div>
                </section>

                <section class="table-panel">
                    <div class="table-header">
                        <div class="table-header-main">
                            <div class="breadcrumb-tabs" aria-label="Dashboard hierarchy">
                                <button type="button" class="breadcrumb-tab" @click="goToLevel('province')">
                                    Region XI
                                </button>
                                <span class="breadcrumb-separator">›</span>
                                <button type="button" class="breadcrumb-tab" @click="goToLevel('province')">
                                    Provinces
                                </button>
                                <span v-if="selectedProvince" class="breadcrumb-separator">›</span>
                                <button
                                    v-if="selectedProvince"
                                    type="button"
                                    class="breadcrumb-tab"
                                    @click="goToLevel('municipality')"
                                >
                                    {{ selectedProvince.name }}
                                </button>
                                <span v-if="selectedMunicipality" class="breadcrumb-separator">›</span>
                                <button
                                    v-if="selectedMunicipality"
                                    type="button"
                                    class="breadcrumb-tab"
                                    @click="goToLevel('barangay')"
                                >
                                    {{ selectedMunicipality.name }}
                                </button>
                                <span v-if="selectedBarangay" class="breadcrumb-separator">›</span>
                                <span v-if="selectedBarangay" class="breadcrumb-current">{{ selectedBarangay.name }}</span>
                            </div>
                            <div class="table-header-actions">
                                <label for="hierarchy-date-from">Data range</label>
                                <input
                                    id="hierarchy-date-from"
                                    v-model="dateFrom"
                                    type="date"
                                    aria-label="Choose start date"
                                    :max="dateTo || undefined"
                                    @change="applyDateRange('from')"
                                />
                                <span>to</span>
                                <input
                                    v-model="dateTo"
                                    type="date"
                                    aria-label="Choose end date"
                                    :min="dateFrom || undefined"
                                    @change="applyDateRange('to')"
                                />
                                <button
                                    v-if="selectedProvince || selectedMunicipality || selectedBarangay"
                                    type="button"
                                    class="back-btn"
                                    @click.stop="goBack"
                                >
                                    {{ selectedProvince && !selectedMunicipality && !selectedBarangay ? 'Back to All Provinces' : 'Back' }}
                                </button>
                            </div>
                        </div>
                    </div>

                    <table v-if="activeLevel !== 'detail'">
                        <thead>
                            <tr>
                                <th v-for="column in tableColumns" :key="column">{{ column }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr
                                v-for="row in currentTableRows"
                                :key="row.name"
                                class="click-row"
                                @click="handleRowClick(row)"
                            >
                                <td>{{ row.name }}</td>
                                <td>{{ row.target }}</td>
                                <td>{{ row.paid }}</td>
                                <td>
                                    <div class="bar-wrap">
                                        <span :style="{ width: row.progress + '%' }"></span>
                                        <small>{{ row.progress }}%</small>
                                    </div>
                                </td>
                            </tr>
                            <tr class="total-row">
                                <td>Total</td>
                                <td>{{ totalTableTarget }}</td>
                                <td>{{ totalTablePaid }}</td>
                                <td>
                                    <div class="bar-wrap">
                                        <span :style="{ width: totalProgress + '%' }"></span>
                                        <small>{{ totalProgress }}%</small>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </section>

                <section class="comparison-section">
                    <div class="comparison-header">
                        <span>{{ selectedBarangay ? `${selectedBarangay.name} Analytics` : 'Data Comparison' }}</span>
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
                            <div class="chart-y-axis">
                                <span v-for="marker in chartMarkers" :key="marker">{{ marker }}</span>
                            </div>
                            <div class="bar-group" v-for="(value, index) in comparisonLeft" :key="index">
                                <div class="bar-column">
                                    <div class="bar" :style="{ height: value + 'px' }"></div>
                                </div>
                            </div>
                            <div class="x-axis">
                                <span>PRO A</span>
                                <span>PRO B</span>
                                <span>PRO C</span>
                                <span>PRO D</span>
                                <span>PRO E</span>
                            </div>
                        </div>
                        <div class="chart-card">
                            <div class="chart-y-axis">
                                <span v-for="marker in chartMarkers" :key="marker">{{ marker }}</span>
                            </div>
                            <div class="bar-group" v-for="(value, index) in comparisonRight" :key="index">
                                <div class="bar-column">
                                    <div class="bar alt" :style="{ height: value + 'px' }"></div>
                                </div>
                            </div>
                            <div class="x-axis">
                                <span>PRO A</span>
                                <span>PRO B</span>
                                <span>PRO C</span>
                                <span>PRO D</span>
                                <span>PRO E</span>
                            </div>
                        </div>
                    </div>
                </section>
            </div>
        </div>
    </div>
</template>

<script setup>
import { computed, reactive, ref } from 'vue';

const currentView = ref('login');
const email = ref('');
const password = ref('');
const rememberMe = ref(false);
const activeTab = ref('AICS');
const selectedDisasterType = ref('Type of Disaster');
const dateFrom = ref('');
const dateTo = ref('');
const appliedDateLabel = ref('as of 9/14/2026 | 10:30:23 AM');

const tabs = ['AICS', 'ECT'];
const selectedProvince = ref(null);
const selectedMunicipality = ref(null);
const selectedBarangay = ref(null);

const errors = reactive({
    email: false,
    password: false,
    rememberMe: false,
});

const dashboardData = {
    AICS: {
        progress: 80,
        count: 1234,
        total: 2345,
        target: '10, 000',
        totalDisbursed: '-----',
        paid: '-----',
        provinces: [
            {
                name: 'MUNI A',
                target: '5,890',
                paid: '2,500',
                progress: 42,
                municipalities: [
                    {
                        name: 'Municipality A1',
                        target: '1,500',
                        paid: '850',
                        progress: 57,
                        barangays: [
                            { name: 'Barangay A1-1', target: '520', paid: '300', progress: 58, beneficiaries: '280' },
                            { name: 'Barangay A1-2', target: '480', paid: '260', progress: 54, beneficiaries: '240' },
                            { name: 'Barangay A1-3', target: '500', paid: '290', progress: 58, beneficiaries: '260' },
                        ],
                    },
                    {
                        name: 'Municipality A2',
                        target: '1,200',
                        paid: '620',
                        progress: 52,
                        barangays: [
                            { name: 'Barangay A2-1', target: '410', paid: '210', progress: 51, beneficiaries: '190' },
                            { name: 'Barangay A2-2', target: '390', paid: '200', progress: 51, beneficiaries: '180' },
                        ],
                    },
                ],
            },
            {
                name: 'MUNI B',
                target: '-----',
                paid: 'PO.00',
                progress: 0,
                municipalities: [
                    {
                        name: 'Municipality B1',
                        target: '0',
                        paid: '0',
                        progress: 0,
                        barangays: [
                            { name: 'Barangay B1-1', target: '0', paid: '0', progress: 0, beneficiaries: '0' },
                        ],
                    },
                ],
            },
            {
                name: 'MUNI C',
                target: '-----',
                paid: 'PO.00',
                progress: 0,
                municipalities: [],
            },
            {
                name: 'MUNI D',
                target: '-----',
                paid: 'PO.00',
                progress: 0,
                municipalities: [],
            },
            {
                name: 'MUNI E',
                target: '-----',
                paid: 'PO.00',
                progress: 0,
                municipalities: [],
            },
        ],
        comparisonLeft: [200, 150, 180, 120, 140],
        comparisonRight: [170, 150, 110, 130, 160],
    },
    ECT: {
        progress: 72,
        count: 1400,
        total: 1945,
        target: '8, 400',
        totalDisbursed: '-----',
        paid: '-----',
        provinces: [
            {
                name: 'BRGY A',
                target: '4,900',
                paid: '2,100',
                progress: 38,
                municipalities: [
                    {
                        name: 'Municipality C1',
                        target: '1,200',
                        paid: '560',
                        progress: 47,
                        barangays: [
                            { name: 'Barangay C1-1', target: '400', paid: '180', progress: 45, beneficiaries: '160' },
                            { name: 'Barangay C1-2', target: '350', paid: '170', progress: 49, beneficiaries: '150' },
                        ],
                    },
                ],
            },
            {
                name: 'BRGY B',
                target: '-----',
                paid: 'PO.00',
                progress: 0,
                municipalities: [],
            },
            {
                name: 'BRGY C',
                target: '-----',
                paid: 'PO.00',
                progress: 0,
                municipalities: [],
            },
            {
                name: 'BRGY D',
                target: '-----',
                paid: 'PO.00',
                progress: 0,
                municipalities: [],
            },
            {
                name: 'BRGY E',
                target: '-----',
                paid: 'PO.00',
                progress: 0,
                municipalities: [],
            },
        ],
        comparisonLeft: [180, 160, 140, 130, 170],
        comparisonRight: [150, 170, 130, 120, 180],
    },
};

const dashboardProgress = computed(() => dashboardData[activeTab.value].progress);
const progressCount = computed(() => dashboardData[activeTab.value].count);
const progressTotal = computed(() => dashboardData[activeTab.value].total);
const totalTarget = computed(() => dashboardData[activeTab.value].target);
const totalDisbursed = computed(() => dashboardData[activeTab.value].totalDisbursed);
const paidValue = computed(() => dashboardData[activeTab.value].paid);
const comparisonLeft = computed(() => dashboardData[activeTab.value].comparisonLeft);
const comparisonRight = computed(() => dashboardData[activeTab.value].comparisonRight);
const chartMarkers = [200, 150, 100, 50, 0];

const activeLevel = computed(() => {
    if (selectedBarangay.value) return 'detail';
    if (selectedMunicipality.value) return 'barangay';
    if (selectedProvince.value) return 'municipality';
    return 'province';
});

const currentTableRows = computed(() => {
    if (activeLevel.value === 'province') return dashboardData[activeTab.value].provinces;
    if (activeLevel.value === 'municipality') return selectedProvince.value.municipalities || [];
    if (activeLevel.value === 'barangay') return selectedMunicipality.value.barangays || [];
    return [];
});

const tableColumns = computed(() => {
    if (activeLevel.value === 'province') return ['Name', 'Total Target', 'Paid', 'Progress Bar'];
    if (activeLevel.value === 'municipality') return ['Name', 'Total Target', 'Paid', 'Progress Bar'];
    if (activeLevel.value === 'barangay') return ['Name', 'Total Target', 'Paid', 'Progress Bar'];
    return ['Name', 'Total Target', 'Paid', 'Progress Bar'];
});

const tableTitle = computed(() => {
    if (selectedBarangay.value) return `Barangay Statistics`;
    if (selectedMunicipality.value) return `${selectedProvince.value.name} > ${selectedMunicipality.value.name}`;
    if (selectedProvince.value) return `Provinces > ${selectedProvince.value.name}`;
    return 'Provinces';
});

const totalTableTarget = computed(() => {
    const items = currentTableRows.value;
    if (!items.length) return '-----';
    const total = items.reduce((sum, item) => sum + Number((item.target || '0').replace(/[^\d]/g, '')), 0);
    return total ? total.toLocaleString() : '-----';
});

const totalTablePaid = computed(() => {
    const items = currentTableRows.value;
    if (!items.length) return 'PO.00';
    const total = items.reduce((sum, item) => sum + Number((item.paid || '0').replace(/[^\d]/g, '')), 0);
    return total ? total.toLocaleString() : 'PO.00';
});

const totalProgress = computed(() => {
    const items = currentTableRows.value;
    if (!items.length) return 0;
    const average = items.reduce((sum, item) => sum + item.progress, 0) / items.length;
    return Math.round(average);
});

const handleRowClick = (row) => {
    if (activeLevel.value === 'province') {
        selectedProvince.value = row;
        selectedMunicipality.value = null;
        selectedBarangay.value = null;
        return;
    }

    if (activeLevel.value === 'municipality') {
        selectedMunicipality.value = row;
        selectedBarangay.value = null;
        return;
    }

    if (activeLevel.value === 'barangay') {
        selectedBarangay.value = row;
    }
};

const goToLevel = (level) => {
    if (level === 'province') {
        selectedProvince.value = null;
        selectedMunicipality.value = null;
        selectedBarangay.value = null;
        return;
    }

    if (level === 'municipality') {
        selectedMunicipality.value = null;
        selectedBarangay.value = null;
        return;
    }

    if (level === 'barangay') {
        selectedBarangay.value = null;
    }
};

const goBack = () => {
    if (selectedBarangay.value) {
        selectedBarangay.value = null;
        return;
    }

    if (selectedMunicipality.value) {
        selectedMunicipality.value = null;
        return;
    }

    if (selectedProvince.value) {
        selectedProvince.value = null;
    }
};

const setTab = (tab) => {
    activeTab.value = tab;
    selectedProvince.value = null;
    selectedMunicipality.value = null;
    selectedBarangay.value = null;
};

const applyDateRange = (changedField) => {
    if (dateFrom.value && dateTo.value && dateTo.value < dateFrom.value) {
        if (changedField === 'from') {
            dateTo.value = dateFrom.value;
        } else {
            dateFrom.value = dateTo.value;
        }
    }

    if (!dateFrom.value && !dateTo.value) {
        appliedDateLabel.value = 'as of 9/14/2026 | 10:30:23 AM';
        return;
    }

    if (dateFrom.value && dateTo.value) {
        appliedDateLabel.value = `from ${dateFrom.value} to ${dateTo.value}`;
        return;
    }

    appliedDateLabel.value = `from ${dateFrom.value || dateTo.value}`;
};

const login = () => {
    errors.email = !email.value;
    errors.password = !password.value;
    errors.rememberMe = !rememberMe.value;

    if (errors.email || errors.password || errors.rememberMe) {
        return;
    }

    currentView.value = 'dashboard';
};
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
    border: 0;
    border-top: 6px solid #f5bd18;
    border-radius: 7px;
    padding: 30px 30px 24px;
    margin-bottom: 28px;
    background: linear-gradient(115deg, #073a91 0%, #0649b9 100%);
    color: #fff;
    box-shadow: 0 3px 5px rgba(14, 49, 103, 0.25);
}

.brand {
    font-size: 12px;
    letter-spacing: 0.12em;
    text-transform: uppercase;
    color: #c9ddff;
    font-weight: 700;
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

.top-actions {
    margin-left: auto;
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
    background: #fff;
    border: 2px solid #d8e9f8;
    border-radius: 7px;
    min-height: 190px;
    padding: 16px 18px;
    display: flex;
    flex-direction: column;
    justify-content: space-between;
    box-shadow: 0 3px 5px rgba(24, 67, 101, 0.2);
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

.metric-label {
    font-size: 12px;
    font-weight: 700;
    letter-spacing: 0.04em;
    text-transform: uppercase;
    color: #4b5563;
    line-height: 1.4;
}

.metric-value {
    font-size: clamp(2rem, 3vw, 3rem);
    font-weight: 800;
    line-height: 1;
    color: #050505;
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
    box-shadow: 0 2px 5px rgba(24, 67, 101, 0.1);
}

.table-header {
    padding: 12px 18px;
    border-bottom: 1px solid #d8e5f2;
    font-weight: 800;
    text-transform: uppercase;
    background: #f8fbff;
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
    min-width: 220px;
}

.bar-wrap span {
    display: inline-block;
    height: 8px;
    background: #2588d2;
    border-radius: 999px;
    min-width: 10px;
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
    box-shadow: 0 2px 5px rgba(24, 67, 101, 0.08);
}

.comparison-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    gap: 12px;
    margin-bottom: 18px;
    font-weight: 700;
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
    font-size: 10px;
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

    .top-actions {
        width: 100%;
        margin-left: 0;
        margin-top: 4px;
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
}
</style>
