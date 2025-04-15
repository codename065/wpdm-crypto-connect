import { createApp } from 'vue';
import { createPinia } from 'pinia';
import Connect from './components/Connect.vue';
import RequestPayment from './components/RequestPayment.vue';
import Paid from './components/Paid.vue';
import WalletInfo from './components/WalletInfo.vue';

const pinia = createPinia();

const components = {
    Connect,
    RequestPayment,
    Paid,
    WalletInfo,
};

document.querySelectorAll('.vue-app').forEach(el => {
    const appName = el.dataset.vueApp;
    const id = el.dataset.id;
    console.log(id);
    const props = {
        recipient: el.getAttribute('data-recipient'),
        amount: el.getAttribute('data-amount'),
        style: el.getAttribute('data-style'),
        label: el.getAttribute('data-label'),
        product: el.getAttribute('data-product'),
        network: el.getAttribute('data-network'),
        id: el.getAttribute('data-id'),
    };
    if (components[appName]) {
        const app = createApp(components[appName], props);
        app.use(pinia);
        app.mount(`#${id}`);
    }
});
