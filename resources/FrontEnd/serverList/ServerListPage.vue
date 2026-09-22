<template>
  <section class="server-list-view">
    <div class="server-list-heading">
      <div>
        <span class="eyebrow">Import New Served List</span>
        <h2>Server List</h2>
      </div>
      <span>as of {{ currentTime }}</span>
    </div>

    <form class="served-list-form" @submit.prevent="emit('upload-served-list')">
      <label class="served-field served-program-field">
        <span>Select Program</span>
        <Select v-model="servedListForm.program" :options="tabs" placeholder="Select one..." />
      </label>

      <div class="served-location-fields">
        <label class="served-field">
          <span>Province</span>
          <Select v-model="servedListForm.province" :options="servedProvinceOptions" placeholder="Province" />
        </label>
        <label class="served-field">
          <span>Municipality</span>
          <Select v-model="servedListForm.municipality" :options="servedMunicipalityOptions" placeholder="Municipality" />
        </label>
        <label class="served-field">
          <span>Barangay</span>
          <Select v-model="servedListForm.barangay" :options="servedBarangayOptions" placeholder="Barangay" />
        </label>
      </div>

      <label class="served-field">
        <span>Upload File</span>
        <span class="file-dropzone">
          <i class="pi pi-upload"></i>
          <span>{{ servedListForm.file?.name || 'Choose a CSV file' }}</span>
          <small>CSV files up to 10 MB</small>
          <input accept=".csv,text/csv" type="file" @change="emit('select-served-list-file', $event)" />
        </span>
      </label>

      <p v-if="servedListMessage" :class="['served-list-message', { error: servedListError }]">{{ servedListMessage }}</p>

      <div class="served-list-actions">
        <Button
          type="submit"
          :label="isUploading ? 'Uploading...' : 'Upload'"
          :disabled="isUploading || !servedListForm.program || !servedListForm.file"
        />
      </div>
    </form>

    <div class="table-panel served-list-table-panel">
      <div class="table-header served-list-table-header">
        <span>Imported Served Lists</span>
        <div class="served-list-date-filter">
          <span>{{ appliedServedListDateLabel }}</span>
          <div class="date-range-inputs">
            <input
              :value="servedListDateFrom"
              type="date"
              aria-label="Filter served lists from date"
              :max="servedListDateTo || undefined"
              @change="emit('update:servedListDateFrom', $event.target.value)"
            />
            <span>to</span>
            <input
              :value="servedListDateTo"
              type="date"
              aria-label="Filter served lists to date"
              :min="servedListDateFrom || undefined"
              @change="emit('update:servedListDateTo', $event.target.value)"
            />
            <Button label="Apply" size="small" @click="emit('apply-served-list-date-filter')" />
          </div>
        </div>
      </div>

      <DataTable :value="servedListRows" class="dashboard-table served-list-table">
        <Column field="file_name" header="File Name" style="width: 42%" />
        <Column field="imported_at" header="Date Imported" style="width: 28%" />
        <Column field="imported_by" header="Imported By" style="width: 30%" />
        <template #empty>
          <div class="served-list-empty">No served lists have been imported yet.</div>
        </template>
      </DataTable>
    </div>
  </section>
</template>

<script setup>
const props = defineProps({
  currentTime: { type: String, required: true },
  tabs: { type: Array, required: true },
  servedListForm: { type: Object, required: true },
  servedProvinceOptions: { type: Array, default: () => [] },
  servedMunicipalityOptions: { type: Array, default: () => [] },
  servedBarangayOptions: { type: Array, default: () => [] },
  isUploading: { type: Boolean, default: false },
  servedListMessage: { type: String, default: '' },
  servedListError: { type: Boolean, default: false },
  servedListRows: { type: Array, default: () => [] },
  servedListDateFrom: { type: String, default: '' },
  servedListDateTo: { type: String, default: '' },
  appliedServedListDateLabel: { type: String, default: 'as of today' },
});

const emit = defineEmits([
  'upload-served-list',
  'select-served-list-file',
  'update:servedListDateFrom',
  'update:servedListDateTo',
  'apply-served-list-date-filter',
]);
</script>

<style scoped>
.server-list-view {
  display: grid;
  gap: 24px;
}

.server-list-heading {
  display: flex;
  justify-content: space-between;
  align-items: end;
  gap: 16px;
  margin-top: 8px;
}

.eyebrow {
  display: block;
  color: #ffb703;
  text-transform: uppercase;
  letter-spacing: 0.08em;
  font-size: 0.72rem;
  font-weight: 800;
}

.server-list-heading h2 {
  margin: 6px 0 0;
  color: #0d234a;
  font-size: clamp(1.8rem, 2.5vw, 2.5rem);
}

.server-list-heading > span {
  color: #5b7288;
  font-size: 0.8rem;
}

.served-list-form {
  display: grid;
  gap: 18px;
  padding: 22px;
  border-radius: 18px;
  background: rgba(255,255,255,0.96);
  border: 1px solid rgba(12, 35, 77, 0.08);
  box-shadow: 0 12px 26px rgba(15, 41, 83, 0.06);
}

.served-field {
  display: grid;
  gap: 8px;
  color: #1a2d4d;
  font-size: 0.8rem;
  font-weight: 700;
}

.served-program-field :deep(.p-select),
.served-field :deep(.p-select) {
  width: 100%;
}

.served-location-fields {
  display: grid;
  grid-template-columns: repeat(3, minmax(180px, 1fr));
  gap: 16px;
}

.file-dropzone {
  position: relative;
  display: grid;
  place-items: center;
  gap: 8px;
  min-height: 120px;
  padding: 18px 16px;
  border: 1.5px dashed #9ab6de;
  border-radius: 12px;
  background: linear-gradient(180deg, #f4f8ff 0%, #edf5ff 100%);
  color: #29548d;
  text-align: center;
  cursor: pointer;
}

.file-dropzone i {
  font-size: 1.5rem;
  color: #2d70c8;
}

.file-dropzone input {
  position: absolute;
  inset: 0;
  opacity: 0;
  cursor: pointer;
}

.served-list-message {
  margin: 0;
  color: #1d7b55;
  font-weight: 600;
}

.served-list-message.error {
  color: #c62828;
}

.served-list-actions {
  display: flex;
  justify-content: flex-end;
}

.served-list-actions button {
  width: auto;
  min-width: 120px;
}

.table-panel {
  background: linear-gradient(180deg, rgba(255,255,255,0.96), rgba(245,249,255,0.97));
  border: 1px solid rgba(12, 35, 77, 0.08);
  border-radius: 18px;
  box-shadow: 0 10px 26px rgba(16, 40, 78, 0.08);
  overflow: hidden;
}

.table-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  gap: 12px;
  padding: 16px 18px 8px;
  flex-wrap: wrap;
}

.table-header > span {
  color: #15366d;
  font-size: 0.9rem;
  font-weight: 800;
}

.served-list-date-filter {
  display: flex;
  align-items: center;
  gap: 10px;
  color: #55708f;
  font-size: 0.76rem;
  flex-wrap: wrap;
}

.date-range-inputs {
  display: inline-flex;
  align-items: center;
  gap: 8px;
  flex-wrap: wrap;
}

.date-range-inputs input {
  border: 1px solid #cbd9eb;
  border-radius: 8px;
  padding: 8px 10px;
  background: #fff;
  color: #0d234a;
}

:deep(.dashboard-table) {
  width: 100%;
}

:deep(.p-datatable-thead > tr > th) {
  background: #f3f7ff;
  color: #1a2d4d;
  font-size: 0.82rem;
  font-weight: 700;
  text-transform: uppercase;
  letter-spacing: 0.04em;
}

.served-list-empty {
  padding: 18px;
  color: #607897;
}

@media (max-width: 768px) {
  .served-location-fields {
    grid-template-columns: 1fr;
  }

  .server-list-heading,
  .table-header {
    flex-direction: column;
    align-items: flex-start;
  }
}
</style>
