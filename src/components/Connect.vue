<template>
  <div class="w3eden">
    <button :class="'btn ' + connectButton.style" data-toggle="modal" data-target="#selectwallet"><img :src="walletLogo()" style="width: 22px;height: auto;border-radius: 4px;margin-right: 6px" />{{ connectButton.label }}</button>
    <div class="modal fade" tabindex="-1" id="selectwallet" data-backdrop="static">
      <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable" style="max-width: 100%;width: 300px">
        <div class="modal-content" role="document">
          <div class="modal-header">
            <h5 class="modal-title">{{ connectButton.connected ? 'Wallet Connected' : 'Connect Wallet' }}</h5>
            <button type="button" data-dismiss="modal" aria-label="Close" style="background: transparent;border: none;">
              <svg fill="none" height="16" viewBox="0 0 16 16" width="16" xmlns="http://www.w3.org/2000/svg"><path clip-rule="evenodd" d="M8 16C12.4183 16 16 12.4183 16 8C16 3.58172 12.4183 0 8 0C3.58172 0 0 3.58172 0 8C0 12.4183 3.58172 16 8 16ZM4.29289 5.70711L6.58579 8L4.29289 10.2929L5.70711 11.7071L8 9.41421L10.2929 11.7071L11.7071 10.2929L9.41421 8L11.7071 5.70711L10.2929 4.29289L8 6.58579L5.70711 4.29289L4.29289 5.70711Z" fill="#777" fill-rule="evenodd"/></svg>
            </button>
          </div>
          <div class="modal-body-np">
            <div id="walletList" class="list-group-flush">
              <div v-if="connectButton.connected" class="list-group-item" style="font-weight: bold;color: #3d9331;text-align: center; background: #f1f1f1">{{ formatString(connectButton.address) }} <i class="fa fa-files ttip" title="Copy Address"></i></div>
              <template  v-for="wallet in wallets" :key="wallet.id">
                <div class="list-group-item"> <img :src="walletLogo(wallet.id)" style="width: 22px;height: auto;border-radius: 4px;margin-right: 6px" /> {{ wallet.name }}
                  <button v-on:click="connectWallet(wallet.id)" v-if="wallet.provider" :data-wallet="wallet.id" :class="'btn btn-sm wltcnnct float-right ' + wallet.style">{{ wallet.label }}</button>
                  <button v-else :data-wallet="wallet.id" style="opacity: 0.5" class="btn btn-sm btn-secondary wltcnnct float-right">Not Installed</button>
                </div>
              </template>
            </div>
            <div id="walletInfo"></div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
import { ref, onMounted } from 'vue';
import { wpdmCryptoConnect } from '@/data';

export default {
  setup() {
    const wpdmcc = wpdmCryptoConnect();
    onMounted(() => {
      wpdmcc.detectWallets();
      wpdmcc.data.connectButton.connected = wpdmcc.isConnected();
      wpdmcc.data.connectButton.label = wpdmcc.isConnected() ? 'Connected' : 'Connect';
      wpdmcc.data.connectButton.style = wpdmcc.isConnected() ? 'btn-success' : 'btn-info';
    });

    function walletLogo(walletId) {
      if(!walletId) walletId = localStorage.getItem('wallet_id') || 'connect';
      return wpdm_url.home + 'wp-content/plugins/wpdm-crypto-connect/public/images/' + walletId + '.svg';
    }

    return {
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
      walletLogo
    };
  }
};
</script>


