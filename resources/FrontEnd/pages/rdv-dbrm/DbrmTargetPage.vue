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
                        :class="{ 'filter-active': hasSelectedFilters }"
                        :aria-expanded="filterOpen"
                        @click="filterOpen = !filterOpen"
                    >
                        <i class="pi pi-filter" aria-hidden="true"></i>
                        Filter
                        <i class="pi pi-chevron-down" aria-hidden="true"></i>
                    </button>
                    <div v-if="actionNotification" class="action-notification" role="status">
                        <i class="pi pi-check-circle" aria-hidden="true"></i>
                        {{ actionNotification }}
                    </div>
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
            aria-label="Target management table"
            tabindex="0"
            :style="{ '--visible-row-height': `${visibleTargets.length ? visibleTargets.length * 76 : 100}px` }"
        >
            <table :class="['target-table', { 'drmd-table': isDrmd }]">
                <thead>
                    <tr>
                        <th>Payout Type</th>
                        <th v-if="isDrmd">Disaster Type</th>
                        <template v-else>
                            <th>Program Type</th>
                            <th>Type of Assistance</th>
                        </template>
                        <th>Target Beneficiary</th>
                        <th>Target Disbursement</th>
                        <th>Payout Site</th>
                        <th>Date Start</th>
                        <th>Date End</th>
                        <th class="action-col">Action</th>
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
                        <td class="payout-cell">
                            <button
                                type="button"
                                class="payout-toggle"
                                :aria-expanded="expandedPayoutId === target.id"
                                @click="togglePayoutDetails(target.id)"
                            >
                                Payout Site
                                <i
                                    :class="['pi', expandedPayoutId === target.id ? 'pi-chevron-up' : 'pi-chevron-down']"
                                    aria-hidden="true"
                                ></i>
                            </button>
                            <ol v-if="expandedPayoutId === target.id" class="payout-details">
                                <li><span>Province</span>{{ target.province }}</li>
                                <li><span>City/ Municipality</span>{{ target.city }}</li>
                                <li><span>Barangay</span>{{ target.barangay }}</li>
                            </ol>
                        </td>
                        <td>{{ formatDate(target.dateStart) }}</td>
                        <td>{{ formatDate(target.dateEnd) }}</td>
                        <td class="action-col">
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
        </div>

        <div v-if="showTargetDialog" class="dialog-backdrop" @click.self="closeTargetDialog">
            <form class="target-dialog" @submit.prevent="saveTarget">
                <h2>{{ editingTargetId === null ? 'Add New Target' : 'Edit Target' }}</h2>
                <label>
                    Payout Type
                    <select v-model="targetDraft.payoutType" class="payout-type-select">
                        <option>CCAM</option>
                        <option>Cash Assistance</option>
                    </select>
                </label>
                <label v-if="!isDrmd">
                    Program Type
                    <select v-model="targetDraft.programType" class="program-type-select">
                        <option v-for="program in programTypes" :key="program">{{ program }}</option>
                    </select>
                </label>
                <div v-else class="dropdown-field">
                    <span class="dropdown-field-label">Disaster Type</span>
                    <div :class="['ss-control', { 'ss-open': openField === disasterField.key }]">
                        <input
                            class="ss-input"
                            :class="{ 'ss-invalid': disasterError }"
                            type="text"
                            role="combobox"
                            autocomplete="off"
                            aria-autocomplete="list"
                            :aria-label="disasterField.label"
                            :aria-expanded="openField === disasterField.key"
                            :value="openField === disasterField.key ? fieldQuery : targetDraft.disasterName"
                            :placeholder="disasterField.placeholder"
                            @focus="openFieldList(disasterField)"
                            @click="openFieldList(disasterField)"
                            @input="onFieldInput"
                            @keydown="onFieldKeydown($event, disasterField)"
                        />
                        <i class="pi pi-chevron-down ss-chevron" aria-hidden="true"></i>
                    </div>
                    <ul v-if="openField === disasterField.key" class="ss-list" role="listbox">
                        <li v-if="activeOptions.length === 0" class="ss-empty">No matches found.</li>
                        <li
                            v-for="(option, index) in activeOptions"
                            :key="`${option}-${index}`"
                            role="option"
                            :aria-selected="option === targetDraft.disasterName"
                            :class="{
                                'ss-active': index === activeIndex,
                                'ss-selected': option === targetDraft.disasterName,
                            }"
                            @mousedown.prevent="pickOption(disasterField, option)"
                            @mousemove="activeIndex = index"
                        >{{ option }}</li>
                    </ul>
                    <p v-if="disasterError" class="field-error" role="alert">{{ disasterError }}</p>
                </div>
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

                <div class="dropdown-field">
                    <span class="dropdown-field-label">Payout Site</span>
                    <button
                        type="button"
                        :class="[
                            'payout-trigger',
                            { 'ss-invalid': payoutError, 'payout-open': payoutOpen },
                        ]"
                        :aria-expanded="payoutOpen"
                        @click="payoutOpen = !payoutOpen"
                    >
                        <span :class="{ 'payout-placeholder': !draftPayoutSummary }">{{ draftPayoutSummary || 'Select payout site' }}</span>
                    </button>
                    <p v-if="payoutError" class="field-error" role="alert">{{ payoutError }}</p>
                    <div v-if="payoutOpen" class="payout-fields">
                        <div v-for="(field, fieldIndex) in payoutFields" :key="field.key" class="dropdown-field">
                            <span class="dropdown-field-label">{{ fieldIndex + 1 }}. {{ field.label }}</span>
                            <div :class="['ss-control', { 'ss-open': openField === field.key }]">
                                <input
                                    class="ss-input"
                                    type="text"
                                    role="combobox"
                                    autocomplete="off"
                                    aria-autocomplete="list"
                                    :aria-label="field.label"
                                    :aria-expanded="openField === field.key"
                                    :disabled="field.disabled"
                                    :value="openField === field.key ? fieldQuery : targetDraft[field.key]"
                                    :placeholder="field.loading ? 'Loading…' : field.placeholder"
                                    @focus="openFieldList(field)"
                                    @click="openFieldList(field)"
                                    @input="onFieldInput"
                                    @keydown="onFieldKeydown($event, field)"
                                />
                                <i class="pi pi-chevron-down ss-chevron" aria-hidden="true"></i>
                            </div>
                            <ul v-if="openField === field.key" class="ss-list" role="listbox">
                                <li v-if="field.loading" class="ss-empty">Loading…</li>
                                <li v-else-if="activeOptions.length === 0" class="ss-empty">No matches found.</li>
                                <li
                                    v-for="(option, index) in activeOptions"
                                    :key="`${option}-${index}`"
                                    role="option"
                                    :aria-selected="option === targetDraft[field.key]"
                                    :class="{
                                        'ss-active': index === activeIndex,
                                        'ss-selected': option === targetDraft[field.key],
                                    }"
                                    @mousedown.prevent="pickOption(field, option)"
                                    @mousemove="activeIndex = index"
                                >{{ option }}</li>
                            </ul>
                        </div>
                        <p v-if="payoutLoadError" class="field-error" role="alert">
                            {{ payoutLoadError }}
                            <button type="button" class="payout-retry" @click="retryPayoutLoad">Retry</button>
                        </p>
                    </div>
                </div>

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

        <div
            v-if="pendingTargetSave"
            class="dialog-backdrop"
            @click.self="pendingTargetSave = null"
        >
            <section class="confirmation-dialog" role="alertdialog" aria-modal="true">
                <span class="confirmation-icon" aria-hidden="true">
                    <i class="pi pi-exclamation-circle"></i>
                </span>
                <h2>{{ pendingTargetSave.isNew ? 'Save new target?' : 'Save target changes?' }}</h2>
                <p>
                    {{ pendingTargetSave.isNew
                        ? 'Add this target to the list?'
                        : 'Save the changes to this target record?' }}
                </p>
                <div class="dialog-actions">
                    <button type="button" class="cancel-button" @click="pendingTargetSave = null">
                        Cancel
                    </button>
                    <button type="button" class="save-target-button" @click="confirmTargetSave">
                        {{ pendingTargetSave.isNew ? 'Save Target' : 'Save Changes' }}
                    </button>
                </div>
            </section>
        </div>
        <AppFooter />
    </section>
</template>

<script setup>
import { computed, nextTick, onMounted, onUnmounted, ref, watch } from 'vue';
import AppFooter from '../../components/AppFooter.vue';

const props = defineProps({
    pageTitle: { type: String, required: true },
    variant: { type: String, default: 'cis' },
});

const programTypes = ['AICS', 'AICS-Uplift', 'AKAP', 'ECT'];
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
const filterLabel = computed(() => (isDrmd.value ? 'Disaster Type' : 'Program Type'));
const filterOptions = computed(() => (isDrmd.value ? disasterNames : programTypes));
const search = ref('');
const page = ref(1);
const pageSize = 10;
const filterOpen = ref(false);
const filterRoot = ref(null);
const draftPrograms = ref([]);
const selectedPrograms = ref([]);
const hasSelectedFilters = computed(() => draftPrograms.value.length > 0);
const showTargetDialog = ref(false);
const editingTargetId = ref(null);
const pendingTargetSave = ref(null);
const actionNotification = ref('');
let actionNotificationTimer;
const targetDraft = ref(createEmptyTarget());
const payoutOpen = ref(false);
const payoutError = ref('');
const disasterError = ref('');
const expandedPayoutId = ref(null);
const targets = ref(
    Array.from({ length: 20 }, (_, index) => ({
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
        province: 'Davao del Sur',
        city: 'City of Davao',
        barangay: `Barangay ${index + 1}`,
        dateStart: '2026-09-30',
        dateEnd: '2026-10-05',
    })),
);

function createEmptyTarget() {
    const target = {
        payoutType: isDrmd.value ? 'ECT' : 'AICS',
        targetBeneficiary: 0,
        targetDisbursement: 0,
        province: '',
        city: '',
        barangay: '',
        dateStart: '',
        dateEnd: '',
    };

    if (isDrmd.value) {
        target.disasterName = '';
    } else {
        target.programType = programTypes[0];
        target.assistanceType = 'Cash Assistance';
    }

    return target;
}

const draftPayoutSummary = computed(() =>
    [targetDraft.value.province, targetDraft.value.city, targetDraft.value.barangay]
        .map((part) => (part || '').trim())
        .filter(Boolean)
        .join(' › '),
);

const togglePayoutDetails = (id) => {
    expandedPayoutId.value = expandedPayoutId.value === id ? null : id;
};

// Location data from the Philippine Standard Geographic Code (PSGC) API
const PSGC_BASE = 'https://psgc.gitlab.io/api';
const NCR_CODE = '130000000';
const NCR_NAME = 'Metro Manila (NCR)';
const provinces = ref([]);
const cities = ref([]);
const barangays = ref([]);
const provincesLoading = ref(false);
const citiesLoading = ref(false);
const barangaysLoading = ref(false);
const payoutLoadError = ref('');
const loadErrorMessage = 'Could not load locations. Check your connection and try again.';
let provincesPromise = null;
let cityRequestId = 0;
let barangayRequestId = 0;

const provinceNames = computed(() => provinces.value.map((item) => item.name));
const cityNames = computed(() => cities.value.map((item) => item.name));
const barangayNames = computed(() => barangays.value.map((item) => item.name));
const codeFor = (list, name) => list.find((item) => item.name === name)?.code;
const byName = (a, b) => a.name.localeCompare(b.name);

const fetchPsgc = async (path) => {
    const response = await fetch(`${PSGC_BASE}${path}`);
    if (!response.ok) throw new Error(`PSGC request failed (${response.status})`);
    const data = await response.json();
    return data.map(({ code, name }) => ({ code, name })).sort(byName);
};

const loadProvinces = () => {
    if (!provincesPromise) {
        provincesLoading.value = true;
        provincesPromise = fetchPsgc('/provinces/')
            .then((list) => {
                // NCR has no provinces in PSGC, so it is listed as its own entry.
                provinces.value = [...list, { code: NCR_CODE, name: NCR_NAME }].sort(byName);
            })
            .catch(() => {
                provincesPromise = null;
                payoutLoadError.value = loadErrorMessage;
            })
            .finally(() => {
                provincesLoading.value = false;
            });
    }
    return provincesPromise;
};

const loadCities = async (provinceName) => {
    const requestId = ++cityRequestId;
    cities.value = [];
    const code = codeFor(provinces.value, provinceName);
    if (!code) return;

    const path =
        code === NCR_CODE
            ? `/regions/${code}/cities-municipalities/`
            : `/provinces/${code}/cities-municipalities/`;
    citiesLoading.value = true;
    try {
        const list = await fetchPsgc(path);
        if (requestId === cityRequestId) cities.value = list;
    } catch {
        if (requestId === cityRequestId) payoutLoadError.value = loadErrorMessage;
    } finally {
        if (requestId === cityRequestId) citiesLoading.value = false;
    }
};

const loadBarangays = async (cityName) => {
    const requestId = ++barangayRequestId;
    barangays.value = [];
    const code = codeFor(cities.value, cityName);
    if (!code) return;

    barangaysLoading.value = true;
    try {
        const list = await fetchPsgc(`/cities-municipalities/${code}/barangays/`);
        if (requestId === barangayRequestId) barangays.value = list;
    } catch {
        if (requestId === barangayRequestId) payoutLoadError.value = loadErrorMessage;
    } finally {
        if (requestId === barangayRequestId) barangaysLoading.value = false;
    }
};

const hydratePayoutOptions = async (target) => {
    payoutLoadError.value = '';
    cities.value = [];
    barangays.value = [];
    await loadProvinces();
    if (target.province) await loadCities(target.province);
    if (target.city) await loadBarangays(target.city);
};

const retryPayoutLoad = () => hydratePayoutOptions(targetDraft.value);

const onProvinceSelect = (name) => {
    targetDraft.value.province = name;
    targetDraft.value.city = '';
    targetDraft.value.barangay = '';
    payoutError.value = '';
    barangayRequestId++;
    barangays.value = [];
    loadCities(name);
};

const onCitySelect = (name) => {
    targetDraft.value.city = name;
    targetDraft.value.barangay = '';
    payoutError.value = '';
    loadBarangays(name);
};

const onBarangaySelect = (name) => {
    targetDraft.value.barangay = name;
    payoutError.value = '';
};

const onDisasterSelect = (name) => {
    targetDraft.value.disasterName = name;
    disasterError.value = '';
};

// Searchable dropdown behaviour shared by Disaster Type, Province, City/ Municipality and Barangay
const openField = ref(null);
const fieldQuery = ref('');
const activeIndex = ref(0);

const disasterField = computed(() => ({
    key: 'disasterName',
    label: 'Disaster Type',
    placeholder: 'Search disaster type',
    options: disasterNames,
    loading: false,
    disabled: false,
    select: onDisasterSelect,
}));

const payoutFields = computed(() => [
    {
        key: 'province',
        label: 'Province',
        placeholder: 'Search province',
        options: provinceNames.value,
        loading: provincesLoading.value,
        disabled: false,
        select: onProvinceSelect,
    },
    {
        key: 'city',
        label: 'City/ Municipality',
        placeholder: 'Search city or municipality',
        options: cityNames.value,
        loading: citiesLoading.value,
        disabled: !targetDraft.value.province,
        select: onCitySelect,
    },
    {
        key: 'barangay',
        label: 'Barangay',
        placeholder: 'Search barangay',
        options: barangayNames.value,
        loading: barangaysLoading.value,
        disabled: !targetDraft.value.city,
        select: onBarangaySelect,
    },
]);

const activeOptions = computed(() => {
    const field = [disasterField.value, ...payoutFields.value].find((item) => item.key === openField.value);
    if (!field) return [];
    const term = fieldQuery.value.trim().toLowerCase();
    return term ? field.options.filter((option) => option.toLowerCase().includes(term)) : field.options;
});

const scrollActiveIntoView = () => {
    nextTick(() => {
        document.querySelector('.target-dialog .ss-list li.ss-active')?.scrollIntoView({ block: 'nearest' });
    });
};

const closeFieldList = () => {
    openField.value = null;
    fieldQuery.value = '';
};

const openFieldList = (field) => {
    if (field.disabled || openField.value === field.key) return;
    openField.value = field.key;
    fieldQuery.value = '';
    activeIndex.value = Math.max(0, field.options.indexOf(targetDraft.value[field.key]));
    scrollActiveIntoView();
};

const pickOption = (field, option) => {
    field.select(option);
    closeFieldList();
    document.activeElement?.blur();
};

const onFieldInput = (event) => {
    fieldQuery.value = event.target.value;
    activeIndex.value = 0;
};

const onFieldKeydown = (event, field) => {
    if (event.key === 'ArrowDown') {
        event.preventDefault();
        if (openField.value !== field.key) return openFieldList(field);
        activeIndex.value = Math.min(activeIndex.value + 1, activeOptions.value.length - 1);
        scrollActiveIntoView();
    } else if (event.key === 'ArrowUp') {
        event.preventDefault();
        activeIndex.value = Math.max(activeIndex.value - 1, 0);
        scrollActiveIntoView();
    } else if (event.key === 'Enter' && openField.value === field.key) {
        event.preventDefault();
        const option = activeOptions.value[activeIndex.value];
        if (option !== undefined) pickOption(field, option);
    } else if (event.key === 'Escape' && openField.value === field.key) {
        event.preventDefault();
        closeFieldList();
    } else if (event.key === 'Tab') {
        closeFieldList();
    }
};

const closeFieldOnOutsidePointer = (event) => {
    if (openField.value && !event.target.closest?.('.dropdown-field')) closeFieldList();
};

watch([payoutOpen, showTargetDialog], closeFieldList);

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

const pageCount = computed(() => Math.max(1, Math.ceil(filteredTargets.value.length / pageSize)));
const visibleTargets = computed(() => {
    const start = (page.value - 1) * pageSize;
    return filteredTargets.value.slice(start, start + pageSize);
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
    payoutOpen.value = false;
    payoutError.value = '';
    disasterError.value = '';
    payoutLoadError.value = '';
    cities.value = [];
    barangays.value = [];
    showTargetDialog.value = true;
    loadProvinces();
};

const openEditTarget = (target) => {
    editingTargetId.value = target.id;
    targetDraft.value = { ...target };
    payoutOpen.value = false;
    payoutError.value = '';
    disasterError.value = '';
    showTargetDialog.value = true;
    hydratePayoutOptions(target);
};

const closeTargetDialog = () => {
    showTargetDialog.value = false;
};

const saveTarget = () => {
    const draft = targetDraft.value;
    let valid = true;

    if (isDrmd.value && !(draft.disasterName || '').trim()) {
        disasterError.value = 'Select a disaster type.';
        valid = false;
    }
    if (![draft.province, draft.city, draft.barangay].every((part) => (part || '').trim())) {
        payoutError.value = 'Fill in Province, City/ Municipality and Barangay.';
        payoutOpen.value = true;
        valid = false;
    }
    if (!valid) return;

    disasterError.value = '';
    payoutError.value = '';
    pendingTargetSave.value = {
        isNew: editingTargetId.value === null,
        target: { ...draft },
        targetId: editingTargetId.value,
    };
};

const showActionNotification = (message) => {
    window.clearTimeout(actionNotificationTimer);
    actionNotification.value = message;
    actionNotificationTimer = window.setTimeout(() => {
        actionNotification.value = '';
    }, 3500);
};

const confirmTargetSave = () => {
    const pendingSave = pendingTargetSave.value;
    if (!pendingSave) return;

    if (pendingSave.isNew) {
        targets.value.unshift({ id: Date.now(), ...pendingSave.target });
    } else {
        const targetIndex = targets.value.findIndex((item) => item.id === pendingSave.targetId);
        if (targetIndex !== -1) {
            targets.value[targetIndex] = { id: pendingSave.targetId, ...pendingSave.target };
        }
    }

    showActionNotification(pendingSave.isNew ? 'Target added successfully.' : 'Target updated successfully.');
    pendingTargetSave.value = null;
    showTargetDialog.value = false;
};

const closeFilterOnOutsidePointer = (event) => {
    if (filterOpen.value && !filterRoot.value?.contains(event.target)) {
        filterOpen.value = false;
    }
};

onMounted(() => {
    document.addEventListener('pointerdown', closeFilterOnOutsidePointer);
    document.addEventListener('pointerdown', closeFieldOnOutsidePointer);
});
onUnmounted(() => {
    document.removeEventListener('pointerdown', closeFilterOnOutsidePointer);
    document.removeEventListener('pointerdown', closeFieldOnOutsidePointer);
    window.clearTimeout(actionNotificationTimer);
});

watch(search, () => { page.value = 1; });
</script>

<style scoped>
* { box-sizing: border-box; }

.target-workspace {
    display: flex;
    flex-direction: column;
    width: 100%;
    min-width: 0;
    height: 100vh;
    padding: 22px 28px 24px;
    overflow: hidden;
    background: #f4f7fb;
}

.target-header {
    flex: 0 0 auto;
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

.filter-trigger.filter-active {
    border-color: #9fc9ed;
    background: #e5f3ff;
    color: #256da8;
}

.action-notification {
    position: absolute;
    right: 0;
    bottom: calc(100% + 10px);
    z-index: 40;
    display: flex;
    align-items: center;
    gap: 7px;
    min-width: max-content;
    padding: 9px 12px;
    border: 1px solid #b8e1c5;
    border-radius: 4px;
    background: #effcf3;
    box-shadow: 0 5px 14px rgb(13 78 42 / 14%);
    color: #237644;
    font-size: 12px;
    font-weight: 600;
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

/* Keep every target column within the page width without horizontal scrolling. */
.target-table-scroll {
    flex: 1 1 auto;
    width: 100%;
    max-width: 100%;
    min-width: 0;
    min-height: 0;
    margin: 0;
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

.target-table-scroll::-webkit-scrollbar {
    width: 12px;
}

.target-table-scroll::-webkit-scrollbar-track,
.target-table-scroll::-webkit-scrollbar-button {
    background: transparent;
}

.target-table-scroll::-webkit-scrollbar-button {
    display: none;
}

.target-table-scroll::-webkit-scrollbar-thumb {
    border: 3px solid transparent;
    border-radius: 8px;
    background-color: #9aa6b2;
    background-clip: content-box;
}

.target-table {
    width: 100%;
    min-width: 0;
    table-layout: fixed;
    border-collapse: collapse;
    border-spacing: 0;
    background: #fff;
    color: #111827;
    font-size: 14px;
    font-weight: 500;
    text-align: left;
    white-space: normal;
}

.target-table th:nth-child(1),
.target-table td:nth-child(1) { width: 11%; }
.target-table th:nth-child(2),
.target-table td:nth-child(2) { width: 12.5%; }
.target-table th:nth-child(3),
.target-table td:nth-child(3) { width: 16.5%; }
.target-table th:nth-child(4),
.target-table td:nth-child(4) { width: 17.5%; }
.target-table th:nth-child(5),
.target-table td:nth-child(5) { width: 18%; }
.target-table th:nth-child(6),
.target-table td:nth-child(6) { width: 10%; }
.target-table th:nth-child(7),
.target-table td:nth-child(7) { width: 10%; }
.target-table th:nth-child(8),
.target-table td:nth-child(8) { width: 4.5%; }

.target-table td {
    height: 76px;
    padding: 0 14px;
    border-top: 1px solid #e4e8ef;
    overflow-wrap: anywhere;
}

.target-table th {
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

.target-table .action-col {
    width: auto;
    text-align: center;
    background: #fff;
}

.target-table th.action-col {
    background: #f8faff;
}

.target-table tbody tr:hover {
    background: #f8faff;
}

.target-table tbody tr:hover td.action-col {
    background: #f8faff;
}

.payout-cell {
    vertical-align: middle;
}

.payout-toggle {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    padding: 4px 0;
    border: 0;
    background: transparent;
    color: #302b9c;
    cursor: pointer;
    font: inherit;
    font-size: 14px;
    font-weight: 600;
}

.payout-toggle i {
    font-size: 10px;
}

.payout-details {
    display: grid;
    gap: 4px;
    margin: 4px 0 8px;
    padding: 0;
    list-style: none;
    font-size: 13px;
}

.payout-details li {
    display: grid;
    grid-template-columns: 120px 1fr;
    gap: 8px;
}

.payout-details li span {
    color: #718096;
    font-weight: 600;
}

.edit-target-button {
    display: grid;
    width: 28px;
    height: 28px;
    margin: 0 auto;
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
    flex: 0 0 auto;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 24px;
    width: 100%;
    min-height: 44px;
    margin: 0 0 16px;
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

/* Input fields: light gray, regular weight */
.target-dialog input,
.target-dialog select {
    width: 100%;
    min-height: 34px;
    padding: 6px 9px;
    border: 1px solid #cfd8e5;
    border-radius: 4px;
    background: #fff;
    color: #8a94a6;
    font: inherit;
    font-weight: 400;
}

.target-dialog input::placeholder {
    color: #a0aab8;
    font-weight: 400;
    opacity: 1;
}

.target-dialog .program-type-select {
    padding-right: 38px;
    appearance: none;
    background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 12 8'%3E%3Cpath d='m1 1 5 5 5-5' fill='none' stroke='%23516074' stroke-linecap='round' stroke-linejoin='round' stroke-width='1.5'/%3E%3C/svg%3E");
    background-position: right 18px center;
    background-repeat: no-repeat;
    background-size: 10px 7px;
}

.target-dialog .payout-type-select {
    padding-right: 38px;
    appearance: none;
    background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 12 8'%3E%3Cpath d='m1 1 5 5 5-5' fill='none' stroke='%23516074' stroke-linecap='round' stroke-linejoin='round' stroke-width='1.5'/%3E%3C/svg%3E");
    background-position: right 18px center;
    background-repeat: no-repeat;
    background-size: 10px 7px;
}

/* Searchable dropdowns (Disaster Type, Province, City/ Municipality, Barangay) */
.dropdown-field {
    display: grid;
    gap: 5px;
}

.dropdown-field-label {
    color: #39465a;
    font-size: 12px;
    font-weight: 600;
}

.ss-control {
    position: relative;
    display: flex;
    align-items: center;
}

.target-dialog .ss-input {
    padding-right: 28px;
    color: #8a94a6;
    font-size: 12px;
    font-weight: 400;
}

.target-dialog .ss-input:focus {
    outline: 2px solid #c9c6f2;
    border-color: #302b9c;
}

.target-dialog .ss-input:disabled {
    background: #edf1f6;
    cursor: not-allowed;
}

.target-dialog .ss-input.ss-invalid {
    border-color: #d64545;
}

.ss-chevron {
    position: absolute;
    right: 18px;
    width: 10px;
    height: 7px;
    background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 12 8'%3E%3Cpath d='m1 1 5 5 5-5' fill='none' stroke='%23516074' stroke-linecap='round' stroke-linejoin='round' stroke-width='1.5'/%3E%3C/svg%3E");
    background-repeat: no-repeat;
    background-size: 10px 7px;
    color: #718096;
    font-size: 0;
    pointer-events: none;
    transition: transform 0.15s ease;
}

.ss-open .ss-chevron {
    transform: rotate(180deg);
}

.ss-list {
    max-height: 180px;
    margin: 0;
    padding: 4px;
    overflow-y: auto;
    border: 1px solid #dce3ed;
    border-radius: 4px;
    background: #fff;
    list-style: none;
}

.ss-list li {
    padding: 7px 9px;
    border-radius: 3px;
    color: #6b7686;
    cursor: pointer;
    font-size: 12px;
    font-weight: 400;
}

.ss-list li.ss-active {
    background: #eeedff;
}

.ss-list li.ss-selected {
    color: #302b9c;
    font-weight: 700;
}

.ss-list li.ss-empty {
    color: #718096;
    cursor: default;
}

/* Payout Site trigger and its ordered fields */
.payout-trigger {
    position: relative;
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 8px;
    width: 100%;
    min-height: 34px;
    padding: 6px 38px 6px 9px;
    border: 1px solid #cfd8e5;
    border-radius: 4px;
    background: #fff;
    color: #8a94a6;
    cursor: pointer;
    font: inherit;
    font-size: 12px;
    font-weight: 400;
    text-align: left;
}

.payout-placeholder {
    color: #a0aab8;
}

.payout-trigger.ss-invalid {
    border-color: #d64545;
}

.payout-trigger::after {
    position: absolute;
    top: 50%;
    right: 18px;
    width: 10px;
    height: 7px;
    background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 12 8'%3E%3Cpath d='m1 1 5 5 5-5' fill='none' stroke='%23516074' stroke-linecap='round' stroke-linejoin='round' stroke-width='1.5'/%3E%3C/svg%3E");
    background-repeat: no-repeat;
    background-size: 10px 7px;
    content: '';
    pointer-events: none;
    transform: translateY(-50%);
    transition: transform 150ms ease;
}

.payout-trigger.payout-open::after {
    transform: translateY(-50%) rotate(180deg);
}

.payout-fields {
    display: grid;
    gap: 10px;
    padding: 12px;
    border: 1px solid #e4e8ef;
    border-radius: 4px;
    background: #f8faff;
}

.field-error {
    margin: 0;
    color: #c53030;
    font-size: 12px;
}

.payout-retry {
    margin-left: 6px;
    padding: 0;
    border: 0;
    background: transparent;
    color: #302b9c;
    cursor: pointer;
    font: inherit;
    font-weight: 600;
    text-decoration: underline;
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

.confirmation-dialog {
    display: grid;
    justify-items: center;
    gap: 12px;
    width: min(100%, 390px);
    padding: 26px;
    border: 1px solid #e0e5ed;
    border-radius: 8px;
    background: #fff;
    box-shadow: 0 16px 48px rgb(13 28 51 / 24%);
    text-align: center;
}

.confirmation-icon {
    display: grid;
    width: 46px;
    height: 46px;
    place-items: center;
    border-radius: 50%;
    background: #eeedff;
    color: #302b9c;
    font-size: 18px;
}

.confirmation-dialog h2 {
    margin: 0;
    color: #20242c;
    font-size: 20px;
}

.confirmation-dialog p {
    margin: 0;
    color: #64748b;
    font-size: 14px;
    line-height: 1.5;
}

.confirmation-dialog .dialog-actions {
    width: 100%;
    justify-content: center;
    margin-top: 8px;
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

}

@media (max-width: 720px) {
    .target-workspace {
        padding: 16px 12px 16px;
    }
}

@media (max-width: 520px) {
    .target-heading h1 {
        font-size: 24px;
    }
}
</style>