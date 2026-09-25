<template>
  <section class="stats-grid">
    <!-- Top row: 3 blue cards (beneficiary counts) -->
    <div class="metric-card metric-card--target">
      <div class="metric-card-label">Target Number of Beneficiaries</div>
      <div class="metric-card-value-box">
        <span class="metric-icon metric-icon--human"><i class="pi pi-user"></i></span>
        <div class="metric-value">{{ formatNumber(totalTarget) }}</div>
      </div>
    </div>
    <div class="metric-card metric-card--paid">
      <div class="metric-card-label">Paid</div>
      <div class="metric-card-value-box">
        <span class="metric-icon metric-icon--human"><i class="pi pi-user"></i></span>
        <div class="metric-value">{{ formatNumber(totalPaidCount) }}</div>
      </div>
    </div>
    <div class="metric-card metric-card--unpaid">
      <div class="metric-card-label">Unpaid</div>
      <div class="metric-card-value-box">
        <span class="metric-icon metric-icon--human"><i class="pi pi-user"></i></span>
        <div class="metric-value">{{ formatNumber(totalRemaining) }}</div>
      </div>
    </div>

    <!-- Bottom row: 3 orange/red cards (peso amounts) -->
    <div class="metric-card metric-card--amount-to-disburse">
      <div class="metric-card-label">Total Amount to be Disbursed</div>
      <div class="metric-card-value-box">
        <span class="metric-icon metric-icon--peso">₱</span>
        <div class="metric-value unpaid-value">{{ stripPeso(totalAmountToDisburse) }}</div>
      </div>
    </div>
    <div class="metric-card metric-card--disbursed">
      <div class="metric-card-label">Disbursed</div>
      <div class="metric-card-value-box">
        <span class="metric-icon metric-icon--peso">₱</span>
        <div class="metric-value unpaid-value">{{ stripPeso(totalDisbursed) }}</div>
      </div>
    </div>
    <div class="metric-card metric-card--balance">
      <div class="metric-card-label">Balance</div>
      <div class="metric-card-value-box">
        <span class="metric-icon metric-icon--peso">₱</span>
        <div class="metric-value unpaid-value">{{ stripPeso(totalUnpaidDisbursed) }}</div>
      </div>
    </div>
  </section>
</template>

<script setup>
const props = defineProps({
  totalTarget: [Number, String],
  totalPaidCount: [Number, String],
  totalRemaining: [Number, String],
  totalAmountToDisburse: { type: String, default: '-----' },
  totalDisbursed: { type: String, default: '-----' },
  totalUnpaidDisbursed: { type: String, default: '-----' },
});

const formatNumber = (value) => {
  if (value === null || value === undefined || value === '' || value === '-----') return '-----';
  const numericValue = Number(value);
  return Number.isFinite(numericValue) ? numericValue.toLocaleString() : String(value);
};

// The peso values arrive pre-formatted with a leading ₱ (e.g. "₱12,000").
// Since the icon badge now carries that symbol, strip it from the text so it isn't duplicated.
const stripPeso = (value) => {
  if (value === null || value === undefined || value === '' || value === '-----') return '-----';
  return String(value).replace(/^\s*₱\s*/, '');
};
</script>

<style scoped>
.metric-card, .metric-card * { box-sizing: border-box; }

.stats-grid {
  display: grid;
  grid-template-columns: repeat(3, minmax(0, 1fr));
  gap: 14px;
  margin: 0 0 28px;
}

.metric-card {
  position: relative;
  border-radius: 14px;
  box-shadow: 0.2px 3px 0 rgba(104, 104, 104, 0.1);
  overflow: visible;
  transition: transform 0.15s ease;
}

.metric-card:hover { transform: translateY(-2px); }

.metric-card-label {
  padding: 14px 20px 40px;
  font-size: 11px;
  font-weight: 700;
  letter-spacing: 0.06em;
  text-transform: uppercase;
  border-radius: 14px 14px 0 0;
  border: 1.5px solid #C7DCEF;
  border-bottom: none;
}

.metric-card-value-box {
  position: relative;
  display: flex;
  align-items: center;
  gap: 14px;
  margin: 0;
  margin-top: -28px;
  background: #fff;
  border-radius: 13px 13px 15px 15px;
  padding: 16px 20px;
  box-shadow: 0 18px 28px -6px rgba(148, 163, 184, 0.45), 0 6px 10px -4px rgba(148, 163, 184, 0.3);
  border: none;
}

.metric-icon {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  flex-shrink: 0;
  width: 42px;
  height: 42px;
  border-radius: 50%;
  font-size: 1.15rem;
  font-weight: 800;
  line-height: 1;
}

.metric-card--target,
.metric-card--paid,
.metric-card--unpaid {
  background: #E0ECFF;
}

.metric-card--target .metric-card-label,
.metric-card--paid .metric-card-label,
.metric-card--unpaid .metric-card-label {
  color: #063B95;
  background: transparent;
  border-color: #4A5579;
}

.metric-card--target .metric-card-value-box,
.metric-card--paid .metric-card-value-box,
.metric-card--unpaid .metric-card-value-box {
  background: #fff;
}

.metric-icon--human {
  background: #E0ECFF;
  color: #063B95;
}

.metric-card--amount-to-disburse,
.metric-card--disbursed,
.metric-card--balance {
  background: #FFF5EE;
}

.metric-card--amount-to-disburse .metric-card-label,
.metric-card--disbursed .metric-card-label,
.metric-card--balance .metric-card-label {
  color: #C2410C;
  background: transparent;
  border-color: #F9B87F;
}

.metric-card--amount-to-disburse .metric-card-value-box,
.metric-card--disbursed .metric-card-value-box,
.metric-card--balance .metric-card-value-box {
  background: #fff;
}

.metric-icon--peso {
  background: #FFF5EE;
  color: #C2410C;
}

.metric-value {
  font-size: clamp(1.7rem, 2.6vw, 2.2rem);
  font-weight: 800;
  letter-spacing: -0.02em;
  font-variant-numeric: tabular-nums;
  color: #1B1F5C;
  line-height: 1;
}

.metric-value.unpaid-value { color: #C92A16; }

@media (max-width: 768px) {
  .stats-grid { grid-template-columns: repeat(2, minmax(0, 1fr)); }
}

@media (max-width: 520px) {
  .stats-grid { grid-template-columns: 1fr; }
}
</style>