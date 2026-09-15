// resources/js/app.js
import { createApp } from 'vue';
import PrimeVue from 'primevue/config';
import Button from 'primevue/button';
import InputText from 'primevue/inputtext';
import Password from 'primevue/password';
import Checkbox from 'primevue/checkbox';
import Dropdown from 'primevue/dropdown';
import DatePicker from 'primevue/datepicker';
import DataTable from 'primevue/datatable';
import Column from 'primevue/column';
import Chart from 'primevue/chart';
import ProgressBar from 'primevue/progressbar';
import Breadcrumb from 'primevue/breadcrumb';
import Tag from 'primevue/tag';

import 'primevue/resources/themes/lara-light-blue/theme.css';
import 'primeicons/primeicons.css';

import App from './App.vue';

const app = createApp(App);
app.use(PrimeVue, { ripple: true });

app.component('Button', Button);
app.component('InputText', InputText);
app.component('Password', Password);
app.component('Checkbox', Checkbox);
app.component('Dropdown', Dropdown);
app.component('DatePicker', DatePicker);
app.component('DataTable', DataTable);
app.component('Column', Column);
app.component('Chart', Chart);
app.component('ProgressBar', ProgressBar);
app.component('Breadcrumb', Breadcrumb);
app.component('Tag', Tag);

app.mount('#app');