<template>
  <div class="table-panel">
    <div class="table-header">
      <div class="table-header-main">
        <Breadcrumb
          :home="{ icon: 'pi pi-home', command: (event) => { event?.originalEvent?.preventDefault(); emit('home-click'); } }"
          :model="breadcrumbItems"
          class="dashboard-breadcrumb"
        />

        <div v-if="activeLevel === 'municipality'" class="table-header-actions">
          <InputText
            :modelValue="municipalitySearch"
            placeholder="Search municipality"
            class="municipality-search"
            @update:modelValue="emit('update:municipalitySearch', $event)"
          />
          <Select
            :modelValue="payoutSiteFilter"
            :options="payoutSiteOptions"
            placeholder="Payout Site"
            showClear
            class="payout-select"
            @update:modelValue="emit('update:payoutSiteFilter', $event)"
          />
          <Button label="Apply" size="small" @click="emit('apply-filters')" />
        </div>
      </div>
    </div>

    <DataTable
      :value="rowsWithProgress"
      sortMode="single"
      @row-click="(event) => emit('row-click', event.data)"
      rowHover
      class="dashboard-table"
      :class="{ 'clickable-rows': activeLevel !== 'detail' }"
    >
      <Column field="name" header="Name" sortable style="width: 28%" />
      <Column field="target" header="Total Target" sortable style="width: 24%">
        <template #body="{ data }">
          {{ data.target ? data.target.toLocaleString() : '-----' }}
        </template>
      </Column>
      <Column field="paid" header="Paid" sortable style="width: 11%">
        <template #body="{ data }">
          <div class="paid-cell">
            <span>{{ data.paid ? data.paid.toLocaleString() : '-----' }}</span>
            <small>₱0.00</small>
          </div>
        </template>
      </Column>
      <Column header="Progress Bar" style="width:15%">
        <template #body="{ data }">
          <div class="progress-cell">
            <div class="progress-cell-top">
              <span class="progress-pct">{{ data.target ? data.progress + '%' : '-----' }}</span>
              <ProgressBar :value="data.target ? data.progress : 0" :showValue="false" class="compact-progress" />
            </div>
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
            <div class="progress-cell-top">
              <span class="progress-pct">{{ totalTableTarget ? totalProgress + '%' : '-----' }}</span>
              <ProgressBar :value="totalTableTarget ? totalProgress : 0" :showValue="false" class="compact-progress" />
            </div>
            <small class="progress-remaining">
              Remaining: {{ totalTableTarget ? (totalTableTarget - totalTablePaid).toLocaleString() : '-----' }}
            </small>
          </div>
        </div>
      </template>
    </DataTable>
  </div>
</template>

<script setup>
const props = defineProps({
  breadcrumbItems: { type: Array, required: true },
  activeLevel: { type: String, required: true },
  municipalitySearch: { type: String, default: '' },
  payoutSiteFilter: { type: String, default: '' },
  payoutSiteOptions: { type: Array, default: () => [] },
  rowsWithProgress: { type: Array, default: () => [] },
  totalTableTarget: { type: Number, default: 0 },
  totalTablePaid: { type: Number, default: 0 },
  totalProgress: { type: Number, default: 0 },
});

const emit = defineEmits([
  'update:municipalitySearch',
  'update:payoutSiteFilter',
  'apply-filters',
  'row-click',
  'home-click',
]);
</script>

<style scoped>
.table-panel {
  background: #fff;
  border: 1px solid #cbd8e5;
  border-radius: 8px;
  box-shadow: 0 9px 7px rgba(24, 67, 101, 0.28);
  overflow: hidden;
}

.table-header {
  padding: 0;
  border-bottom: 1px solid #d8e2ec;
  background: #eaf3fc;
}

.table-header-main {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 14px;
  min-height: 42px;
  padding: 0 18px;
  flex-wrap: wrap;
}

.table-header-actions {
  display: flex;
  align-items: center;
  gap: 10px;
  flex-wrap: wrap;
}

.dashboard-breadcrumb {
  padding: 0;
  border: 0;
  background: transparent;
}

.dashboard-breadcrumb :deep(.p-breadcrumb-list) {
  gap: 4px;
}

.dashboard-breadcrumb :deep(.p-menuitem-link) {
  padding: 6px 7px;
  color: #58709c;
  font-size: 0.68rem;
  font-weight: 700;
  text-decoration: none;
}

.dashboard-breadcrumb :deep(.p-menuitem-link:hover) {
  background: #dceafa;
}

.dashboard-breadcrumb :deep(.p-breadcrumb-list > li:last-child .p-menuitem-link),
.dashboard-breadcrumb :deep(.p-breadcrumb-list > li:last-child .p-menuitem-text),
.dashboard-breadcrumb :deep(.p-breadcrumb-list > li:last-child span) {
  color: #0c234d;
  font-weight: 900;
}

.dashboard-breadcrumb :deep(.p-breadcrumb-separator) {
  color: #78a4d2;
  font-size: 0.65rem;
}

.municipality-search,
.payout-select {
  min-width: 180px;
}

.dashboard-table {
  width: 100%;
  border-radius: 0;
}

:deep(.p-datatable-footer) {
  padding: 0;
  border: 0;
  background: #f2f7fd;
}

:deep(.p-datatable-table-container) {
  border-radius: 0;
}

:deep(.p-datatable-thead > tr > th) {
  padding: 12px 16px;
  border-color: #d8e2ec;
  background: #f7fafd;
  color: #52627b;
  font-size: 0.7rem;
  font-weight: 700;
  text-transform: uppercase;
  letter-spacing: 0.04em;
}

:deep(.p-datatable-tbody > tr) {
  cursor: pointer;
}

:deep(.p-datatable-tbody > tr:hover) {
  background: #f7fbff;
}

:deep(.p-datatable-tbody > tr > td) {
  padding: 12px 16px;
  border-color: #d8e2ec;
  color: #303030;
  font-size: 0.78rem;
  font-weight: 600;
}

.paid-cell {
  display: flex;
  flex-direction: column;
  gap: 2px;
  color: #344292;
  font-weight: 700;
}

.paid-cell small {
  color: #a3a7ad;
  font-size: 0.58rem;
  font-weight: 500;
}

.progress-cell {
  display: flex;
  flex-direction: column;
  gap: 7px;
}

.progress-cell-top {
  display: grid;
  grid-template-columns: 42px 1fr;
  align-items: center;
  gap: 10px;
}

.progress-pct {
  color: #313b83;
  font-size: 0.76rem;
  font-weight: 700;
}

:deep(.compact-progress) {
  height: 12px;
  border-radius: 999px;
  overflow: hidden;
}

:deep(.compact-progress .p-progressbar-value) {
  background: #2588d2;
}

.progress-remaining {
  color: #e47622;
  font-size: 0.78rem;
  font-weight: 700;
}

.total-row-footer {
  display: grid;
  grid-template-columns: 36.3% 31.5% 14.5% 15%;
  align-items: center;
  gap: 0;
  width: 100%;
  padding: 12px 16px;
  box-sizing: border-box;
  background: #f2f7fd;
  border-top: 2px solid #b9d5f1;
  color: #0c234d;
  font-size: 0.78rem;
  font-weight: 700;
}

.total-label {
  color: #0c234d;
}

.total-target,
.total-paid {
  color: #0c234d;
}

.total-progress {
  min-width: 0;
  padding-left: 0;
}

.total-progress .progress-pct,
.total-progress .progress-remaining {
  color: #0c234d;
  font-size: 0.78rem;
}

@media (max-width: 768px) {
  .total-row-footer {
    grid-template-columns: 1fr 1fr;
    gap: 10px;
  }
}
</style>
