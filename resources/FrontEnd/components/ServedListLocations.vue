<template>
  <div class="location-fields">
    <label>
      <span>Province</span>
      <Select v-model="form.province" :options="provinces" option-label="name" option-value="name"
        placeholder="Province" filter :loading="provincesLoading" :disabled="provincesLoading" />
    </label>
    <label>
      <span>Municipality / City</span>
      <Select v-model="form.municipality" :options="municipalities" option-label="name" option-value="name"
        placeholder="Municipality / City" filter :loading="municipalitiesLoading"
        :disabled="!form.province || municipalitiesLoading || !municipalities.length" />
    </label>
    <label>
      <span>Barangay</span>
      <Select v-model="form.barangay" :options="barangays" option-label="name" option-value="name"
        placeholder="Barangay" filter :loading="barangaysLoading"
        :disabled="!form.municipality || barangaysLoading || !barangays.length" />
    </label>
    <p v-if="loadError" class="location-error" role="alert">
      {{ loadError }}
      <button type="button" @click="retryLocations">Retry</button>
    </p>
  </div>
</template>

<script setup>
import { onMounted, ref, watch } from 'vue';
import { loadPsgcProvinces, loadPsgcMunicipalities, loadPsgcBarangays } from '../data/psgcLocations.js';

const props = defineProps({ form: { type: Object, required: true } });
const provinces = ref([]);
const municipalities = ref([]);
const barangays = ref([]);
const provincesLoading = ref(false);
const municipalitiesLoading = ref(false);
const barangaysLoading = ref(false);
const loadError = ref('');
let municipalityRequest = 0;
let barangayRequest = 0;

async function loadMunicipalities() {
  const request = ++municipalityRequest;
  municipalities.value = [];
  municipalitiesLoading.value = false;
  const province = provinces.value.find((place) => place.name === props.form.province);
  if (!province) return;
  municipalitiesLoading.value = true;
  try {
    const places = await loadPsgcMunicipalities(province.code);
    if (request === municipalityRequest) municipalities.value = places;
  } catch {
    if (request === municipalityRequest) loadError.value = 'Could not load municipalities. Please try again.';
  } finally {
    if (request === municipalityRequest) municipalitiesLoading.value = false;
  }
}

async function loadBarangays() {
  const request = ++barangayRequest;
  barangays.value = [];
  barangaysLoading.value = false;
  const municipality = municipalities.value.find((place) => place.name === props.form.municipality);
  if (!municipality) return;
  barangaysLoading.value = true;
  try {
    const places = await loadPsgcBarangays(municipality.code);
    if (request === barangayRequest) barangays.value = places;
  } catch {
    if (request === barangayRequest) loadError.value = 'Could not load barangays. Please try again.';
  } finally {
    if (request === barangayRequest) barangaysLoading.value = false;
  }
}

async function retryLocations() {
  loadError.value = '';
  provincesLoading.value = true;
  try {
    provinces.value = await loadPsgcProvinces();
    await loadMunicipalities();
    await loadBarangays();
  } catch {
    loadError.value = 'Could not load provinces. Please try again.';
  } finally {
    provincesLoading.value = false;
  }
}

watch(() => props.form.province, () => {
  props.form.municipality = '';
  props.form.barangay = '';
  ++barangayRequest;
  barangays.value = [];
  barangaysLoading.value = false;
  loadError.value = '';
  loadMunicipalities();
}, { flush: 'sync' });

watch(() => props.form.municipality, () => {
  props.form.barangay = '';
  loadError.value = '';
  loadBarangays();
}, { flush: 'sync' });

onMounted(retryLocations);
</script>

<style scoped>
.location-fields { display: grid; grid-template-columns: repeat(3, minmax(180px, 1fr)); gap: 16px; }
label { display: grid; gap: 8px; color: #000; font-size: 0.8rem; font-weight: 400; }
label :deep(.p-select) { width: 100%; }
label :deep(.p-select-label) { font-size: 0.75rem; font-weight: 400; }
.location-error { grid-column: 1 / -1; margin: 0; color: #c62828; font-size: 0.8rem; }
.location-error button { margin-left: 8px; cursor: pointer; }
@media (max-width: 768px) { .location-fields { grid-template-columns: 1fr; } }
</style>
