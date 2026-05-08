import Alpine from 'alpinejs';
import axios from 'axios';

window.Alpine = Alpine;
window.axios = axios;

Alpine.start();

// CSRF token setup for axios
axios.defaults.headers.common['X-CSRF-TOKEN'] = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');

console.log('SMM Elite panel ready!');
