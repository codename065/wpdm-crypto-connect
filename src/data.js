import { defineStore } from 'pinia';
/*import { Connection, PublicKey, Transaction, SystemProgram, clusterApiUrl } from '@solana/web3.js';*/
import { Buffer } from "buffer";
import { ref } from 'vue';

window.Buffer = Buffer;
export const wpdmCryptoConnect = defineStore('common', () => {
    const data = ref(
        {
            signature: '',
            amount: 0,
            receiver: '',
            network: 'devnet',
            wallets: [
                { name: 'Phantom', provider: false, id: 'phantom', connected: false, label: 'Connect', style: 'btn-info' },
                { name: 'Solflare', provider: false, id: 'solflare', connected: false, label: 'Connect', style: 'btn-info' },
                { name: 'Enkrypt', provider: false, id: 'enkrypt', connected: false, label: 'Connect', style: 'btn-info' },
                { name: 'Glow', provider: false, id: 'glow', connected: false, label: 'Connect', style: 'btn-info' },
                { name: 'Trust', provider: false, id: 'trust', connected: false, label: 'Connect', style: 'btn-info' }
            ],
            connectButton: { label: 'Connect', style: 'btn-info', connected: false, address: '' }
        }
    );

    function isConnected() {
        return localStorage.getItem('wallet_id') !== null;
    }
    function connectionStatus(walletid, status) {
        data.value.wallets.forEach(wallet => {
            if(wallet.id === walletid) {
                wallet.connected = status;
                wallet.label = status ? 'Disconnect' : 'Connect';
                wallet.style = status ? 'btn-danger' : 'btn-info';
            } else {
                wallet.connected = false;
                wallet.label = 'Connect';
                wallet.style = 'btn-info';
            }
        });
    }
    function getWallet(id) {
        return  data.value.wallets.find(w => w.id === id);
    }
    function detectWallets() {
        const walletid = localStorage.getItem('wallet_id');
        const wallet = localStorage.getItem('wallet');
        connectionStatus(walletid, !!wallet);
        data.value.connectButton.address = wallet;
        console.log(data.value.wallets);
        const checkInterval = setInterval(() => {
            data.value.wallets.forEach(wallet => {
                wallet.provider = getProvider(wallet.id);
            });
            clearInterval(checkInterval);
        }, 2000);
    }
    function getProvider(id) {
        switch (id) {
            case 'phantom': return window.phantom?.solana || false;
            case 'solflare': return window.solflare || false;
            case 'enkrypt': return window.enkrypt?.providers.solana || false;
            case 'trust': return window.trustwallet?.solana || false;
            case 'glow': return window.glowSolana || false;
            default: return false;
        }
    }
    async function connectWallet(walletid) {
        try {
            const wallet = getWallet(walletid);
            if(wallet.connected) {
                await disconnectWallet();
                return;
            }
            const provider = wallet.provider;
            if (!provider) {
                alert(`${wallet.name} Wallet is not installed.`);
                return;
            }

            if(wallet.id === 'enkrypt') {
                await connectEnkryptWallet();
                return;
            }

            // Connect to the wallet
            if(wallet.id === 'trust')
                await window.trustwallet.solana.connect();
             else
                await provider.connect();

            console.log('Wallet Connected:', provider);
            const publicKey = provider.publicKey?.toString();
            connectionStatus(wallet.id, true);
            localStorage.setItem('wallet_id', wallet.id);
            localStorage.setItem('wallet', publicKey);
            data.value.connectButton.connected = true;
            data.value.connectButton.label = 'Connected';
            data.value.connectButton.style = 'btn-success';
            data.value.connectButton.address = publicKey;
            // Display wallet info
            console.log(`${wallet.name} Wallet Public Key:`, publicKey);
        } catch (error) {
            console.error('Connection Error:', error.message);
        }
    }

    async function connectEnkryptWallet() {
        if (window.enkrypt) {
            try {
                const provider = window.enkrypt.providers.solana;
                if (!provider) {
                    console.error('Solana provider not found in Enkrypt.');
                    return;
                }
                // Request connection to the wallet
                const accounts = await provider.connect();

                const publicKey = accounts[0].address;
                connectionStatus('enkrypt', true);
                localStorage.setItem('wallet_id', 'enkrypt');
                localStorage.setItem('wallet', publicKey);
                data.value.connectButton.connected = true;
                data.value.connectButton.label = 'Connected';
                data.value.connectButton.style = 'btn-success';
                data.value.connectButton.address = publicKey;

                // Proceed with your application logic using the connected account
            } catch (err) {
                console.error('Failed to connect to Enkrypt wallet:', err);
            }
        } else {
            console.error('Enkrypt wallet not detected. Please install it from https://www.enkrypt.com/.');
        }
    }

    async function disconnectWallet() {
        const wallet = getWallet(localStorage.getItem('wallet_id'));
        await wallet.provider.disconnect();
        localStorage.removeItem('wallet_id');
        localStorage.removeItem('wallet');
        connectionStatus(wallet.id, false);
        data.value.connectButton.connected = false;
        data.value.connectButton.label = 'Connect';
        data.value.connectButton.style = 'btn-info';
        data.value.connectButton.address = '';
    }
    async function requestPayment(label, product, network) {
        const orig_label = label.value;
        label.value = "Processing...";
        try {
            const walletid = localStorage.getItem('wallet_id');
            const publicKey = localStorage.getItem('wallet');
            if(!walletid || !publicKey) {
                if(confirm("No wallet connected! Do you want to connect wallet?"))
                    //jQuery('#selectwallet').modal('show');
                    throw new Error("connectnow");
                else
                    throw new Error("skipconnect");
            }
            const provider = getWallet(walletid).provider;

            if (!provider || !publicKey) {
                WPDM.bootAlert("No wallet connected!","Please connect your wallet first.");
                return;
            }

            const connection = new solanaWeb3.Connection(solanaWeb3.clusterApiUrl(network.value));
            const payer = new solanaWeb3.PublicKey(publicKey); // The connected wallet (payer)

            // Create a transaction to request payment
            const transaction = new solanaWeb3.Transaction().add(
                solanaWeb3.SystemProgram.transfer({
                    fromPubkey: payer,
                    toPubkey: new solanaWeb3.PublicKey(data.value.receiver), // Replace with your wallet address
                    lamports: parseFloat(data.value.amount) * solanaWeb3.LAMPORTS_PER_SOL // 0.01 SOL in lamports
                })
            );

            transaction.feePayer = payer;
            const { blockhash } = await connection.getLatestBlockhash();
            transaction.recentBlockhash = blockhash;

            // Request the wallet to sign and send the transaction
            let signedTransaction;
            if(walletid === 'trust')
                signedTransaction = await window.trustwallet.solana.signTransaction(transaction);
            else
                signedTransaction = await provider.signTransaction(transaction);

            console.log(signedTransaction);

            data.value.signature = await connection.sendRawTransaction(signedTransaction.serialize());
            label.value = 'Confirming...';

            await connection.confirmTransaction(data.value.signature, 'processed')

            label.value = 'Verifying...';
            checkStatus(product.value, label);

        } catch (error) {
            label.value = orig_label;
            //console.log(error);
            if(error.message === 'skipconnect') {
                return;
            }
            if(error.message === 'connectnow') {
                jQuery('#selectwallet').modal('show');
                return;
            }

            if(data.value.signature) {
                label.value = 'Verifying...';
                checkStatus(product.value, label);
                return;
            }

            if(error.message)
                WPDM.notify("Payment Error: " + error.message, 'danger', 'top-center', 5000);
        }
    }

    function checkStatus(product, label) {
        jQuery.post(wpdm_url.ajax, {product: product, signature :data.value.signature, amount: data.value.amount, receiver: data.value.receiver, action: 'wpdmcrypto_validate_payment'}, function (res) {
            if(parseInt(res.success) === 1) {
                label.value = 'Completed';
                data.value.signature = '';
                WPDM.bootAlert("Payment successful!", res.message + '<hr/><div style="text-align: center"></div><a class="btn btn-success" href="' + res.download_link + '">Download</a></div>', 400, true);
            }
            else
                setTimeout(() => checkStatus(product, label), 1000)
        });
    }

    function formatString(str, startLength = 4, endLength = 4) {
        if (str.length <= startLength + endLength) return str;

        const start = str.slice(0, startLength);
        const end = str.slice(-endLength);

        return `${start}......${end}`;
    }

    return { data, isConnected, getWallet, detectWallets, getProvider, connectWallet, disconnectWallet, requestPayment, connectionStatus, formatString };
});
