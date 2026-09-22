import { createApp } from 'vue';

import '../css/app.css';
import PrimeVue from 'primevue/config';
import Aura from '@primevue/themes/aura';

import Button from 'primevue/button';
import InputText from 'primevue/inputtext';
import Password from 'primevue/password';
import Checkbox from 'primevue/checkbox';
import Select from 'primevue/select'; // was "Dropdown" in v3, renamed in v4
import DatePicker from 'primevue/datepicker'; // was "Calendar" in v3, renamed in v4
import DataTable from 'primevue/datatable';
import Column from 'primevue/column';
import Chart from 'primevue/chart';
import ProgressBar from 'primevue/progressbar';
import Breadcrumb from 'primevue/breadcrumb';
import Tag from 'primevue/tag';

import 'primeicons/primeicons.css'; // this one's still a plain CSS import, no change

import { DSWDPreset } from './theme.js';

import App from './App.vue';

const app = createApp(App);

app.use(PrimeVue, {
    theme: {
        preset: DSWDPreset,
        options: { darkModeSelector: false },
    },
});

app.component('Button', Button);
app.component('InputText', InputText);
app.component('Password', Password);
app.component('Checkbox', Checkbox);
app.component('Select', Select);
app.component('DatePicker', DatePicker);
app.component('DataTable', DataTable);
app.component('Column', Column);
app.component('Chart', Chart);
app.component('ProgressBar', ProgressBar);
app.component('Breadcrumb', Breadcrumb);
app.component('Tag', Tag);

app.mount('#app');
