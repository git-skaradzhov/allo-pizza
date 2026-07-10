import axios from 'axios';

window.axios = axios;
window.axios.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest';

/**
 * Real-time (Reverb/Echo) is only used in the Filament admin panel.
 * The public storefront does not subscribe to broadcast channels.
 */
