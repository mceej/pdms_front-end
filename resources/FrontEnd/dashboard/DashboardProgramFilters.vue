<template>
    <div ref="filterRoot" class="dashboard-program-filters" aria-label="Dashboard program filters">
        <div class="aics-control">
            <button
                type="button"
                :class="['tab', 'aics-tab', { active: program === 'AICS' && !programType && !assistanceType }]"
                aria-haspopup="true"
                :aria-expanded="aicsMenuOpen"
                @click="toggleAicsMenu"
            >
                <i class="pi pi-box" aria-hidden="true"></i>
                AICS
                <i :class="['pi pi-chevron-down', 'filter-chevron', { open: aicsMenuOpen }]" aria-hidden="true"></i>
            </button>

            <div v-if="aicsMenuOpen" class="filter-menu filter-menu--aics">
                <button
                    type="button"
                    class="filter-menu-item filter-menu-clear"
                    :disabled="!programType && !assistanceType"
                    @click="clearAicsFilters"
                >
                    Clear selection
                </button>

                <span class="filter-menu-title">Type of Program</span>
                <button
                    v-for="option in aicsProgramOptions"
                    :key="option.value"
                    type="button"
                    :class="['filter-menu-item', { selected: programType === option.value }]"
                    @click="emit('update:programType', option.value)"
                >
                    {{ option.label }}
                </button>

                <span class="filter-menu-title filter-menu-title--divided">Type of Assistance</span>
                <button
                    v-for="option in assistanceOptions"
                    :key="option"
                    type="button"
                    :class="['filter-menu-item', { selected: assistanceType === option }]"
                    @click="emit('update:assistanceType', option)"
                >
                    {{ option }}
                </button>
            </div>
        </div>

        <div class="ect-control">
            <button
                type="button"
                :class="['tab', 'ect-tab', { active: program === 'ECT' && !disasterName }]"
                aria-haspopup="true"
                :aria-expanded="ectMenuOpen"
                @click="toggleEctMenu"
            >
                <i class="pi pi-file" aria-hidden="true"></i>
                ECT
                <i :class="['pi pi-chevron-down', 'filter-chevron', { open: ectMenuOpen }]" aria-hidden="true"></i>
            </button>

            <div v-if="ectMenuOpen" class="filter-menu filter-menu--ect">
                <button
                    type="button"
                    class="filter-menu-item filter-menu-clear"
                    :disabled="!disasterName"
                    @click="chooseDisaster('')"
                >
                    Clear selection
                </button>
                <span class="filter-menu-title">Name of the Disaster</span>
                <button
                    v-for="option in disasterOptions"
                    :key="option"
                    type="button"
                    :class="['filter-menu-item', { selected: disasterName === option }]"
                    @click="chooseDisaster(option)"
                >
                    {{ option }}
                </button>
            </div>
        </div>

        <template v-if="program === 'AICS'">
            <span v-if="aicsSelectionLabel" class="filter-badge">{{ aicsSelectionLabel }}</span>
        </template>
        <span v-else-if="disasterName" class="filter-badge">{{ disasterName }}</span>
    </div>
</template>

<script setup>
import { computed, defineEmits, defineProps, onMounted, onUnmounted, ref } from 'vue';

const props = defineProps({
    program: { type: String, required: true },
    programType: { type: String, default: '' },
    assistanceType: { type: String, default: '' },
    disasterName: { type: String, default: '' },
    disasterOptions: { type: Array, default: () => [] },
});

const emit = defineEmits([
    'update:program',
    'update:programType',
    'update:assistanceType',
    'update:disasterName',
    'clear:aics',
]);

const aicsProgramOptions = [
    { label: 'AICS', value: 'AICS' },
    { label: 'AKAP', value: 'AKAP' },
    { label: 'Uplift', value: 'AICS-Uplift' },
];

const assistanceOptions = [
    'Food Assistance',
    'Cash Relief Assistance',
    'Educational Assistance',
];

const filterRoot = ref(null);
const aicsMenuOpen = ref(false);
const ectMenuOpen = ref(false);
const programTypeLabel = computed(
    () => aicsProgramOptions.find((option) => option.value === props.programType)?.label || props.programType
);
const aicsSelectionLabel = computed(() => (
    [programTypeLabel.value, props.assistanceType].filter(Boolean).join(' / ')
));

const toggleAicsMenu = () => {
    const switchingProgram = props.program !== 'AICS';
    if (switchingProgram) emit('update:program', 'AICS');
    aicsMenuOpen.value = switchingProgram || !aicsMenuOpen.value;
    ectMenuOpen.value = false;
};

const toggleEctMenu = () => {
    const switchingProgram = props.program !== 'ECT';
    if (switchingProgram) emit('update:program', 'ECT');
    ectMenuOpen.value = switchingProgram || !ectMenuOpen.value;
    aicsMenuOpen.value = false;
};

const clearAicsFilters = () => {
    emit('clear:aics');
};

const chooseDisaster = (value) => {
    emit('update:disasterName', value);
    ectMenuOpen.value = false;
};

const closeMenus = (event) => {
    if (!filterRoot.value?.contains(event.target)) {
        aicsMenuOpen.value = false;
        ectMenuOpen.value = false;
    }
};

onMounted(() => document.addEventListener('click', closeMenus));
onUnmounted(() => document.removeEventListener('click', closeMenus));
</script>

<style scoped>
.dashboard-program-filters {
    display: flex;
    align-items: center;
    flex-wrap: wrap;
    gap: 10px;
}

.aics-control,
.ect-control {
    position: relative;
}

.tab {
    display: inline-flex;
    flex: 0 0 auto;
    align-items: center;
    justify-content: center;
    gap: 7px;
    width: auto;
    height: 34px;
    min-width: 82px;
    padding: 0 20px;
    border: 1px solid #fff;
    border-radius: 8px;
    background: transparent;
    color: #fff;
    font-size: 12px;
    font-weight: 700;
    cursor: pointer;
}

.tab:hover {
    background: #2e2789;
}

.tab.active {
    border-color: #fff;
    background: #fff;
    color: #0b247f;
    box-shadow: 0 2px 8px rgba(5, 37, 87, 0.24);
}

.tab.active {
    font-weight: 900;
    letter-spacing: 0.04em;
}

.aics-tab {
    height: 36px;
    min-width: 72px;
    padding: 0 12px;
    font-size: 11px;
}

.filter-chevron {
    margin-left: 2px;
    font-size: 10px;
    transition: transform 0.15s ease;
}

.filter-chevron.open {
    transform: rotate(180deg);
}

.filter-menu {
    position: absolute;
    top: calc(100% + 6px);
    left: 0;
    z-index: 20;
    display: flex;
    flex-direction: column;
    gap: 2px;
    min-width: 200px;
    max-height: 360px;
    padding: 6px;
    overflow-y: auto;
    border: 1px solid #6d86ed;
    border-radius: 8px;
    background: #073a91;
    box-shadow: 0 6px 16px rgba(5, 37, 87, 0.35);
}

.filter-menu--aics {
    min-width: 230px;
}

.filter-menu-title {
    padding: 7px 10px 4px;
    color: #cbd8ff;
    font-size: 10px;
    font-weight: 800;
    letter-spacing: 0.05em;
    text-transform: uppercase;
}

.filter-menu-title--divided {
    margin-top: 5px;
    border-top: 1px solid rgba(255, 255, 255, 0.15);
    padding-top: 10px;
}

.filter-menu-item {
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

.filter-menu-item:hover:not(:disabled),
.filter-menu-item.selected {
    background: #0649b9;
}

.filter-menu-clear {
    margin-bottom: 4px;
    border-bottom: 1px solid rgba(255, 255, 255, 0.15);
    border-radius: 6px 6px 0 0;
    color: #b9c8f5;
    font-weight: 500;
}

.filter-menu-clear:disabled {
    opacity: 0.4;
    cursor: default;
}

.filter-badge {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: auto;
    min-width: 98px;
    max-width: 320px;
    height: 24px;
    padding: 16px;
    overflow: hidden;
    border-radius: 6px;
    background: #fff;
    color: #063b95;
    font-size: 13px;
    font-weight: 700;
    white-space: nowrap;
    text-overflow: ellipsis;
}

@media (max-width: 520px) {
    .filter-badge {
        max-width: 100%;
    }
}
</style>
