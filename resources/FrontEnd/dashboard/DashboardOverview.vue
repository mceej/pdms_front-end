<template>
  <section class="stats-grid">
    <div class="metric-card">
      <div class="metric-label">Total Balance</div>
      <div class="metric-value">{{ totalBalance }}</div>
    </div>
    <div class="metric-card">
      <div class="metric-label">Total Paid</div>
      <div class="metric-value">{{ formatNumber(totalPaidCount) }}</div>
    </div>
    <div class="metric-card">
      <div class="metric-label">Total Disbursed</div>
      <div class="metric-value">{{ totalDisbursed }}</div>
    </div>
    <div class="metric-card metric-card-warning">
      <div class="metric-label">Unpaid Balance</div>
      <div class="metric-value">{{ unpaidBalance }}</div>
    </div>
    <div class="metric-card metric-card-warning">
      <div class="metric-label">Unpaid Disbursed</div>
      <div class="metric-value">{{ unpaidDisbursed }}</div>
    </div>
  </section>
</template>

<script setup>
const props = defineProps({
  totalTarget: [Number, String],
  totalDisbursed: { type: String, default: '-----' },
  totalPaidCount: [Number, String],
  totalBalance: { type: String, default: '-----' },
  unpaidBalance: { type: String, default: '-----' },
  unpaidDisbursed: { type: String, default: '-----' },
});

const formatNumber = (value) => {
  if (value === null || value === undefined || value === '' || value === '-----') return '-----';
  const numericValue = Number(value);
  return Number.isFinite(numericValue) ? numericValue.toLocaleString() : String(value);
};
</script>

<style scoped>
.stats-grid {
  --card-blue-border: #a9cdf7;
  --card-blue-label: #edf4ff;
  --card-blue-text: #173d68;
  --card-blue-value: #172c8b;
  --card-red-border: #ffbd7d;
  --card-red-label: #fff1e6;
  --card-red-text: #c33118;
  --card-red-value: #c52d12;
}

.stats-grid {
  display: grid;
  grid-template-columns: repeat(6, minmax(0, 1fr));
  gap: 14px;
  margin: 0 0 28px;
}

.metric-card {
  position: relative;
  display: flex;
  flex-direction: column;
  min-height: 112px;
  padding: 0;
  overflow: visible;
  border: 0;
  border-radius: 14px;
  background: #fff;
  box-shadow: 0 4px 14px rgba(24, 67, 101, 0.1);
  transition: transform 0.2s ease, box-shadow 0.2s ease;
}

.metric-card:nth-child(-n + 3) {
  grid-column: span 2;
}

.metric-card-warning {
  grid-column: span 3;
  background: #fff5ed;
  box-shadow: none;
}

.metric-card:hover {
  transform: translateY(-2px);
  box-shadow: 0 8px 20px rgba(24, 67, 101, 0.16);
}

.metric-icon {
  position: absolute;
  top: 16px;
  right: 16px;
  display: flex;
  align-items: center;
  justify-content: center;
  width: 38px;
  height: 38px;
  border-radius: 10px;
  font-size: 1rem;
}

.metric-label {
  display: flex;
  align-items: center;
  min-height: 38px;
  padding: 8px 32px;
  border: 2px solid var(--card-blue-border);
  border-bottom: 0;
  border-radius: 14px 14px 0 0;
  background: var(--card-blue-label);
  color: var(--card-blue-text);
  font-size: 10px;
  text-transform: uppercase;
  letter-spacing: 0.04em;
  line-height: 1.35;
  font-weight: 600;
}

.metric-card-warning .metric-label {
  padding: 8px 32px;
  border-color: var(--card-red-border);
  background: var(--card-red-label);
  color: var(--card-red-text);
  font-size: 10px;
  font-weight: 800;
}

.metric-value {
  display: flex;
  align-items: center;
  flex: 1;
  margin: 0;
  min-height: 72px;
  padding: 10px 32px;
  border: 0;
  border-radius: 0 0 8px 8px;
  background: #fff;
  color: var(--card-blue-value);
  font-size: clamp(1.3rem, 2vw, 2rem);
  font-weight: 800;
  font-variant-numeric: tabular-nums;
  box-shadow: 0 5px 10px rgba(24, 67, 101, 0.2);
}

.metric-card-warning .metric-value {
  color: var(--card-red-value);
  font-size: clamp(1.3rem, 2vw, 2rem);
  box-shadow: 0 5px 10px rgba(62, 39, 24, 0.2);
}

@media (max-width: 768px) {
  .stats-grid {
    grid-template-columns: repeat(2, minmax(0, 1fr));
  }

  .metric-card:nth-child(-n + 3),
  .metric-card-warning {
    grid-column: span 1;
  }
}

@media (max-width: 520px) {
  .stats-grid {
    grid-template-columns: 1fr;
  }
}
</style>
