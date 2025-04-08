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
use WPDM\__\Crypt;
use WPDM\__\HTML\Element;

global $CryptoConnect;

class CryptoConnect {
	function __construct() {

		register_activation_hook(__FILE__, array($this, 'install'));

		add_shortcode( 'wpdm_crypto_connect', [ $this, 'connect' ] );
		add_shortcode( 'wpdm_request_payment', [ $this, 'requestPayment' ] );
		add_action( 'wp_enqueue_scripts', [ $this, 'enqueueScripts' ] );
		add_action( 'wp_ajax_wpdmcrypto_validate_payment', [ $this, 'validatePayment' ] );
		add_action( 'wp_ajax_nopriv_wpdmcrypto_validate_payment', [ $this, 'validatePayment' ] );
		add_action( 'wp_ajax_wpdm_crypto_paid', [ $this, 'isPaid' ] );
		add_filter( 'add_wpdm_settings_tab', [ $this, 'settingsTab' ] );
		add_filter( 'wpdm_package_settings_tabs', [ $this, 'packageSettingsTab' ] );
		add_filter( 'wdm_before_fetch_template', [ $this, 'cryptoConnectButtons' ] );
		add_action('init', [$this, 'download']);

	}

	function install() {
		require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
		global $wpdb;
		$sql = "CREATE TABLE IF NOT EXISTS `{$wpdb->prefix}ahm_crypto_payments` (
			  `ID` int NOT NULL AUTO_INCREMENT,		
    		  `crypto` varchar(40) NOT NULL,
    		  `network` varchar(255) NOT NULL,
    		  `transaction_id` varchar(255) NOT NULL,
			  `amount` double NOT NULL,
			  `payment_from` varchar(255) NOT NULL,  
			  `paid_to` varchar(255) NOT NULL,  
			  `product_id` int NOT NULL DEFAULT '0',			  
			  `user_id` int NOT NULL DEFAULT '0',			  
			  `metadata` json NOT NULL,			  
			  `created_at` int NOT NULL DEFAULT '0',			  
			  PRIMARY KEY (`ID`)
			) ENGINE=InnoDB";
		$wpdb->query($sql);

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

	function packageSettingsTab( $tabs ) {
		$tabs['cryptoconnect'] = array(
			'name'     => __( 'Crypto Connect', 'wpdm-crypto-connect' ),
			'callback' => array( $this, 'packageSettings' )
		);

		return $tabs;
	}

	function packageSettings() {
		WPDM()->template->display('crypto-settings.php', __DIR__.'/views/');
	}


	/**
	 * @return false|string
	 */
	/*function connect() {
		return WPDM()->template->fetch('connect.php', __DIR__.'/views/');
	}*/

	function connect() {
		$id = "cbtn-" . uniqid();

		return (new Element('div'))
			->attr('class', 'vue-app')
			->attr('style', 'display:inline-block;width:auto')
			->attr('id', $id)
			->data('id', $id)
			->data('vue-app', 'Connect')
			->render();
	}

	static function requestPayment( $params = [] ) {
		$id = "btn-" . uniqid();
		$wallet = get_option('__wpdm_crypto_solana_walltet', '');
		$id      = __::valueof($params, 'id', $id);
		$product = __::valueof($params, 'product', 0);
		$label   = __::valueof($params, 'label', 'Make Payment');
		$amount  = __::valueof($params, 'amount');;
		$style   = __::valueof($params, 'style', 'btn btn-primary');
		$network = get_option('__wpdm_crypto_network', 'devnet');

		return (new Element('div'))
			->attr('class', 'vue-app')
			->attr('style', 'display:inline-block;width:auto')
			->attr('id', $id)
			->data('id', $id)
			->data('product', $product)
			->data('vue-app', 'RequestPayment')
			->data('recipient', $wallet)
			->data('label', $label)
			->data('amount', $amount)
			->data('style', $style)
			->data('network', $network)
			->render();
	}

	function cryptoPaid( $params = [] ) {
		return (new Element('div'))
			->attr('class', 'vue-app')
			->attr('id', 'cp-'.$params['product'])
			->data('id', 'cp-'.$params['product'])
			->data('product', $params['product'])
			->data('vue-app', 'Paid')
			->render();
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
		$payer = $transaction['transaction']['message']['accountKeys'][0]['pubkey'] ?? null;
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
			global $wpdb;
			$expiresAt = strtotime('+1 year');
			$rpcUrl = get_option('__wpdm_crypto_network') === 'devnet' ? "https://api.devnet.solana.com" : "https://api.mainnet-beta.solana.com";
			$wpdb->insert( $wpdb->prefix . "ahm_crypto_payments", [
				'crypto' => 'SOL',
				'network' => $rpcUrl,
				'transaction_id' => $signature,
				'amount' => $expectedAmount,
				'product_id' => wpdm_query_var('product', 0),
				'payment_from' => $payer,
				'user_id' => get_current_user_id(),
				'paid_to' => $receiverAddress,
				'metadata' => json_encode(['expiry_date' => $expiresAt, 'IP' => __::get_client_ip()]),
				'created_at' => time(),
			]);
			$download_link = WPDM()->package->expirableDownloadLink(wpdm_query_var('product', 0));
			wp_send_json(['success' => 1, 'message' => 'Thanks for your payment, please click the following button to start download', 'download_link' => $download_link]);
		} elseif ($foundReceiver) {
			wp_send_json(['success' => 0, 'message' => 'Transaction is valid, but the paid amount is insufficient.']);
		} else {
			wp_send_json(['success' => 1, 'message' => 'Transaction is invalid.']);
		}
	}

	function isPaid() {
		$product = wpdm_query_var('product', 'int');
		global $wpdb;
		$data = $wpdb->get_row("SELECT * FROM {$wpdb->prefix}ahm_crypto_payments WHERE product_id = '{$product}' AND user_id = '".get_current_user_id()."' order by ID desc");
		$data->metadata = json_decode($data->metadata);
		if($data->metadata->expiry_date > time()) {
			$data->expiresAt   = wp_date( get_option( 'date_format' ) . " " . get_option( 'time_format' ), $data->metadata->expiry_date );
			$data->downloadURL = add_query_arg( [ 'crytodl' => Crypt::encrypt( $data->ID ) ], home_url( '/' ) );
			wp_send_json( [ 'data' => $data ] );
		} else {
			wp_send_json( [ 'data' => false ] );
		}
	}

	function cryptoConnectButtons( $vars ) {
		$vars['wallet_connect'] = $this->connect();
		$label = get_post_meta($vars['ID'], '__wpdm_crypto_btn_label', true);
		$label = $label ? $label : 'Make Payment';
		$price = get_post_meta($vars['ID'], '__wpdm_crypto_amount', true);
		$style = get_post_meta($vars['ID'], '__wpdm_crypto_btn_style', true);
		$style = $style ?: 'btn btn-primary';
		$vars['crypto_payment'] = $this->requestPayment(['product' => $vars['ID'], 'label' => $label, 'amount' => $price, 'style' => $style]);
		$vars['crypto_paid'] = $this->cryptoPaid(['product' => $vars['ID']]);
		return $vars;
	}

	function download() {
		if(isset($_REQUEST['crytodl'])) {
			global $wpdb;
			$id = Crypt::decrypt($_REQUEST['crytodl']);
			$data = $wpdb->get_row("SELECT * FROM {$wpdb->prefix}ahm_crypto_payments WHERE ID = '{$id}'");
			$data->metadata = json_decode($data->metadata);
			if($data->metadata->expiry_date > time()) {
				wp_redirect(WPDM()->package->expirableDownloadLink($data->product_id, 3));
				exit;
			} else {
				wpdmdd('Payment link has expired.');
			}
		}
	}


}

$CryptoConnect = new CryptoConnect();
