<template>
  <section class="progress-overview">
    <div class="progress-overview-heading">
      <div class="overview-title">
        <i class="pi pi-percentage"></i>
        <strong>Progress Overview</strong>
      </div>
      <div class="overview-filters">
        <button
          type="button"
          :class="['quick-filter', { active: activeQuickFilter === 7 }]"
          @click="applyQuickFilter(7)"
        >
          Last 7 Days
        </button>
        <button
          type="button"
          :class="['quick-filter', { active: activeQuickFilter === 30 }]"
          @click="applyQuickFilter(30)"
        >
          Last 30 Days
        </button>
        <div :class="['date-field', { empty: !dateFrom }]">
          <span class="date-placeholder">From</span>
          <input :value="dateFrom" type="date" aria-label="Progress start date" @change="onManualDate('update:dateFrom', $event.target.value)" />
        </div>
        <div :class="['date-field', { empty: !dateTo }]">
          <span class="date-placeholder">To</span>
          <input :value="dateTo" type="date" aria-label="Progress end date" @change="onManualDate('update:dateTo', $event.target.value)" />
        </div>
        <button type="button" class="apply-filter" @click="emit('apply')">Apply</button>
      </div>
    </div>

    <div class="chart-layout">
      <section class="chart-card target-distribution-card">
        <h3>{{ meta.donutTitle }}</h3>
        <p>{{ donutSubtitle }}</p>
        <div class="donut-content">
          <div class="donut" :style="donutStyle">
            <div class="donut-hole"></div>
            <span v-if="showPaidLabel" class="donut-label paid-label" :style="paidLabelStyle">{{ formatPercent(paidPct) }}</span>
            <span v-if="showRemainingLabel" class="donut-label remaining-label" :style="remainingLabelStyle">{{ formatPercent(remainingPct) }}</span>
          </div>
          <div class="donut-legend">
            <div class="legend-item" v-if="hasPaid">
              <div class="legend-main">
                <span class="legend-label"><i class="legend-dot paid-dot"></i>Paid</span>
                <strong class="legend-amount">{{ paidAmount || formatNumber(totalPaidCount) }}</strong>
              </div>
              <span class="legend-pct">{{ formatPercent(paidPct) }}</span>
            </div>
            <div class="legend-item" v-if="hasRemaining">
              <div class="legend-main">
                <span class="legend-label"><i class="legend-dot remaining-dot"></i>Remaining</span>
                <strong class="legend-amount">{{ remainingAmount || formatNumber(remainingCount) }}</strong>
              </div>
              <span class="legend-pct">{{ formatPercent(remainingPct) }}</span>
            </div>
            <div class="legend-item" v-if="extraStat">
              <div class="legend-main">
                <span class="legend-label"><i class="legend-dot extra-dot"></i>{{ extraStat.label }}</span>
                <strong class="legend-amount">{{ formatNumber(extraStat.value) }}</strong>
              </div>
            </div>
            <div class="legend-empty" v-if="!hasPaid && !hasRemaining && !extraStat">No data yet</div>
          </div>
        </div>
      </section>

      <section class="chart-card barangay-progress-card">
        <div class="chart-card-heading">
          <div>
            <h3>{{ meta.barTitle }}</h3>
            <p>{{ meta.barSubtitle }}</p>
          </div>
          <div class="stacked-legend"><span><i class="legend-dot paid-dot"></i>Paid</span><span><i class="legend-dot remaining-dot"></i>Remaining</span></div>
        </div>
        <div v-if="!comparisonRows.length" class="chart-state">No payout records found for the selected filters.</div>
        <div v-else class="stacked-bars">
          <div v-for="row in comparisonRows" :key="row.id || row.name" class="stacked-row">
            <strong>{{ row.name }}</strong>
            <div class="stacked-track">
              <span class="stacked-paid" :style="{ width: `${row.progress || 0}%` }"></span>
              <span class="stacked-remaining" :style="{ width: `${100 - (row.progress || 0)}%` }"></span>
            </div>
            <small>{{ row.progress || 0 }}%<br>{{ formatNumber(row.paid) }}/{{ formatNumber(row.target) }}</small>
          </div>
        </div>
      </section>
    </div>
  </section>
</template>

<script setup>
import { computed, ref, watch } from 'vue';

const props = defineProps({
  comparisonRows: { type: Array, default: () => [] },
  dateFrom: { type: String, default: '' },
  dateTo: { type: String, default: '' },
  dashboardProgress: { type: Number, default: 0 },
  totalPaidCount: { type: [Number, String], default: 0 },
  totalTarget: { type: [Number, String], default: 0 },
  paidAmount: { type: String, default: '' },
  remainingAmount: { type: String, default: '' },
  // Which drill-down level is currently showing, so titles/labels stay accurate:
  // 'province' (nothing selected), 'municipality' (a province is selected),
  // 'barangay' (a municipality is selected), 'detail' (a barangay is selected)
  activeLevel: { type: String, default: 'province' },
  // Name of the parent entity currently drilled into (province/municipality/barangay name)
  scopeName: { type: String, default: '' },
  // Optional extra data point to surface once a barangay is selected (e.g. beneficiaries served)
  extraStat: { type: Object, default: null },
});
const emit = defineEmits(['update:dateFrom', 'update:dateTo', 'apply']);


/* ---------- Quick filter (Last 7 / 30 Days) ---------- */
const activeQuickFilter = ref(7);
let settingViaQuickFilter = false;

const toISODate = (d) => d.toISOString().slice(0, 10);

const applyQuickFilter = (days) => {
  settingViaQuickFilter = true;
  activeQuickFilter.value = days;
  const to = new Date();
  const from = new Date();
  from.setDate(to.getDate() - (days - 1));
  emit('update:dateFrom', toISODate(from));
  emit('update:dateTo', toISODate(to));
};

// If the person edits a date field by hand, the quick filter no longer applies — clear the highlight.
const onManualDate = (eventName, value) => {
  activeQuickFilter.value = null;
  emit(eventName, value);
};

watch([() => props.dateFrom, () => props.dateTo], () => {
  if (settingViaQuickFilter) {
    settingViaQuickFilter = false;
  }
});

/* ---------- Level-aware titles ---------- */
const LEVEL_META = {
  province: {
    donutTitle: 'Target Distribution',
    donutSubtitle: 'Total paid vs. remaining across all provinces.',
    barTitle: 'Province Progress',
    barSubtitle: 'Paid vs. remaining target for each province.',
  },
  municipality: {
    donutTitle: 'Province Progress',
    donutSubtitle: 'Total paid vs. remaining across all municipalities',
    barTitle: 'Municipality Progress',
    barSubtitle: 'Paid vs. remaining target for each municipality.',
  },
  barangay: {
    donutTitle: 'Municipality Progress',
    donutSubtitle: 'Total paid vs. remaining across all barangays',
    barTitle: 'Barangay Progress',
    barSubtitle: 'Paid vs. remaining target for each barangay.',
  },
  detail: {
    donutTitle: 'Barangay Progress',
    donutSubtitle: 'Paid vs. remaining for the selected barangay',
    barTitle: 'Barangay Detail',
    barSubtitle: 'Breakdown for the selected barangay.',
  },
};

const meta = computed(() => LEVEL_META[props.activeLevel] || LEVEL_META.province);
const donutSubtitle = computed(() => {
  if (props.activeLevel === 'province' || !props.scopeName) return meta.value.donutSubtitle;
  return `${meta.value.donutSubtitle} in ${props.scopeName}.`;
});

/* ---------- Donut math (unchanged) ---------- */
const remainingCount = computed(() => Math.max(Number(props.totalTarget || 0) - Number(props.totalPaidCount || 0), 0));
const hasPaid = computed(() => Number(props.totalPaidCount || 0) > 0);
const hasRemaining = computed(() => remainingCount.value > 0);

const donutStyle = computed(() => {
  if (hasPaid.value && !hasRemaining.value) {
    return { background: '#f9e943' };
  }
  if (!hasPaid.value && hasRemaining.value) {
    return { background: '#211889' };
  }
  if (!hasPaid.value && !hasRemaining.value) {
    return { background: '#e6ebf2' };
  }
  return { '--paid-angle': `${props.dashboardProgress * 3.6}deg` };
});

const paidPct = computed(() => {
  if (!hasPaid.value) return 0;
  if (!hasRemaining.value) return 100;
  return Math.min(Math.max(Number(props.dashboardProgress) || 0, 0), 100);
});
const remainingPct = computed(() => (hasRemaining.value ? Math.round((100 - paidPct.value) * 100) / 100 : 0));

const MIN_LABEL_PCT = 6;
const showPaidLabel = computed(() => hasPaid.value && paidPct.value >= MIN_LABEL_PCT);
const showRemainingLabel = computed(() => hasRemaining.value && remainingPct.value >= MIN_LABEL_PCT);

const paidAngle = computed(() => paidPct.value * 3.6);
const labelPosition = (midAngle) => {
  const rad = (midAngle * Math.PI) / 180;
  return { '--lx': Math.sin(rad).toFixed(4), '--ly': (-Math.cos(rad)).toFixed(4) };
};
const paidLabelStyle = computed(() => labelPosition(paidAngle.value / 2));
const remainingLabelStyle = computed(() => labelPosition(paidAngle.value + (360 - paidAngle.value) / 2));

const formatNumber = (value) => Number(value || 0).toLocaleString();
const formatPercent = (value) => `${Number(Number(value).toFixed(2))}%`;
</script>

<style scoped>
.progress-overview {
  overflow: hidden;
  margin-top: 26px;
  border: 1px solid #d4dee8;
  border-radius: 8px;
  background: #f4f8fc;
  box-shadow: 0 8px 18px rgba(24, 67, 101, 0.14);
}

.progress-overview-heading {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 18px;
  min-height: 54px;
  padding: 10px 24px;
  border-bottom: 1px solid #d5e0ea;
  background: linear-gradient(90deg, #E8F3FF 0%, #FBFDFF 50%, #FDFEFF 100%);
}

.overview-title {
  display: flex;
  align-items: center;
  gap: 14px;
  color: #52658b;
  font-size: 0.86rem;
}

.overview-title i { font-size: 1.45rem; }

.overview-filters {
  display: flex;
  align-items: center;
  gap: 8px;
  flex-wrap: wrap;
}

.quick-filter,
.apply-filter {
  width: auto;
  min-width: 104px;
  height: 26px;
  padding: 4px 14px;
  border: 1.5px solid #181A7E;
  border-radius: 5px;
  color: #192782;
  font-size: 0.7rem;
  font-weight: 600;
  box-sizing: border-box;
  cursor: pointer;
  background: #fff;
  transition: background 0.15s ease, box-shadow 0.15s ease, color 0.15s ease;
}

.quick-filter:hover {
  background: #eef2ff;
  box-shadow: 0 2px 6px rgba(21, 42, 132, 0.15);
}

.quick-filter.active {
  background: #181A7E;
  color: #fff;
  box-shadow: 0 2px 6px rgba(21, 42, 132, 0.22);
}

.quick-filter.active:hover {
  background: #12145f;
}

.overview-filters input {
  width: 150px;
  height: 26px;
  padding: 4px 8px;
  border: 1.4px solid #181A7E;
  border-radius: 5px;
  background: #fff;
  color: #26366e;
  font-size: 0.67rem;
  box-sizing: border-box;
}

.date-field {
  position: relative;
  display: inline-flex;
}

.date-placeholder {
  display: none;
  position: absolute;
  top: 50%;
  left: 9px;
  transform: translateY(-50%);
  color: #6b7a99;
  font-size: 0.67rem;
  pointer-events: none;
}

.date-field.empty .date-placeholder { display: block; }
.date-field.empty input { color: transparent; }
.date-field.empty input:focus { color: #26366e; }
.date-field.empty:focus-within .date-placeholder { display: none; }

.apply-filter {
  min-width: 52px;
  border-radius: 5px;
  background: #171b82;
  color: #fff;
  border-color: #171b82;
}

.apply-filter:hover { background: #12145f; }

.chart-layout {
  display: grid;
  grid-template-columns: 1fr 1.2fr;
  gap: 20px;
  padding: 24px;
}

.chart-card {
  min-height: 300px;
  padding: 20px 22px;
  border: 1px solid #d9e1e9;
  border-radius: 8px;
  background: #fff;
  box-shadow: 0 5px 9px rgba(24, 67, 101, 0.18);
}

.chart-card h3 { margin: 0; color: #252525; font-size: 1.15rem; font-weight: 700; }
.chart-card p { margin: 6px 0 0; color: #a1a1a1; font-size: 0.72rem; }

.donut-content {
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 28px;
  height: 220px;
}

.donut {
  --donut-size: 184px;
  --hole-size: 104px;
  --ring-r: calc((var(--donut-size) + var(--hole-size)) / 4);
  position: relative;
  display: grid;
  place-items: center;
  width: var(--donut-size);
  height: var(--donut-size);
  border-radius: 50%;
  background: conic-gradient(#F7E64B 0 var(--paid-angle, 0deg), #130774 var(--paid-angle, 0deg) 360deg);
}

.donut-hole { width: var(--hole-size); height: var(--hole-size); border-radius: 50%; background: #fff; }

.donut-label {
  position: absolute;
  left: calc(50% + var(--lx) * var(--ring-r));
  top: calc(50% + var(--ly) * var(--ring-r));
  transform: translate(-50%, -50%);
  font-size: 0.66rem;
  font-weight: 700;
  white-space: nowrap;
  pointer-events: none;
}

.paid-label { color: #2b2500; }
.remaining-label { color: #f9e943; }

.donut-legend {
  display: grid;
  grid-template-columns: auto auto;
  column-gap: 46px;
  row-gap: 18px;
  min-width: 150px;
}

.legend-item {
  display: grid;
  grid-template-columns: subgrid;
  grid-column: 1 / -1;
  align-items: end;
}

.legend-empty { grid-column: 1 / -1; }
.legend-main {
  display: flex;
  flex-direction: column;
  gap: 4px;
}

.legend-label {
  display: inline-flex;
  align-items: center;
  gap: 6px;
  color: #343434;
  font-size: 0.72rem;
}

.legend-amount { color: #202020; font-size: 0.95rem; font-weight: 700; }

.legend-pct {
  padding-bottom: 2px;
  color: #a1a1a1;
  font-size: 0.72rem;
  font-weight: 500;
}

.legend-empty { color: #a1a1a1; font-size: 0.78rem; align-self: center; }

.legend-dot { display: inline-block; width: 8px; height: 8px; border-radius: 50%; }
.paid-dot { background: #f9e943; }
.remaining-dot { background: #211889; }
.extra-dot { background: #25a269; }

.chart-card-heading { display: flex; justify-content: space-between; gap: 12px; }

.stacked-legend { display: flex; gap: 12px; color: #242424; font-size: 0.68rem; }
.stacked-legend span { display: inline-flex; align-items: center; gap: 5px; }

.stacked-bars { display: grid; gap: 14px; margin-top: 24px; }

.stacked-row {
  display: grid;
  grid-template-columns: 72px 1fr 54px;
  align-items: center;
  gap: 10px;
}

.stacked-row > strong { color: #606060; font-size: 0.72rem; }
.stacked-track { display: flex; height: 28px; overflow: hidden; background: #211889; }
.stacked-paid { background: #f9e943; }
.stacked-remaining { background: #211889; }
.stacked-row > small { color: #4b4b4b; font-size: 0.62rem; line-height: 1.4; }
.chart-state { padding: 80px 10px; color: #607897; text-align: center; font-size: 0.82rem; }

@media (max-width: 900px) { .chart-layout { grid-template-columns: 1fr; } }

@media (max-width: 768px) {
  .progress-overview-heading { align-items: flex-start; flex-direction: column; }
  .overview-filters { width: 100%; }
  .date-field { flex: 1; min-width: 120px; }
  .date-field input { width: 100%; }
  .donut-content { gap: 12px; }
  .donut { --donut-size: 150px; --hole-size: 84px; }
  .donut-label { font-size: 0.6rem; }
}
</style>