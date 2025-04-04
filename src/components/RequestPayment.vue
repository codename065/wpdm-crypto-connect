<template>
  <div class="w3eden">
    <button :class="style" @click="requestPayment()" :data-label="label">{{  label }}</button>
  </div>
</template>

<script type="module">
import { ref, onMounted } from 'vue';
import { wpdmCryptoConnect } from '@/data';

export default {
  props: ['recipient', 'amount', 'label', 'style', 'product', 'id'],
  setup(props) {
    const wpdmcc = wpdmCryptoConnect();
    const style = ref(props.style || 'btn btn-link');
    const label = ref(props.label || 'Pay Now');
    const product = ref(props.product || 0);
    const id = ref(props.id || '');
    const recipientAddress = ref(props.recipient || '');
    const amount = ref(props.amount ? parseFloat(props.amount) : 0);
    onMounted(() => {
      wpdmcc.detectWallets();
      wpdmcc.data.connectButton.connected = wpdmcc.isConnected();
      wpdmcc.data.connectButton.label = wpdmcc.isConnected() ? 'Connected' : 'Connect';
      wpdmcc.data.connectButton.style = wpdmcc.isConnected() ? 'btn-success' : 'btn-info';
      wpdmcc.data.amount = amount;
      wpdmcc.data.receiver = recipientAddress;
    });

    async function _requestPayment() {
      return await wpdmcc.requestPayment(label, product);
    }

    return {
      style,
      label,
      product,
      id,
      connectButton: wpdmcc.data.connectButton,
      wallets: wpdmcc.data.wallets,
      connectWallet: wpdmcc.connectWallet,
      formatString: wpdmcc.formatString,
      isConnected: wpdmcc.isConnected,
      connectionStatus: wpdmcc.connectionStatus,
      getWallet: wpdmcc.getWallet,
      detectWallets: wpdmcc.detectWallets,
      getProvider: wpdmcc.getProvider,
      disconnectWallet: wpdmcc.disconnectWallet,
      requestPayment: _requestPayment,
    };
  }
};
</script>


