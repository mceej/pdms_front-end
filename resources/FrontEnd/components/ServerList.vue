<template>
    <section class="server-list-view">
        <div class="server-list-heading">
            <div>
                <span class="eyebrow">Import New Served List</span>
                <h2>Server List</h2>
            </div>
            <span>as of {{ currentTime }}</span>
        </div>

        <form class="served-list-form" @submit.prevent="$emit('upload')">
            <label class="served-field served-program-field">
                <span>Select Program</span>
                <Select v-model="servedListForm.program" :options="tabs" placeholder="Select one..." />
            </label>
            <div class="served-location-fields">
                <label class="served-field"><span>Province</span><Select v-model="servedListForm.province" :options="provinceOptions" placeholder="Province" /></label>
                <label class="served-field"><span>Municipality</span><Select v-model="servedListForm.municipality" :options="municipalityOptions" placeholder="Municipality" /></label>
                <label class="served-field"><span>Barangay</span><Select v-model="servedListForm.barangay" :options="barangayOptions" placeholder="Barangay" /></label>
            </div>
            <label class="served-field">
                <span>Upload File</span>
                <span class="file-dropzone">
                    <i class="pi pi-upload"></i>
                    <span>{{ servedListForm.file?.name || 'Choose a CSV file' }}</span>
                    <small>CSV files up to 10 MB</small>
                    <input accept=".csv,text/csv" type="file" @change="$emit('select-file', $event)" />
                </span>
            </label>
            <p v-if="message" :class="['served-list-message', { error: hasError }]">{{ message }}</p>
            <div class="served-list-actions">
                <Button type="submit" :label="isUploading ? 'Uploading...' : 'Upload'" :disabled="isUploading || !servedListForm.program || !servedListForm.file" />
            </div>
        </form>

        <div class="table-panel served-list-table-panel">
            <div class="table-header served-list-table-header">
                <span>Imported Served Lists</span>
                <div class="served-list-date-filter">
                    <span>{{ appliedDateLabel }}</span>
                    <div class="date-range-inputs">
                        <input :value="dateFrom" type="date" aria-label="Filter served lists from date" :max="dateTo || undefined" @change="$emit('update:date-from', $event.target.value)" />
                        <span>to</span>
                        <input :value="dateTo" type="date" aria-label="Filter served lists to date" :min="dateFrom || undefined" @change="$emit('update:date-to', $event.target.value)" />
                        <Button label="Apply" size="small" @click="$emit('apply-date-filter')" />
                    </div>
                </div>
            </div>
            <DataTable :value="rows" class="dashboard-table served-list-table">
                <Column field="file_name" header="File Name" style="width: 42%" />
                <Column field="imported_at" header="Date Imported" style="width: 28%" />
                <Column field="imported_by" header="Imported By" style="width: 30%" />
                <template #empty><div class="served-list-empty">No served lists have been imported yet.</div></template>
            </DataTable>
        </div>
    </section>
</template>

<script setup>
defineProps({
    currentTime: { type: String, required: true },
    tabs: { type: Array, required: true },
    servedListForm: { type: Object, required: true },
    provinceOptions: { type: Array, required: true },
    municipalityOptions: { type: Array, required: true },
    barangayOptions: { type: Array, required: true },
    isUploading: { type: Boolean, required: true },
    message: { type: String, required: true },
    hasError: { type: Boolean, required: true },
    rows: { type: Array, required: true },
    dateFrom: { type: String, required: true },
    dateTo: { type: String, required: true },
    appliedDateLabel: { type: String, required: true },
});

defineEmits(['apply-date-filter', 'select-file', 'update:date-from', 'update:date-to', 'upload']);
</script>

<style scoped src="../styles/server-list.css"></style>
