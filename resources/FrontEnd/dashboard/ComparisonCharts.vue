<template>
  <section class="progress-overview">
    <div class="progress-overview-heading">
      <div class="overview-title">
        <i class="pi pi-percentage"></i>
        <strong>Progress Overview</strong>
      </div>
      <div class="overview-filters">
        <button type="button" class="quick-filter active">Last 7 Days</button>
        <button type="button" class="quick-filter">Last 30 Days</button>
        <input :value="dateFrom" type="date" aria-label="Progress start date" @change="emit('update:dateFrom', $event.target.value)" />
        <input :value="dateTo" type="date" aria-label="Progress end date" @change="emit('update:dateTo', $event.target.value)" />
        <button type="button" class="apply-filter" @click="emit('update:dateFrom', dateFrom)">Apply</button>
      </div>
    </div>

    <div class="chart-layout">
      <section class="chart-card target-distribution-card">
        <h3>Target Distribution</h3>
        <p>Total paid vs. remaining across all barangays.</p>
        <div class="donut-content">
          <div class="donut" :style="donutStyle">
            <div class="donut-hole"></div>
          </div>
          <div class="donut-legend">
            <div class="legend-item" v-if="hasPaid">
              <span class="legend-circle paid-dot">{{ dashboardProgress }}%</span>
              <div class="legend-info">
                <span>Paid</span>
                <strong>{{ formatNumber(totalPaidCount) }}</strong>
              </div>
            </div>
            <div class="legend-item" v-if="hasRemaining">
              <span class="legend-circle remaining-dot">{{ 100 - dashboardProgress }}%</span>
              <div class="legend-info">
                <span>Remaining</span>
                <strong>{{ formatNumber(remainingCount) }}</strong>
              </div>
            </div>
            <div class="legend-empty" v-if="!hasPaid && !hasRemaining">No data yet</div>
          </div>
        </div>
      </section>

      <section class="chart-card barangay-progress-card">
        <div class="chart-card-heading">
          <div>
            <h3>Barangay Progress</h3>
            <p>Paid vs. remaining target for each barangay.</p>
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
import { computed } from 'vue';

const props = defineProps({
  comparisonRows: { type: Array, default: () => [] },
  dateFrom: { type: String, default: '' },
  dateTo: { type: String, default: '' },
  dashboardProgress: { type: Number, default: 0 },
  totalPaidCount: { type: [Number, String], default: 0 },
  totalTarget: { type: [Number, String], default: 0 },
});

const emit = defineEmits(['update:dateFrom', 'update:dateTo']);

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

const formatNumber = (value) => Number(value || 0).toLocaleString();
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
  background: #eaf3fc;
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
  border: 1px solid #5968bb;
  border-radius: 5px;
  background: #fff;
  color: #192782;
  font-size: 0.7rem;
  font-weight: 700;
  box-sizing: border-box;
}

.quick-filter.active { box-shadow: 0 2px 6px rgba(21, 42, 132, 0.22); }

.overview-filters input {
  width: 150px;
  height: 26px;
  padding: 4px 8px;
  border: 1px solid #5968bb;
  border-radius: 5px;
  background: #fff;
  color: #26366e;
  font-size: 0.67rem;
  box-sizing: border-box;
}

.apply-filter {
  min-width: 52px;
  border-radius: 5px;
  background: #171b82;
  color: #fff;
}

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

.chart-card h3 { margin: 0; color: #252525; font-size: 0.95rem; }
.chart-card p { margin: 6px 0 0; color: #a1a1a1; font-size: 0.72rem; }

.donut-content {
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 28px;
  height: 220px;
}

.donut {
  display: grid;
  place-items: center;
  width: 184px;
  height: 184px;
  border-radius: 50%;
  background: conic-gradient(#f9e943 0 var(--paid-angle, 0deg), #211889 var(--paid-angle, 0deg) 360deg);
}

.donut-hole { width: 104px; height: 104px; border-radius: 50%; background: #fff; }

.donut-legend { display: grid; gap: 20px; min-width: 150px; }

.legend-item {
  display: flex;
  align-items: center;
  gap: 12px;
}

.legend-circle {
  display: grid;
  place-items: center;
  width: 48px;
  height: 48px;
  border-radius: 50%;
  font-size: 0.68rem;
  font-weight: 800;
  flex-shrink: 0;
}

.legend-circle.paid-dot { background: #f9e943; color: #4a3f00; }
.legend-circle.remaining-dot { background: #211889; color: #fff; }

.legend-info {
  display: flex;
  flex-direction: column;
  gap: 2px;
  color: #343434;
  font-size: 0.72rem;
}

.legend-info strong { font-size: 0.9rem; color: #202020; }
.legend-empty { color: #a1a1a1; font-size: 0.78rem; align-self: center; }

.legend-dot { display: inline-block; width: 8px; height: 8px; border-radius: 50%; }
.paid-dot { background: #f9e943; }
.remaining-dot { background: #211889; }

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
  .overview-filters input { flex: 1; min-width: 120px; }
  .donut-content { gap: 12px; }
  .donut { width: 150px; height: 150px; }
  .donut-hole { width: 84px; height: 84px; }
}
</style>