<?php
/*
  Plugin Name: WPDM - Crypto Connect
  Plugin URI: https://www.wpdownloadmanager.com/
  Description: Connect with crypto wallet
  Author: WordPress Download Manager
  Version: 1.0.0
  Author URI: https://www.wpdownloadmanager.com/
 */


namespace WPDMPP\AddOn;

use WPDM\__\__;

class CryptoConnect {
	function __construct() {
		add_shortcode( 'wpdm_crypto_connect', [ $this, 'connect' ] );
		add_shortcode( 'wpdm_request_payment', [ $this, 'requestPayment' ] );
		add_action( 'wp_enqueue_scripts', [ $this, 'enqueueScripts' ] );
		add_action( 'wp_ajax_wpdmcrypto_validate_payment', [ $this, 'validatePayment' ] );
		add_action( 'wp_ajax_nopriv_wpdmcrypto_validate_payment', [ $this, 'validatePayment' ] );
		add_filter( 'add_wpdm_settings_tab', [ $this, 'settingsTab' ] );
	}


	function enqueueScripts() {
		wp_enqueue_script(
			'vuejs-app',
			plugin_dir_url( __FILE__ ) . 'dist/app.js', // The bundled Vue app file
			array( 'wp-element' ), // Ensure WP's React library doesn't conflict
			filemtime( plugin_dir_path( __FILE__ ) . 'dist/app.js' ),
			true
		);

		wp_enqueue_script(
			'solana-web3',
			'https://unpkg.com/@solana/web3.js@latest/lib/index.iife.min.js', // The bundled Vue app file
			array( 'wp-element' )
		);

		/*wp_enqueue_style(
			'vuejs-app-style',
			plugin_dir_url( __FILE__ ) . 'dist/style.css',
			array(),
			filemtime( plugin_dir_path( __FILE__ ) . 'dist/style.css' )
		);*/
	}

	function settingsTab( $tabs ) {
		$tabs['wpdm-crypto'] = wpdm_create_settings_tab( 'wpdm-crypto', 'Crypto Connect', [$this, 'settings'], 'fas fa-wallet' );
		return $tabs;
	}

	function settings() {
		if(isset($_POST['__wpdm_crypto_network'])) {
			update_option('__wpdm_crypto_network', sanitize_text_field($_POST['__wpdm_crypto_network']));
			update_option('__wpdm_crypto_solana_walltet', sanitize_text_field($_POST['__wpdm_crypto_solana_walltet']));
			die('Settings Saved Successfully.');
		}
		WPDM()->template->display('settings.php', __DIR__.'/views/');
	}

	/**
	 * @return false|string
	 */
	/*function connect() {
		return WPDM()->template->fetch('connect.php', __DIR__.'/views/');
	}*/

	function connect() {
		$id = "cbtn-" . uniqid();

		return '<div data-id="' . $id . '" id="' . $id . '" class="vue-app" data-vue-app="Connect"></div>';
	}

	function requestPayment( $params = [] ) {
		$id = "btn-" . uniqid();
		$wallet = get_option('__wpdm_crypto_solana_walltet', '');
		$id      = __::valueof($params, 'id', $id);
		$product = __::valueof($params, 'product', 0);
		$label   = __::valueof($params, 'label', 'Make Payment');
		$amount  = __::valueof($params, 'amount');;
		$style   = __::valueof($params, 'style', 'btn btn-primary');
		$network = get_option('__wpdm_crypto_network', 'devnet');

		return "<div style='display:inline-block;width:auto'>
			    <div
			        class='vue-app'
			        data-id='{$id}'
			        id='{$id}'
			        data-product='{$product}'
			        data-vue-app='RequestPayment'
			        data-recipient='{$wallet}'
			        data-label='{$label}'
			        data-amount='{$amount}'
			        data-style='{$style}'
			        data-network='{$network}'
			    ></div></div>";
		//return '<div  style="display:inline-block;width:auto"><div class="vue-app" data-id="' . __::valueof( $params, 'id', $id ) . '" id="' . __::valueof( $params, 'id', $id ) . '" data-product="' . __::valueof( $params, 'product', 0 ) . '"  data-vue-app="RequestPayment" data-recipient="' . $wallet . '" data-label="' . $params['label'] . '" data-amount="' . $params['amount'] . '" data-style="' . $params['style'] . '" data-network="'.get_option('__wpdm_crypto_network', 'devnet').'"></div></div>';
	}

	function validatePayment() {
		// Check if the request is valid
		if (!isset($_POST['signature'])) {
			wp_send_json(['success' => 0, 'message' => 'Missing signature.'], 400);
		}

		$signature = sanitize_text_field($_POST['signature']);
		$expectedReceiver = get_option('__wpdm_crypto_connect_mywallet_address', 'dLn6LRrRjSF97vsjNkvBt9j3otMGoKKLoUPx4TvMD5h');
		$expectedAmount = 10000000; // Amount in Lamports (e.g., 0.01 SOL = 10,000,000 Lamports)
		$rpcUrl = get_option('__wpdm_crypto_network') === 'devnet' ? "https://api.devnet.solana.com" : "https://api.mainnet-beta.solana.com";
		$postData = [
			"jsonrpc" => "2.0",
			"id" => 1,
			"method" => "getTransaction",
			"params" => [$signature, "jsonParsed"]
		];

		$ch = curl_init($rpcUrl);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_POST, true);
		curl_setopt($ch, CURLOPT_HTTPHEADER, [
			'Content-Type: application/json'
		]);
		curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($postData));

		$response = curl_exec($ch);
		curl_close($ch);

		$result = json_decode($response, true);

		if (!isset($result['result']) || $result['result'] === null) {
			wp_send_json(['success' => 0, 'message' => 'Transaction not found or invalid signature.']);
		}

		$transaction = $result['result'];
		$instructions = $transaction['transaction']['message']['instructions'];
		$transactionStatus = $transaction['meta']['status'];
		// Check if transaction was successful
		if (isset($transactionStatus['err']) && $transactionStatus['err'] !== null) {
			wp_send_json(['success' => 0, 'message' => 'Transaction Failed.']);
		}

		$foundReceiver = false;

		foreach ($instructions as $instruction) {
			if (isset($instruction['parsed']['info']['destination'])) {
				$receiverAddress = $instruction['parsed']['info']['destination'];
				$paidAmount = $instruction['parsed']['info']['lamports'] ?? 0;

				if ($receiverAddress === $expectedReceiver) {
					$foundReceiver = true;
					if ( $paidAmount >= $expectedAmount ) {
						$amountValid = true;
					}
					break;
				}
			}
		}

		if ($foundReceiver && $amountValid) {
			wp_send_json(['success' => 1, 'message' => 'Transaction is valid, receiver address matches, and amount is correct.']);
		} elseif ($foundReceiver) {
			wp_send_json(['success' => 0, 'message' => 'Transaction is valid, but the paid amount is insufficient.']);
		} else {
			wp_send_json(['success' => 0, 'message' => 'Transaction is valid, but receiver address does not match.']);
		}
	}


}

new CryptoConnect();
