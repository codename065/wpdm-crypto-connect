<?php
if(!defined('ABSPATH')) die('Dream more!');
?>
<script src="https://unpkg.com/@solana/web3.js@latest/lib/index.iife.js"></script>
<!--<script src="https://unpkg.com/@solana/web3.js@latest/lib/index.iife.min.js"></script>-->
<div class="w3eden">
    <script src="https://unpkg.com/vue@3/dist/vue.global.js"></script>



    <div id="app">
	<button :class="'btn ' + connectButton.style" data-toggle="modal" data-target="#selectwallet">{{ connectButton.label }}</button>
        <hr/>
        <button class="btn btn-primary" v-on:click="requestPayment()">Request 0.3 Solana</button>

	<div class="modal fade" tabindex="-1" id="selectwallet" data-backdrop="static">
		<div class="modal-dialog modal-dialog-centered modal-dialog-scrollable" style="max-width: 100%;width: 300px">
			<div class="modal-content">
				<div class="modal-header">
					<h5 class="modal-title">{{ connectButton.connected ? 'Wallet Connected' : 'Connect Wallet' }}</h5>
					<button type="button" data-dismiss="modal" aria-label="Close" style="background: transparent">
						<svg fill="none" height="16" viewBox="0 0 16 16" width="16" xmlns="http://www.w3.org/2000/svg"><path clip-rule="evenodd" d="M8 16C12.4183 16 16 12.4183 16 8C16 3.58172 12.4183 0 8 0C3.58172 0 0 3.58172 0 8C0 12.4183 3.58172 16 8 16ZM4.29289 5.70711L6.58579 8L4.29289 10.2929L5.70711 11.7071L8 9.41421L10.2929 11.7071L11.7071 10.2929L9.41421 8L11.7071 5.70711L10.2929 4.29289L8 6.58579L5.70711 4.29289L4.29289 5.70711Z" fill="#777" fill-rule="evenodd"/></svg>
					</button>
				</div>
				<div class="modal-body-np">
                    <div id="walletList" class="list-group-flush">
                        <div v-if="connectButton.connected" class="list-group-item" style="font-weight: bold;color: #3d9331;text-align: center; background: #f1f1f1">{{ this.formatString(connectButton.address) }} <i class="fa fa-files ttip" title="Copy Address"></i></div>
                        <template  v-for="wallet in wallets" :key="wallet.id">
                        <div class="list-group-item"> {{ wallet.name }}
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

</div>

<script type="module">
    import { Buffer } from 'https://cdn.skypack.dev/buffer/';
    window.Buffer = Buffer;

    const { createApp } = Vue

    createApp({
        data() {
            return {
                wallets: [
                    { name: 'Phantom', provider: false, id: 'phantom', connected: false, label: 'Connect', style: 'btn-info' },
                    { name: 'Solflare', provider: false, id: 'solflare', connected: false, label: 'Connect', style: 'btn-info' },
                    { name: 'Torus', provider: false, id: 'torus', connected: false, label: 'Connect', style: 'btn-info' },
                    { name: 'Glow', provider: false, id: 'glow', connected: false, label: 'Connect', style: 'btn-info' }
                ],
                connectButton: { label: 'Connect', style: 'btn-info', connected: false, address: '' }
            }
        },
        mounted() {
            this.detectWallets();
            this.connectButton.connected = this.isConnected();
            this.connectButton.label = this.isConnected() ? 'Connected' : 'Connect';
            this.connectButton.style = this.isConnected() ? 'btn-success' : 'btn-info';
        },
        methods: {
            isConnected() {
                return localStorage.getItem('wallet_id') !== null;
            },
            connectionStatus(walletid, status) {
                this.wallets.forEach(wallet => {
                    if(wallet.id === walletid) {
                        wallet.connected = status;
                        wallet.label = status ? 'Disconnect' : 'Connect';
                        wallet.style = status ? 'btn-danger' : 'btn-info';
                    }
                });
            },
            getWallet(id) {
                return  this.wallets.find(w => w.id === id);
            },
            detectWallets() {
                const walletid = localStorage.getItem('wallet_id');
                const wallet = localStorage.getItem('wallet');
                console.log(walletid, wallet);
                this.connectionStatus(walletid, !!wallet);
                this.connectButton.address = wallet;
                console.log(this.wallets);
                const checkInterval = setInterval(() => {
                    this.wallets.forEach(wallet => {
                        wallet.provider = this.getProvider(wallet.id);
                    });
                    clearInterval(checkInterval);
                }, 2000);
            },
            getProvider(id) {
                switch (id) {
                    case 'phantom': return window.phantom?.solana || false;
                    case 'solflare': return window.solflare || false;
                    case 'torus': return window.torus || false;
                    case 'glow': return window.glowSolana || false;
                    default: return false;
                }
            },
            async connectWallet(walletid) {
                try {
                    const wallet = this.getWallet(walletid);
                    if(wallet.connected) {
                        await this.disconnectWallet();
                        return;
                    }
                    const provider = wallet.provider;
                    if (!provider) {
                        alert(`${wallet.name} Wallet is not installed.`);
                        return;
                    }

                    // Connect to the wallet
                    const response = await provider.connect();
                    const publicKey = response.publicKey.toString();
                    this.connectionStatus(wallet.id, true);
                    localStorage.setItem('wallet_id', wallet.id);
                    localStorage.setItem('wallet', publicKey);
                    this.connectButton.connected = true;
                    this.connectButton.label = 'Connected';
                    this.connectButton.style = 'btn-success';
                    this.connectButton.address = publicKey;
                    // Display wallet info
                    console.log(`${wallet.name} Wallet Public Key:`, publicKey);
                } catch (error) {
                    console.error('Connection Error:', error.message);
                }
            },
            async disconnectWallet() {
                const wallet = this.getWallet(localStorage.getItem('wallet_id'));
                await wallet.provider.disconnect();
                localStorage.removeItem('wallet_id');
                localStorage.removeItem('wallet');
                this.connectionStatus(wallet.id, false);
                this.connectButton.connected = false;
                this.connectButton.label = 'Connect';
                this.connectButton.style = 'btn-info';
                this.connectButton.address = '';
            },
            async requestPayment() {
                const { Connection, PublicKey, Transaction, SystemProgram, clusterApiUrl } = solanaWeb3;

                try {
                    const provider = this.getWallet(localStorage.getItem('wallet_id')).provider;
                    const publicKey = localStorage.getItem('wallet');

                    if (!provider || !publicKey) {
                        WPDM.bootAlert("No wallet connected!","Please connect your wallet first.");
                        return;
                    }

                    const connection = new Connection(clusterApiUrl('devnet'));
                    const recipient = new PublicKey(publicKey); // The connected wallet (payer)

                    // Create a transaction to request payment
                    const transaction = new Transaction().add(
                        SystemProgram.transfer({
                            fromPubkey: recipient,
                            toPubkey: new PublicKey('46vrVh4LseWmZhoreyCxBVLPsbB3XgG3Vzb84akQ9HtW'), // Replace with your wallet address
                            lamports: 0.01 * 1e9 // 0.01 SOL in lamports
                        })
                    );

                    transaction.feePayer = recipient;
                    const { blockhash } = await connection.getLatestBlockhash();
                    transaction.recentBlockhash = blockhash;

                    // Request the wallet to sign and send the transaction
                    const signedTransaction = await provider.signTransaction(transaction);
                    const signature = await connection.sendRawTransaction(signedTransaction.serialize());

                    await connection.confirmTransaction(signature, 'processed');

                    alert("Payment successful! Transaction Signature: " + signature);
                    console.log("Transaction Signature:", signature);
                } catch (error) {
                    console.error("Payment Request Error:", error.message);
                }
            },
            formatString(str, startLength = 4, endLength = 4) {
                if (str.length <= startLength + endLength) return str;

                const start = str.slice(0, startLength);
                const end = str.slice(-endLength);

                return `${start}......${end}`;
            }
        }
    }).mount('#app')
</script>

