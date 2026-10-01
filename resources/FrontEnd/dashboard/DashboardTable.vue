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
      <Column field="name" header="Name" sortable />
      <Column field="target" header="Total Target" sortable>
        <template #body="{ data }">
          {{ data.target ? data.target.toLocaleString() : '-----' }}
        </template>
      </Column>
      <Column field="paid" header="Paid" sortable>
        <template #body="{ data }">
          <div class="paid-cell">
            <span>{{ data.paid ? data.paid.toLocaleString() : '-----' }}</span>
            <small>₱0.00</small>
          </div>
        </template>
      </Column>
      <Column header="Progress Bar">
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

      <ColumnGroup type="footer">
        <Row>
          <Column footer="Total" footerClass="total-label" />
          <Column :footer="totalTableTarget ? totalTableTarget.toLocaleString() : '-----'" footerClass="total-target" />
          <Column :footer="totalTablePaid ? totalTablePaid.toLocaleString() : '-----'" footerClass="total-paid" />
          <Column>
            <template #footer>
              <div class="progress-cell">
                <div class="progress-cell-top">
                  <span class="progress-pct">{{ totalTableTarget ? totalProgress + '%' : '-----' }}</span>
                  <ProgressBar :value="totalTableTarget ? totalProgress : 0" :showValue="false" class="compact-progress" />
                </div>
                <small class="progress-remaining">
                  Remaining: {{ totalTableTarget ? (totalTableTarget - totalTablePaid).toLocaleString() : '-----' }}
                </small>
              </div>
            </template>
          </Column>
        </Row>
      </ColumnGroup>
    </DataTable>
  </div>
</template>

<script setup>
import ColumnGroup from 'primevue/columngroup';
import Row from 'primevue/row';

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
  border: 0;
  border-radius: 0;
  box-shadow: none;
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
  padding: 20px 18px;
  font-size: 1.15rem;
  flex-wrap: wrap;
  background: linear-gradient(90deg, #E8F3FF 0%, #FBFDFF 50%, #FDFEFF 100%);
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
  font-weight: 600;
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
  --col-1: 25%;
  --col-2: 25%;
  --col-3: 25%;
  --col-4: 25%;
}

:deep(.p-datatable-table) {
  width: 100%;
  table-layout: fixed;
}

:deep(.p-datatable-thead > tr > th:nth-child(1)),
:deep(.p-datatable-tbody > tr > td:nth-child(1)) {
  width: var(--col-1);
}

:deep(.p-datatable-thead > tr > th:nth-child(2)),
:deep(.p-datatable-tbody > tr > td:nth-child(2)) {
  width: var(--col-2);
}

:deep(.p-datatable-thead > tr > th:nth-child(3)),
:deep(.p-datatable-tbody > tr > td:nth-child(3)) {
  width: var(--col-3);
}

:deep(.p-datatable-thead > tr > th:nth-child(4)),
:deep(.p-datatable-tbody > tr > td:nth-child(4)) {
  width: var(--col-4);
}

:deep(.p-datatable-table-container) {
  border-radius: 0;
}

:deep(.p-datatable-thead > tr > th) {
  padding: 18px 16px;
  border-color: #014e9b;
  background: #FAFBFC;
  color: #000001;
  font-size: 0.9rem;
  font-weight: 500;
  text-transform: uppercase;
  letter-spacing: 0.04em;
}

:deep(.p-datatable-thead > tr > th .p-datatable-column-title) {
  font-weight: 700;
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
  font-size: 1.05rem;
  font-weight: 0;
}

:deep(.p-datatable-tfoot > tr > td),
:deep(.p-datatable-tfoot > tr > th) {
  padding: 14px 16px;
  background: #f2f7fd;
  border-top: 2px solid #b9d5f1;
  border-bottom: 0;
  color: #0c234d;
  font-size: 1.05rem;
  font-weight: 700;
  text-align: left;
}

:deep(.p-datatable-tfoot .total-label) {
  color: #0c234d;
}

:deep(.p-datatable-tfoot .total-target),
:deep(.p-datatable-tfoot .total-paid) {
  color: #4d0c0e;
}

.paid-cell {
  display: flex;
  flex-direction: column;
  gap: 2px;
  color: #344292;
  font-size: 1.05rem;
  font-weight: 700;
}

.paid-cell small {
  color: #a3a7ad;
  font-size: 0.95rem;
  font-weight: 500;
}

.progress-cell {
  display: flex;
  flex-direction: column;
  gap: 7px;
}

.progress-cell-top {
  display: grid;
  grid-template-columns: 56px 1fr;
  align-items: center;
  gap: 10px;
}

.progress-pct {
  color: #313b83;
  font-size: 0.95rem;
  font-weight: 700;
}

:deep(.compact-progress) {
  height: 12px;
  border-radius: 999px;
  overflow: hidden;
}

:deep(.compact-progress .p-progressbar-value) {
  background: #2E3192;
}

.progress-remaining {
  color: #D93F2F;
  font-size: 0.90rem;
  font-weight: 700;
}
</style>