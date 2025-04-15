<template>
  <div v-if="wpdmcc.data.connectButton.connected">
    <div class="p-4 border rounded shadow bg-white">
      <div class="row">
        <div class="col-md-8">
          <div class="card">
            <div class="card-body">
              <small class="text-muted">Address</small>
              <h3 class="text-info ellipsis">{{ wpdmcc.getWalletAddress() }}</h3>
            </div>
          </div>
        </div>
        <div class="col-md-4">
          <div class="card">
            <div class="card-body">
              <small class="text-muted">Balance</small>
              <h3 class="text-success">
                <div style="margin-right: 6px;float: left;" >
                  <svg style="width:18px;opacity: 0.5;float: left;margin-top: 2px" fill="var(--color-success-active)" data-name="Layer 1" id="Layer_1" viewBox="0 0 128 128" xmlns="http://www.w3.org/2000/svg"><title/><polygon points="93.94 42.63 13.78 42.63 34.06 22.41 114.22 22.41 93.94 42.63"/><polyline points="93.94 105.59 13.78 105.59 34.06 85.38 114.22 85.38"/><polyline points="34.06 74.11 114.22 74.11 93.94 53.89 13.78 53.89"/></svg>
                </div>
                {{ solBalance }}</h3>
            </div>
          </div>
        </div>
      </div>

      <div class="card" style="margin-bottom: 5px">
        <div class="card-body">
          <button @click="copyAddress" class="btn btn-info">Copy Address</button>&nbsp;
          <button @click="viewExplorer" class="btn btn-secondary">View on Explorer</button>&nbsp;
          <button @click="refreshBalance" class="btn btn-primary">Refresh</button>&nbsp;
        </div>
      </div>
    </div>
  </div>
  <div v-else>
    <div class="lead">No wallet connected.</div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { wpdmCryptoConnect } from '@/data';

const props = defineProps({
  network: {
    type: String,
    default: 'mainnet-beta'
  }
})

const wpdmcc = wpdmCryptoConnect();

const walletAddress = ref(null)
const solBalance = ref('0')
let rpcurl = props.network === 'devnet' ? solanaWeb3.clusterApiUrl(props.network) : 'https://solana-mainnet.g.alchemy.com/v2/q06zlFMF0RUfRWw_XYCJBKTgLiKFZFJH';
const connection = new solanaWeb3.Connection(rpcurl)

const refreshBalance = async () => {
  const pubkey = new solanaWeb3.PublicKey(wpdmcc.getWalletAddress())
  const lamports = await connection.getBalance(pubkey)
  console.log('lamports', lamports)
  solBalance.value = (lamports / 1e9).toFixed(4)
}

const copyAddress = () => navigator.clipboard.writeText(wpdmcc.getWalletAddress())
const viewExplorer = () => window.open(`https://explorer.solana.com/address/${walletAddress.value}`, '_blank')



onMounted(async () => {

  await refreshBalance()

  /*if (window.solana) {
    window.solana.on('connect', async () => {
      walletAddress.value = window.solana.publicKey.toString()
      await refreshBalance()
    })

    // Restore wallet if previously connected
    await tryRestoreWallet()
  } else {
    alert('Phantom Wallet not found. Please install it.')
  }*/
})
</script>
